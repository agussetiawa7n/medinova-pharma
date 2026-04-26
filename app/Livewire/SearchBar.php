<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;

class SearchBar extends Component
{
    public string $query = '';

    public array $results = [];

    public bool $open = false;

    public function updatedQuery(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            $this->open = false;
            return;
        }

        $this->results = Product::active()
            ->where('name', 'like', '%' . $this->query . '%')
            ->with('brand')
            ->limit(6)
            ->get()
            ->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'slug'  => $p->slug,
                'price' => number_format($p->price, 2),
                'image' => $p->thumbnail_url,
            ])
            ->toArray();

        $this->open = count($this->results) > 0;
    }

    public function close(): void
    {
        $this->open = false;
        $this->query = '';
        $this->results = [];
    }

    public function render()
    {
        return view('livewire.search-bar');
    }
}
