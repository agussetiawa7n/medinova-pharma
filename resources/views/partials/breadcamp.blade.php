{{-- Glowify-style breadcrumb banner with bg image. Vars: $bcTitle, $bcSubtitle?, $bcCrumbs? (array of ['label','url'?]), $bcBg? --}}
@php
    $bcBgUrl = $bcBg ?? asset('assets/glowify/images/breadcamp_bg_1.jpeg');
@endphp

<style>
    .cs_breadcamp_wrap_professional {
        position: relative;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .cs_breadcamp_wrap_professional::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.4) 100%);
        border-radius: 8px;
        z-index: 1;
    }
    
    .cs_breadcamp_content {
        position: relative;
        z-index: 2;
        padding: 60px 40px;
    }
    
    .cs_breadcamp_title {
        color: #ffc1eb;
        margin-bottom: 15px;
        font-weight: 600;
        line-height: 1.2;
    }
    
    .cs_breadcamp_subtitle {
        color: rgba(255,255,255,0.9);
        margin-bottom: 20px;
        font-size: 18px;
    }
    
    .cs_custom_breadcrumb {
        display: flex;
        flex-wrap: wrap;
        padding: 0;
        margin: 0;
        list-style: none;
        background: transparent;
    }
    
    .cs_custom_breadcrumb li {
        color: rgba(255,255,255,0.85);
        font-size: 18px;
    }
    
    .cs_custom_breadcrumb li a {
        color: rgba(255,255,255,0.85);
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .cs_custom_breadcrumb li a:hover {
        color: #ffffff;
        text-decoration: underline;
    }
    
    .cs_custom_breadcrumb .active {
        color: #ffffff;
        font-weight: 500;
    }
    
    .cs_breadcrumb_separator {
        color: rgba(255,255,255,0.6);
        margin: 0 10px;
        font-size: 18px;
    }
    
    @media (max-width: 768px) {
        .cs_breadcamp_content {
            padding: 40px 25px;
        }
        .cs_breadcamp_title {
            font-size: 32px !important;
        }
        .cs_custom_breadcrumb li,
        .cs_breadcrumb_separator {
            font-size: 14px;
        }
    }
</style>

<div class="cs_height_40 cs_height_lg_30"></div>
<div class="container">
    <div class="cs_breadcamp_wrap_professional cs_radius_8" style="background-image: url('{{ $bcBgUrl }}');">
        <div class="cs_breadcamp_content">
            <h1 class="cs_breadcamp_title cs_fs_54 cs_semibold">{{ $bcTitle }}</h1>
            
            @if(!empty($bcSubtitle))
                <p class="cs_breadcamp_subtitle">{{ $bcSubtitle }}</p>
            @endif
            
            @if(!empty($bcCrumbs))
                <ul class="cs_custom_breadcrumb">
                    @foreach($bcCrumbs as $crumb)
                        @if(!empty($crumb['url']) && !($loop->last))
                            <li><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
                            <li class="cs_breadcrumb_separator">/</li>
                        @else
                            <li class="active">{{ $crumb['label'] }}</li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>