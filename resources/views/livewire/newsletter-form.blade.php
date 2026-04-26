<div>
    @if($submitted)
        <div class="d-flex align-items-center gap-2" style="color:#b81964; font-size:14px; font-weight:600;">
            <i class="fa-solid fa-circle-check"></i>
            <span>You're subscribed! Thank you.</span>
        </div>
    @else
        <form wire:submit.prevent="subscribe" class="d-flex gap-2">
            <input type="email"
                   wire:model="email"
                   placeholder="your@email.com"
                   required
                   class="form-control flex-grow-1"
                   style="background:#1F2937; border:1px solid #374151; color:#fff; font-size:14px; border-radius:8px;">
            <button type="submit" wire:loading.attr="disabled"
                    class="btn btn-pharma" style="white-space:nowrap; padding:8px 18px;">
                <span wire:loading.remove>Subscribe</span>
                <span wire:loading>...</span>
            </button>
        </form>
        @error('email')
            <p class="mt-2 mb-0" style="color:#FCA5A5; font-size:12px;">{{ $message }}</p>
        @enderror
    @endif
</div>
