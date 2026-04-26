<div class="position-relative w-100" x-data @click.outside="$wire.close()">
    <div class="position-relative">
        <i class="fa-solid fa-magnifying-glass position-absolute"
           style="left:16px; top:50%; transform:translateY(-50%); color:#9CA3AF; font-size:14px; pointer-events:none;"></i>
        <input
            type="text"
            wire:model.live.debounce.300ms="query"
            placeholder="Search medicines, vitamins, health products…"
            autocomplete="off"
            class="form-control"
            style="padding:12px 44px; height:48px; border-radius:999px; border:1.5px solid #E5E7EB; background:#F8FAFB; font-size:14px; box-shadow:none;"
            onfocus="this.style.background='#fff'; this.style.borderColor='#e61f7f';"
            onblur="this.style.background='#F8FAFB'; this.style.borderColor='#E5E7EB';"
        />
        @if($query)
            <button type="button" wire:click="close"
                    class="btn p-0 border-0 bg-transparent position-absolute"
                    style="right:14px; top:50%; transform:translateY(-50%); color:#9CA3AF;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        @endif
    </div>

    @if($open && count($results))
        <div class="position-absolute top-100 start-0 end-0 mt-2 shadow bg-white overflow-hidden"
             style="border-radius:12px; border:1px solid #EDEFF2; z-index:1050;">
            @foreach($results as $result)
                <a href="{{ route('products.show', $result['slug']) }}"
                   class="d-flex align-items-center gap-3 px-3 py-2 text-decoration-none text-reset"
                   style="transition:background .15s;"
                   onmouseover="this.style.background='#faeff2'"
                   onmouseout="this.style.background='#fff'">
                    @if($result['image'])
                        <img src="{{ $result['image'] }}" alt="{{ $result['name'] }}"
                             style="width:44px; height:44px; object-fit:contain; padding:4px; background:#F8FAFB; border-radius:8px; flex-shrink:0;">
                    @else
                        <div style="width:44px; height:44px; background:#F8FAFB; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#9CA3AF; flex-shrink:0;">
                            <i class="fa-solid fa-pills"></i>
                        </div>
                    @endif
                    <div class="flex-grow-1 min-w-0">
                        <p class="m-0 text-truncate" style="font-size:14px; font-weight:600; color:#303030;">{{ $result['name'] }}</p>
                        <p class="m-0" style="font-size:13px; color:#e61f7f; font-weight:700;">₹{{ $result['price'] }}</p>
                    </div>
                </a>
            @endforeach

            <div class="border-top px-3 py-2" style="background:#F8FAFB;">
                <a href="{{ route('products.index', ['search' => $query]) }}"
                   style="font-size:13px; color:#e61f7f; font-weight:700; text-decoration:none;">
                    See all results for "{{ $query }}" <i class="fa-solid fa-arrow-right ms-1" style="font-size:11px;"></i>
                </a>
            </div>
        </div>
    @elseif($query && strlen($query) >= 2)
        <div class="position-absolute top-100 start-0 end-0 mt-2 shadow bg-white p-4 text-center"
             style="border-radius:12px; border:1px solid #EDEFF2; z-index:1050;">
            <i class="fa-regular fa-face-frown" style="font-size:28px; color:#9CA3AF; margin-bottom:8px;"></i>
            <p class="m-0 text-muted" style="font-size:14px;">No products found for "{{ $query }}"</p>
        </div>
    @endif
</div>
