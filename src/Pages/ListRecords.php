<?php

declare(strict_types=1);

namespace Laravilt\Panel\Pages;

use Illuminate\Http\Request;
use Laravilt\Tables\Table;

abstract class ListRecords extends Page
{
    /**
     * Default view mode: 'table' or 'grid'
     */
    protected string $defaultView = 'table';

    public function table(Table $table): Table
    {
        $resource = static::getResource();

        return $resource::table($table);
    }

    /**
     * @return array<mixed>
     */
    public function getHeaderActions(): array
    {
        return [];
    }

    public function getTableQuery()
    {
        $resource = static::getResource();

        return $resource::getModel()::query();
    }

    /**
     * Check if this resource supports view toggle (table has card configuration).
     */
    public function hasViewToggle(): bool
    {
        $resource = static::getResource();

        return $resource::hasTable() && $resource::hasCardConfig();
    }

    /**
     * Get the session key for storing view preference.
     */
    protected function getViewSessionKey(): string
    {
        $resource = static::getResource();

        return 'laravilt_view_preference_'.$resource::getSlug();
    }

    /**
     * Get the current view mode from request, session, or default.
     */
    public function getCurrentView(): string
    {
        $sessionKey = $this->getViewSessionKey();
        $urlView = request()->query('view');

        // If URL has view param, validate and save to session
        if ($urlView !== null) {
            if (in_array($urlView, ['table', 'grid'])) {
                session()->put($sessionKey, $urlView);

                return $urlView;
            }

            return $this->defaultView;
        }

        // No URL param - check session for saved preference
        $sessionView = session()->get($sessionKey);

        if ($sessionView !== null && in_array($sessionView, ['table', 'grid'])) {
            return $sessionView;
        }

        return $this->defaultView;
    }

    /**
     * Get extra props for Inertia response.
     *
     * @return array<string, mixed>
     */
    protected function getInertiaProps(): array
    {
        return [
            'hasViewToggle' => $this->hasViewToggle(),
            'currentView' => $this->getCurrentView(),
        ];
    }

    /**
     * Get the schema (table) for this page.
     * The table handles both table and grid views based on card configuration.
     */
    public function getSchema(): array
    {
        $table = $this->table(new Table);
        $table->query(fn () => $this->getTableQuery());

        return [$table];
    }
}
