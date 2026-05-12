<?php

namespace App\Filament\Pages;

use App\Jobs\GenerateProductImageJob;
use App\Jobs\GenerateProductTextJob;
use App\Models\AIProductQueue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
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

    public function mount(): void
    {
        // Restore state after page refresh — load queue items from DB
        $existing = AIProductQueue::whereNull('approved_by')
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

        // Clean old non-approved items for these names
        AIProductQueue::whereIn('product_name', $this->parsedNames)
            ->whereNull('approved_by')
            ->delete();

        // Create items + dispatch with 2s delay between jobs (prevents API rate limits)
        $delay = 0;
        foreach ($this->parsedNames as $name) {
            $item = AIProductQueue::create(['product_name' => $name, 'status' => 'pending']);
            dispatch(new GenerateProductTextJob($item->id))->delay(now()->addSeconds($delay));
            $delay += 2;
        }

        $this->isGenerating   = true;
        $this->totalCount     = count($this->parsedNames);
        $this->completedCount = 0;
        $this->refreshQueueItems();

        Notification::make()->title("{$this->totalCount} jobs dispatched to queue.")->info()->send();
    }

    public function pollStatus(): void
    {
        // ONLY read DB — never call APIs here (prevents 504 timeouts)
        $this->refreshQueueItems();
        $this->completedCount = collect($this->queueItems)
            ->whereIn('status', ['completed', 'failed', 'skipped'])->count();

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
        // Check if there are any pending jobs in the jobs table
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->where('failed_at', '>=', now()->subMinutes(5))->count();

        // Check if any queue item has been stuck (updated > 3 mins ago but not completed)
        $staleItems = AIProductQueue::whereIn('status', ['generating', 'text_generated'])
            ->where('updated_at', '<', now()->subMinutes(3))
            ->count();

        if ($pendingJobs > 0 || collect($this->queueItems)->whereIn('status', ['generating', 'text_generated'])->isNotEmpty()) {
            if ($staleItems > 0) {
                $this->workerStatus = 'stale'; // Worker might have crashed
            } else {
                $this->workerStatus = 'running';
            }
        } elseif (collect($this->queueItems)->where('status', 'pending')->isNotEmpty()) {
            $this->workerStatus = 'stopped'; // Jobs waiting but nothing processing
        } else {
            $this->workerStatus = 'idle';
        }
    }

    public function startQueueWorker(): void
    {
        // On shared hosting, background processes don't work.
        // Detect environment and route accordingly.
        $isSharedHosting = !function_exists('exec') || (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' && !is_writable('/proc'));

        if (!$isSharedHosting) {
            try {
                $phpPath = PHP_BINARY;
                $artisan = base_path('artisan');
                $logFile = storage_path('logs/queue-worker.log');

                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $cmd = "cmd /c start /MIN /B \"{$phpPath}\" \"{$artisan}\" queue:work --timeout=350 --tries=2 --stop-when-empty > \"{$logFile}\" 2>&1";
                    pclose(popen($cmd, 'r'));
                } else {
                    $cmd = "\"{$phpPath}\" \"{$artisan}\" queue:work --timeout=350 --tries=2 --stop-when-empty > \"{$logFile}\" 2>&1 &";
                    exec($cmd);
                }

                $this->workerStatus = 'running';
                Notification::make()->title('Queue worker started!')->body('Processing will begin shortly.')->success()->send();
                Log::info('Queue worker started from frontend');
                return;
            } catch (\Exception $e) {
                Log::warning('Background worker failed, falling back to sync: ' . $e->getMessage());
            }
        }

        // Shared hosting / fallback: run synchronously
        $this->processNow();
    }

    /**
     * Process pending jobs synchronously — works on ALL hosting including shared.
     * Uses the ai:process-queue Artisan command which handles timeouts safely.
     */
    public function processNow(): void
    {
        $pending = AIProductQueue::whereIn('status', ['pending', 'text_generated'])->count();

        if ($pending === 0) {
            Notification::make()->title('No pending jobs')->body('All products are already processed.')->info()->send();
            return;
        }

        try {
            set_time_limit(400);

            // Run our custom safe command synchronously
            \Artisan::call('ai:process-queue', ['--max' => 5]);

            $output = \Artisan::output();
            Log::info('ai:process-queue output: ' . $output);

            $this->refreshQueueItems();
            $this->workerStatus = 'running';

            $stillPending = AIProductQueue::whereIn('status', ['pending', 'text_generated'])->count();

            Notification::make()
                ->title('⚡ Processing complete!')
                ->body($stillPending > 0 ? "Done this batch. {$stillPending} items still pending — click again." : 'All items processed!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('processNow failed: ' . $e->getMessage());
            Notification::make()->title('Processing failed')->body($e->getMessage())->danger()->send();
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
        $this->queueItems = AIProductQueue::whereIn('product_name', $this->parsedNames)
            ->select('id', 'product_name', 'status', 'generated_data', 'image_path', 'error_message', 'approved_by', 'approved_at', 'updated_at', 'text_model_used', 'image_model_used')
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
                                   ->whereNotNull('approved_by')
                                   ->get();

        if ($approved->isEmpty()) {
            Notification::make()->title('No approved products to save.')->warning()->send();
            return;
        }

        $saved = 0;
        $skipped = 0;
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

            // Auto-create category if missing
            $categoryId = null;
            if (!empty($d['category'])) {
                $categoryId = Category::firstOrCreate(
                    ['name' => $d['category']],
                    ['slug' => Str::slug($d['category'])]
                )->id;
            }

            // Auto-create brand if missing
            $brandId = null;
            if (!empty($d['brand'])) {
                $brandId = Brand::firstOrCreate(
                    ['name' => $d['brand']],
                    ['slug' => Str::slug($d['brand'])]
                )->id;
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
                'brand_id'              => $brandId,
                'tags'                  => $d['tags'] ?? [],
                'composition'           => $d['composition'] ?? '',
                'manufacturer'          => $d['manufacturer'] ?? '',
                'storage_conditions'    => $d['storage_conditions'] ?? '',
                'meta_title'            => $d['meta_title'] ?? '',
                'meta_description'      => $d['meta_description'] ?? '',
                'unit'                  => $d['unit'] ?? 'strip',
                'weight'                => $d['weight'] ?? null,
                'requires_prescription' => $d['requires_prescription'] ?? false,
                'thumbnail'             => $item->image_path,
                'is_active'             => true,
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