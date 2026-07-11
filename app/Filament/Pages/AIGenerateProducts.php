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
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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
    public bool $processingImage = false; // Lock to prevent concurrent image processing

    public function mount(): void
    {
        // Restore state after page refresh — load queue items from DB
        $existing = AIProductQueue::whereNull('approved_by')
            ->whereIn('status', ['pending', 'generating', 'text_generated', 'completed', 'failed'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($existing->isNotEmpty()) {
            // Restore correct type based on active items
            $this->generationType = $existing->first()->type ?? 'product';
            
            $filtered = $existing->where('type', $this->generationType);
            if ($filtered->isNotEmpty()) {
                $this->parsedNames = $filtered->pluck('product_name')->unique()->values()->toArray();
                $this->currentStep = 2;
                $this->totalCount  = count($this->parsedNames);
                $this->refreshQueueItems();

                $hasActive = $filtered->whereIn('status', ['pending', 'generating', 'text_generated'])->isNotEmpty();
                $hasFullyDone = $filtered->whereIn('status', ['completed', 'failed', 'skipped', 'saved'])->count();
                if ($hasActive && $hasFullyDone < $this->totalCount) {
                    $this->isGenerating = true;
                }
                $this->completedCount = $hasFullyDone;
            }
        }
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

        $existing = AIProductQueue::whereNull('approved_by')
            ->where('type', $value)
            ->whereIn('status', ['pending', 'generating', 'text_generated', 'completed', 'failed'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($existing->isNotEmpty()) {
            $this->parsedNames = $existing->pluck('product_name')->unique()->values()->toArray();
            $this->currentStep = 2;
            $this->totalCount  = count($this->parsedNames);
            $this->refreshQueueItems();

            $hasActive = $existing->whereIn('status', ['pending', 'generating', 'text_generated'])->isNotEmpty();
            $hasFullyDone = $existing->whereIn('status', ['completed', 'failed', 'skipped', 'saved'])->count();
            if ($hasActive && $hasFullyDone < $this->totalCount) {
                $this->isGenerating = true;
            }
            $this->completedCount = $hasFullyDone;
        }
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

        // Clean old non-approved items for these names of the current type
        AIProductQueue::whereIn('product_name', $this->parsedNames)
            ->where('type', $this->generationType)
            ->whereNull('approved_by')
            ->delete();

        // Create all queue items as 'pending'
        $items = [];
        foreach ($this->parsedNames as $name) {
            $items[] = AIProductQueue::create([
                'product_name' => $name,
                'type'         => $this->generationType,
                'status'       => 'pending'
            ]);
        }

        $this->isGenerating   = true;
        $this->totalCount     = count($this->parsedNames);
        $this->completedCount = 0;
        $this->workerStatus   = 'running';
        $this->currentStep    = 2;

        // ── Immediately process TEXT generation for all products ──
        // Text is fast (~5-10s each). Images are dispatched to queue for cron.
        set_time_limit(600);
        ini_set('max_execution_time', 600);

        foreach ($items as $item) {
            try {
                $this->currentTask = "📝 Generating: {$item->product_name}";
                dispatch_sync(new GenerateProductTextJob($item->id));
            } catch (\Exception $e) {
                Log::error("generateAll text failed [{$item->product_name}]: " . $e->getMessage());
            }
        }

        $this->currentTask = '';
        $this->refreshQueueItems();
        $this->completedCount = collect($this->queueItems)
            ->whereIn('status', ['completed', 'failed', 'skipped'])->count();

        // Check if any images still pending (will be processed by cron)
        $imagePending = collect($this->queueItems)->where('status', 'text_generated')->count();

        if ($imagePending > 0) {
            $this->workerStatus = 'running';
            Notification::make()
                ->title('✅ Text done! Images generating...')
                ->body("Text generated for all {$this->totalCount} items. Images are being generated (auto-updates every 2s).")
                ->success()
                ->send();
        } else {
            $this->workerStatus = 'idle';
            Notification::make()
                ->title('✅ All items ready!')
                ->body('Review the results below and approve them to save.')
                ->success()
                ->send();
        }
    }

    public function pollStatus(): void
    {
        // Refresh DB state
        $this->refreshQueueItems();
        $this->completedCount = collect($this->queueItems)
            ->whereIn('status', ['completed', 'failed', 'skipped'])->count();

        // ── Auto-process ONE image job per poll (3s interval) ──
        // This handles images automatically after text is done, no extra clicks needed.
        // Lock prevents concurrent execution if image takes longer than poll interval.
        if ($this->isGenerating && !$this->processingImage) {
            $imageItem = AIProductQueue::where('status', 'text_generated')
                ->where('type', 'product') // only products have images
                ->whereIn('product_name', $this->parsedNames)
                ->orderBy('updated_at')
                ->first();

            if ($imageItem) {
                try {
                    $this->processingImage = true;
                    set_time_limit(360);
                    $this->currentTask = "🖼️ Image: {$imageItem->product_name}";
                    $this->workerStatus = 'running';
                    dispatch_sync(new GenerateProductImageJob($imageItem->id));
                    $this->refreshQueueItems();
                    $this->completedCount = collect($this->queueItems)
                        ->whereIn('status', ['completed', 'failed', 'skipped'])->count();
                } catch (\Exception $e) {
                    Log::error("pollStatus image failed [{$imageItem->product_name}]: " . $e->getMessage());
                } finally {
                    $this->processingImage = false;
                }
            }
        }

        // Detect current task
        $this->detectCurrentTask();

        // Check worker health
        $this->checkWorkerStatus();

        if ($this->completedCount >= $this->totalCount && $this->totalCount > 0) {
            $this->isGenerating = false;
            $this->currentStep = 2;
            $this->currentTask = '';
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

        $name = $active['product_name'];
        $status = $active['status'];

        if ($status === 'generating') {
            $this->currentTask = "📝 Generating text for \"{$name}\"";
        } elseif ($status === 'text_generated') {
            $this->currentTask = "🖼️ Generating image for \"{$name}\"";
        } else {
            $this->currentTask = "⏳ Waiting in queue: \"{$name}\"";
        }

        // Calculate elapsed time from updated_at
        if (!empty($active['updated_at'])) {
            $elapsed = now()->diffInSeconds($active['updated_at']);
            if ($elapsed < 60) {
                $this->elapsedTime = $elapsed . 's';
            } else {
                $this->elapsedTime = floor($elapsed / 60) . 'm ' . ($elapsed % 60) . 's';
            }
        }
    }

    public function checkWorkerStatus(): void
    {
        $pendingItems    = collect($this->queueItems)->whereIn('status', ['pending'])->count();
        $processingItems = collect($this->queueItems)->whereIn('status', ['generating', 'text_generated'])->count();

        // Check if any queue item has been stuck (updated > 5 mins ago but not completed)
        // Use 5 min threshold since image generation can take 2-3 min on shared hosting
        $staleItems = AIProductQueue::whereIn('status', ['generating'])
            ->where('updated_at', '<', now()->subMinutes(5))
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
        // Hostinger shared hosting: background processes are not supported.
        // Always process synchronously via processNow().
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
            // Set high timeout — image generation can take 2-3 minutes per product
            set_time_limit(600);
            ini_set('max_execution_time', 600);

            $this->workerStatus = 'running';

            // Process max 3 items per click to stay within hosting limits
            $batchSize = 3;
            $processed = 0;

            // Step 1: Process pending → text_generated / completed
            $textItems = AIProductQueue::where('status', 'pending')
                ->where('type', $this->generationType)
                ->orderBy('created_at')
                ->take($batchSize)
                ->get();

            foreach ($textItems as $item) {
                $this->currentTask = "📝 Generating text for \"{$item->product_name}\"";
                dispatch_sync(new GenerateProductTextJob($item->id));
                $processed++;
            }

            // Step 2: Process text_generated → completed (image, only for products)
            if ($this->generationType === 'product') {
                $imageItems = AIProductQueue::where('status', 'text_generated')
                    ->where('type', 'product')
                    ->orderBy('updated_at')
                    ->take($batchSize - $processed)
                    ->get();

                foreach ($imageItems as $item) {
                    $this->currentTask = "🖼️ Generating image for \"{$item->product_name}\"";
                    dispatch_sync(new GenerateProductImageJob($item->id));
                    $processed++;
                }
            }

            $this->currentTask = '';
            $this->refreshQueueItems();
            $this->completedCount = collect($this->queueItems)
                ->whereIn('status', ['completed', 'failed', 'skipped'])->count();

            $stillPending = AIProductQueue::whereIn('status', ['pending', 'text_generated'])
                ->where('type', $this->generationType)
                ->count();

            if ($stillPending > 0) {
                $this->workerStatus = 'stopped'; // Needs another click
                Notification::make()
                    ->title("⚡ Batch done! {$processed} processed.")
                    ->body("{$stillPending} items still pending — click '▶ Start Worker' again.")
                    ->warning()
                    ->send();
            } else {
                $this->workerStatus = 'idle';
                Notification::make()
                    ->title('✅ All products processed!')
                    ->body('Review the results and approve products to save them.')
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            Log::error('processNow failed: ' . $e->getMessage());
            $this->workerStatus = 'stale';
            Notification::make()
                ->title('Processing error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function restartQueueWorker(): void
    {
        try {
            \Artisan::call('queue:restart');
            sleep(1);
            $this->startQueueWorker();
            Notification::make()->title('Queue worker restarted!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Restart failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function forceStopJobs(): void
    {
        // Mark all active jobs as failed
        $affected = AIProductQueue::whereIn('status', ['pending', 'generating', 'text_generated'])
            ->whereIn('product_name', $this->parsedNames)
            ->update([
                'status' => 'failed',
                'error_message' => 'Force stopped by admin at ' . now()->format('H:i:s'),
            ]);

        // Clear pending jobs from queue table
        DB::table('jobs')->delete();

        // Send restart signal to kill running workers
        \Artisan::call('queue:restart');

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

    public function regenerateText(int $queueId): void
    {
        $item = AIProductQueue::findOrFail($queueId);
        $item->update(['status' => 'pending', 'error_message' => null]);
        dispatch(new GenerateProductTextJob($queueId));
        $this->refreshQueueItems();
        Notification::make()->title('Text regeneration dispatched.')->success()->send();
    }

    public function regenerateImage(int $queueId): void
    {
        $item = AIProductQueue::findOrFail($queueId);
        $item->update([
            'status'       => 'text_generated',
            'image_path'   => null,
            'error_message' => null,
        ]);
        dispatch(new GenerateProductImageJob($queueId));
        $this->refreshQueueItems();
        Notification::make()->title('Image regeneration dispatched.')->success()->send();
    }

    public function saveApproved(): void
    {
        $approved = AIProductQueue::whereIn('status', ['completed', 'text_generated'])
                                   ->where('type', $this->generationType)
                                   ->whereNotNull('approved_by')
                                   ->get();

        if ($approved->isEmpty()) {
            Notification::make()->title('No approved items to save.')->warning()->send();
            return;
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

        foreach ($approved as $item) {
            $d = $item->generated_data ?? [];
            $productName = $d['name'] ?? $item->product_name;

            // Skip duplicates
            if (Product::where('name', $productName)->exists()) {
                $item->update(['status' => 'skipped']);
                $skipped++;
                continue;
            }

            // Unique slug
            $slug = Str::slug($productName);
            $originalSlug = $slug;
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
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

            // Auto-create brand if missing
            $brandId = null;
            if (!empty($d['brand'])) {
                $brandId = Brand::firstOrCreate(
                    ['name' => $d['brand']],
                    ['slug' => Str::slug($d['brand'])]
                )->id;
            }

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
                    // Generate the salt page content only for brand-new compositions.
                    if ($composition->wasRecentlyCreated) {
                        GenerateCompositionContentJob::dispatch($composition->id);
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
                'sku'                   => $d['sku'] ?? null,
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
                'thumbnail'             => $item->image_path,
                'content_status'        => $needsReview ? 'needs_review' : 'published',
                // Content that failed validation stays hidden from the storefront
                // until an admin reviews and activates it.
                'is_active'             => !$needsReview,
            ]);

            $item->update(['status' => 'saved']);
            $saved++;
        }

        $msg = "{$saved} products saved.";
        if ($skipped > 0) {
            $msg .= " {$skipped} skipped (duplicates).";
        }
        Notification::make()->title($msg)->success()->send();
        $this->resetForm();
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