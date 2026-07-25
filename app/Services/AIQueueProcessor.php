<?php

namespace App\Services;

use App\Jobs\GenerateProductImageJob;
use App\Jobs\GenerateProductTextJob;
use App\Models\AIProductQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Drives AI product generation ONE STEP AT A TIME.
 *
 * Why it works this way — verified constraints of the Hostinger shared plan
 * this app runs on (hPanel → Advanced → PHP Configuration):
 *
 *   maxExecutionTime  = 300s
 *   disableFunctions  = system, exec, shell_exec, passthru, popen, proc_open, …
 *
 * Consequences:
 *   1. No queue worker and no `runInBackground()` — both need proc_open/exec.
 *      Everything must run inline, inside the cron run or the web request.
 *   2. A single web request cannot generate a whole batch. One DeepSeek text
 *      call takes 45–90s and one image 2–3 min, so looping over N products in
 *      one request blows past 300s and the request is killed mid-flight,
 *      leaving rows stranded in `generating`.
 *
 * So generation is modelled as a state machine: every caller (cron tick or
 * admin-page poll) claims exactly ONE item and performs exactly ONE step.
 * Claiming is an atomic conditional UPDATE on `locked_at`, which is the only
 * thing that actually prevents two overlapping callers from generating the
 * same product twice — a PHP-side flag cannot, since each request has its own.
 */
class AIQueueProcessor
{
    /** A claim older than this is treated as dead and reclaimed. */
    public const LOCK_TTL_MINUTES = 6;

    /** Text attempts per item before it is parked as failed. */
    public const MAX_ATTEMPTS = 3;

    /** Statuses that still need work, in the order they should be picked up. */
    private const WORKABLE = ['pending', 'text_generated'];

    /**
     * Reset items whose worker died mid-run (request killed at 300s, cron
     * timeout, deploy). Without this they sit in `generating` forever and the
     * whole queue wedges — which is exactly what the admin had to "Force Stop".
     */
    public function reclaimStale(): int
    {
        $cutoff = now()->subMinutes(self::LOCK_TTL_MINUTES);

        $stale = AIProductQueue::where('status', 'generating')
            ->where(function ($q) use ($cutoff) {
                $q->where('locked_at', '<', $cutoff)
                  ->orWhere(function ($q2) use ($cutoff) {
                      $q2->whereNull('locked_at')->where('updated_at', '<', $cutoff);
                  });
            })
            ->get();

        foreach ($stale as $item) {
            $attempts = (int) $item->retry_count + 1;
            $exhausted = $attempts >= self::MAX_ATTEMPTS;

            $item->update([
                'retry_count'   => $attempts,
                'locked_at'     => null,
                'status'        => $exhausted ? 'failed' : 'pending',
                'error_message' => $exhausted
                    ? "Generation timed out after {$attempts} attempts. Check the DeepSeek API key and try again."
                    : "Previous attempt timed out — retrying (attempt {$attempts} of " . self::MAX_ATTEMPTS . ').',
            ]);

            Log::warning("AI queue: reclaimed stale item #{$item->id} [{$item->product_name}] attempt {$attempts}");
        }

        return $stale->count();
    }

    /**
     * Atomically take ownership of the next item needing work.
     *
     * The conditional UPDATE is the lock: MySQL serialises row updates, so of
     * two concurrent callers only one sees affected-rows = 1. The loser simply
     * moves to the next candidate. Returns null when nothing is available.
     */
    public function claimNext(?string $type = null): ?AIProductQueue
    {
        $cutoff = now()->subMinutes(self::LOCK_TTL_MINUTES);

        $candidateIds = AIProductQueue::query()
            ->whereIn('status', self::WORKABLE)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('locked_at')->orWhere('locked_at', '<', $cutoff);
            })
            // Finish text for everything before spending time on images.
            ->orderByRaw("FIELD(status, 'pending', 'text_generated')")
            ->orderBy('created_at')
            ->limit(10)
            ->pluck('id');

        foreach ($candidateIds as $id) {
            $won = AIProductQueue::where('id', $id)
                ->whereIn('status', self::WORKABLE)
                ->where(function ($q) use ($cutoff) {
                    $q->whereNull('locked_at')->orWhere('locked_at', '<', $cutoff);
                })
                ->update(['locked_at' => now()]);

            if ($won === 1) {
                return AIProductQueue::find($id);
            }
        }

        return null;
    }

    /**
     * Claim one item and run its next step. Returns the item that was worked
     * on (already refreshed), or null when there was nothing to claim.
     */
    public function step(?string $type = null): ?AIProductQueue
    {
        $this->reclaimStale();

        $item = $this->claimNext($type);
        if (!$item) {
            return null;
        }

        try {
            $this->runStep($item);
        } catch (\Throwable $e) {
            $this->recordFailure($item, $e);
        } finally {
            // Release the claim without clobbering whatever the job wrote.
            AIProductQueue::where('id', $item->id)->update(['locked_at' => null]);
        }

        return $item->fresh();
    }

    /**
     * Run the ONE step this item is due: text if it has none, otherwise image.
     * Never both — that is what keeps a single request inside the time limit.
     */
    private function runStep(AIProductQueue $item): void
    {
        if ($item->status === 'pending') {
            dispatch_sync(new GenerateProductTextJob($item->id));
            return;
        }

        if ($item->status === 'text_generated') {
            // Categories have no image step; close them out.
            if ($item->type !== 'product') {
                $item->update(['status' => 'completed']);
                return;
            }

            dispatch_sync(new GenerateProductImageJob($item->id));
        }
    }

    private function recordFailure(AIProductQueue $item, \Throwable $e): void
    {
        $fresh    = $item->fresh() ?? $item;
        $attempts = (int) $fresh->retry_count + 1;
        $exhausted = $attempts >= self::MAX_ATTEMPTS;

        Log::error("AI queue: step failed for #{$item->id} [{$item->product_name}]: " . $e->getMessage());

        $fresh->update([
            'retry_count'   => $attempts,
            'status'        => $exhausted ? 'failed' : 'pending',
            'error_message' => Str::limit($e->getMessage(), 450),
        ]);
    }

    /** Items of this type that still need at least one more step. */
    public function pendingCount(?string $type = null): int
    {
        return AIProductQueue::whereIn('status', array_merge(self::WORKABLE, ['generating']))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->count();
    }
}
