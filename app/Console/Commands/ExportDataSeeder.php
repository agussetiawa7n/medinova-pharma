<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportDataSeeder extends Command
{
    protected $signature = 'db:export-seeders';
    protected $description = 'Export all current DB data as Laravel seeders';

    protected array $tables = [
        'users',
        'settings',
        'categories',
        'brands',
        'products',
        'banners',
        'pages',
        'coupons',
        'wallets',
        'orders',
        'order_items',
        'product_variants',
        'product_reviews',
    ];

    public function handle(): void
    {
        $this->info('Exporting database data to seeders...');

        foreach ($this->tables as $table) {
            $this->exportTable($table);
        }

        // Generate main seeder that calls all table seeders
        $this->generateMainSeeder();

        $this->info('Done! Seeders created in database/seeders/data/');
        $this->info('Run: php artisan db:seed --class=DataSeeder');
    }

    private function exportTable(string $table): void
    {
        $rows = DB::table($table)->get();

        if ($rows->isEmpty()) {
            $this->warn("  ⏭ {$table} — empty, skipped");
            return;
        }

        $className = ucfirst(str_replace('_', '', ucwords($table, '_'))) . 'TableSeeder';
        $rowsArray = $rows->map(fn ($row) => (array) $row)->toArray();

        $export = "<?php\n\nuse Illuminate\\Database\\Seeder;\nuse Illuminate\\Support\\Facades\\DB;\n\n";
        $export .= "class {$className} extends Seeder\n{\n";
        $export .= "    public function run(): void\n    {\n";
        $export .= "        DB::table('{$table}')->delete();\n\n";
        $export .= '        $rows = ' . $this->varExport($rowsArray, 2) . ";\n\n";
        $export .= "        foreach (\$rows as \$row) {\n";
        $export .= "            DB::table('{$table}')->insert(\$row);\n";
        $export .= "        }\n";
        $export .= "    }\n}\n";

        $path = database_path("seeders/data/{$className}.php");
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $export);

        $this->info("  ✓ {$table} — {$rows->count()} rows → {$className}");
    }

    private function generateMainSeeder(): void
    {
        $export = "<?php\n\nuse Illuminate\\Database\\Seeder;\n\n";
        $export .= "class DataSeeder extends Seeder\n{\n";
        $export .= "    public function run(): void\n    {\n";

        // Disable FK checks during seeding
        $export .= "        DB::statement('SET FOREIGN_KEY_CHECKS=0');\n\n";

        foreach ($this->tables as $table) {
            $className = ucfirst(str_replace('_', '', ucwords($table, '_'))) . 'TableSeeder';
            $path = database_path("seeders/data/{$className}.php");
            if (File::exists($path)) {
                $export .= "        \$this->call({$className}::class);\n";
            }
        }

        $export .= "\n        DB::statement('SET FOREIGN_KEY_CHECKS=1');\n";
        $export .= "    }\n}\n";

        File::put(database_path('seeders/DataSeeder.php'), $export);
        $this->info('  ✓ Main DataSeeder created');
    }

    private function varExport(mixed $var, int $indent = 0): string
    {
        $pad = str_repeat(' ', $indent * 4);
        if (is_array($var)) {
            if (empty($var)) return '[]';
            $isList = array_keys($var) === range(0, count($var) - 1);
            $items = [];
            foreach ($var as $k => $v) {
                $key = $isList ? '' : var_export($k, true) . ' => ';
                $items[] = $pad . '    ' . $key . $this->varExport($v, $indent + 1);
            }
            return "[\n" . implode(",\n", $items) . "\n{$pad}]";
        }
        if ($var === null) return 'null';
        if (is_bool($var)) return $var ? 'true' : 'false';
        if (is_int($var) || is_float($var)) return (string) $var;
        return var_export((string) $var, true);
    }
}
