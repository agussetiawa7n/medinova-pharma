@php
$checkoutUrl = route('checkout');
$cartUrl = route('cart');
$productsUrl = route('products.index');
@endphp
<div class="offcanvas offcanvas-end mn-cart-offcanvas" tabindex="-1" id="mnCartCanvas"
     aria-labelledby="mnCartCanvasLabel">
    <div class="offcanvas-header border-bottom" style="padding:18px 22px;">
        <h5 class="offcanvas-title m-0 d-flex align-items-center gap-2" id="mnCartCanvasLabel" style="font-weight:800; font-size:18px;">
            <i class="fa-solid fa-bag-shopping" style="color:#e61f7f;"></i>
            Your Cart
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mn-cart-body p-0">
        <div x-data="cartOffcanvas" class="d-flex flex-column" style="height:100%;">

            {{-- Loading spinner --}}
            <div x-show="loading" x-cloak class="mn-cart-section mn-cart-section-loading">
                <div class="text-center">
                    <div class="spinner-border text-muted mb-2" role="status" style="width:32px;height:32px;"></div>
                    <p class="text-muted" style="font-size:13px;">Loading cart...</p>
                </div>
            </div>

            {{-- Has items --}}
            <div x-show="!loading && items.length" x-cloak class="mn-cart-section mn-cart-section-items">

                <div class="mn-cart-items-scroll px-3 px-md-4 py-2">

                    {{-- Real items --}}
                    <template x-for="item in items" :key="item.id">
                        <div class="mn-cart-item">
                            <a :href="'/products/' + item.slug">
                                <img :src="item.image" :alt="item.name" x-show="item.image" style="width:72px;height:72px;object-fit:contain;padding:10px;border-radius:8px;">
                                <div x-show="!item.image" style="width:72px;height:72px;background:#F8FAFB;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#9CA3AF;">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            </a>
                            <div class="min-w-0">
                                <a :href="'/products/' + item.slug" class="text-decoration-none text-reset">
                                    <p class="t text-truncate" x-text="item.name"></p>
                                </a>
                                <div class="q" x-text="'Qty ' + item.quantity + ' x $' + Number(item.price).toFixed(2)"></div>
                                <div class="p mt-1" x-text="'$' + Number(item.line).toFixed(2)"></div>
                            </div>
                            <button type="button" @click="removeItem(item.id)" :disabled="!!removing[item.id]"
                                    class="btn btn-sm border-0 bg-transparent text-muted p-1 align-self-start"
                                    title="Remove">
                                <i class="fa-solid fa-xmark" x-show="!removing[item.id]"></i>
                                <i class="fa-solid fa-spinner fa-spin" x-show="!!removing[item.id]" style="display:none;"></i>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="border-top px-3 px-md-4 py-3" style="background:#fff; flex-shrink:0;">
                    <div class="row align-items-baseline g-0 mb-3">
                        <div class="col">
                            <span style="font-size:14px; font-weight:600; color:#111827;">Subtotal</span>
                            <span style="font-size:14px; color:#9CA3AF; font-weight:400;" x-text="' (' + count + ' ' + (count === 1 ? 'item' : 'items') + ')'"></span>
                        </div>
                        <div class="col-auto">
                            <span style="font-size:20px; font-weight:700; color:#111827; white-space:nowrap;" x-text="'$' + Number(subtotal).toFixed(2)"></span>
                        </div>
                    </div>
                    <a href="{{ $checkoutUrl }}" class="btn btn-dark d-block w-100 mb-2" style="border-radius:10px; padding:12px; font-weight:600; font-size:14px; background:#111827; border-color:#111827;">
                        Checkout
                    </a>
                    <a href="{{ $cartUrl }}" class="btn d-block w-100" style="border-radius:10px; padding:10px; font-size:13px; font-weight:500; color:#6B7280; background:#F3F4F6; border:none;">
                        View Cart
                    </a>
                </div>
            </div>

            {{-- Empty state --}}
            <div x-show="!loading && !items.length && !$store.cartPending.items.length" x-cloak
                 class="mn-cart-section mn-cart-section-empty">
                <div style="width:96px; height:96px; border-radius:50%; background:#faeff2; display:flex; align-items:center; justify-content:center; color:#e61f7f; font-size:38px; margin-bottom:20px;">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <h5 style="font-weight:800; color:#303030; margin-bottom:6px;">Your cart is empty</h5>
                <p class="text-muted" style="font-size:14px; max-width:260px;">Add medicines or wellness products to get started.</p>
                <a href="{{ $productsUrl }}" class="btn btn-pharma mt-3">
                    Browse Products <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            {{-- Optimistic pending items — single source, shows whenever adding --}}
            <div x-show="$store.cartPending.items.length" x-cloak
                 class="mn-cart-pending px-3 px-md-4 py-2">
                <template x-for="item in $store.cartPending.items" :key="item.id">
                    <div class="mn-cart-item" style="opacity:0.7;">
                        <a :href="'/products/' + item.slug">
                            <img :src="item.image" :alt="item.name" x-show="item.image" style="width:72px;height:72px;object-fit:contain;padding:10px;border-radius:8px;">
                            <div x-show="!item.image" style="width:72px;height:72px;background:#F8FAFB;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#9CA3AF;">
                                <i class="fa-solid fa-image"></i>
                            </div>
                        </a>
                        <div class="min-w-0">
                            <a :href="'/products/' + item.slug" class="text-decoration-none text-reset">
                                <p class="t text-truncate" x-text="item.name"></p>
                            </a>
                            <div class="q" x-text="'Qty ' + item.quantity + ' x $' + Number(item.price).toFixed(2)"></div>
                            <div class="p mt-1" x-text="'$' + Number(item.line).toFixed(2)"></div>
                        </div>
                        <span class="badge" style="background:#e8f5e9;color:#2e7d32;font-size:10px;">Adding...</span>
                    </div>
                </template>
            </div>

        </div>
    </div>
</div>
