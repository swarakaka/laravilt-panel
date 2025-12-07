<?php

declare(strict_types=1);

namespace Laravilt\Panel\Resources;

use Illuminate\Database\Eloquent\Model;
use Laravilt\Schemas\Schema;
use Laravilt\Tables\Table;

abstract class Resource
{
    /** @var class-string<Model> */
    protected static string $model;

    protected static ?string $label = null;

    protected static ?string $pluralLabel = null;

    protected static ?string $icon = null;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = null;

    protected static int $navigationSort = 0;

    protected static bool $navigationVisible = true;

    protected static ?string $navigationBadge = null;

    protected static ?string $navigationBadgeColor = null;

    protected static ?string $slug = null;

    protected static bool $hasApi = false;

    protected static bool $hasFlutter = false;

    public static function makeForm(): Schema
    {
        $form = new \Laravilt\Forms\Form;
        $form->model(static::$model);

        return $form;
    }

    public static function makeTable(): Table
    {
        return new Table;
    }

    public static function makeInfoList(): Schema
    {
        return new \Laravilt\Infolists\InfoList;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function api(\Laravilt\Api\Api $api): \Laravilt\Api\Api
    {
        return $api;
    }

    public static function flutter(\Laravilt\Flutter\Flutter $flutter): \Laravilt\Flutter\Flutter
    {
        return $flutter;
    }

    /**
     * @return array<string, array{class: string, path: string}>
     */
    public static function getPages(): array
    {
        return [];
    }

    /**
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return class-string<Model>
     */
    public static function getModel(): string
    {
        return static::$model;
    }

    public static function getLabel(): string
    {
        return static::$label ?? str(class_basename(static::class))->remove('Resource')->headline()->toString();
    }

    public static function getPluralLabel(): string
    {
        return static::$pluralLabel ?? str(static::getLabel())->remove('Resource')->plural()->toString();
    }

    public static function getIcon(): ?string
    {
        return static::$icon;
    }

    public static function getNavigationIcon(): ?string
    {
        return static::$navigationIcon ?? static::$icon;
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup;
    }

    public static function getNavigationSort(): int
    {
        return static::$navigationSort;
    }

    public static function isNavigationVisible(): bool
    {
        return static::$navigationVisible;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::$navigationBadge;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::$navigationBadgeColor;
    }

    public static function getSlug(): string
    {
        if (static::$slug) {
            return static::$slug;
        }

        return str(class_basename(static::class))
            ->replace('Resource', '')
            ->kebab()
            ->toString();
    }

    public static function getUrl($panelOrPage = 'list', array $parameters = []): string
    {
        // Support both getUrl($panel) and getUrl('list', $parameters)
        if ($panelOrPage instanceof \Laravilt\Panel\Panel) {
            $panelId = $panelOrPage->getId();
            $page = 'list';
        } else {
            $panelId = 'admin'; // Default panel ID
            $page = $panelOrPage;
        }

        return route(
            "{$panelId}.resources.".static::getSlug().".{$page}",
            $parameters
        );
    }

    public static function getRouteBaseName(): string
    {
        return 'laravilt.resources.'.static::getSlug();
    }

    public static function hasApi(): bool
    {
        return static::$hasApi;
    }

    public static function hasFlutter(): bool
    {
        return static::$hasFlutter;
    }

    /**
     * Check if the resource's table has a card configuration (supports grid view).
     */
    public static function hasCardConfig(): bool
    {
        if (! static::hasTable()) {
            return false;
        }

        $table = static::table(new Table);

        return $table->getCard() !== null;
    }

    /**
     * Check if the resource uses a Table for listing.
     */
    public static function hasTable(): bool
    {
        return method_exists(static::class, 'table') &&
               (new \ReflectionMethod(static::class, 'table'))->getDeclaringClass()->getName() !== self::class;
    }

    /**
     * Transform a model instance for API response.
     *
     * @return array<string, mixed>
     */
    public static function toApiResource(Model $record): array
    {
        return $record->toArray();
    }

    /**
     * Transform a model instance for Flutter consumption.
     *
     * @return array<string, mixed>
     */
    public static function toFlutterResource(Model $record): array
    {
        return $record->toArray();
    }

    /**
     * Get API routes configuration for this resource.
     *
     * @return array<string, mixed>
     */
    public static function getApiRoutes(): array
    {
        if (! static::hasApi()) {
            return [];
        }

        $slug = static::getSlug();

        return [
            'index' => ['method' => 'GET', 'path' => "/api/{$slug}"],
            'show' => ['method' => 'GET', 'path' => "/api/{$slug}/{id}"],
            'store' => ['method' => 'POST', 'path' => "/api/{$slug}"],
            'update' => ['method' => 'PUT', 'path' => "/api/{$slug}/{id}"],
            'destroy' => ['method' => 'DELETE', 'path' => "/api/{$slug}/{id}"],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(): array
    {
        return [
            'model' => static::$model,
            'label' => static::getLabel(),
            'pluralLabel' => static::getPluralLabel(),
            'icon' => static::$icon,
            'navigationIcon' => static::getNavigationIcon(),
            'navigationGroup' => static::$navigationGroup,
            'navigationSort' => static::$navigationSort,
            'navigationVisible' => static::$navigationVisible,
            'slug' => static::getSlug(),
            'pages' => static::getPages(),
            'relations' => static::getRelations(),
            'hasApi' => static::hasApi(),
            'hasFlutter' => static::hasFlutter(),
            'apiRoutes' => static::getApiRoutes(),
        ];
    }
}
