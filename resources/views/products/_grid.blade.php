@forelse($products as $product)
    <div class="col">
        @include('products._card', compact('product'))
    </div>
@empty
    <div class="col-12 text-center py-5" style="flex:0 0 100%; max-width:100%; width:100%;">
        <div style="width:96px; height:96px; border-radius:50%; background:#F8FAFB; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; color:#9CA3AF; font-size:36px;">
            <i class="fa-regular fa-face-frown"></i>
        </div>
        <h5 class="mb-2" style="font-weight:800; color:#1F2A37;">No products found</h5>
        <p class="text-muted mb-4">Try adjusting your filters or search terms.</p>
        <a href="{{ route('products.index') }}" class="btn btn-pharma-outline">Clear filters</a>
    </div>
@endforelse
