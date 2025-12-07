<?php

namespace Laravilt\Panel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class MakeResourceCommand extends Command
{
    protected $signature = 'laravilt:resource {panel?} {--table=} {--model=}';

    protected $description = 'Create a new panel resource from database table with CRUD pages';

    protected array $excludedColumns = [
        'id', 'created_at', 'updated_at', 'deleted_at', 'remember_token',
        'email_verified_at', 'password', 'two_factor_secret', 'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected array $columnTypeMap = [
        'string' => 'TextInput',
        'text' => 'Textarea',
        'integer' => 'TextInput',
        'bigint' => 'TextInput',
        'smallint' => 'TextInput',
        'tinyint' => 'TextInput',
        'float' => 'TextInput',
        'double' => 'TextInput',
        'decimal' => 'TextInput',
        'boolean' => 'Toggle',
        'date' => 'DatePicker',
        'datetime' => 'DateTimePicker',
        'timestamp' => 'DateTimePicker',
        'time' => 'TimePicker',
        'json' => 'Textarea',
        'enum' => 'Select',
    ];

    public function handle(): int
    {
        // Get available panels from the Laravilt directory
        $panels = $this->getAvailablePanels();

        if (empty($panels)) {
            $this->components->error('No panels found. Please create a panel first.');
            return self::FAILURE;
        }

        // Get panel name
        $panel = $this->argument('panel');
        if (!$panel) {
            $panel = select(
                label: 'Select a panel:',
                options: $panels,
                default: $panels[0] ?? 'Admin'
            );
        }
        $panel = Str::studly($panel);

        // Get all tables from database
        $tables = $this->getDatabaseTables();

        if (empty($tables)) {
            $this->components->error('No tables found in the database.');
            return self::FAILURE;
        }

        // Let user select a table
        $tableName = $this->option('table');
        if (!$tableName) {
            $tableName = search(
                label: 'Select a database table to generate resource from:',
                options: fn (string $value) => strlen($value) > 0
                    ? collect($tables)->filter(fn ($table) => str_contains(strtolower($table), strtolower($value)))->values()->all()
                    : $tables,
                placeholder: 'Search for a table...',
                scroll: 15
            );
        }

        if (!in_array($tableName, $tables)) {
            $this->components->error("Table '{$tableName}' not found in database.");
            return self::FAILURE;
        }

        // Get model name from table
        $modelName = $this->option('model') ?? Str::studly(Str::singular($tableName));

        // Get table columns
        $columns = $this->getTableColumns($tableName);

        if (empty($columns)) {
            $this->components->error("No columns found in table '{$tableName}'.");
            return self::FAILURE;
        }

        $this->components->info("Generating resource for table: {$tableName}");
        $this->components->info("Model: {$modelName}");
        $this->newLine();

        // Display columns info
        $this->components->info("Found " . count($columns) . " columns:");
        foreach ($columns as $column) {
            $this->line("  - {$column['name']} ({$column['type']}" . ($column['nullable'] ? ', nullable' : '') . ")");
        }
        $this->newLine();

        // Confirm generation
        if (!confirm('Proceed with resource generation?', true)) {
            $this->components->info('Generation cancelled.');
            return self::SUCCESS;
        }

        // Create model if it doesn't exist
        $this->createModel($modelName, $tableName, $columns);

        // Create resource directory structure
        $resourceDir = app_path("Laravilt/{$panel}/Resources/{$modelName}");
        File::ensureDirectoryExists($resourceDir);
        File::ensureDirectoryExists("{$resourceDir}/Form");
        File::ensureDirectoryExists("{$resourceDir}/Table");
        File::ensureDirectoryExists("{$resourceDir}/InfoList");
        File::ensureDirectoryExists("{$resourceDir}/Pages");

        // Generate all files
        $this->createResourceFile($panel, $modelName, $tableName, $resourceDir);
        $this->createFormFile($panel, $modelName, $columns, $resourceDir);
        $this->createTableFile($panel, $modelName, $columns, $resourceDir);
        $this->createInfoListFile($panel, $modelName, $columns, $resourceDir);
        $this->createPageFiles($panel, $modelName, $resourceDir);

        $this->newLine();
        $this->components->info("Resource [{$modelName}] created successfully for panel [{$panel}]!");
        $this->newLine();
        $this->components->info("Generated files:");
        $this->line("  Resource: app/Laravilt/{$panel}/Resources/{$modelName}/{$modelName}Resource.php");
        $this->line("  Form: app/Laravilt/{$panel}/Resources/{$modelName}/Form/{$modelName}Form.php");
        $this->line("  Table: app/Laravilt/{$panel}/Resources/{$modelName}/Table/{$modelName}Table.php");
        $this->line("  InfoList: app/Laravilt/{$panel}/Resources/{$modelName}/InfoList/{$modelName}InfoList.php");
        $this->line("  Pages: List, Create, Edit, View");

        return self::SUCCESS;
    }

    protected function getAvailablePanels(): array
    {
        $laraviltPath = app_path('Laravilt');

        if (!File::isDirectory($laraviltPath)) {
            return [];
        }

        $directories = File::directories($laraviltPath);

        return collect($directories)
            ->map(fn ($dir) => basename($dir))
            ->values()
            ->toArray();
    }

    protected function getDatabaseTables(): array
    {
        $tables = [];
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            $results = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            $tables = array_map(fn ($row) => $row->name, $results);
        } elseif ($driver === 'mysql') {
            $database = config("database.connections.{$connection}.database");
            $results = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name", [$database]);
            $tables = array_map(fn ($row) => $row->table_name ?? $row->TABLE_NAME, $results);
        } elseif ($driver === 'pgsql') {
            $results = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
            $tables = array_map(fn ($row) => $row->tablename, $results);
        }

        // Filter out Laravel system tables
        $systemTables = ['migrations', 'password_reset_tokens', 'password_resets', 'failed_jobs', 'personal_access_tokens', 'jobs', 'job_batches', 'cache', 'cache_locks', 'sessions'];

        return array_values(array_filter($tables, fn ($table) => !in_array($table, $systemTables)));
    }

    protected function getTableColumns(string $tableName): array
    {
        $columns = [];
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            $results = DB::select("PRAGMA table_info({$tableName})");
            foreach ($results as $column) {
                $columns[] = [
                    'name' => $column->name,
                    'type' => $this->normalizeColumnType($column->type),
                    'nullable' => !$column->notnull,
                    'default' => $column->dflt_value,
                ];
            }
        } elseif ($driver === 'mysql') {
            $database = config("database.connections.{$connection}.database");
            $results = DB::select("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_TYPE FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ORDINAL_POSITION", [$database, $tableName]);
            foreach ($results as $column) {
                $columns[] = [
                    'name' => $column->COLUMN_NAME,
                    'type' => $this->normalizeColumnType($column->DATA_TYPE),
                    'nullable' => $column->IS_NULLABLE === 'YES',
                    'default' => $column->COLUMN_DEFAULT,
                    'full_type' => $column->COLUMN_TYPE,
                ];
            }
        } elseif ($driver === 'pgsql') {
            $results = DB::select("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position", [$tableName]);
            foreach ($results as $column) {
                $columns[] = [
                    'name' => $column->column_name,
                    'type' => $this->normalizeColumnType($column->data_type),
                    'nullable' => $column->is_nullable === 'YES',
                    'default' => $column->column_default,
                ];
            }
        }

        return $columns;
    }

    protected function normalizeColumnType(string $type): string
    {
        $type = strtolower($type);

        $typeMap = [
            'varchar' => 'string',
            'char' => 'string',
            'text' => 'text',
            'mediumtext' => 'text',
            'longtext' => 'text',
            'int' => 'integer',
            'integer' => 'integer',
            'bigint' => 'bigint',
            'smallint' => 'smallint',
            'tinyint' => 'boolean',
            'float' => 'float',
            'double' => 'double',
            'decimal' => 'decimal',
            'numeric' => 'decimal',
            'boolean' => 'boolean',
            'bool' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
            'timestamp' => 'timestamp',
            'time' => 'time',
            'json' => 'json',
            'jsonb' => 'json',
            'enum' => 'enum',
        ];

        return $typeMap[$type] ?? 'string';
    }

    protected function createModel(string $modelName, string $tableName, array $columns): void
    {
        $modelPath = app_path("Models/{$modelName}.php");

        if (File::exists($modelPath)) {
            $this->components->info("Model [{$modelName}] already exists, skipping...");
            return;
        }

        $fillable = collect($columns)
            ->pluck('name')
            ->filter(fn ($name) => !in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at']))
            ->map(fn ($name) => "        '{$name}'")
            ->implode(",\n");

        $casts = collect($columns)
            ->filter(fn ($col) => in_array($col['type'], ['boolean', 'date', 'datetime', 'timestamp', 'json', 'decimal', 'float', 'double']))
            ->map(function ($col) {
                $castType = match ($col['type']) {
                    'boolean' => 'boolean',
                    'date' => 'date',
                    'datetime', 'timestamp' => 'datetime',
                    'json' => 'array',
                    'decimal', 'float', 'double' => 'decimal:2',
                    default => 'string',
                };
                return "        '{$col['name']}' => '{$castType}'";
            })
            ->implode(",\n");

        $hasSoftDeletes = collect($columns)->contains(fn ($col) => $col['name'] === 'deleted_at');
        $softDeletesUse = $hasSoftDeletes ? "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n" : '';
        $softDeletesTrait = $hasSoftDeletes ? ', SoftDeletes' : '';

        $content = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
{$softDeletesUse}
class {$modelName} extends Model
{
    use HasFactory{$softDeletesTrait};

    protected \$table = '{$tableName}';

    protected \$fillable = [
{$fillable},
    ];

    protected \$casts = [
{$casts},
    ];
}
PHP;

        File::put($modelPath, $content);
        $this->components->info("Created Model: app/Models/{$modelName}.php");
    }

    protected function createResourceFile(string $panel, string $modelName, string $tableName, string $dir): void
    {
        $content = <<<PHP
<?php

namespace App\Laravilt\\{$panel}\Resources\\{$modelName};

use App\Laravilt\\{$panel}\Resources\\{$modelName}\Form\\{$modelName}Form;
use App\Laravilt\\{$panel}\Resources\\{$modelName}\InfoList\\{$modelName}InfoList;
use App\Laravilt\\{$panel}\Resources\\{$modelName}\Pages\Create{$modelName};
use App\Laravilt\\{$panel}\Resources\\{$modelName}\Pages\Edit{$modelName};
use App\Laravilt\\{$panel}\Resources\\{$modelName}\Pages\List{$modelName};
use App\Laravilt\\{$panel}\Resources\\{$modelName}\Pages\View{$modelName};
use App\Laravilt\\{$panel}\Resources\\{$modelName}\Table\\{$modelName}Table;
use App\Models\\{$modelName};
use Laravilt\Panel\Resources\Resource;
use Laravilt\Schemas\Schema;
use Laravilt\Tables\Table;

class {$modelName}Resource extends Resource
{
    protected static string \$model = {$modelName}::class;

    protected static ?string \$table = '{$tableName}';

    protected static ?string \$navigationIcon = 'Database';

    protected static ?string \$navigationGroup = null;

    protected static int \$navigationSort = 1;

    public static function form(Schema \$schema): Schema
    {
        return {$modelName}Form::configure(\$schema);
    }

    public static function table(Table \$table): Table
    {
        return {$modelName}Table::configure(\$table);
    }

    public static function infolist(Schema \$schema): Schema
    {
        return {$modelName}InfoList::configure(\$schema);
    }

    public static function getPages(): array
    {
        return [
            'list' => List{$modelName}::route('/'),
            'create' => Create{$modelName}::route('/create'),
            'edit' => Edit{$modelName}::route('/{record}/edit'),
            'view' => View{$modelName}::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers here
        ];
    }
}
PHP;

        File::put("{$dir}/{$modelName}Resource.php", $content);
    }

    protected function createFormFile(string $panel, string $modelName, array $columns, string $dir): void
    {
        $formFields = $this->generateFormFields($columns);
        $imports = $this->generateFormImports($columns);

        $content = <<<PHP
<?php

namespace App\Laravilt\\{$panel}\Resources\\{$modelName}\Form;

{$imports}
use Laravilt\Schemas\Components\Grid;
use Laravilt\Schemas\Components\Section;
use Laravilt\Schemas\Schema;

class {$modelName}Form
{
    public static function configure(Schema \$form): Schema
    {
        return \$form
            ->schema([
                Section::make('Information')
                    ->icon('info')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->columns(2)
                            ->schema([
{$formFields}
                            ]),
                    ]),
            ]);
    }
}
PHP;

        File::put("{$dir}/Form/{$modelName}Form.php", $content);
    }

    protected function createTableFile(string $panel, string $modelName, array $columns, string $dir): void
    {
        $tableColumns = $this->generateTableColumns($columns);

        $content = <<<PHP
<?php

namespace App\Laravilt\\{$panel}\Resources\\{$modelName}\Table;

use Laravilt\Actions\BulkActionGroup;
use Laravilt\Actions\DeleteAction;
use Laravilt\Actions\DeleteBulkAction;
use Laravilt\Actions\EditAction;
use Laravilt\Actions\ViewAction;
use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Tables\Table;

class {$modelName}Table
{
    public static function configure(Table \$table): Table
    {
        return \$table
            ->columns([
{$tableColumns}
            ])
            ->filters([
                // Add filters here
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
PHP;

        File::put("{$dir}/Table/{$modelName}Table.php", $content);
    }

    protected function createInfoListFile(string $panel, string $modelName, array $columns, string $dir): void
    {
        $infoEntries = $this->generateInfoEntries($columns);

        $content = <<<PHP
<?php

namespace App\Laravilt\\{$panel}\Resources\\{$modelName}\InfoList;

use Laravilt\Infolists\Entries\TextEntry;
use Laravilt\Schemas\Components\Grid;
use Laravilt\Schemas\Components\Section;
use Laravilt\Schemas\Schema;

class {$modelName}InfoList
{
    public static function configure(Schema \$infolist): Schema
    {
        return \$infolist
            ->schema([
                Section::make('Information')
                    ->schema([
                        Grid::make(2)
                            ->columns(2)
                            ->schema([
{$infoEntries}
                            ]),
                    ]),
            ]);
    }
}
PHP;

        File::put("{$dir}/InfoList/{$modelName}InfoList.php", $content);
    }

    protected function createPageFiles(string $panel, string $modelName, string $dir): void
    {
        // List Page
        $listContent = <<<PHP
<?php

namespace App\Laravilt\\{$panel}\Resources\\{$modelName}\Pages;

use App\Laravilt\\{$panel}\Resources\\{$modelName}\\{$modelName}Resource;
use Laravilt\Actions\CreateAction;
use Laravilt\Panel\Pages\ListRecords;

class List{$modelName} extends ListRecords
{
    protected static ?string \$resource = {$modelName}Resource::class;

    public function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
PHP;
        File::put("{$dir}/Pages/List{$modelName}.php", $listContent);

        // Create Page
        $createContent = <<<PHP
<?php

namespace App\Laravilt\\{$panel}\Resources\\{$modelName}\Pages;

use App\Laravilt\\{$panel}\Resources\\{$modelName}\\{$modelName}Resource;
use Laravilt\Panel\Pages\CreateRecord;

class Create{$modelName} extends CreateRecord
{
    protected static ?string \$resource = {$modelName}Resource::class;
}
PHP;
        File::put("{$dir}/Pages/Create{$modelName}.php", $createContent);

        // Edit Page
        $editContent = <<<PHP
<?php

namespace App\Laravilt\\{$panel}\Resources\\{$modelName}\Pages;

use App\Laravilt\\{$panel}\Resources\\{$modelName}\\{$modelName}Resource;
use Laravilt\Actions\DeleteAction;
use Laravilt\Actions\ViewAction;
use Laravilt\Panel\Pages\EditRecord;

class Edit{$modelName} extends EditRecord
{
    protected static ?string \$resource = {$modelName}Resource::class;

    public function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
PHP;
        File::put("{$dir}/Pages/Edit{$modelName}.php", $editContent);

        // View Page
        $viewContent = <<<PHP
<?php

namespace App\Laravilt\\{$panel}\Resources\\{$modelName}\Pages;

use App\Laravilt\\{$panel}\Resources\\{$modelName}\\{$modelName}Resource;
use Laravilt\Actions\DeleteAction;
use Laravilt\Actions\EditAction;
use Laravilt\Panel\Pages\ViewRecord;

class View{$modelName} extends ViewRecord
{
    protected static ?string \$resource = {$modelName}Resource::class;

    public function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
PHP;
        File::put("{$dir}/Pages/View{$modelName}.php", $viewContent);
    }

    protected function generateFormFields(array $columns): string
    {
        $fields = [];

        foreach ($columns as $column) {
            if (in_array($column['name'], $this->excludedColumns)) {
                continue;
            }

            $field = $this->generateFormField($column);
            if ($field) {
                $fields[] = $field;
            }
        }

        return implode("\n\n", $fields);
    }

    protected function generateFormField(array $column): ?string
    {
        $name = $column['name'];
        $type = $column['type'];
        $nullable = $column['nullable'];
        $indent = '                                ';

        $component = $this->columnTypeMap[$type] ?? 'TextInput';
        $required = !$nullable ? "\n{$indent}    ->required()" : '';

        switch ($component) {
            case 'TextInput':
                $extra = '';
                if (str_contains($name, 'email')) {
                    $extra = "\n{$indent}    ->email()";
                } elseif (str_contains($name, 'url') || str_contains($name, 'website')) {
                    $extra = "\n{$indent}    ->url()";
                } elseif (str_contains($name, 'phone') || str_contains($name, 'tel')) {
                    $extra = "\n{$indent}    ->tel()";
                } elseif (in_array($type, ['integer', 'bigint', 'smallint', 'float', 'double', 'decimal'])) {
                    $extra = "\n{$indent}    ->numeric()";
                }
                return "{$indent}TextInput::make('{$name}'){$extra}{$required}\n{$indent}    ->maxLength(255),";

            case 'Textarea':
                return "{$indent}Textarea::make('{$name}'){$required}\n{$indent}    ->rows(3),";

            case 'Toggle':
                return "{$indent}Toggle::make('{$name}')\n{$indent}    ->default(false),";

            case 'DatePicker':
                return "{$indent}DatePicker::make('{$name}'){$required},";

            case 'DateTimePicker':
                return "{$indent}DateTimePicker::make('{$name}'){$required},";

            case 'Select':
                return "{$indent}Select::make('{$name}'){$required}\n{$indent}    ->options([\n{$indent}        // Add options here\n{$indent}    ]),";

            default:
                return "{$indent}TextInput::make('{$name}'){$required},";
        }
    }

    protected function generateFormImports(array $columns): string
    {
        $imports = ['use Laravilt\Forms\Components\TextInput;'];

        $types = collect($columns)->pluck('type')->unique()->toArray();
        $names = collect($columns)->pluck('name')->toArray();

        if (in_array('text', $types) || in_array('json', $types)) {
            $imports[] = 'use Laravilt\Forms\Components\Textarea;';
        }
        if (in_array('boolean', $types)) {
            $imports[] = 'use Laravilt\Forms\Components\Toggle;';
        }
        if (in_array('date', $types)) {
            $imports[] = 'use Laravilt\Forms\Components\DatePicker;';
        }
        if (in_array('datetime', $types) || in_array('timestamp', $types)) {
            $imports[] = 'use Laravilt\Forms\Components\DateTimePicker;';
        }
        if (in_array('enum', $types) || collect($names)->contains(fn ($n) => in_array($n, ['status', 'type', 'role']))) {
            $imports[] = 'use Laravilt\Forms\Components\Select;';
        }

        sort($imports);
        return implode("\n", $imports);
    }

    protected function generateTableColumns(array $columns): string
    {
        $tableColumns = [];
        $indent = '                ';

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $column['type'];

            if ($name === 'deleted_at') {
                continue;
            }

            $extra = '';
            $hidden = '';

            // Add sortable to most columns
            $sortable = '->sortable()';

            // Add searchable to text columns
            $searchable = in_array($name, ['name', 'email', 'title', 'description']) ? '->searchable()' : '';

            // Handle special column types
            if (in_array($name, ['status', 'type', 'role'])) {
                $extra = "\n{$indent}    ->badge()";
            } elseif (in_array($type, ['datetime', 'timestamp'])) {
                $extra = "\n{$indent}    ->dateTime()";
            } elseif ($type === 'date') {
                $extra = "\n{$indent}    ->date()";
            } elseif ($type === 'boolean') {
                $extra = "\n{$indent}    ->badge()";
            } elseif (in_array($type, ['decimal', 'float', 'double']) && str_contains($name, 'price') || str_contains($name, 'amount') || str_contains($name, 'total') || str_contains($name, 'spent')) {
                $extra = "\n{$indent}    ->money('USD')";
            }

            // Hide some columns by default
            if (in_array($name, ['created_at', 'updated_at', 'id'])) {
                $hidden = "\n{$indent}    ->toggleable(isToggledHiddenByDefault: true)";
            }

            $tableColumns[] = "{$indent}TextColumn::make('{$name}'){$searchable}{$sortable}{$extra}{$hidden},";
        }

        return implode("\n\n", $tableColumns);
    }

    protected function generateInfoEntries(array $columns): string
    {
        $entries = [];
        $indent = '                                ';

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $column['type'];

            if (in_array($name, ['id', 'deleted_at', 'password', 'remember_token'])) {
                continue;
            }

            $extra = '';

            if (in_array($name, ['status', 'type', 'role'])) {
                $extra = "\n{$indent}    ->badge()";
            } elseif (in_array($type, ['datetime', 'timestamp'])) {
                $extra = "\n{$indent}    ->dateTime()";
            } elseif ($type === 'date') {
                $extra = "\n{$indent}    ->date()";
            } elseif (in_array($type, ['decimal', 'float', 'double']) && (str_contains($name, 'price') || str_contains($name, 'amount') || str_contains($name, 'total') || str_contains($name, 'spent'))) {
                $extra = "\n{$indent}    ->money('USD')";
            }

            $entries[] = "{$indent}TextEntry::make('{$name}'){$extra},";
        }

        return implode("\n\n", $entries);
    }

    protected function findTitleField(array $columns): string
    {
        $titleCandidates = ['name', 'title', 'label', 'subject', 'heading'];

        foreach ($titleCandidates as $candidate) {
            if (collect($columns)->contains(fn ($col) => $col['name'] === $candidate)) {
                return $candidate;
            }
        }

        // Return first string column
        foreach ($columns as $column) {
            if ($column['type'] === 'string' && !in_array($column['name'], $this->excludedColumns)) {
                return $column['name'];
            }
        }

        return 'id';
    }

    protected function findDescriptionField(array $columns): string
    {
        $descCandidates = ['email', 'description', 'subtitle', 'summary', 'excerpt'];

        foreach ($descCandidates as $candidate) {
            if (collect($columns)->contains(fn ($col) => $col['name'] === $candidate)) {
                return $candidate;
            }
        }

        // Return second string column or created_at
        $stringColumns = collect($columns)->filter(fn ($col) => $col['type'] === 'string' && !in_array($col['name'], $this->excludedColumns))->values();

        if ($stringColumns->count() > 1) {
            return $stringColumns[1]['name'];
        }

        return 'created_at';
    }
}
