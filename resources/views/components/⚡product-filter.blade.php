<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Category;
use App\Models\Brand;

new class extends Component
{
    #[Url]
    public string $q = '';
    #[Url]
    public string $category = '';
    #[Url]
    public string $brand = '';
    #[Url]
    public int $minPrice = 0;
    #[Url]
    public int $maxPrice = 10000;
    #[Url]
    public string $sort = 'latest';

    public function updated(): void
    {
        $this->dispatch('filters-updated', [
            'q'        => $this->q,
            'category' => $this->category,
            'brand'    => $this->brand,
            'minPrice' => $this->minPrice,
            'maxPrice' => $this->maxPrice,
            'sort'     => $this->sort,
        ]);
    }

    public function clear(): void
    {
        $this->q = '';
        $this->category = '';
        $this->brand = '';
        $this->minPrice = 0;
        $this->maxPrice = 10000;
        $this->sort = 'latest';
        $this->updated();
    }
};
?>

<div class="space-y-6">
    <!-- Search -->
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Search</label>
        <input wire:model.live.debounce.400ms="q" type="text" placeholder="Product name…"
               class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
    </div>

    <!-- Categories -->
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Category</label>
        <select wire:model.live="category" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
            <option value="">All Categories</option>
            @foreach(\App\Models\Category::active()->roots()->get() as $cat)
            <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>

    <!-- Brands -->
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Brand</label>
        <select wire:model.live="brand" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
            <option value="">All Brands</option>
            @foreach(\App\Models\Brand::active()->orderBy('name')->get() as $b)
            <option value="{{ $b->slug }}">{{ $b->name }}</option>
            @endforeach
        </select>
    </div>

    <!-- Price range -->
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Price Range</label>
        <div class="flex items-center gap-2">
            <input wire:model.live.debounce.500ms="minPrice" type="number" min="0" placeholder="Min"
                   class="w-1/2 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
            <span class="text-gray-400">—</span>
            <input wire:model.live.debounce.500ms="maxPrice" type="number" min="0" placeholder="Max"
                   class="w-1/2 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
        </div>
    </div>

    <!-- Sort -->
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Sort By</label>
        <select wire:model.live="sort" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
            <option value="latest">Newest First</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
            <option value="popular">Most Popular</option>
            <option value="rating">Top Rated</option>
        </select>
    </div>

    <button wire:click="clear" class="w-full py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
        Clear Filters
    </button>
</div>
