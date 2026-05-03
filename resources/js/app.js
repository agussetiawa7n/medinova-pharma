import './bootstrap';
import Alpine from 'alpinejs';
window.Alpine = Alpine;
import intersect from '@alpinejs/intersect';
import focus from '@alpinejs/focus';
import AOS from 'aos';
import 'aos/dist/aos.css';
window.AOS = AOS;

// Bootstrap 5 — vanilla JS (no jQuery needed)
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Swiper (vanilla) for sliders
import Swiper from 'swiper/bundle';
window.Swiper = Swiper;

// Glowify template header/menu helpers (dropdowns, sticky, mobile menu, data-src bg)
import { initGlowifyHeader } from './glowify-header.js';

Alpine.plugin(intersect);
Alpine.plugin(focus);

Alpine.store('cart', {
    count: 0,
    init() {
        fetch('/ajax/cart/count')
            .then(r => r.json())
            .then(data => { this.count = data.count ?? 0; })
            .catch(() => {});
    },
});

Alpine.store('wishlist', {
    count: 0,
    init() {
        fetch('/ajax/wishlist/count')
            .then(r => r.json())
            .then(data => { this.count = data.count ?? 0; })
            .catch(() => {});
    },
});

Alpine.store('toast', {
    items: [],
    add(message, type = 'success', duration = 3500) {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), duration);
    },
    remove(id) {
        this.items = this.items.filter(i => i.id !== id);
    },
});

// Optimistic cart items — shown instantly in offcanvas before server confirms
Alpine.store('cartPending', {
    items: [],
});

// Keep Alpine cart count in sync whenever any component signals a cart change
window.addEventListener('cart-updated', () => {
    fetch('/ajax/cart/count')
        .then(r => r.json())
        .then(data => { Alpine.store('cart').count = data.count ?? 0; })
        .catch(() => {});
});

// Keep Alpine wishlist count in sync
window.addEventListener('wishlist-updated', () => {
    fetch('/ajax/wishlist/count')
        .then(r => r.json())
        .then(data => { Alpine.store('wishlist').count = data.count ?? 0; })
        .catch(() => {});
});

// Open the Bootstrap cart offcanvas programmatically
window.openCartOffcanvas = function () {
    const el = document.getElementById('mnCartCanvas');
    if (!el) return;
    const bs = window.bootstrap;
    const instance = bs.Offcanvas.getInstance(el) || new bs.Offcanvas(el);
    instance.show();
};

// CSRF token — must be set before Alpine starts so fetch helpers can use it
const token = document.querySelector('meta[name="csrf-token"]');
if (token) {
    window.csrfToken = token.getAttribute('content');
}

// Helper used by cart page and product card to call JSON endpoints
window.apiFetch = function (url, options = {}) {
    const opts = {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {}),
        },
        ...options,
    };
    if (opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData)) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(opts.body);
    }
    return fetch(url, opts).then(async r => {
        const text = await r.text();
        let data = null;
        try { data = text ? JSON.parse(text) : null; } catch { /* keep null */ }
        if (!r.ok) {
            const msg = (data && (data.message || data.error)) || `Request failed (${r.status})`;
            throw new Error(msg);
        }
        return data;
    });
};

Alpine.data('productGrid', () => ({
    page: 1,
    hasMore: true,
    loading: false,
    init() {
        window.currentFilters = window.currentFilters || {};
        this.load(true);
    },
    load(reset = false) {
        if (this.loading) return;
        this.loading = true;
        const params = new URLSearchParams({
            ...(window.currentFilters || {}),
            page: this.page,
        });
        fetch('/ajax/products?' + params)
            .then(r => r.json())
            .then(data => {
                const grid = document.getElementById('product-grid');
                if (grid) {
                    if (reset) {
                        grid.innerHTML = data.html;
                        Alpine.initTree(grid);
                    } else {
                        const tmp = document.createElement('div');
                        tmp.innerHTML = data.html;
                        const children = [...tmp.children];
                        children.forEach(c => grid.appendChild(c));
                        children.forEach(c => Alpine.initTree(c));
                    }
                }
                this.hasMore = data.hasMore;
                this.page = data.nextPage;
                this.loading = false;
            })
            .catch(() => {
                this.loading = false;
                if (reset) {
                    const grid = document.getElementById('product-grid');
                    if (grid) grid.innerHTML = '<div class="text-center py-5"><p class="text-muted">Could not load products. Please try again.</p><button class="btn btn-outline-secondary mt-2" onclick="location.reload()">Reload page</button></div>';
                }
                Alpine.store('toast').add('Could not load products', 'error');
            });
    },
}));

// Product card add-to-cart (used by _card partial)
Alpine.data('productCard', (productId, productName, productPrice, productImage, productSlug) => ({
    loading: false,
    added: false,
    addToCart() {
        if (this.loading || this.added) return;

        const prevCount = Alpine.store('cart').count;
        const pendingId = 'p-' + Date.now();
        Alpine.store('cart').count = prevCount + 1;
        this.loading = true;

        Alpine.store('cartPending').items.push({
            id: pendingId, name: productName, price: productPrice,
            image: productImage, slug: productSlug, quantity: 1,
            line: productPrice,
        });

        window.openCartOffcanvas();

        window.apiFetch('/ajax/cart/add', {
            method: 'POST',
            body: { product_id: productId, quantity: 1 },
        })
            .then(data => {
                Alpine.store('cart').count = data.count;
                // Pending cleared by load() when confirmed data arrives — seamless transition
                window.dispatchEvent(new CustomEvent('cart-updated'));
                this.loading = false;
                this.added = true;
                setTimeout(() => { this.added = false; }, 2000);
            })
            .catch(err => {
                Alpine.store('cart').count = prevCount;
                Alpine.store('cartPending').items = Alpine.store('cartPending').items.filter(i => i.id !== pendingId);
                Alpine.store('toast').add(err.message || 'Something went wrong', 'error');
                this.loading = false;
            });
    },
    toggleWishlist(e) {
        e.preventDefault();
        const btn = e.currentTarget; // Capture before async — currentTarget becomes null in .then()
        window.apiFetch('/ajax/wishlist/toggle', {
            method: 'POST',
            body: { product_id: productId },
        })
            .then(data => {
                btn.classList.toggle('active', data.in_wishlist);
                window.dispatchEvent(new CustomEvent('wishlist-updated'));
                Alpine.store('toast').add(data.message || 'Wishlist updated', 'success');
            })
            .catch(err => Alpine.store('toast').add(err.message || 'Please sign in', 'error'));
    },
}));

// Cart offcanvas — pure Alpine + AJAX (replaces Livewire CartDropdown)
Alpine.data('cartOffcanvas', () => ({
    items: [],
    count: 0,
    subtotal: 0,
    loading: true,
    removing: {},
    _loadId: 0,
    _lastLoadAt: 0,
    _initDone: false,

    init() {
        this.load();
        window.addEventListener('cart-updated', () => this.load());
        document.addEventListener('show.bs.offcanvas', (e) => {
            // Only auto-load on manual open (header btn). Skip when product-add
            // is in progress — cart-updated from the AJAX response handles that.
            if (e.target?.id === 'mnCartCanvas' && !Alpine.store('cartPending').items.length) {
                this.load();
            }
        });
    },

    async load() {
        // Debounce: skip if data was loaded less than 3 seconds ago
        if (Date.now() - this._lastLoadAt < 3000 && this.items.length >= 0 && this._loadId > 0) return;
        const id = ++this._loadId;
        try {
            const data = await window.apiFetch('/ajax/cart/data');
            if (id === this._loadId && data) {
                this.items = data.items || [];
                this.count = data.count || 0;
                this.subtotal = data.subtotal || 0;
                Alpine.store('cartPending').items = [];
                // Keep header badge in sync
                Alpine.store('cart').count = data.count || 0;
                this._lastLoadAt = Date.now();
            }
        } catch (e) {
            // silent
        } finally {
            if (id === this._loadId) this.loading = false;
        }
    },

    async removeItem(itemId) {
        if (this.removing[itemId]) return;
        // Create new object ref — Alpine tracks assignment, not property mutation
        this.removing = {...this.removing, [itemId]: true};

        // Optimistic: remove from local state immediately
        const removed = this.items.find(i => i.id === itemId);
        this.items = this.items.filter(i => i.id !== itemId);
        this.count = this.items.reduce((s, i) => s + i.quantity, 0);
        this.subtotal = this.items.reduce((s, i) => s + (Number(i.line) || 0), 0);

        try {
            await window.apiFetch(`/ajax/cart/item/${itemId}`, { method: 'DELETE' });
            window.dispatchEvent(new CustomEvent('cart-updated'));
        } catch (e) {
            // If server says item doesn't exist, it's already gone — accept it
            if (!(e.message && e.message.includes('No query results'))) {
                if (removed) {
                    this.items.push(removed);
                    this.items.sort((a, b) => a.id - b.id);
                    this.count = this.items.reduce((s, i) => s + i.quantity, 0);
                    this.subtotal = this.items.reduce((s, i) => s + (Number(i.line) || 0), 0);
                }
            }
            Alpine.store('toast').add(e.message || 'Could not remove item', 'error');
        } finally {
            const next = {...this.removing};
            delete next[itemId];
            this.removing = next;
        }
    },
}));

// Shop sidebar filters — registered here to avoid alpine:init race
Alpine.data('shopFilters', () => ({
    filters: { ...(window.currentFilters || {}) },
    apply() {
        const cleaned = Object.fromEntries(
            Object.entries(this.filters).filter(([k, v]) => v !== '' && v !== false && v !== null && v !== undefined)
        );
        if (cleaned.in_stock) cleaned.in_stock = 1; else delete cleaned.in_stock;
        if (cleaned.on_sale)  cleaned.on_sale  = 1; else delete cleaned.on_sale;
        window.currentFilters = cleaned;
        window.dispatchEvent(new CustomEvent('filters-updated', { detail: cleaned }));
    },
    reset() {
        this.filters = { q:'', category:'', brand:'', minPrice:'', maxPrice:'', sort:'newest', in_stock:false, on_sale:false };
        this.apply();
    },
}));

// Newsletter form — pure Alpine + AJAX (replaces Livewire NewsletterForm)
Alpine.data('newsletterForm', () => ({
    email: '',
    submitted: false,
    loading: false,
    error: '',

    async subscribe() {
        this.error = '';
        this.loading = true;
        try {
            await window.apiFetch('/ajax/newsletter/subscribe', {
                method: 'POST',
                body: { email: this.email },
            });
            this.submitted = true;
            this.email = '';
        } catch (e) {
            this.error = e.message || 'Something went wrong.';
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.start();

// Initialise AOS. `startEvent: 'load'` defers until all images finish loading,
// which gives Swiper and Livewire time to settle — otherwise AOS computes
// offsets against an old document height and sections stay hidden on fast scroll.
AOS.init({
    once: true,
    duration: 600,
    easing: 'ease-out-cubic',
    offset: 40,
    startEvent: 'load',
    disableMutationObserver: false,
});

// After load + small delay (Swiper slides, lazy images) recompute positions so
// no "blank screen on scroll" gotcha remains.
window.addEventListener('load', () => {
    setTimeout(() => AOS.refreshHard(), 300);
    setTimeout(() => AOS.refreshHard(), 1200);
    setTimeout(() => document.body.classList.add('mn-aos-fallback'), 2500);
});

let __mnAosResizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(__mnAosResizeTimer);
    __mnAosResizeTimer = setTimeout(() => AOS.refreshHard(), 250);
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => requestAnimationFrame(initGlowifyHeader));
} else {
    requestAnimationFrame(initGlowifyHeader);
}

// Register Service Worker for offline-capable instant reloads
if ('serviceWorker' in navigator && location.protocol === 'https:') {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
}

// Speculation Rules — prerender next page on hover for SPA-like feel
if (HTMLScriptElement.supports?.('speculationrules')) {
    const specScript = document.createElement('script');
    specScript.type = 'speculationrules';
    specScript.textContent = JSON.stringify({
        prerender: [{ source: 'document', where: { href_matches: '/*' }, eagerness: 'moderate' }]
    });
    document.head.appendChild(specScript);
}
