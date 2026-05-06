<?php

namespace App\Filament\Pages;

use App\Jobs\GenerateProductFromAI;
use App\Models\AIProductQueue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\AIProductService;
use App\Services\ImageProcessingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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
    public $csvFile               = null;
    public $txtFile               = null;
    public $excelFile             = null;
    public array  $parsedNames    = [];
    public array  $queueItems     = [];
    public bool   $isGenerating   = false;
    public int    $totalCount     = 0;
    public int    $completedCount = 0;
    public int    $currentStep    = 1;

    public function parseInput(): void
    {
        $service = app(AIProductService::class);
        if (!empty($this->rawProductList)) {
            $this->parsedNames = $service->parseProductList($this->rawProductList);
        }
        // CSV upload
        if ($this->csvFile) {
            $csv = file_get_contents($this->csvFile->getRealPath());
            $this->parsedNames = array_merge($this->parsedNames, $service->parseProductList($csv));
        }
        // TXT/Notepad upload
        if ($this->txtFile) {
            $txt = file_get_contents($this->txtFile->getRealPath());
            $this->parsedNames = array_merge($this->parsedNames, $service->parseProductList($txt));
        }
        // Excel upload (.xlsx/.xls)
        if ($this->excelFile) {
            $rows = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->excelFile->getRealPath())
                ->getActiveSheet()->toArray();
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
        Notification::make()->title(count($this->parsedNames) . ' products parsed.')->success()->send();
    }

    public function removeFromParsed(int $index): void
    {
        array_splice($this->parsedNames, $index, 1);
        $this->parsedNames = array_values($this->parsedNames);
    }

    public function generateAll(): void
    {
        if (empty($this->parsedNames)) { Notification::make()->title('No products.')->warning()->send(); return; }
        $this->isGenerating = true;
        $this->totalCount   = count($this->parsedNames);
        $this->completedCount = 0;
        $this->currentStep  = 2;
        foreach ($this->parsedNames as $name) {
            $q = AIProductQueue::create(['product_name' => $name, 'status' => 'pending']);
            GenerateProductFromAI::dispatch($q->id);
        }
        $this->refreshQueueItems();
        Notification::make()->title("Generating {$this->totalCount} products...")->info()->send();
    }

    public function pollStatus(): void
    {
        $this->refreshQueueItems();
        $c = collect($this->queueItems)->whereIn('status', ['completed','failed','skipped'])->count();
        $this->completedCount = $c;
        if ($c >= $this->totalCount && $this->totalCount > 0) $this->isGenerating = false;
    }

    private function refreshQueueItems(): void
    {
        $ids = AIProductQueue::whereIn('product_name', $this->parsedNames)->pluck('id');
        $this->queueItems = AIProductQueue::whereIn('id', $ids)
            ->orderByRaw("FIELD(status,'generating','pending','completed','failed','skipped','saved')")->get()->toArray();
    }

    public function approveProduct(int $queueId): void
    {
        AIProductQueue::findOrFail($queueId)->update(['approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->refreshQueueItems();
    }

    public function skipProduct(int $queueId): void
    {
        AIProductQueue::findOrFail($queueId)->update(['status' => 'skipped']);
        $this->refreshQueueItems();
    }

    public function regenerateImage(int $queueId): void
    {
        try {
            app(ImageProcessingService::class)->regenerate($queueId);
            $this->refreshQueueItems();
            Notification::make()->title('Image regenerated.')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
        }
    }

    public function regenerateText(int $queueId): void
    {
        $item = AIProductQueue::findOrFail($queueId);
        $item->update(['status' => 'generating']);
        try {
            $data = app(AIProductService::class)->generateProductDetails($item->product_name);
            $item->update(['generated_data' => $data, 'status' => 'completed']);
        } catch (\Exception $e) {
            $item->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
        $this->refreshQueueItems();
        Notification::make()->title('Text regenerated.')->success()->send();
    }

    public function saveApproved(): void
    {
        $approved = AIProductQueue::where('status', 'completed')->whereNotNull('approved_by')->get();
        if ($approved->isEmpty()) { Notification::make()->title('No approved products.')->warning()->send(); return; }
        $saved = 0;
        foreach ($approved as $item) {
            $d = $item->generated_data ?? [];
            Product::create([
                'name' => $d['name'] ?? $item->product_name,
                'slug' => Str::slug($d['name'] ?? $item->product_name),
                'short_description' => $d['short_description'] ?? '',
                'description' => $d['description'] ?? '',
                'price' => $d['price'] ?? 0,
                'compare_price' => $d['compare_price'] ?? null,
                'sku' => $d['sku'] ?? null,
                'category_id' => Category::where('name', $d['category'] ?? '')->value('id'),
                'brand_id' => Brand::where('name', $d['brand'] ?? '')->value('id'),
                'tags' => $d['tags'] ?? [],
                'composition' => $d['composition'] ?? '',
                'manufacturer' => $d['manufacturer'] ?? '',
                'storage_conditions' => $d['storage_conditions'] ?? '',
                'meta_title' => $d['meta_title'] ?? '',
                'meta_description' => $d['meta_description'] ?? '',
                'unit' => $d['unit'] ?? 'strip',
                'weight' => $d['weight'] ?? null,
                'requires_prescription' => $d['requires_prescription'] ?? false,
                'thumbnail' => $item->image_path,
                'is_active' => true,
            ]);
            $item->update(['status' => 'saved']); $saved++;
        }
        Notification::make()->title("{$saved} products saved!")->success()->send();
        $this->parsedNames = []; $this->queueItems = []; $this->rawProductList = '';
        $this->csvFile = null; $this->txtFile = null; $this->excelFile = null;
        $this->currentStep = 1; $this->isGenerating = false; $this->totalCount = 0; $this->completedCount = 0;
    }

    public function getProgressPercent(): int
    {
        if ($this->totalCount === 0) return 0;
        return min(100, (int)round(($this->completedCount / $this->totalCount) * 100));
    }
}
