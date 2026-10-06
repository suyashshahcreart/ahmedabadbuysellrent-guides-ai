<?php

use App\Support\ThemeModeCssOverrides;

/**
 * Default theme CSS variables mirroring resources/scss/config/_theme-mode.scss.
 * Option keys: theme_* → values stored in options table.
 *
 * @see ThemeModeCssOverrides
 */
$selectorMenuBsDark = 'html[data-bs-theme="dark"][data-menu-color="dark"], html[data-bs-theme="dark"][data-menu-color="light"]';
$selectorTopbarBsDark = 'html[data-bs-theme="dark"][data-topbar-color="dark"], html[data-bs-theme="dark"][data-topbar-color="light"]';

return [
    'meta' => [
        // :root (layout; logo heights use existing logo_lg_height / logo_sm_height options)
        'theme_root_main_nav_width' => [':root', '--bs-main-nav-width', '260px'],
        'theme_root_main_nav_width_sm' => [':root', '--bs-main-nav-width-sm', '75px'],
        'theme_root_main_nav_item_icon_size' => [':root', '--bs-main-nav-item-icon-size', '18px'],
        'theme_root_main_nav_item_font_size' => [':root', '--bs-main-nav-item-font-size', '15px'],
        'theme_root_main_nav_item_padding_x' => [':root', '--bs-main-nav-item-padding-x', '15px'],
        'theme_root_main_nav_item_padding_y' => [':root', '--bs-main-nav-item-padding-y', '10px'],
        'theme_root_main_nav_item_margin_y' => [':root', '--bs-main-nav-item-margin-y', '2px'],
        'theme_root_topbar_height' => [':root', '--bs-topbar-height', '70px'],
        'theme_root_footer_height' => [':root', '--bs-footer-height', '60px'],
        'theme_root_input_border_color' => [':root', '--bs-input-border-color', '#d8dfe7'],
        'theme_root_input_focus_border_color' => [':root', '--bs-input-focus-border-color', '#b0b0bb'],

        /* html[data-menu-color="light"] */
        'theme_menu_light_main_nav_bg' => ['html[data-menu-color="light"]', '--bs-main-nav-bg', '#eeeef3'],
        'theme_menu_light_main_nav_item_color' => ['html[data-menu-color="light"]', '--bs-main-nav-item-color', '#696b84'],
        'theme_menu_light_main_nav_item_hover_bg' => ['html[data-menu-color="light"]', '--bs-main-nav-item-hover-bg', '#f8f9fa'],
        'theme_menu_light_main_nav_item_hover_color' => ['html[data-menu-color="light"]', '--bs-main-nav-item-hover-color', '#604ae3'],
        'theme_menu_light_main_nav_item_active_color' => ['html[data-menu-color="light"]', '--bs-main-nav-item-active-color', '#604ae3'],
        'theme_menu_light_main_nav_border_color' => ['html[data-menu-color="light"]', '--bs-main-nav-border-color', '#eaedf1'],

        /* html[data-menu-color="dark"] */
        'theme_menu_dark_main_nav_bg' => ['html[data-menu-color="dark"]', '--bs-main-nav-bg', '#1C252E'],
        'theme_menu_dark_main_nav_item_color' => ['html[data-menu-color="dark"]', '--bs-main-nav-item-color', '#a8b2b5'],
        'theme_menu_dark_main_nav_item_hover_bg' => ['html[data-menu-color="dark"]', '--bs-main-nav-item-hover-bg', 'rgba(255,255,255,0.06)'],
        'theme_menu_dark_main_nav_item_hover_color' => ['html[data-menu-color="dark"]', '--bs-main-nav-item-hover-color', '#ffffff'],
        'theme_menu_dark_main_nav_item_active_color' => ['html[data-menu-color="dark"]', '--bs-main-nav-item-active-color', '#eef2f7'],
        'theme_menu_dark_main_nav_border_color' => ['html[data-menu-color="dark"]', '--bs-main-nav-border-color', '#2f3944'],

        /* html[data-bs-theme="dark"] + menu color */
        'theme_menu_bs_dark_main_nav_bg' => [$selectorMenuBsDark, '--bs-main-nav-bg', '#1C252E'],
        'theme_menu_bs_dark_main_nav_item_color' => [$selectorMenuBsDark, '--bs-main-nav-item-color', '#afb9cf'],
        'theme_menu_bs_dark_main_nav_item_hover_bg' => [$selectorMenuBsDark, '--bs-main-nav-item-hover-bg', '#303943'],
        'theme_menu_bs_dark_main_nav_item_hover_color' => [$selectorMenuBsDark, '--bs-main-nav-item-hover-color', '#ffffff'],
        'theme_menu_bs_dark_main_nav_item_active_color' => [$selectorMenuBsDark, '--bs-main-nav-item-active-color', '#eef2f7'],
        'theme_menu_bs_dark_main_nav_border_color' => [$selectorMenuBsDark, '--bs-main-nav-border-color', '#2f3944'],

        /* html[data-topbar-color="light"] */
        'theme_topbar_light_topbar_bg' => ['html[data-topbar-color="light"]', '--bs-topbar-bg', '#ffffff'],
        'theme_topbar_light_topbar_item_color' => ['html[data-topbar-color="light"]', '--bs-topbar-item-color', '#707793'],
        'theme_topbar_light_topbar_search_bg' => ['html[data-topbar-color="light"]', '--bs-topbar-search-bg', '#e8edf1'],

        /* html[data-topbar-color="dark"] */
        'theme_topbar_dark_topbar_bg' => ['html[data-topbar-color="dark"]', '--bs-topbar-bg', '#141A21'],
        'theme_topbar_dark_topbar_item_color' => ['html[data-topbar-color="dark"]', '--bs-topbar-item-color', '#afb9cf'],
        'theme_topbar_dark_topbar_search_bg' => ['html[data-topbar-color="dark"]', '--bs-topbar-search-bg', '#212b36'],

        /* html[data-bs-theme="dark"] + topbar color */
        'theme_topbar_bs_dark_topbar_bg' => [$selectorTopbarBsDark, '--bs-topbar-bg', '#141A21'],
        'theme_topbar_bs_dark_topbar_item_color' => [$selectorTopbarBsDark, '--bs-topbar-item-color', '#afb9cf'],
        'theme_topbar_bs_dark_topbar_search_bg' => [$selectorTopbarBsDark, '--bs-topbar-search-bg', '#303943'],
    ],

    /** Ordered labels for admin Theme Mode tab sections (keys = selector strings from meta). */
    'selector_labels' => [
        ':root' => 'Layout & inputs',
        'html[data-menu-color="light"]' => 'Sidebar — light menu preset',
        'html[data-menu-color="dark"]' => 'Sidebar — dark menu preset',
        $selectorMenuBsDark => 'Sidebar — Bootstrap dark theme',
        'html[data-topbar-color="light"]' => 'Topbar — light preset',
        'html[data-topbar-color="dark"]' => 'Topbar — dark preset',
        $selectorTopbarBsDark => 'Topbar — Bootstrap dark theme',
    ],
];
