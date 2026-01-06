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
    'width' => 1200,
    'height' => 500,
    'progress_height' => 150,
    'doughnut_width' => 700,
    'doughnut_height' => 500,
    'doughnut_large_width' => 1000,
    'doughnut_large_height' => 600,

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
        '#4f46e5',  // Deep Indigo
        '#059669',  // Deep Emerald
        '#b91c1c',  // Deep Red
        '#7c3aed',  // Violet
        '#0369a1',  // Deep Sky
        '#c026d3',  // Fuchsia
        '#ca8a04',  // Deep Yellow
        '#0d9488',  // Deep Teal
        '#e11d48',  // Rose
        '#0284c7',  // Light Blue
        '#15803d',  // Forest Green
        '#7e22ce',  // Deep Purple
        '#be123c',  // Deep Rose
        '#1d4ed8',  // Royal Blue
        '#047857',  // Dark Emerald
        '#a21caf',  // Deep Fuchsia
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
    'font_family' => 'Arial, Helvetica, sans-serif',
    'font_size' => 14,
    'title_font_size' => 16,
    'font_color' => '#1e293b',
    'legend_font_size' => 13,
];
