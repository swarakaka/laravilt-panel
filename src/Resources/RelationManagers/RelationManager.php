<?php

declare(strict_types=1);

namespace Laravilt\Panel\Resources\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravilt\Schemas\Schema;
use Laravilt\Tables\Table;

abstract class RelationManager
{
    protected static string $relationship;

    protected static ?string $recordTitleAttribute = null;

    protected static ?string $label = null;

    protected static ?string $pluralLabel = null;

    protected static ?string $icon = null;

    protected Model $ownerRecord;

    public function __construct(Model $ownerRecord)
    {
        $this->ownerRecord = $ownerRecord;
    }

    public static function make(Model $ownerRecord): static
    {
        return new static($ownerRecord);
    }

    public static function getRelationship(): string
    {
        return static::$relationship;
    }

    public static function getLabel(): string
    {
        return static::$label ?? str(static::getRelationship())->title()->toString();
    }

    public static function getPluralLabel(): string
    {
        return static::$pluralLabel ?? str(static::getLabel())->plural()->toString();
    }

    public static function getIcon(): ?string
    {
        return static::$icon;
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return static::$recordTitleAttribute;
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function canCreate(): bool
    {
        return ! $this->isReadOnly();
    }

    public function canEdit(): bool
    {
        return ! $this->isReadOnly();
    }

    public function canDelete(): bool
    {
        return ! $this->isReadOnly();
    }

    public function getRelationshipQuery(): Relation
    {
        return $this->ownerRecord->{static::getRelationship()}();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'relationship' => static::getRelationship(),
            'label' => static::getLabel(),
            'pluralLabel' => static::getPluralLabel(),
            'icon' => static::getIcon(),
            'recordTitleAttribute' => static::getRecordTitleAttribute(),
            'readOnly' => $this->isReadOnly(),
            'canCreate' => $this->canCreate(),
            'canEdit' => $this->canEdit(),
            'canDelete' => $this->canDelete(),
            'form' => $this->form(Schema::make())->toInertiaProps(),
            'table' => $this->table(Table::make())->toInertiaProps(),
        ];
    }
}
