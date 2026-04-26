{{-- Glowify-style breadcrumb banner with bg image. Vars: $bcTitle, $bcSubtitle?, $bcCrumbs? (array of ['label','url'?]), $bcBg? --}}
@php
    $bcBgUrl = $bcBg ?? asset('assets/glowify/images/breadcamp_bg_1.jpeg');
@endphp
<div class="cs_height_40 cs_height_lg_30"></div>
<div class="container">
    <div class="cs_breadcamp_wrap cs_style_1 cs_accent_light_bg cs_bg_filed cs_radius_8" data-src="{{ $bcBgUrl }}">
        <div>
            <h1 class="cs_breadcamp_title cs_fs_54 cs_semibold">{{ $bcTitle }}</h1>
            @if(!empty($bcSubtitle))
                <p class="mb-0 cs_fs_18">{{ $bcSubtitle }}</p>
            @endif
            @if(!empty($bcCrumbs))
                <ol class="breadcrumb cs_fs_18 mb-0">
                    @foreach($bcCrumbs as $crumb)
                        @if(!empty($crumb['url']) && !($loop->last))
                            <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
                        @else
                            <li class="breadcrumb-item active">{{ $crumb['label'] }}</li>
                        @endif
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</div>
