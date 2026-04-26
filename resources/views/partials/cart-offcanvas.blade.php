{{-- Bootstrap offcanvas that hosts the Livewire cart component --}}
<div class="offcanvas offcanvas-end mn-cart-offcanvas" tabindex="-1" id="mnCartCanvas"
     aria-labelledby="mnCartCanvasLabel">
    <div class="offcanvas-header border-bottom" style="padding:18px 22px;">
        <h5 class="offcanvas-title m-0 d-flex align-items-center gap-2" id="mnCartCanvasLabel" style="font-weight:800; font-size:18px;">
            <i class="fa-solid fa-bag-shopping" style="color:#e61f7f;"></i>
            Your Cart
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0" style="display:flex; flex-direction:column;">
        <livewire:cart-dropdown />
    </div>
</div>
