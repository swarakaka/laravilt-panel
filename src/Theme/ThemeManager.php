<?php

namespace Laravilt\Panel\Theme;

use Laravilt\Panel\Panel;

class ThemeManager
{
    /**
     * Generate CSS variables for the panel.
     */
    public static function generateCssVariables(Panel $panel): string
    {
        $colors = $panel->getColors();
        $primaryColor = $colors['primary'] ?? '#6366f1';

        // Convert hex to RGB for Tailwind
        [$r, $g, $b] = sscanf($primaryColor, '#%02x%02x%02x');

        $css = ":root {\n";
        $css .= "    --color-primary: {$r} {$g} {$b};\n";

        // Add font if specified
        if ($font = $panel->getFont()) {
            $css .= "    --font-sans: '{$font}', ui-sans-serif, system-ui, sans-serif;\n";
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Generate Tailwind config for the panel.
     */
    public static function generateTailwindConfig(Panel $panel): array
    {
        $colors = $panel->getColors();

        return [
            'theme' => [
                'extend' => [
                    'colors' => [
                        'primary' => [
                            50 => 'rgb(var(--color-primary) / 0.05)',
                            100 => 'rgb(var(--color-primary) / 0.1)',
                            200 => 'rgb(var(--color-primary) / 0.2)',
                            300 => 'rgb(var(--color-primary) / 0.3)',
                            400 => 'rgb(var(--color-primary) / 0.4)',
                            500 => 'rgb(var(--color-primary) / 0.5)',
                            600 => 'rgb(var(--color-primary) / 1)',
                            700 => 'rgb(var(--color-primary) / 0.8)',
                            800 => 'rgb(var(--color-primary) / 0.7)',
                            900 => 'rgb(var(--color-primary) / 0.6)',
                        ],
                    ],
                ],
            ],
        ];
    }
}
