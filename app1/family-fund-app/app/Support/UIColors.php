<?php

namespace App\Support;

/**
 * Standard color palette for UI elements.
 * Based on Tailwind CSS colors (600 shade).
 */
class UIColors
{
    const EMERALD  = '#059669';
    const TEAL     = '#0d9488';
    const CYAN     = '#0891b2';
    const SKY      = '#0284c7';
    const BLUE     = '#2563eb';
    const INDIGO   = '#4f46e5';
    const VIOLET   = '#7c3aed';
    const PURPLE   = '#9333ea';
    const FUCHSIA  = '#c026d3';
    const PINK     = '#db2777';
    const ROSE     = '#e11d48';
    const RED      = '#dc2626';
    const ORANGE   = '#ea580c';
    const AMBER    = '#d97706';
    const YELLOW   = '#ca8a04';
    const LIME     = '#65a30d';
    const GREEN    = '#16a34a';
    const GRAY     = '#6b7280';
    const SLATE    = '#475569';
    const ZINC     = '#52525b';

    /**
     * Get all colors as an array.
     */
    public static function all(): array
    {
        return [
            'emerald'  => self::EMERALD,
            'teal'     => self::TEAL,
            'cyan'     => self::CYAN,
            'sky'      => self::SKY,
            'blue'     => self::BLUE,
            'indigo'   => self::INDIGO,
            'violet'   => self::VIOLET,
            'purple'   => self::PURPLE,
            'fuchsia'  => self::FUCHSIA,
            'pink'     => self::PINK,
            'rose'     => self::ROSE,
            'red'      => self::RED,
            'orange'   => self::ORANGE,
            'amber'    => self::AMBER,
            'yellow'   => self::YELLOW,
            'lime'     => self::LIME,
            'green'    => self::GREEN,
            'gray'     => self::GRAY,
            'slate'    => self::SLATE,
            'zinc'     => self::ZINC,
        ];
    }

    /**
     * Get a color by index (cycles through palette).
     */
    public static function byIndex(int $index): string
    {
        $colors = array_values(self::all());
        return $colors[$index % count($colors)];
    }
}
