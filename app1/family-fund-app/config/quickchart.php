<?php

return [
    /*
    |--------------------------------------------------------------------------
    | QuickChart Service URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the QuickChart service. In development, this points to
    | the local Docker container. In production, use the same Docker setup
    | or the public quickchart.io API.
    |
    */
    'base_url' => env('QUICKCHART_URL', 'http://quickchart:3400'),

    /*
    |--------------------------------------------------------------------------
    | Default Chart Dimensions
    |--------------------------------------------------------------------------
    */
    'width' => 900,
    'height' => 400,
    'progress_height' => 200,

    /*
    |--------------------------------------------------------------------------
    | Chart Colors (Corporate Palette)
    |--------------------------------------------------------------------------
    */
    'colors' => [
        'primary' => '#2563eb',      // Blue
        'secondary' => '#64748b',    // Slate
        'success' => '#16a34a',      // Green
        'warning' => '#d97706',      // Amber
        'danger' => '#dc2626',       // Red
        'info' => '#0891b2',         // Cyan
        'purple' => '#9333ea',       // Purple
        'pink' => '#db2777',         // Pink
        'gray' => '#6b7280',         // Gray
    ],

    /*
    |--------------------------------------------------------------------------
    | Dataset Colors (for multi-series charts)
    |--------------------------------------------------------------------------
    */
    'dataset_colors' => [
        '#2563eb',  // Blue
        '#dc2626',  // Red
        '#16a34a',  // Green
        '#d97706',  // Amber
        '#9333ea',  // Purple
        '#0891b2',  // Cyan
        '#db2777',  // Pink
        '#64748b',  // Slate
        '#f59e0b',  // Yellow
        '#10b981',  // Emerald
        '#6366f1',  // Indigo
        '#ec4899',  // Pink
        '#14b8a6',  // Teal
        '#f97316',  // Orange
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Color
    |--------------------------------------------------------------------------
    */
    'background_color' => '#ffffff',

    /*
    |--------------------------------------------------------------------------
    | Font Configuration
    |--------------------------------------------------------------------------
    */
    'font_family' => 'Inter, system-ui, sans-serif',
    'font_size' => 12,
    'title_font_size' => 14,
];
