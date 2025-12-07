<?php

namespace Laravilt\Panel\Concerns;

trait HasId
{
    protected string $id;

    /**
     * Set the panel ID.
     */
    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the panel ID.
     */
    public function getId(): string
    {
        return $this->id;
    }
}
