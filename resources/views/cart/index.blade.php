@extends('layouts.app')

@section('title', 'Shopping Cart — MediNova Pharma')

@section('breadcrumb')
    <ol>
        <li><a href="{{ route('home') }}"><i class="fa-solid fa-house me-1"></i> Home</a></li>
        <li class="sep">/</li>
        <li aria-current="page">Cart</li>
    </ol>
@endsection

@section('content')
<div class="container-xxl px-3 px-md-4 py-4 py-lg-5">
    <h1 class="mb-4" style="font-weight:800; font-size:clamp(24px, 3vw, 34px); color:#303030;">
        <i class="fa-solid fa-bag-shopping me-2" style="color:#e61f7f;"></i>
        Your Shopping Cart
    </h1>

    @if(empty($items) || $items->isEmpty())
        {{-- ═════════ Empty state ═════════ --}}
        <div class="text-center py-5 my-5">
            <div class="mx-auto mb-4" style="width:120px; height:120px; border-radius:50%; background:#faeff2; display:flex; align-items:center; justify-content:center; color:#e61f7f; font-size:52px;">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <h3 style="font-weight:800; color:#303030;">Your cart is empty</h3>
            <p class="text-muted mb-4" style="font-size:15px;">Looks like you haven't added any products yet. Browse our catalogue and find what you need.</p>
            <a href="{{ route('products.index') }}" class="btn btn-pharma">
                <i class="fa-solid fa-store me-2"></i> Start Shopping
            </a>
        </div>
    @else
        <div class="row g-4 g-lg-5" x-data="cartPage()">

            {{-- ═════════ Items ═════════ --}}
            <div class="col-lg-8">
                <div class="bg-white border rounded-4 overflow-hidden">
                    <div class="d-none d-md-grid px-4 py-3 border-bottom"
                         style="grid-template-columns: 2fr 1fr 1fr 1fr 40px; gap:16px; background:#F8FAFB; font-size:12px; text-transform:uppercase; letter-spacing:1px; font-weight:700; color:#6B7280;">
                        <div>Product</div>
                        <div>Price</div>
                        <div>Quantity</div>
                        <div>Total</div>
                        <div></div>
                    </div>

                    @foreach($items as $item)
                        <div class="row row-cols-md-5 g-3 p-3 p-md-4 border-bottom align-items-center mn-cart-row"
                             style="display:grid !important; grid-template-columns: 2fr 1fr 1fr 1fr 40px; gap:16px;"
                             data-item-id="{{ $item->id }}">
                            <div class="d-flex gap-3 align-items-center">
                                @if($item->product?->thumbnail)
                                    <img src="{{ $item->product->thumbnail_url }}" alt="{{ $item->product->name }}"
                                         style="width:80px; height:80px; object-fit:contain; padding:8px; background:#F8FAFB; border-radius:10px; flex-shrink:0;">
                                @else
                                    <div style="width:80px; height:80px; background:#F8FAFB; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#D1D5DB; font-size:28px; flex-shrink:0;">
                                        <i class="fa-solid fa-pills"></i>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('products.show', $item->product->slug) }}"
                                       class="d-block text-decoration-none" style="color:#303030; font-weight:700; font-size:14.5px; line-height:1.35;">
                                        {{ $item->product->name ?? 'Product' }}
                                    </a>
                                    @if($item->variant)
                                        <div class="text-muted mt-1" style="font-size:12.5px;">Variant: {{ $item->variant->name }}</div>
                                    @endif
                                    @if($item->product?->requires_prescription)
                                        <span class="badge mt-2" style="background:#f2ecfa; color:#583fa8; font-size:11px; font-weight:700;">
                                            <i class="fa-solid fa-prescription me-1"></i> Rx required
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="mn-cart-col" style="color:#e61f7f; font-weight:700;">
                                <span class="d-md-none text-muted me-2" style="font-size:12px;">Price:</span>
                                ${{ number_format($item->unit_price, 2) }}
                            </div>

                            <div>
                                <span class="d-md-none text-muted me-2" style="font-size:12px;">Qty:</span>
                                <div class="mn-quantity">
                                    <button type="button" @click="updateItem({{ $item->id }}, {{ max(1, $item->quantity - 1) }})" :disabled="busy[{{ $item->id }}]">−</button>
                                    <input type="text" value="{{ $item->quantity }}" readonly data-qty="{{ $item->id }}">
                                    <button type="button" @click="updateItem({{ $item->id }}, {{ $item->quantity + 1 }})" :disabled="busy[{{ $item->id }}]">+</button>
                                </div>
                            </div>

                            <div style="font-weight:800; color:#303030;">
                                <span class="d-md-none text-muted me-2" style="font-size:12px;">Total:</span>
                                ${{ number_format($item->unit_price * $item->quantity, 2) }}
                            </div>

                            <div class="text-end">
                                <button type="button" @click="removeItem({{ $item->id }})"
                                        :disabled="busy[{{ $item->id }}]"
                                        class="btn btn-sm border-0 bg-transparent text-muted" title="Remove">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <div class="p-3 p-md-4 d-flex flex-wrap gap-3 justify-content-between align-items-center" style="background:#F8FAFB;">
                        <a href="{{ route('products.index') }}" class="text-decoration-none fw-bold" style="color:#e61f7f;">
                            <i class="fa-solid fa-arrow-left me-2"></i> Continue Shopping
                        </a>
                        <div class="text-muted" style="font-size:13px;">
                            <i class="fa-solid fa-truck-fast me-1" style="color:#e61f7f;"></i>
                            Free shipping on orders above ${{ setting('pricing.free_shipping_threshold', 499) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═════════ Summary ═════════ --}}
            <div class="col-lg-4">
                <div class="bg-white border rounded-4 p-4 position-sticky" style="top:120px;">
                    <h4 class="mb-4" style="font-weight:800; color:#303030;">Order Summary</h4>

                    {{-- Coupon --}}
                    @if(!isset($coupon) || !$coupon)
                        <form action="{{ route('cart.coupon') }}" method="POST" class="mb-4">
                            @csrf
                            <label class="form-label fw-bold" style="font-size:13px; color:#303030;">Have a coupon?</label>
                            <div class="d-flex gap-2">
                                <input name="code" type="text" placeholder="Enter code"
                                       class="form-control" required
                                       style="border-radius:10px;">
                                <button type="submit" class="btn btn-pharma" style="padding:0 18px; white-space:nowrap;">Apply</button>
                            </div>
                            @error('code')
                                <p class="text-danger mt-2 mb-0" style="font-size:12.5px;">{{ $message }}</p>
                            @enderror
                        </form>
                    @else
                        <div class="d-flex justify-content-between align-items-center p-3 rounded-3 mb-4" style="background:#faeff2;">
                            <div>
                                <div class="fw-bold" style="color:#e61f7f; font-size:14px;">
                                    <i class="fa-solid fa-ticket me-1"></i> {{ $coupon->code }} applied
                                </div>
                                <small class="text-muted">{{ $coupon->description }}</small>
                            </div>
                            <form action="{{ route('cart.coupon.remove') }}" method="POST" class="m-0">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm border-0 bg-transparent text-danger" title="Remove coupon">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success py-2 px-3" style="font-size:13.5px;">{{ session('success') }}</div>
                    @endif

                    {{-- Totals --}}
                    <div class="d-flex flex-column gap-2 mb-3" style="font-size:14.5px;">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Subtotal</span>
                            <span style="color:#303030; font-weight:600;">${{ number_format($subtotal ?? 0, 2) }}</span>
                        </div>
                        @if(isset($discount) && $discount > 0)
                            <div class="d-flex justify-content-between" style="color:#e61f7f;">
                                <span>Discount</span>
                                <span>−${{ number_format($discount, 2) }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Shipping</span>
                            <span style="color:{{ ($shipping ?? 0) > 0 ? '#303030' : '#e61f7f' }}; font-weight:600;">
                                {{ ($shipping ?? 0) > 0 ? '$' . number_format($shipping, 2) : 'FREE' }}
                            </span>
                        </div>
                        @if(isset($tax) && $tax > 0)
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Tax ({{ (int) setting('pricing.tax_rate', 18) }}% GST)</span>
                                <span style="color:#303030; font-weight:600;">${{ number_format($tax, 2) }}</span>
                            </div>
                        @endif
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <span style="font-weight:800; color:#303030;">Total</span>
                        <span style="font-weight:800; font-size:22px; color:#e61f7f;">${{ number_format($total ?? 0, 2) }}</span>
                    </div>

                    <a href="{{ route('checkout') }}" class="btn btn-pharma w-100" style="padding:14px;">
                        Proceed to Checkout <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>

                    <div class="d-flex align-items-center justify-content-center gap-3 text-muted mt-3" style="font-size:12px;">
                        <i class="fa-solid fa-lock"></i>
                        <span>Secure 256-bit SSL checkout</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('cartPage', () => ({
            busy: {},
            updateItem(itemId, qty) {
                if (qty < 1) return this.removeItem(itemId);
                if (this.busy[itemId]) return;
                this.busy[itemId] = true;
                window.apiFetch(`/ajax/cart/item/${itemId}`, { method:'PATCH', body:{ quantity: qty } })
                    .then(() => {
                        Alpine.store('toast').add('Cart updated', 'success');
                        window.dispatchEvent(new CustomEvent('cart-updated'));
                        // Reload to refresh totals on summary
                        setTimeout(() => location.reload(), 180);
                    })
                    .catch(err => {
                        Alpine.store('toast').add(err.message || 'Could not update cart', 'error');
                        this.busy[itemId] = false;
                    });
            },
            removeItem(itemId) {
                if (this.busy[itemId]) return;
                if (!confirm('Remove this item from your cart?')) return;
                this.busy[itemId] = true;
                window.apiFetch(`/ajax/cart/item/${itemId}`, { method:'DELETE' })
                    .then(() => {
                        Alpine.store('toast').add('Item removed', 'success');
                        window.dispatchEvent(new CustomEvent('cart-updated'));
                        const row = document.querySelector(`.mn-cart-row[data-item-id="${itemId}"]`);
                        if (row) row.style.opacity = '0.4';
                        setTimeout(() => location.reload(), 220);
                    })
                    .catch(err => {
                        Alpine.store('toast').add(err.message || 'Could not remove item', 'error');
                        this.busy[itemId] = false;
                    });
            },
        }));
    });
</script>
@endpush
