<?php

declare(strict_types=1);

namespace Laravilt\Panel\Pages;

use Illuminate\Database\Eloquent\Model;
use Laravilt\Schemas\Schema;

abstract class ViewRecord extends Page
{
    protected Model $record;

    /**
     * Display the page (GET request handler).
     * Receives the record ID from route parameter and resolves the model.
     */
    public function create(\Illuminate\Http\Request $request, ...$parameters)
    {
        // Extract the record ID from parameters (first parameter after request)
        $recordId = $parameters[0] ?? null;

        if (!$recordId) {
            throw new \InvalidArgumentException('Record ID parameter is required for ViewRecord pages');
        }

        // Get the model class from the resource
        $resource = static::getResource();
        $modelClass = $resource::getModel();

        // Resolve the model instance from the ID
        $this->record = $modelClass::findOrFail($recordId);

        return $this->render();
    }

    public function infolist(Schema $schema): Schema
    {
        $resource = static::getResource();

        return $resource::infolist($schema);
    }

    /**
     * @return array<mixed>
     */
    public function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Get the schema (infolist) for this page.
     */
    public function getSchema(): array
    {
        // Configure infolist
        $infolist = $this->infolist(new \Laravilt\Schemas\Schema);

        // Fill with record data if available
        if (isset($this->record)) {
            $infolist->fill($this->record->toArray());
        }

        return [$infolist];
    }
}
