<?php

declare(strict_types=1);

namespace Laravilt\Panel\Resources\Pages;

use Laravilt\Grids\Grid;

class ListRecordsGrid extends ListRecords
{
    public static function grid(Grid $grid): Grid
    {
        $resource = static::getResource();

        return $resource::grid($grid);
    }

    public function getSchema(): array
    {
        $grid = static::grid(new Grid);
        $grid->query(fn () => $this->getTableQuery());

        return [$grid];
    }
}
