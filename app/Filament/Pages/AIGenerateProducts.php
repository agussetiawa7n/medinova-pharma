<?php

namespace App\Filament\Pages;

use App\Jobs\GenerateProductFromAI;
use App\Models\AIProductQueue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageProcessingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class AIGenerateProducts extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'AI Generate Products';
    protected static ?string $navigationLabel = 'AI Generate';

    protected string $view = 'filament.pages.ai-generate-products';

    public string $rawProductList = '';
    public array  $parsedNames    = [];
    public array  $queueItems     = [];
    public bool   $isGenerating   = false;
    public int    $totalCount     = 0;
    public int    $completedCount = 0;
    public int    $currentStep    = 1;

    public function parseInput(): void
    {
        if (empty($this->rawProductList)) {
            Notification::make()->title('Please paste product names first.')->warning()->send();
            return;
        }
        $service = app(\App\Services\AIProductService::class);
        $this->parsedNames = $service->parseProductList($this->rawProductList);
        Notification::make()->title(count($this->parsedNames) . ' products parsed.')->success()->send();
    }

    public function removeFromParsed(int $index): void
    {
        array_splice($this->parsedNames, $index, 1);
        $this->parsedNames = array_values($this->parsedNames);
    }

    public function generateAll(): void
    {
        if (empty($this->parsedNames)) {
            Notification::make()->title('No products to generate.')->warning()->send();
            return;
        }

        $this->isGenerating = true;
        $this->totalCount   = count($this->parsedNames);
        $this->completedCount = 0;
        $this->currentStep  = 2;

        foreach ($this->parsedNames as $productName) {
            $queueItem = AIProductQueue::create(['product_name' => $productName, 'status' => 'pending']);
            GenerateProductFromAI::dispatch($queueItem->id);
        }

        $this->refreshQueueItems();
        Notification::make()->title("Generating {$this->totalCount} products...")->info()->send();
    }

    public function pollStatus(): void
    {
        $this->refreshQueueItems();
        $completed = collect($this->queueItems)->where('status', 'completed')->count();
        $failed    = collect($this->queueItems)->where('status', 'failed')->count();
        $total     = count($this->queueItems);
        $this->completedCount = $completed + $failed;
        if ($this->completedCount >= $total && $total > 0) {
            $this->isGenerating = false;
        }
    }

    private function refreshQueueItems(): void
    {
        $ids = AIProductQueue::whereIn('product_name', $this->parsedNames)->latest()->pluck('id')->toArray();
        $this->queueItems = AIProductQueue::whereIn('id', $ids)
            ->orderByRaw("FIELD(status,'generating','pending','completed','failed')")
            ->get()->toArray();
    }

    public function approveProduct(int $queueId): void
    {
        $item = AIProductQueue::findOrFail($queueId);
        $item->update(['approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->refreshQueueItems();
    }

    public function skipProduct(int $queueId): void
    {
        AIProductQueue::findOrFail($queueId)->update(['status' => 'skipped']);
        $this->refreshQueueItems();
    }

    public function regenerateImage(int $queueId): void
    {
        app(ImageProcessingService::class)->regenerate($queueId);
        $this->refreshQueueItems();
        Notification::make()->title('Image regenerated.')->success()->send();
    }

    public function regenerateText(int $queueId): void
    {
        $item  = AIProductQueue::findOrFail($queueId);
        $ai    = app(\App\Services\AIProductService::class);
        $data  = $ai->generateProductDetails($item->product_name);
        $item->update(['generated_data' => $data]);
        $this->refreshQueueItems();
        Notification::make()->title('Product details regenerated.')->success()->send();
    }

    public function saveApproved(): void
    {
        $approved = AIProductQueue::where('status', 'completed')->whereNotNull('approved_by')->get();
        $savedCount = 0;

        foreach ($approved as $item) {
            try {
                $data = $item->generated_data ?? [];
                $catId = Category::where('name', $data['category'] ?? '')->value('id');
                $brandId = Brand::where('name', $data['brand'] ?? '')->value('id');

                Product::create([
                    'name'               => $data['name'] ?? $item->product_name,
                    'slug'               => Str::slug($data['name'] ?? $item->product_name),
                    'short_description'  => $data['short_description'] ?? '',
                    'description'        => $data['description'] ?? '',
                    'price'              => $data['price'] ?? 0,
                    'compare_price'      => $data['compare_price'] ?? null,
                    'sku'                => $data['sku'] ?? null,
                    'category_id'        => $catId,
                    'brand_id'           => $brandId,
                    'tags'               => $data['tags'] ?? [],
                    'composition'        => $data['composition'] ?? '',
                    'manufacturer'       => $data['manufacturer'] ?? '',
                    'storage_conditions' => $data['storage_conditions'] ?? '',
                    'meta_title'         => $data['meta_title'] ?? '',
                    'meta_description'   => $data['meta_description'] ?? '',
                    'unit'               => $data['unit'] ?? 'strip',
                    'weight'             => $data['weight'] ?? null,
                    'requires_prescription' => $data['requires_prescription'] ?? false,
                    'thumbnail'          => $item->image_path,
                    'is_active'          => true,
                ]);

                $item->update(['status' => 'saved']);
                $savedCount++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Save failed: {$item->product_name}: " . $e->getMessage());
            }
        }

        Notification::make()->title("{$savedCount} products saved!")->success()->send();
        $this->parsedNames = [];
        $this->queueItems  = [];
        $this->rawProductList = '';
        $this->currentStep = 1;
        $this->isGenerating = false;
    }

    public function getProgressPercent(): int
    {
        if ($this->totalCount === 0) return 0;
        return (int) round(($this->completedCount / $this->totalCount) * 100);
    }
}
