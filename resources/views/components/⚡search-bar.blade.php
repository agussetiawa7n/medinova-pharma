<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Product;

new class extends Component
{
    public string $query = '';
    public array $results = [];
    public bool $showResults = false;

    public function showDropdown(): void
    {
        if (strlen($this->query) >= 2 && count($this->results)) {
            $this->showResults = true;
        }
    }

    public function hideDropdown(): void
    {
        $this->showResults = false;
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            $this->showResults = false;
            return;
        }
        $this->results = Product::active()
            ->where('name', 'like', '%' . $this->query . '%')
            ->select('id', 'name', 'slug', 'thumbnail')
            ->limit(6)
            ->get()
            ->toArray();
        $this->showResults = true;
    }

    public function search(): void
    {
        $this->redirect(route('products.index', ['q' => $this->query]));
    }

    public function clear(): void
    {
        $this->query = '';
        $this->results = [];
        $this->showResults = false;
    }
};
?>

<div class="relative w-full" wire:click.outside="hideDropdown">
    <form wire:submit="search" class="flex">
        <div class="relative flex-1">
            <input wire:model.live.debounce.300ms="query"
                   wire:focus="showDropdown"
                   type="text"
                   placeholder="Search medicines, brands…"
                   class="w-full pl-4 pr-10 py-2 rounded-l-full border border-r-0 border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-300">
            @if($query)
            <button type="button" wire:click="clear" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            @endif
        </div>
        <button type="submit" class="px-4 py-2 rounded-r-full text-white text-sm font-medium" style="background:linear-gradient(135deg,#FF647B,#FA4E67)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </button>
    </form>
    @if($showResults && count($results))
    <div class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50">
        @foreach($results as $product)
        <a href="{{ route('products.show', $product['slug']) }}" wire:click="clear"
           class="flex items-center gap-3 px-4 py-3 hover:bg-brand-50 transition-colors">
            @if($product['thumbnail'])
            <img src="{{ Storage::url($product['thumbnail']) }}" class="w-10 h-10 rounded-lg object-cover">
            @else
            <div class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            @endif
            <span class="text-sm font-medium text-gray-800">{{ $product['name'] }}</span>
        </a>
        @endforeach
        <a href="{{ route('products.index', ['q' => $query]) }}"
           class="block px-4 py-3 text-center text-sm text-brand-700 font-medium bg-brand-50 hover:bg-brand-100 transition-colors">
            See all results for "{{ $query }}"
        </a>
    </div>
    @endif
</div>
