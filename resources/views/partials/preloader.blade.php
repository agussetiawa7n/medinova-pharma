<div id="medinova-preloader">
    <div class="preloader-content">
        <svg class="med-svg" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
            {{-- Document / prescription --}}
            <g class="doc">
                <rect x="35" y="20" width="50" height="60" rx="5" fill="#E2E8F0"/>
                <rect x="55" y="35" width="10" height="25" fill="#E53E3E" rx="2"/>
                <rect x="47.5" y="42.5" width="25" height="10" fill="#E53E3E" rx="2"/>
                <rect x="45" y="70" width="30" height="4" fill="#CBD5E0" rx="2"/>
            </g>
            {{-- Shield / bag body --}}
            <path d="M 20 50 L 50 50 L 60 60 L 100 60 C 105 60 105 65 105 65 L 105 100 C 105 105 100 105 100 105 L 20 105 C 15 105 15 100 15 100 L 15 55 C 15 50 20 50 20 50 Z" fill="#FF8BA7"/>
            <path d="M 15 65 L 105 65 L 100 105 L 20 105 Z" fill="#FF6584"/>
            {{-- Cross on bag --}}
            <path d="M 50 75 L 70 75 L 70 90 C 70 100 60 105 60 105 C 60 105 50 100 50 90 Z" fill="#F687B3" stroke="#FFF" stroke-width="2"/>
            <path d="M 58 82 H 62 V 92 H 58 Z" fill="#FFF"/>
            <path d="M 55 85 H 65 V 89 H 55 Z" fill="#FFF"/>
            {{-- Floating pills --}}
            <path class="pill pill-1" d="M 27 37 L 33 37 Q 36 37 36 40 Q 36 43 33 43 L 27 43 Q 24 43 24 40 Q 24 37 27 37" fill="#F6E05E"/>
            <path class="pill pill-2" d="M 80 26 L 90 26 Q 94 26 94 30 Q 94 34 90 34 L 80 34 Q 76 34 76 30 Q 76 26 80 26" fill="#63B3ED"/>
            <path class="pill pill-3" d="M 67 17 L 77 17 Q 81 17 81 20 Q 81 23 77 23 L 67 23 Q 63 23 63 20 Q 63 17 67 17" fill="#48BB78"/>
        </svg>
        <div class="brand-text">MediNova<span class="dot">.</span></div>
    </div>
</div>

<script>
(function(){
    var el = document.getElementById('medinova-preloader');
    if (!el) return;
    var done = function(){ el.style.opacity='0'; el.style.visibility='hidden'; };
    window.addEventListener('load', function(){ setTimeout(done, 600); });
    // Failsafe: hide after 5s even if load event never fires
    setTimeout(done, 5000);
})();
</script>
