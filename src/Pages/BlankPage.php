<?php

namespace Laravilt\Panel\Pages;

abstract class BlankPage extends Page
{
    protected static string $view = 'laravilt-panel::pages.blank';

    /**
     * Get the page content.
     */
    abstract protected function getContent(): string;

    /**
     * Get the view data.
     */
    protected function getViewData(): array
    {
        return [
            'content' => $this->getContent(),
        ];
    }
}
