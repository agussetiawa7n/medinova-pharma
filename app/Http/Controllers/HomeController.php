<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $categoryIds = json_decode(Setting::get('home.category_ids', '[]'), true) ?: [];
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->when(!empty($categoryIds), fn ($q) => $q->whereIn('id', $categoryIds))
            ->orderBy('sort_order')
            ->withCount('products')
            ->get();

        $allBrands    = Brand::where('is_active', true)->get()->keyBy('id');
        $allCats      = Category::where('is_active', true)->get()->keyBy('id');

        $setRelations = function ($products) use ($allBrands, $allCats) {
            foreach ($products as $p) {
                $p->setRelation('brand', $allBrands->get($p->brand_id));
                $p->setRelation('category', $allCats->get($p->category_id));
            }
        };

        $baseQuery = fn () => Product::query()
            ->where('is_active', true)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        // Dynamic section toggles (default: true)
        $show = fn (string $key) => (bool) Setting::get("home.{$key}", true);
        $sections = [
            'show_feature_strip'    => $show('show_feature_strip'),
            'show_categories'       => $show('show_categories'),
            'show_flash_sale'       => $show('show_flash_sale'),
            'show_promo_banners'    => $show('show_promo_banners'),
            'show_featured'         => $show('show_featured'),
            'show_prescription_cta' => $show('show_prescription_cta'),
            'show_new_arrivals'     => $show('show_new_arrivals'),
            'show_best_sellers'     => $show('show_best_sellers'),
            'show_faq'              => $show('show_faq'),
        ];

        $flashSale = $baseQuery()
            ->whereColumn('compare_price', '>', 'price')
            ->where(fn (Builder $q) => $q->where('stock_quantity', '>', 0)->orWhere('allow_backorder', true))
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $setRelations($flashSale);

        $featuredProducts = $baseQuery()->where('is_featured', true)->limit(8)->get();
        $setRelations($featuredProducts);

        $bestSellers = $baseQuery()->where('is_best_seller', true)->limit(8)->get();
        $setRelations($bestSellers);

        $newArrivals = $baseQuery()->orderByDesc('created_at')->limit(10)->get();
        $setRelations($newArrivals);

        return view('home', array_merge(compact(
            'categories',
            'flashSale', 'featuredProducts', 'bestSellers', 'newArrivals'
        ), ['sections' => $sections]));
    }

    public function newsletterSubscribe(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);

        NewsletterSubscriber::firstOrCreate(
            ['email' => $request->email],
            ['is_active' => true]
        );

        return response()->json(['message' => 'Subscribed successfully!']);
    }
}
