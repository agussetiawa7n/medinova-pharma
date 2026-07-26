<?php

namespace App\Filament\Pages;

use App\Jobs\GenerateCompositionContentJob;
use App\Jobs\GenerateProductImageJob;
use App\Jobs\GenerateProductTextJob;
use App\Models\AIProductQueue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Composition;
use App\Models\Product;
use App\Services\AIQueueProcessor;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class AIGenerateProducts extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'AI Generate Products';
    protected static ?string $navigationLabel = 'AI Generate';

    protected string $view = 'filament.pages.ai-generate-products';

    public string $generationType = 'product'; // 'product' or 'category'

    public string $rawProductList = '';
    public $csvFile   = null;
    public $txtFile   = null;
    public $excelFile = null;

    public array $parsedNames = [];
    public array $queueItems = [];

    public bool $isParsing = false;
    public bool $isGenerating = false;
    public int $totalCount = 0;
    public int $completedCount = 0;
    public int $currentStep = 1;

    // Queue worker management
    public string $workerStatus = 'unknown'; // running, stopped, stale, unknown
    public string $currentTask = '';
    public string $elapsedTime = '';
    public string $textModel = '';
    public string $imageModel = '';
    public bool $processingImage = false; // legacy flag kept for the blade; the real lock lives in the DB

    public function mount(): void
    {
        // Restore state after a page refresh — the batch lives in the database.
        $this->restoreBatch();
    }

    public function updatedGenerationType($value): void
    {
        $this->parsedNames = [];
        $this->queueItems = [];
        $this->rawProductList = '';
        $this->csvFile = null;
        $this->txtFile = null;
        $this->excelFile = null;
        $this->totalCount = 0;
        $this->completedCount = 0;
        $this->isGenerating = false;
        $this->currentStep = 1;

        $this->restoreBatch($value);
    }

    /**
     * Rebuild the on-screen batch from the database.
     *
     * Restores every row that has not reached a terminal state, INCLUDING ones
     * the admin already approved. Both callers used to filter on
     * whereNull('approved_by'), which meant an approved-but-unsaved row was
     * never put back into $parsedNames and therefore never rendered — while
     * generateAll() still refused to re-run that name because of it. The card
     * was invisible and the block was unexplainable. Approved rows are exactly
     * the ones waiting for "Save to Database", so they belong on screen.
     */
    private function restoreBatch(?string $type = null): void
    {
        $rows = AIProductQueue::query()
            ->whereNotIn('status', ['saved', 'skipped'])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderByDesc('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $this->generationType = $type ?? ($rows->first()->type ?? 'product');

        $rows = $rows->where('type', $this->generationType);
        if ($rows->isEmpty()) {
            return;
        }

        $this->parsedNames = $rows->pluck('product_name')->unique()->values()->all();
        $this->currentStep = 2;

        $this->refreshQueueItems();
        $this->recountProgress();

        $this->isGenerating = $rows
            ->whereIn('status', ['pending', 'generating', 'text_generated'])
            ->isNotEmpty();
    }

    public function loadExistingCategories(): void
    {
        $categoryNames = Category::pluck('name')->toArray();
        if (empty($categoryNames)) {
            Notification::make()->title('No existing categories found.')->warning()->send();
            return;
        }

        $this->rawProductList = implode("\n", $categoryNames);
        Notification::make()->title(count($categoryNames) . ' categories loaded.')->success()->send();
    }

    public function parseInput(): void
    {
        $this->isParsing = true;
        try {
            $service = app(\App\Services\AIProductService::class);

            // Start clean. Previously only the textarea branch reassigned, so
            // parsing a file twice — or a file after an earlier parse — appended
            // to the list that was already there.
            $this->parsedNames = [];

            if (!empty($this->rawProductList)) {
                $this->parsedNames = $service->parseProductList($this->rawProductList);
            }

            if ($this->csvFile) {
                $csv = file_get_contents($this->csvFile->getRealPath());
                $this->parsedNames = array_merge($this->parsedNames, $service->parseProductList($csv));
            }

            if ($this->txtFile) {
                $txt = file_get_contents($this->txtFile->getRealPath());
                $this->parsedNames = array_merge($this->parsedNames, $service->parseProductList($txt));
            }

            if ($this->excelFile) {
                $rows = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->excelFile->getRealPath())
                    ->getActiveSheet()->toArray();
                $rows = array_slice($rows, 0, 500); // prevent memory issues
                $names = [];
                foreach ($rows as $row) {
                    foreach ($row as $cell) {
                        if (!empty(trim((string)$cell))) {
                            $names[] = trim((string)$cell);
                        }
                    }
                }
                $this->parsedNames = array_merge($this->parsedNames, $service->parseProductList(implode(',', $names)));
            }

            $this->parsedNames = array_values(array_unique($this->parsedNames));

            if (empty($this->parsedNames)) {
                Notification::make()->title('No valid product names found.')->warning()->send();
                return;
            }

            Notification::make()->title(count($this->parsedNames) . ' products ready for AI.')->success()->send();
        } finally {
            $this->isParsing = false;
        }
    }

    public function removeFromParsed(int $index): void
    {
        array_splice($this->parsedNames, $index, 1);
        $this->parsedNames = array_values($this->parsedNames);
    }

    public function generateAll(): void
    {
        if (empty($this->parsedNames)) {
            Notification::make()->title('Queue is empty.')->warning()->send();
            return;
        }

        // Drop previous attempts for these names, but ONLY the ones the admin has
        // not acted on. Anything already approved is deliberately left alone.
        AIProductQueue::whereIn('product_name', $this->parsedNames)
            ->where('type', $this->generationType)
            ->whereNull('approved_by')
            ->delete();

        // ...which is exactly why an approved row must also block a re-generate:
        // the delete above skips it, so without this a second row appeared for
        // the same name and the product was generated (and billed) twice.
        //
        // Only LIVE approved rows count. A row that was already saved to the
        // catalogue — or skipped — is history and must not block anything;
        // scoping this to approved_by alone meant a name could never be
        // generated again once it had been saved, even after the admin cleared
        // every visible card. Creating a duplicate Product is separately
        // prevented by the name check in saveApproved().
        $blocking = AIProductQueue::whereIn('product_name', $this->parsedNames)
            ->where('type', $this->generationType)
            ->whereNotNull('approved_by')
            ->whereNotIn('status', ['saved', 'skipped'])
            ->pluck('product_name')
            ->all();

        $toGenerate = array_values(array_diff($this->parsedNames, $blocking));

        if (empty($toGenerate)) {
            Notification::make()
                ->title('Nothing to generate')
                ->body('These are already generated and waiting to be saved: ' . implode(', ', $blocking)
                     . '. Save or remove those cards first, or use "🔄 Text" on one to redo it.')
                ->warning()
                ->send();
            return;
        }

        foreach ($toGenerate as $name) {
            AIProductQueue::create([
                'product_name' => $name,
                'type'         => $this->generationType,
                'status'       => 'pending',
            ]);
        }

        if ($skipped = count($blocking)) {
            Notification::make()
                ->title("Skipped {$skipped} item(s) awaiting save")
                ->body(implode(', ', $blocking))
                ->info()
                ->send();
        }

        $this->isGenerating   = true;
        $this->totalCount     = count($toGenerate);
        $this->completedCount = 0;
        $this->workerStatus   = 'running';
        $this->currentStep    = 2;
        $this->currentTask    = '';

        $this->refreshQueueItems();

        // Deliberately NO generation here. This request only enqueues.
        //
        // Generating the batch inline used to blow past maxExecutionTime (300s
        // on this plan) — one DeepSeek text call alone is 45-90s — so the
        // request died mid-loop and left rows stranded in `generating`.
        // pollStatus() now advances the queue one step at a time, and the
        // per-minute cron does the same, so both can run without colliding.
        Notification::make()
            ->title("Queued {$this->totalCount} item(s)")
            ->body('Generation runs one item at a time and updates below automatically. You can safely leave this page — the cron keeps it moving.')
            ->success()
            ->send();
    }

    /**
     * Advance the queue by exactly ONE step, then report state.
     *
     * The claim happens in the database (AIQueueProcessor), not in a component
     * property: `wire:poll` fires every few seconds while a step can take
     * minutes, so several polls overlap. A PHP flag is per-request and cannot
     * stop them picking up the same row — which is how the same product ended
     * up generated repeatedly.
     */
    public function pollStatus(): void
    {
        if ($this->isGenerating) {
            // Comfortably above one step, comfortably under the 300s cap.
            @set_time_limit(280);

            try {
                $worked = app(AIQueueProcessor::class)->step($this->generationType);
                if ($worked) {
                    $this->workerStatus = 'running';
                }
            } catch (\Throwable $e) {
                // Never let a single bad item break the polling loop.
                Log::error('pollStatus step failed: ' . $e->getMessage());
            }
        }

        $this->refreshQueueItems();
        $this->completedCount = collect($this->queueItems)
            ->whereIn('status', ['completed', 'failed', 'skipped', 'saved'])->count();

        $this->detectCurrentTask();
        $this->checkWorkerStatus();

        if ($this->totalCount > 0 && $this->completedCount >= $this->totalCount) {
            $this->isGenerating = false;
            $this->currentStep  = 2;
            $this->currentTask  = '';
            $this->workerStatus = 'idle';
        }
    }

    private function detectCurrentTask(): void
    {
        $active = collect($this->queueItems)->first(function ($item) {
            return in_array($item['status'], ['generating', 'text_generated', 'pending']);
        });

        if (!$active) {
            $this->currentTask = '';
            $this->elapsedTime = '';
            return;
        }

        $name   = $active['product_name'];
        $status = $active['status'];
        $left   = collect($this->queueItems)
            ->whereIn('status', ['pending', 'generating', 'text_generated'])->count();

        // Spell out which of the two steps is running and how much is left. One
        // product = one text call then one image call, each its own request, so
        // "generating" on its own told the admin very little.
        $this->currentTask = match ($status) {
            'generating'      => "📝 Step 1 of 2 — writing content for \"{$name}\" · {$left} item(s) left",
            'text_generated'  => "🖼️ Step 2 of 2 — creating image for \"{$name}\" · {$left} item(s) left",
            default           => "⏳ Queued: \"{$name}\" · {$left} item(s) left — runs automatically, you can close this page",
        };

        // Elapsed time since the item last changed. Carbon 3 returns a SIGNED
        // float here, which is why the page was showing "-586.828076s".
        if (!empty($active['updated_at'])) {
            $elapsed = (int) abs(now()->diffInSeconds($active['updated_at']));

            $this->elapsedTime = $elapsed < 60
                ? $elapsed . 's'
                : intdiv($elapsed, 60) . 'm ' . ($elapsed % 60) . 's';
        } else {
            $this->elapsedTime = '';
        }
    }

    public function checkWorkerStatus(): void
    {
        $pendingItems    = collect($this->queueItems)->whereIn('status', ['pending'])->count();
        $processingItems = collect($this->queueItems)->whereIn('status', ['generating', 'text_generated'])->count();

        // Anything claimed longer ago than the processor's own lock TTL is not
        // running any more — its request was killed. Match that number rather
        // than keeping a second, different threshold here.
        $staleItems = AIProductQueue::where('status', 'generating')
            ->where('updated_at', '<', now()->subMinutes(AIQueueProcessor::LOCK_TTL_MINUTES))
            ->count();

        if ($processingItems > 0) {
            if ($staleItems > 0) {
                $this->workerStatus = 'stale';
            } else {
                $this->workerStatus = 'running';
            }
        } elseif ($pendingItems > 0) {
            // Pending but nothing processing → need to start worker
            $this->workerStatus = 'stopped';
        } else {
            $this->workerStatus = 'idle';
        }
    }

    public function startQueueWorker(): void
    {
        // There is no worker process to start on this plan (proc_open/exec are
        // disabled), so "start" just means: resume stepping through the queue.
        $this->isGenerating = true;
        $this->processNow();
    }

    /**
     * Process pending jobs synchronously — safe for Hostinger shared hosting.
     * Processes ONE product at a time (text + image) to avoid PHP timeouts.
     */
    public function processNow(): void
    {
        $pending = AIProductQueue::whereIn('status', ['pending', 'text_generated'])
            ->where('type', $this->generationType)
            ->count();

        if ($pending === 0) {
            Notification::make()->title('No pending jobs')->body('All items are already processed.')->info()->send();
            return;
        }

        try {
            @set_time_limit(280);

            $this->workerStatus = 'running';

            // ONE step per click, same as a poll or a cron tick. Doing three in
            // a row is what used to exceed maxExecutionTime and kill the request.
            $item = app(AIQueueProcessor::class)->step($this->generationType);

            $this->currentTask = '';
            $this->refreshQueueItems();
            $this->completedCount = collect($this->queueItems)
                ->whereIn('status', ['completed', 'failed', 'skipped', 'saved'])->count();

            $stillPending = app(AIQueueProcessor::class)->pendingCount($this->generationType);

            if ($stillPending > 0) {
                $this->workerStatus = 'running';
                Notification::make()
                    ->title($item ? "Processed: {$item->product_name}" : 'Another worker is busy')
                    ->body("{$stillPending} item(s) left — they continue automatically here and via cron.")
                    ->success()
                    ->send();
            } else {
                $this->isGenerating = false;
                $this->workerStatus = 'idle';
                Notification::make()
                    ->title('All items processed')
                    ->body('Review the results and approve them to save.')
                    ->success()
                    ->send();
            }

        } catch (\Throwable $e) {
            Log::error('processNow failed: ' . $e->getMessage());
            $this->workerStatus = 'stale';
            Notification::make()
                ->title('Processing error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * There is no worker daemon to restart on this plan. "Restart" means:
     * free any dead locks so stuck items become claimable again, then resume.
     */
    public function restartQueueWorker(): void
    {
        $reclaimed = app(AIQueueProcessor::class)->reclaimStale();

        AIProductQueue::where('type', $this->generationType)
            ->whereIn('product_name', $this->parsedNames)
            ->where('status', 'failed')
            ->update([
                'status'        => 'pending',
                'locked_at'     => null,
                'retry_count'   => 0,
                'error_message' => null,
            ]);

        $this->isGenerating = true;
        $this->refreshQueueItems();

        Notification::make()
            ->title('Queue resumed')
            ->body("{$reclaimed} stuck item(s) released; failed items re-queued.")
            ->success()
            ->send();
    }

    public function forceStopJobs(): void
    {
        // Mark all active jobs as failed.
        // NOTE: `DB` was previously used here without an import, so this method
        // fatally errored ("Class App\Filament\Pages\DB not found") the moment
        // it got past the update — the admin saw a 500, not a clean stop.
        $affected = AIProductQueue::whereIn('status', ['pending', 'generating', 'text_generated'])
            ->where('type', $this->generationType)
            ->whereIn('product_name', $this->parsedNames)
            ->update([
                'status'        => 'failed',
                'locked_at'     => null,
                'error_message' => 'Force stopped by admin at ' . now()->format('H:i:s'),
            ]);

        // Only drop this feature's queued jobs. The old code truncated the whole
        // `jobs` table, which also threw away unrelated work such as the
        // composition-content jobs dispatched when a product is saved.
        DB::table('jobs')
            ->where('payload', 'like', '%GenerateProduct%')
            ->delete();

        $this->isGenerating = false;
        $this->workerStatus = 'stopped';
        $this->currentTask = '';
        $this->refreshQueueItems();
        $this->completedCount = collect($this->queueItems)
            ->whereIn('status', ['completed', 'failed', 'skipped'])->count();

        Notification::make()->title("Force stopped — {$affected} jobs cancelled.")->warning()->send();
    }

    private function refreshQueueItems(): void
    {
        $this->queueItems = AIProductQueue::where('type', $this->generationType)
            ->whereIn('product_name', $this->parsedNames)
            ->select('id', 'type', 'product_name', 'status', 'generated_data', 'image_path', 'error_message', 'approved_by', 'approved_at', 'updated_at', 'text_model_used', 'image_model_used')
            ->orderByRaw("FIELD(status, 'generating', 'pending', 'text_generated', 'completed', 'failed', 'skipped', 'saved')")
            ->get()
            ->toArray();

        // Load current model names for display
        $this->textModel = \App\Models\Setting::get('ai.text_model', 'openai/gpt-4o');
        $this->imageModel = \App\Models\Setting::get('ai.image_model', 'openai/gpt-5-image-mini');
    }

    public function approveProduct(int $queueId): void
    {
        AIProductQueue::findOrFail($queueId)->update([
            'approved_by' => auth()->id(),
            'approved_at' => now()
        ]);
        $this->refreshQueueItems();
    }

    public function skipProduct(int $queueId): void
    {
        AIProductQueue::findOrFail($queueId)->update(['status' => 'skipped']);
        $this->refreshQueueItems();
    }

    /** Put one failed card back in the queue with a clean attempt counter. */
    public function retryItem(int $queueId): void
    {
        $item = AIProductQueue::findOrFail($queueId);

        $item->update([
            'status'        => 'pending',
            'locked_at'     => null,
            'retry_count'   => 0,
            'error_message' => null,
        ]);

        $this->isGenerating = true;
        $this->refreshQueueItems();
        $this->recountProgress();

        Notification::make()
            ->title("Re-queued \"{$item->product_name}\"")
            ->body('It will be picked up on the next cycle.')
            ->success()
            ->send();
    }

    /** Delete every failed card in this batch in one go. */
    public function removeFailed(): void
    {
        $names = AIProductQueue::where('type', $this->generationType)
            ->whereIn('product_name', $this->parsedNames)
            ->where('status', 'failed')
            ->pluck('product_name');

        if ($names->isEmpty()) {
            Notification::make()->title('No failed items to remove.')->info()->send();
            return;
        }

        AIProductQueue::where('type', $this->generationType)
            ->whereIn('product_name', $this->parsedNames)
            ->where('status', 'failed')
            ->delete();

        // Keep the name list in step, otherwise refreshQueueItems() still filters
        // on names that no longer have a row and the counters drift.
        $this->parsedNames = array_values(array_diff($this->parsedNames, $names->all()));

        $this->refreshQueueItems();
        $this->recountProgress();

        Notification::make()
            ->title("Removed {$names->count()} failed item(s)")
            ->body($names->implode(', '))
            ->success()
            ->send();
    }

    /** Remove a single card from the batch — useful for a duplicate or a dud. */
    public function deleteItem(int $queueId): void
    {
        $item = AIProductQueue::findOrFail($queueId);
        $name = $item->product_name;
        $item->delete();

        // Drop the name from the batch only once no row is left for it, so
        // removing one of two duplicates does not hide the survivor.
        $stillHasRow = AIProductQueue::where('type', $this->generationType)
            ->where('product_name', $name)
            ->exists();

        if (!$stillHasRow) {
            $this->parsedNames = array_values(array_diff($this->parsedNames, [$name]));
        }

        $this->refreshQueueItems();
        $this->recountProgress();

        Notification::make()->title("Removed \"{$name}\" from the batch")->success()->send();
    }

    /**
     * One button for "something is wedged, sort it out".
     *
     * Covers the three states an admin cannot fix from the cards themselves:
     * a row still marked `generating` after its worker died, duplicate rows for
     * the same name, and failed rows that deserve another attempt.
     */
    public function clearStuck(): void
    {
        $processor = app(AIQueueProcessor::class);

        // 1. Dead locks → back to pending (or failed once attempts run out).
        $unstuck = $processor->reclaimStale();

        // 2. Anything still claiming to be mid-generation with no live worker.
        $unstuck += AIProductQueue::where('type', $this->generationType)
            ->where('status', 'generating')
            ->update([
                'status'        => 'pending',
                'locked_at'     => null,
                'error_message' => null,
            ]);

        // 3. Duplicate rows for one name — keep the newest, drop the rest.
        //    Approved rows are never touched; they are the admin's decision.
        $removed = 0;
        $groups = AIProductQueue::where('type', $this->generationType)
            ->whereNull('approved_by')
            ->orderByDesc('id')
            ->get()
            ->groupBy('product_name');

        foreach ($groups as $rows) {
            foreach ($rows->skip(1) as $duplicate) {
                $duplicate->delete();
                $removed++;
            }
        }

        // 4. Give failed items a clean slate so the queue can pick them up.
        $retried = AIProductQueue::where('type', $this->generationType)
            ->where('status', 'failed')
            ->update([
                'status'        => 'pending',
                'locked_at'     => null,
                'retry_count'   => 0,
                'error_message' => null,
            ]);

        $this->isGenerating = ($unstuck + $retried) > 0;
        $this->refreshQueueItems();
        $this->recountProgress();

        Notification::make()
            ->title('Queue cleaned up')
            ->body("Unstuck: {$unstuck} · Duplicates removed: {$removed} · Re-queued: {$retried}")
            ->success()
            ->send();
    }

    private function recountProgress(): void
    {
        $this->totalCount     = count($this->queueItems);
        $this->completedCount = collect($this->queueItems)
            ->whereIn('status', ['completed', 'failed', 'skipped', 'saved'])->count();
    }

    /**
     * Regeneration runs INLINE.
     *
     * These used to call dispatch(), which pushes onto the database queue —
     * but this plan cannot run `queue:work` (proc_open/exec are disabled), so
     * the job sat in the `jobs` table forever while the UI claimed success.
     */
    public function regenerateText(int $queueId): void
    {
        $item = AIProductQueue::findOrFail($queueId);
        $item->update([
            'status'        => 'pending',
            'locked_at'     => null,
            'retry_count'   => 0,
            'error_message' => null,
        ]);

        $this->runSingleStep($item, 'Text regenerated');
    }

    /** Reference photo URLs the admin typed, keyed by queue item id. */
    public array $referenceUrls = [];

    /**
     * Point one item at a specific product photo and rebuild its image from it.
     *
     * The guaranteed path to a real picture: search may not find an obscure
     * brand, and without a reference the model invents packaging. Pasting the
     * IndiaMart (or any) image URL skips search entirely.
     */
    public function useReferenceImage(int $queueId): void
    {
        $url = trim((string) ($this->referenceUrls[$queueId] ?? ''));

        if ($url === '') {
            Notification::make()->title('Paste an image URL first.')->warning()->send();
            return;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Notification::make()->title('That is not a valid URL.')->warning()->send();
            return;
        }

        $item = AIProductQueue::findOrFail($queueId);
        $item->update([
            'reference_url' => $url,
            'status'        => 'text_generated',
            'image_path'    => null,
            'locked_at'     => null,
            'error_message' => null,
        ]);

        $this->runSingleStep($item->fresh(), 'Image rebuilt from your reference');
    }

    public function regenerateImage(int $queueId): void
    {
        $item = AIProductQueue::findOrFail($queueId);
        $item->update([
            'status'        => 'text_generated',
            'image_path'    => null,
            'locked_at'     => null,
            'error_message' => null,
        ]);

        $this->runSingleStep($item, 'Image regenerated');
    }

    private function runSingleStep(AIProductQueue $item, string $successTitle): void
    {
        @set_time_limit(280);

        try {
            dispatch_sync(
                $item->status === 'pending'
                    ? new GenerateProductTextJob($item->id)
                    : new GenerateProductImageJob($item->id)
            );

            Notification::make()->title($successTitle)->body($item->product_name)->success()->send();
        } catch (\Throwable $e) {
            Log::error("Regeneration failed for #{$item->id}: " . $e->getMessage());
            $item->update(['status' => 'failed', 'error_message' => Str::limit($e->getMessage(), 450)]);
            Notification::make()->title('Regeneration failed')->body($e->getMessage())->danger()->send();
        } finally {
            AIProductQueue::where('id', $item->id)->update(['locked_at' => null]);
            $this->refreshQueueItems();
        }
    }

    public function saveApproved(): void
    {
        // Scoped to the batch on screen. Without this the query picked up every
        // approved row of this type — including leftovers from an earlier
        // session the admin could not see — so "Save 2 Products" could write
        // three, and resetForm() then cleared the evidence.
        if (empty($this->parsedNames)) {
            Notification::make()->title('No batch loaded to save.')->warning()->send();
            return;
        }

        // Only fully finished items. Accepting 'text_generated' here is why
        // products kept landing without a picture: the image step had not run
        // yet, so thumbnail was null — and saving flipped the row to 'saved',
        // which GenerateProductImageJob treats as done, so the image was never
        // generated at all. Categories have no image step and complete directly.
        $pendingImage = AIProductQueue::where('status', 'text_generated')
            ->where('type', $this->generationType)
            ->whereIn('product_name', $this->parsedNames)
            ->whereNotNull('approved_by')
            ->pluck('product_name');

        $approved = AIProductQueue::where('status', 'completed')
                                   ->where('type', $this->generationType)
                                   ->whereIn('product_name', $this->parsedNames)
                                   ->whereNotNull('approved_by')
                                   ->get();

        if ($approved->isEmpty()) {
            Notification::make()
                ->title('Nothing ready to save yet')
                ->body($pendingImage->isNotEmpty()
                    ? 'Still waiting on the image step for: ' . $pendingImage->implode(', ')
                        . '. Approve them again once the card shows Done.'
                    : 'Approve at least one finished item first.')
                ->warning()
                ->send();
            return;
        }

        if ($pendingImage->isNotEmpty()) {
            Notification::make()
                ->title("Holding back {$pendingImage->count()} item(s)")
                ->body('Image still generating for: ' . $pendingImage->implode(', '))
                ->info()
                ->send();
        }

        $saved = 0;
        $skipped = 0;

        if ($this->generationType === 'category') {
            foreach ($approved as $item) {
                $d = $item->generated_data ?? [];
                $categoryName = $d['name'] ?? $item->product_name;
                $slug = Str::slug($categoryName);

                // Update or create Category details
                Category::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name'             => $categoryName,
                        'description'      => $d['description'] ?? '',
                        'icon'             => $d['icon'] ?? 'heroicon-o-tag',
                        'meta_title'       => $d['meta_title'] ?? '',
                        'meta_description' => $d['meta_description'] ?? '',
                        'is_active'        => true,
                        'show_in_menu'     => true,
                    ]
                );

                $item->update(['status' => 'saved']);
                $saved++;
            }

            $msg = "{$saved} categories saved/updated.";
            Notification::make()->title($msg)->success()->send();
            $this->resetForm();
            return;
        }

        $failures = [];

        foreach ($approved as $item) {
          // One malformed AI payload used to take the whole Livewire request down
          // with it — the admin saw Filament's "Error while loading page", nothing
          // was saved, and no message said which item was at fault.
          try {
            $d = $item->generated_data ?? [];
            $productName = $d['name'] ?? $item->product_name;

            // Case-insensitive so "Iverheal 6" and "iverheal 6" are one product.
            if (Product::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($productName))])->exists()) {
                $item->update(['status' => 'skipped']);
                $skipped++;
                continue;
            }

            // Unique slug.
            // withTrashed(): the unique index covers soft-deleted rows too, so a
            // plain exists() reported "free" for a slug that would still collide.
            $slug = Str::slug($productName);
            $originalSlug = $slug;
            $counter = 1;
            while (Product::withTrashed()->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            // Unique SKU. products.sku is unique as well, and the model derives
            // the same code for different products — "Iverheal 3" and
            // "Iverhuman 3" both produce MED-IVE-3 — which aborted the entire
            // save with "Duplicate entry 'MED-IVE-3' for key products_sku_unique".
            // An empty SKU becomes NULL: a unique index allows many NULLs but
            // only one empty string.
            $sku = trim((string) ($d['sku'] ?? ''));
            if ($sku === '') {
                $sku = null;
            } else {
                $originalSku = $sku;
                $skuCounter  = 2;
                while (Product::withTrashed()->where('sku', $sku)->exists()) {
                    $sku = $originalSku . '-' . $skuCounter++;
                }
            }

            // Category whitelist: only match an EXISTING category. Never auto-create
            // a new one from AI output (prevents junk categories like "Sexual Wellness").
            $categoryId = null;
            $matchedCategory = false;
            if (!empty($d['category'])) {
                $match = Category::whereRaw('LOWER(name) = ?', [strtolower(trim($d['category']))])->first();
                if ($match) {
                    $categoryId = $match->id;
                    $matchedCategory = true;
                } else {
                    \Illuminate\Support\Facades\Log::warning(
                        "AI returned unknown category '{$d['category']}' for product '{$productName}' — left uncategorized for manual review."
                    );
                }
            }

            // Brands cannot be pre-seeded the way categories are, so a genuinely
            // new one is still created — but the raw value is normalised and
            // matched case-insensitively first, so "iverheal", "Iverheal 6mg"
            // and "Unknown" no longer each become their own Brand row.
            $brandId = Brand::resolveFromAi($d['brand'] ?? null)?->id;

            // Link (and auto-create) the composition / salt page.
            // "Tadalafil 5mg" → salt "Tadalafil". Combination drugs ("A + B") are
            // linked to their FIRST salt for now (a pivot can be added later).
            $compositionId = null;
            if (!empty($d['composition'])) {
                $firstSalt = trim(preg_split('/[+\/,]/', $d['composition'])[0] ?? '');
                $saltName  = trim(preg_replace('/\d+\s*(mg|mcg|ml|g|iu|%)\b.*/i', '', $firstSalt));
                if ($saltName !== '') {
                    $composition = Composition::firstOrCreate(
                        ['slug' => Str::slug($saltName)],
                        ['name' => $saltName, 'content_status' => 'needs_review']
                    );
                    $compositionId = $composition->id;

                    // Salt-page content for a brand-new composition. dispatch()
                    // pushed this onto the database queue, which nothing on this
                    // plan consumes (no worker, proc_open disabled), so those
                    // pages stayed permanently empty. Run it inline, and never
                    // let its failure abort the product save.
                    if ($composition->wasRecentlyCreated) {
                        try {
                            dispatch_sync(new GenerateCompositionContentJob($composition->id));
                        } catch (\Throwable $e) {
                            Log::warning("Composition content failed for '{$saltName}': " . $e->getMessage());
                        }
                    }
                }
            }

            // YMYL validation gate: hold medical content for human review if it
            // fails any safety/completeness check instead of auto-publishing it.
            $issues = app(\App\Services\AIProductService::class)
                ->validateProductData($d, $matchedCategory);
            $needsReview = count($issues) > 0;
            if ($needsReview) {
                \Illuminate\Support\Facades\Log::warning(
                    "Product '{$productName}' held for review: " . implode(' ', $issues)
                );
            }

            Product::create([
                'name'                  => $productName,
                'slug'                  => $slug,
                'short_description'     => $d['short_description'] ?? '',
                'description'           => $d['description'] ?? '',
                'price'                 => $d['price'] ?? 0,
                'compare_price'         => $d['compare_price'] ?? null,
                'sku'                   => $sku,
                'category_id'           => $categoryId,
                'composition_id'        => $compositionId,
                'brand_id'              => $brandId,
                'tags'                  => $d['tags'] ?? [],
                'composition'           => $d['composition'] ?? '',
                'drug_class'            => $d['drug_class'] ?? null,
                'how_it_works'          => $d['how_it_works'] ?? null,
                'side_effects'          => $d['side_effects'] ?? null,
                'contraindications'     => $d['contraindications'] ?? null,
                'medical_disclaimer'    => $d['medical_disclaimer'] ?? null,
                'faq'                   => is_array($d['faq'] ?? null) ? $d['faq'] : [],
                'manufacturer'          => $d['manufacturer'] ?? '',
                'storage_conditions'    => $d['storage_conditions'] ?? '',
                'meta_title'            => $d['meta_title'] ?? '',
                'meta_description'      => $d['meta_description'] ?? '',
                'unit'                  => $d['unit'] ?? 'strip',
                'weight'                => $d['weight'] ?? null,
                'weight_unit'           => $d['weight_unit'] ?? 'g',
                'requires_prescription' => $d['requires_prescription'] ?? false,
                // Never set before, so every AI product inherited the column
                // default of 0 and went live out of stock. Configurable under
                // AI Settings → Product Defaults.
                'stock_quantity'        => (int) \App\Models\Setting::get('ai.default_stock', '100'),
                'low_stock_threshold'   => (int) \App\Models\Setting::get('ai.default_low_stock', '5'),
                'thumbnail'             => $item->image_path,
                'content_status'        => $needsReview ? 'needs_review' : 'published',
                // Content that failed validation stays hidden from the storefront
                // until an admin reviews and activates it.
                'is_active'             => !$needsReview,
            ]);

            $item->update(['status' => 'saved']);
            $saved++;

          } catch (\Throwable $e) {
            Log::error("saveApproved failed for #{$item->id} [{$item->product_name}]: " . $e->getMessage());
            $failures[$item->product_name] = $e->getMessage();

            $item->update([
                'status'        => 'failed',
                'error_message' => 'Save failed: ' . Str::limit($e->getMessage(), 400),
            ]);
          }
        }

        // Report what actually happened. The old version always claimed success
        // and then wiped the batch, so a partial save looked like a clean one.
        if ($failures) {
            $detail = collect($failures)
                ->map(fn ($reason, $name) => "{$name}: " . Str::limit($reason, 120))
                ->implode(' — ');

            Notification::make()
                ->title(sprintf('%d saved, %d failed', $saved, count($failures)))
                ->body($detail . ' — failed items stay in the list, marked Failed.')
                ->danger()
                ->persistent()
                ->send();
        } else {
            $msg = "{$saved} products saved.";
            if ($skipped > 0) {
                $msg .= " {$skipped} skipped (already in the catalogue).";
            }
            Notification::make()->title($msg)->success()->send();
        }

        $this->refreshQueueItems();
        $this->recountProgress();

        // Clear the batch only when nothing is left needing attention, so a
        // failed or image-pending item stays on screen to be dealt with.
        $leftover = AIProductQueue::where('type', $this->generationType)
            ->whereIn('product_name', $this->parsedNames)
            ->where('status', '!=', 'saved')
            ->exists();

        if (!$leftover) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->parsedNames = [];
        $this->queueItems = [];
        $this->rawProductList = '';
        $this->csvFile = null;
        $this->txtFile = null;
        $this->excelFile = null;
        $this->currentStep = 1;
        $this->isParsing = false;
        $this->isGenerating = false;
        $this->totalCount = 0;
        $this->completedCount = 0;
    }

    public function dehydrate(): void
    {
        $this->csvFile = null;
        $this->txtFile = null;
        $this->excelFile = null;
    }

    public function getProgressPercent(): int
    {
        if ($this->totalCount === 0) return 0;
        return min(100, (int)round(($this->completedCount / $this->totalCount) * 100));
    }
}