<?php

namespace App\Http\Controllers;

use App\Models\Composition;

class CompositionController extends Controller
{
    public function show(Composition $composition)
    {
        $products = $composition->products()
            ->where('is_active', true)
            ->with(['brand', 'category'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('is_featured')
            ->latest()
            ->get();

        return view('compositions.show', compact('composition', 'products'));
    }
}
