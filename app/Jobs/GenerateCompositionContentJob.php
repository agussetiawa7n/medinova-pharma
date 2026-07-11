<?php

namespace App\Jobs;

use App\Models\Composition;
use App\Services\AIProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCompositionContentJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [15, 30];
    // Salt-page generation uses the 90s service HTTP timeout; keep the job timeout
    // above it so a real queue worker doesn't kill it mid-generation.
    public int $timeout = 120;

    public function __construct(public readonly int $compositionId) {}

    public function uniqueId(): string
    {
        return 'composition_content_' . $this->compositionId;
    }

    public function handle(AIProductService $aiService): void
    {
        set_time_limit(120);

        $composition = Composition::find($this->compositionId);
        if (!$composition) {
            return;
        }

        // Skip if content already generated
        if (!empty($composition->overview)) {
            return;
        }

        try {
            $d = $aiService->generateCompositionDetails($composition->name);

            $composition->update([
                'overview'           => $d['overview'] ?? null,
                'how_it_works'       => $d['how_it_works'] ?? null,
                'uses'               => $d['uses'] ?? null,
                'side_effects'       => $d['side_effects'] ?? null,
                'precautions'        => $d['precautions'] ?? null,
                'medical_disclaimer' => $d['medical_disclaimer'] ?? null,
                'meta_title'         => $d['meta_title'] ?? null,
                'meta_description'   => $d['meta_description'] ?? null,
                // Content is drafted by AI; keep it as needs_review so an admin can
                // verify medical accuracy before treating it as fully trusted.
                'content_status'     => 'needs_review',
            ]);
        } catch (\Exception $e) {
            Log::error("Composition content failed #{$this->compositionId}: " . $e->getMessage());
            throw $e;
        }
    }
}
