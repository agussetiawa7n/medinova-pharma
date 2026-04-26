<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    public function index()
    {
        $heroBanners  = Banner::active()->hero()->ordered()->get();
        $promoBanners = Banner::active()->promo()->ordered()->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->withCount('products')
            ->get();

        // Flash-sale: products with a compare_price higher than price and in stock
        $flashSale = Product::query()
            ->where('is_active', true)
            ->whereColumn('compare_price', '>', 'price')
            ->where(fn (Builder $q) => $q->where('stock_quantity', '>', 0)->orWhere('allow_backorder', true))
            ->with(['brand', 'category'])
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with(['brand', 'category'])
            ->limit(8)
            ->get();

        $bestSellers = Product::query()
            ->where('is_active', true)
            ->where('is_best_seller', true)
            ->with(['brand', 'category'])
            ->limit(8)
            ->get();

        $newArrivals = Product::query()
            ->where('is_active', true)
            ->with(['brand', 'category'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('home', compact(
            'heroBanners', 'promoBanners', 'categories',
            'flashSale', 'featuredProducts', 'bestSellers', 'newArrivals'
        ));
    }
}
