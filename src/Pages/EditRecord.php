<?php

declare(strict_types=1);

namespace Laravilt\Panel\Pages;

use Illuminate\Database\Eloquent\Model;
use Laravilt\Schemas\Schema;

abstract class EditRecord extends Page
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
            throw new \InvalidArgumentException('Record ID parameter is required for EditRecord pages');
        }

        // Get the model class from the resource
        $resource = static::getResource();

        if (!$resource) {
            throw new \InvalidArgumentException('Resource is not set for '.static::class);
        }

        $modelClass = $resource::getModel();

        if (!$modelClass) {
            throw new \InvalidArgumentException('Model class is not set for resource '.$resource);
        }

        // Resolve the model instance from the ID
        $this->record = $modelClass::findOrFail($recordId);

        return $this->render();
    }

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function form(Schema $schema): Schema
    {
        $resource = static::getResource();

        return $resource::form($schema);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateFormDataBeforeFill(array $data): array
    {
        // Load many-to-many relationship IDs for the form
        $data = $this->loadRelationshipData($data);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    protected function afterSave(): void
    {
        //
    }

    protected function getRedirectUrl(): ?string
    {
        $resource = static::getResource();

        return $resource::getUrl('list');
    }

    public function save(array $data): Model
    {
        $data = $this->mutateFormDataBeforeSave($data);

        // Extract relationship data for many-to-many relationships
        $relationships = $this->extractRelationshipData($data);

        $this->record->update($data);

        // Sync many-to-many relationships
        $this->syncRelationships($this->record, $relationships);

        $this->afterSave();

        return $this->record;
    }

    /**
     * Load many-to-many relationship IDs into form data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function loadRelationshipData(array $data): array
    {
        $reflectionClass = new \ReflectionClass($this->record);

        // List of methods to skip (Eloquent methods that should not be called)
        $skipMethods = [
            'delete', 'forceDelete', 'restore', 'save', 'update', 'fresh', 'refresh',
            'push', 'touch', 'replicate', 'toArray', 'toJson', 'jsonSerialize',
            'getKey', 'getTable', 'getConnection', 'newQuery', 'newQueryWithoutScopes',
            // SoftDeletes trait methods
            'forceDeleteQuietly', 'deleteQuietly', 'restoreQuietly',
        ];

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            // Skip magic methods, methods with parameters, and dangerous Eloquent methods
            if (str_starts_with($methodName, '__')
                || $method->getNumberOfParameters() > 0
                || in_array($methodName, $skipMethods)
                || $method->getDeclaringClass()->getName() !== $this->record::class) {
                continue;
            }

            try {
                $relation = $this->record->{$methodName}();

                // Check if it's a BelongsToMany relationship
                if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                    $data[$methodName] = $this->record->{$methodName}()->pluck($relation->getRelated()->getTable().'.id')->toArray();
                }
            } catch (\Throwable $e) {
                // Not a relationship method, skip
                continue;
            }
        }

        return $data;
    }

    /**
     * Extract many-to-many relationship data from form data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractRelationshipData(array &$data): array
    {
        $relationships = [];

        $reflectionClass = new \ReflectionClass($this->record);

        // List of methods to skip (Eloquent methods that should not be called)
        $skipMethods = [
            'delete', 'forceDelete', 'restore', 'save', 'update', 'fresh', 'refresh',
            'push', 'touch', 'replicate', 'toArray', 'toJson', 'jsonSerialize',
            'getKey', 'getTable', 'getConnection', 'newQuery', 'newQueryWithoutScopes',
            // SoftDeletes trait methods
            'forceDeleteQuietly', 'deleteQuietly', 'restoreQuietly',
        ];

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            // Skip magic methods, methods with parameters, and dangerous Eloquent methods
            if (str_starts_with($methodName, '__')
                || $method->getNumberOfParameters() > 0
                || in_array($methodName, $skipMethods)
                || $method->getDeclaringClass()->getName() !== $this->record::class) {
                continue;
            }

            // Check if this key exists in the data
            if (! array_key_exists($methodName, $data)) {
                continue;
            }

            try {
                $relation = $this->record->{$methodName}();

                // Check if it's a BelongsToMany relationship
                if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                    $relationships[$methodName] = $data[$methodName];
                    unset($data[$methodName]);
                }
            } catch (\Throwable $e) {
                // Not a relationship method, skip
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Sync many-to-many relationships.
     *
     * @param  array<string, mixed>  $relationships
     */
    protected function syncRelationships(Model $record, array $relationships): void
    {
        foreach ($relationships as $relationName => $relationData) {
            if ($relationData !== null) {
                $record->{$relationName}()->sync($relationData);
            }
        }
    }

    /**
     * @return array<mixed>
     */
    public function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Get the schema (form) for this page.
     */
    public function getSchema(): array
    {
        $form = $this->form(new \Laravilt\Schemas\Schema);

        $resource = static::getResource();

        // Fill with record data if available
        if (isset($this->record)) {
            $formData = $this->mutateFormDataBeforeFill($this->record->toArray());
            $form->fill($formData);
        }

        // Get the form schema
        $schema = $form->getSchema();

        // Add actions to the bottom of the form
        $actions = [
            \Laravilt\Actions\Action::make('save')
                ->label('Save')
                ->color('primary')
                ->preserveState(false)
                ->action(function (mixed $record, array $data) {
                    $this->save($data);
                    $redirectUrl = $this->getRedirectUrl();

                    \Laravilt\Notifications\Notification::success()
                        ->title('Saved successfully')
                        ->body('The changes have been saved.')
                        ->send();

                    return redirect($redirectUrl);
                }),

            \Laravilt\Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('secondary')
                ->outlined()
                ->url($resource::getUrl('list')),
        ];

        // Append actions to schema
        foreach ($actions as $action) {
            $schema[] = $action;
        }

        $form->schema($schema);

        return [$form];
    }
}
