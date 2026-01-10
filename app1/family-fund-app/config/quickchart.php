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
    'device_pixel_ratio' => env('QUICKCHART_DPR', 2.0),  // Higher = better quality (1.0, 2.0, 3.0)
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
    // Colors matching web Chart.js version for consistency
    'dataset_colors' => [
        '#ff6384',  // Red/Pink - rgb(255, 99, 132)
        '#36a2eb',  // Blue - rgb(54, 162, 235)
        '#4bc0c0',  // Green/Teal - rgb(75, 192, 192)
        '#ff9f40',  // Orange - rgb(255, 159, 64)
        '#00d4ff',  // Cyan - close to rgb(0, 255, 255)
        '#ff00ff',  // Magenta - rgb(255, 0, 255)
        '#8b4513',  // Brown - rgb(139, 69, 19)
        '#800080',  // Purple - rgb(128, 0, 128)
        '#9333ea',  // Violet
        '#16a34a',  // Green
        '#d97706',  // Amber
        '#db2777',  // Pink
        '#0891b2',  // Teal
        '#64748b',  // Slate
        '#f59e0b',  // Yellow
        '#10b981',  // Emerald
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
