import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.csp.esm.js';
import intersect from '@alpinejs/intersect';
import focus from '@alpinejs/focus';
import AOS from 'aos';
import 'aos/dist/aos.css';
window.AOS = AOS;

// jQuery — needed by Glowify's header/menu/dropdown behaviours
import $ from 'jquery';
window.$ = window.jQuery = $;

// Bootstrap 5 — works alongside jQuery, registers data-bs-* behaviours globally
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
                    if (reset) grid.innerHTML = data.html;
                    else grid.insertAdjacentHTML('beforeend', data.html);
                }
                this.hasMore = data.hasMore;
                this.page = data.nextPage;
                this.loading = false;
            })
            .catch(() => {
                this.loading = false;
                Alpine.store('toast').add('Could not load products', 'error');
            });
    },
}));

// Product card add-to-cart (used by _card partial)
Alpine.data('productCard', (productId) => ({
    loading: false,
    addToCart() {
        if (this.loading) return;
        this.loading = true;
        window.apiFetch('/ajax/cart/add', {
            method: 'POST',
            body: { product_id: productId, quantity: 1 },
        })
            .then(data => {
                Alpine.store('cart').count = data.count;
                Alpine.store('toast').add('Added to cart!', 'success');
                window.dispatchEvent(new CustomEvent('cart-updated'));
            })
            .catch(err => Alpine.store('toast').add(err.message || 'Something went wrong', 'error'))
            .finally(() => { this.loading = false; });
    },
    toggleWishlist(e) {
        e.preventDefault();
        window.apiFetch('/ajax/wishlist/toggle', {
            method: 'POST',
            body: { product_id: productId },
        })
            .then(data => {
                const btn = e.currentTarget;
                btn.classList.toggle('active', data.in_wishlist);
                Alpine.store('toast').add(data.message || 'Wishlist updated', 'success');
            })
            .catch(err => Alpine.store('toast').add(err.message || 'Please sign in', 'error'));
    },
}));

Livewire.start();

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
    // Fallback: if AOS still hasn't animated a data-aos element 2.5s after
    // load, mark body so CSS can force them visible. Prevents "blank screen
    // on scroll" in rare cases where AOS fails to fire at all.
    setTimeout(() => document.body.classList.add('mn-aos-fallback'), 2500);
});

// Also recompute whenever the window is resized (sticky header height changes
// the scroll offsets too).
let __mnAosResizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(__mnAosResizeTimer);
    __mnAosResizeTimer = setTimeout(() => AOS.refreshHard(), 250);
});

// Initialise Glowify header/menu/dropdown behaviours (jQuery-based, template-verbatim)
$(function () {
    initGlowifyHeader($);
});

