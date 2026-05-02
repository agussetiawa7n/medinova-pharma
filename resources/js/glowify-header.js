// Glowify header/menu behaviours — vanilla JS rewrite (zero dependencies)
// Replaces the jQuery-based original (~120 lines of jQuery → ~90 lines of vanilla JS)

export function initGlowifyHeader() {
    if (window.__mnGlowifyHeaderInit) return;
    window.__mnGlowifyHeaderInit = true;

    const doc = document;

    // ── Inject mobile toggle buttons ──
    doc.querySelectorAll('.cs_site_header').forEach(el => {
        if (!el.querySelector(':scope > .cs_menu_toggle')) {
            el.insertAdjacentHTML('beforeend', '<span class="cs_menu_toggle"><span></span></span>');
        }
    });
    doc.querySelectorAll('.menu-item-has-children').forEach(el => {
        if (!el.querySelector(':scope > .cs_munu_dropdown_toggle')) {
            el.insertAdjacentHTML('beforeend', '<span class="cs_munu_dropdown_toggle"><span></span></span>');
        }
    });

    // ── Delegated click handlers ──
    doc.addEventListener('click', function (e) {
        const target = e.target;

        // Hamburger menu toggle
        const menuToggle = target.closest('.cs_menu_toggle');
        if (menuToggle) {
            menuToggle.classList.toggle('cs_toggle_active');
            menuToggle.closest('.cs_site_header')?.classList.toggle('cs_mobile_active');
            return;
        }

        // Mobile overlay / close button
        if (target.closest('.cs_header_overlay_mobile') || target.closest('.cs_close_mobile_active')) {
            doc.querySelectorAll('.cs_site_header').forEach(el => el.classList.remove('cs_mobile_active'));
            doc.querySelectorAll('.cs_menu_toggle').forEach(el => el.classList.remove('cs_toggle_active'));
            return;
        }

        // Nested dropdown toggle (mobile)
        const ddToggle = target.closest('.cs_munu_dropdown_toggle');
        if (ddToggle) {
            ddToggle.classList.toggle('active');
            const ul = ddToggle.parentElement?.querySelector(':scope > ul');
            if (ul) ul.style.display = ul.style.display === 'block' ? '' : 'block';
            ddToggle.parentElement?.classList.toggle('active');
            return;
        }

        // Mobile tab switch
        const tabBtn = target.closest('.cs_mobile_tab_btn');
        if (tabBtn) {
            const parent = tabBtn.parentElement;
            parent.classList.add('cs_mobile_active');
            [...parent.parentElement.children].filter(c => c !== parent).forEach(c => c.classList.remove('cs_mobile_active'));
            return;
        }

        // Mobile search toggle
        if (target.closest('.cs_mobile_search_toggle')) {
            doc.querySelector('.cs_header_search_form_wrap')?.classList.toggle('active');
            return;
        }

        // Custom dropdown toggle
        const ddBtn = target.closest('.cs_dropdown_btn');
        if (ddBtn) {
            const content = ddBtn.parentElement?.querySelector(':scope > .cs_dropdown_content');
            doc.querySelectorAll('.cs_dropdown_content').forEach(el => {
                if (el !== content) { el.classList.remove('active'); el.previousElementSibling?.classList.remove('active'); }
            });
            content?.classList.toggle('active');
            ddBtn.classList.toggle('active');
            ddBtn.parentElement?.classList.add('cs_mobile_active');
            return;
        }

        // Close dropdowns when clicking outside
        if (!target.closest('.cs_dropdown')) {
            doc.querySelectorAll('.cs_dropdown_content').forEach(el => {
                el.classList.remove('active');
                el.previousElementSibling?.classList.remove('active');
            });
        }

        // FAQ accordion
        const accHead = target.closest('.cs_accordian_head');
        if (accHead) {
            const accordian = accHead.closest('.cs_accordian');
            const parent = accordian?.parentElement;
            if (parent) {
                parent.querySelectorAll(':scope > .cs_accordian').forEach(sib => {
                    if (sib !== accordian) {
                        sib.querySelector('.cs_accordian_body')?.style.setProperty('display', 'none');
                        sib.classList.remove('active');
                    }
                });
            }
            const body = accHead.nextElementSibling;
            if (body) body.style.display = body.style.display === 'none' ? 'block' : 'none';
            accordian?.classList.add('active');
        }
    });

    // ── Sticky header ──
    const stickyHeader = doc.querySelector('.cs_sticky_header');
    if (stickyHeader) {
        const offset = stickyHeader.offsetHeight + 30;
        window.addEventListener('scroll', () => {
            stickyHeader.classList.toggle('cs_sticky_active', window.scrollY >= offset);
        }, { passive: true });
    }

    // ── Lazy background images [data-src] ──
    doc.querySelectorAll('[data-src]').forEach(el => {
        const src = el.getAttribute('data-src');
        if (src) el.style.backgroundImage = `url(${src})`;
    });

    // ── FAQ accordion init (hide all except .active) ──
    doc.querySelectorAll('.cs_accordian').forEach(acc => {
        const body = acc.querySelector(':scope > .cs_accordian_body');
        if (body && !acc.classList.contains('active')) body.style.display = 'none';
    });
}
