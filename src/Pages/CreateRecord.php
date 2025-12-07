<?php

declare(strict_types=1);

namespace Laravilt\Panel\Pages;

use Illuminate\Database\Eloquent\Model;
use Laravilt\Schemas\Schema;

abstract class CreateRecord extends Page
{

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
    public function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    protected function afterCreate(): void
    {
        //
    }

    protected function getRedirectUrl(): ?string
    {
        $resource = static::getResource();

        return $resource::getUrl('list');
    }

    public function createRecord(array $data): Model
    {
        $resource = static::getResource();
        $model = $resource::getModel();

        $data = $this->mutateFormDataBeforeCreate($data);

        // Extract relationship data for many-to-many relationships
        $relationships = $this->extractRelationshipData($data);

        $record = $model::create($data);

        // Sync many-to-many relationships
        $this->syncRelationships($record, $relationships);

        $this->afterCreate();

        return $record;
    }

    /**
     * Extract many-to-many relationship data from form data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractRelationshipData(array &$data): array
    {
        $model = static::getResource()::getModel();
        $modelInstance = new $model;
        $relationships = [];

        foreach ($data as $key => $value) {
            // Check if this key corresponds to a relationship method
            if (method_exists($modelInstance, $key)) {
                try {
                    $relation = $modelInstance->{$key}();

                    // Check if it's a BelongsToMany relationship
                    if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                        $relationships[$key] = $value;
                        unset($data[$key]);
                    }
                } catch (\Throwable $e) {
                    // Not a relationship method, skip
                    continue;
                }
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
     * Get the schema (form) for this page.
     */
    public function getSchema(): array
    {
        $form = $this->form(new \Laravilt\Schemas\Schema);

        $resource = static::getResource();

        // Get the form schema
        $schema = $form->getSchema();

        // Add actions to the bottom of the form (as standalone actions, not component-based)
        $actions = [
            \Laravilt\Actions\Action::make('create')
                ->label('Create')
                ->color('primary')
                ->preserveState(false)
                ->action(function (mixed $record, array $data) {
                    $newRecord = $this->createRecord($data);
                    $redirectUrl = $this->getRedirectUrl();

                    \Laravilt\Notifications\Notification::success()
                        ->title('Created successfully')
                        ->body('The record has been created.')
                        ->send();

                    return redirect($redirectUrl);
                }),

            \Laravilt\Actions\Action::make('createAnother')
                ->label('Create & Create Another')
                ->color('secondary')
                ->preserveState(false)
                ->action(function (mixed $record, array $data) {
                    $this->createRecord($data);
                    $resource = static::getResource();
                    $createUrl = $resource::getUrl('create');

                    \Laravilt\Notifications\Notification::success()
                        ->title('Created successfully')
                        ->body('The record has been created. You can create another one.')
                        ->send();

                    return redirect($createUrl);
                }),

            \Laravilt\Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('secondary')
                ->outlined()
                ->url($resource::getUrl('list')),
        ];

        // Append actions to schema (they will use standalone action tokens)
        foreach ($actions as $action) {
            $schema[] = $action;
        }

        $form->schema($schema);

        return [$form];
    }
}
