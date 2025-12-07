<?php

declare(strict_types=1);

namespace Laravilt\Panel\Resources\Pages;

use Laravilt\Tables\Table;

class ListRecordsTable extends ListRecords
{
    public static function table(Table $table): Table
    {
        $resource = static::getResource();

        return $resource::table($table);
    }

    public function getSchema(): array
    {
        $table = static::table(new Table);
        $table->query($this->getTableQuery());

        return [$table];
    }
}
