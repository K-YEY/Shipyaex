<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | List all locales that your app should support.
    |
    */
    'locales' => ['ar', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Flags
    |--------------------------------------------------------------------------
    |
    | Map each locale to its flag image URL.
    |
    */
    'flags' => [
        'ar' => '/flags/ar.png',
        'en' => '/flags/en.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    |
    | Optionally override the label for each locale.
    |
    */
    'labels' => [
        'ar' => 'عربي',
        'en' => 'English',
    ],

    /*
    |--------------------------------------------------------------------------
    | Display Locale
    |--------------------------------------------------------------------------
    |
    | The locale to use when displaying locale names.
    |
    */
    'display_locale' => 'ar',

    /*
    |--------------------------------------------------------------------------
    | Visibility
    |--------------------------------------------------------------------------
    |
    | Control where the language switcher appears.
    |
    */
    'visible' => [
        'inside_panels' => true,
        'outside_panels' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Render Hook
    |--------------------------------------------------------------------------
    |
    | The Filament render hook where the language switcher should appear.
    |
    */
    'render_hook' => 'panels::global-search.after',
];
