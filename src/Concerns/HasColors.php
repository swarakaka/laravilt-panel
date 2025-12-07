<?php

namespace Laravilt\Panel\Concerns;

use Closure;
use Laravilt\Support\Colors\Color;

trait HasColors
{
    protected array|Closure $colors = [];

    /**
     * Set the panel colors.
     */
    public function colors(array|Closure $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * Get the panel colors.
     */
    public function getColors(): array
    {
        $colors = $this->evaluate($this->colors);

        return array_map(function ($color) {
            if ($color instanceof Color) {
                return $color->getValue();
            }

            return $color;
        }, $colors);
    }

    /**
     * Get a specific color.
     */
    public function getColor(string $name): ?string
    {
        return $this->getColors()[$name] ?? null;
    }

    /**
     * Evaluate a closure or return the value.
     */
    protected function evaluate(mixed $value): mixed
    {
        if ($value instanceof Closure) {
            return $value();
        }

        return $value;
    }
}
