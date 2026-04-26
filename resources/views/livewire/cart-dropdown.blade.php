{{-- Cart panel content — rendered inside Bootstrap offcanvas --}}
<div x-data @cart-updated.window="$wire.refresh()" class="d-flex flex-column h-100">

    @if(count($items))

        {{-- Items list (scrollable) --}}
        <div class="flex-grow-1 overflow-auto px-3 px-md-4 py-2">
            @foreach($items as $item)
                <div class="mn-cart-item" wire:key="cart-item-{{ $item['id'] }}">
                    <a href="{{ route('products.show', $item['slug']) }}" data-bs-dismiss="offcanvas">
                        @if($item['image'])
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}">
                        @else
                            <div style="width:72px; height:72px; background:#F8FAFB; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#9CA3AF;">
                                <i class="fa-solid fa-image"></i>
                            </div>
                        @endif
                    </a>
                    <div class="min-w-0">
                        <a href="{{ route('products.show', $item['slug']) }}" class="text-decoration-none text-reset" data-bs-dismiss="offcanvas">
                            <p class="t text-truncate">{{ $item['name'] }}</p>
                        </a>
                        <div class="q">Qty {{ $item['quantity'] }} × ₹{{ number_format($item['price'], 2) }}</div>
                        <div class="p mt-1">₹{{ number_format($item['line'], 2) }}</div>
                    </div>
                    <button type="button" wire:click="removeItem({{ $item['id'] }})"
                            class="btn btn-sm border-0 bg-transparent text-muted p-1 align-self-start"
                            title="Remove">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Footer summary + CTAs --}}
        <div class="border-top px-3 px-md-4 py-3" style="background:#F8FAFB;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span style="font-size:14px; color:#6B7280;">Subtotal ({{ $count }} {{ \Illuminate\Support\Str::plural('item', $count) }})</span>
                <span style="font-size:20px; font-weight:800; color:#e61f7f;">₹{{ $subtotal }}</span>
            </div>
            <div class="d-grid gap-2">
                <a href="{{ route('cart') }}" class="btn btn-pharma-outline" data-bs-dismiss="offcanvas">View Cart</a>
                <a href="{{ route('checkout') }}" class="btn btn-pharma" data-bs-dismiss="offcanvas">
                    Checkout <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
            <p class="text-center text-muted mt-3 mb-0" style="font-size:12px;">
                <i class="fa-solid fa-shield-halved me-1" style="color:#e61f7f;"></i>
                Shipping &amp; taxes calculated at checkout
            </p>
        </div>

    @else

        {{-- Empty state --}}
        <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-center px-4 py-5">
            <div style="width:96px; height:96px; border-radius:50%; background:#faeff2; display:flex; align-items:center; justify-content:center; color:#e61f7f; font-size:38px; margin-bottom:20px;">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <h5 style="font-weight:800; color:#303030; margin-bottom:6px;">Your cart is empty</h5>
            <p class="text-muted" style="font-size:14px; max-width:260px;">Add some medicines or wellness products to get started.</p>
            <a href="{{ route('products.index') }}" class="btn btn-pharma mt-3" data-bs-dismiss="offcanvas">
                Browse Products <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

    @endif
</div>
