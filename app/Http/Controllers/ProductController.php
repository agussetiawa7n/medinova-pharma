<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()->where('is_active', true)->whereNull('parent_id')
            ->with('children')->orderBy('sort_order')->get();
        $brands = Brand::query()->where('is_active', true)->orderBy('name')->get();

        $initialFilters = $request->only(['q', 'search', 'category', 'brand', 'minPrice', 'maxPrice', 'sort', 'in_stock', 'on_sale']);

        return view('products.index', compact('categories', 'brands', 'initialFilters'));
    }

    public function show(string $slug)
    {
        $product = Product::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->with(['brand', 'category', 'variants'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->firstOrFail();

        $product->loadMissing(['reviews' => fn ($q) => $q->latest()->limit(20), 'reviews.user']);

        $related = Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['brand', 'category'])
            ->limit(8)
            ->get();

        return view('products.show', compact('product', 'related'));
    }

    public function ajaxIndex(Request $request)
    {
        $validated = $request->validate([
            'q'        => 'nullable|string|max:100',
            'search'   => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'brand'    => 'nullable|string|max:100',
            'minPrice' => 'nullable|numeric|min:0',
            'maxPrice' => 'nullable|numeric|min:0',
            'sort'     => 'nullable|in:newest,popular,rating,price_asc,price_desc',
            'in_stock' => 'nullable|boolean',
            'on_sale'  => 'nullable|boolean',
            'page'     => 'nullable|integer|min:1',
        ]);

        $query = Product::query()
            ->where('is_active', true)
            ->with(['brand', 'category'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        $term = $validated['q'] ?? $validated['search'] ?? null;
        if ($term) {
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('short_description', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "{$term}%"); // Prefix-only: uses index
            });
        }

        if (!empty($validated['category'])) {
            $category = Category::where('slug', $validated['category'])->with('children:id,parent_id')->first();
            if ($category) {
                $childIds = $category->children->pluck('id')->push($category->id);
                $query->whereIn('category_id', $childIds);
            }
        }

        if (!empty($validated['brand'])) {
            $brand = Brand::where('slug', $validated['brand'])->first();
            if ($brand) {
                $query->where('brand_id', $brand->id);
            }
        }

        if (isset($validated['minPrice'])) {
            $query->where('price', '>=', $validated['minPrice']);
        }
        if (isset($validated['maxPrice'])) {
            $query->where('price', '<=', $validated['maxPrice']);
        }

        if (!empty($validated['in_stock'])) {
            $query->where(function (Builder $q) {
                $q->where('stock_quantity', '>', 0)->orWhere('allow_backorder', true);
            });
        }

        if (!empty($validated['on_sale'])) {
            $query->whereColumn('compare_price', '>', 'price');
        }

        match ($validated['sort'] ?? 'newest') {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'popular'    => $query->orderByDesc('is_best_seller')->orderByDesc('views'),
            'rating'     => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating'),
            default      => $query->orderByDesc('created_at'),
        };

        $products = $query->paginate(12)->withQueryString();

        return response()->json([
            'html'     => view('products._grid', ['products' => $products])->render(),
            'hasMore'  => $products->hasMorePages(),
            'nextPage' => $products->currentPage() + 1,
            'total'    => $products->total(),
        ]);
    }
}
