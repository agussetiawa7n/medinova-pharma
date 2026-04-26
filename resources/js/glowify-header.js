// Glowify header/menu behaviours — extracted from assets/js/main.js
// Requires jQuery (window.$ / window.jQuery). Only header-related pieces kept.

export function initGlowifyHeader($) {
    // Prevent double-init
    if (window.__mnGlowifyHeaderInit) return;
    window.__mnGlowifyHeaderInit = true;

    // ── Main Nav (hamburger + nested caret toggles) ──
    $('.cs_site_header').each(function () {
        if (!$(this).find('> .cs_menu_toggle').length) {
            $(this).append('<span class="cs_menu_toggle"><span></span></span>');
        }
    });
    $('.menu-item-has-children').each(function () {
        if (!$(this).find('> .cs_munu_dropdown_toggle').length) {
            $(this).append('<span class="cs_munu_dropdown_toggle"><span></span></span>');
        }
    });

    $(document).on('click', '.cs_menu_toggle', function () {
        $(this)
            .toggleClass('cs_toggle_active')
            .parents('.cs_site_header')
            .toggleClass('cs_mobile_active');
    });
    $(document).on('click', '.cs_header_overlay_mobile, .cs_close_mobile_active', function () {
        $('.cs_site_header').removeClass('cs_mobile_active');
        $('.cs_menu_toggle').removeClass('cs_toggle_active');
    });
    $(document).on('click', '.cs_munu_dropdown_toggle', function () {
        $(this).toggleClass('active').siblings('ul').slideToggle();
        $(this).parent().toggleClass('active');
    });
    $(document).on('click', '.cs_mobile_tab_btn', function () {
        $(this).parent().addClass('cs_mobile_active').siblings().removeClass('cs_mobile_active');
    });

    // ── Mobile search toggle ──
    $(document).on('click', '.cs_mobile_search_toggle', function () {
        $('.cs_header_search_form_wrap').toggleClass('active');
    });

    // ── Sticky header ──
    (function stickyHeader() {
        const $header = $('.cs_sticky_header');
        if (!$header.length) return;
        const headerHeight = $header.outerHeight() + 30;
        $(window).on('scroll', function () {
            if ($(window).scrollTop() >= headerHeight) {
                $header.addClass('cs_sticky_active');
            } else {
                $header.removeClass('cs_sticky_active');
            }
        });
    })();

    // ── Dynamic background (data-src → background-image) ──
    $('[data-src]').each(function () {
        const src = $(this).attr('data-src');
        if (src) $(this).css('background-image', 'url(' + src + ')');
    });

    // ── Custom dropdown (click-based) + mobile tab-switch behaviour ──
    // Matches Glowify main.js customDropdown() exactly: toggles .active on the
    // dropdown content AND makes its wrapping parent .cs_mobile_active so the
    // "All Categories" tab becomes selectable inside the mobile menu drawer.
    $(document).on('click', '.cs_dropdown_btn', function (event) {
        const $dropdown = $(this).siblings('.cs_dropdown_content');
        $('.cs_dropdown_content').not($dropdown).removeClass('active');
        $('.cs_dropdown_content').not($dropdown).siblings().removeClass('active');
        $dropdown.toggleClass('active');
        $dropdown.siblings().toggleClass('active');
        $(this)
            .parent()
            .addClass('cs_mobile_active')
            .siblings()
            .removeClass('cs_mobile_active');
        event.stopPropagation();
    });
    $(document).on('click', function (event) {
        if (!$(event.target).closest('.cs_dropdown').length) {
            $('.cs_dropdown_content').removeClass('active');
            $('.cs_dropdown_content').siblings().removeClass('active');
        }
    });
    // Any click inside an open dropdown keeps it marked active (mirrors Glowify)
    $(document).on('click', '.cs_dropdown_content', function () {
        $(this).addClass('active').siblings('.cs_dropdown_btn').addClass('active');
    });

    // ── Cart card sliding panel (when present — we use Bootstrap offcanvas by default) ──
    $(document).on('click', '.cs_cart_card_trigger', function () {
        $('.cs_cart_card_wrap').toggleClass('active');
    });
    $(document).on('click', '.cs_cart_overlay, .cs_cart_close', function () {
        $('.cs_cart_card_wrap').removeClass('active');
    });

    // ── Accordion (cs_accordian / cs_accordian_head / cs_accordian_body) ──
    $('.cs_accordian').children('.cs_accordian_body').hide();
    $('.cs_accordian.active').children('.cs_accordian_body').show();
    $(document).on('click', '.cs_accordian_head', function () {
        $(this)
            .parent('.cs_accordian')
            .siblings()
            .children('.cs_accordian_body')
            .slideUp(250);
        $(this).siblings().slideDown(250);
        $(this)
            .parent()
            .parent()
            .siblings()
            .find('.cs_accordian_body')
            .slideUp(250);
        $(this).parents('.cs_accordian').addClass('active');
        $(this).parent('.cs_accordian').siblings().removeClass('active');
    });
}
