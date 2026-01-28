<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeGooglePlacesModelCommand extends Command
{
    protected $signature = 'mfw-google-places:make-geo-model';

    protected $description = 'Create a model with Google Places fields and its migration';

    public function handle(): int
    {
        $modelInput = $this->ask('Enter the model name (e.g., "Geo" or "Location/Geo")');

        if (!$modelInput) {
            $this->error('Model name is required.');

            return self::FAILURE;
        }

        $parentModel = $this->ask('Enter the parent model for the foreign key (e.g., "User" or leave empty for none)', '');

        // Parse model name and path
        $modelInput = str_replace('\\', '/', $modelInput);
        $parts = explode('/', $modelInput);
        $modelName = array_pop($parts);
        $subPath = implode('/', $parts);

        // Build full model class path
        $modelNamespace = 'App\\Models' . ($subPath ? '\\' . str_replace('/', '\\', $subPath) : '');
        $modelClass = $modelNamespace . '\\' . $modelName;

        // Build table name
        $tableName = Str::snake(Str::pluralStudly($modelName));

        // Create the model using Laravel's make:model
        $modelPath = $subPath ? $subPath . '/' . $modelName : $modelName;
        $this->info("Creating model: {$modelClass}");

        $this->call('make:model', ['name' => $modelPath]);

        // Create migration
        $migrationName = 'create_' . $tableName . '_table';
        $migrationContent = $this->buildMigration($tableName, $parentModel);

        $timestamp = date('Y_m_d_His');
        $migrationFileName = $timestamp . '_' . $migrationName . '.php';
        $migrationPath = database_path('migrations/' . $migrationFileName);

        file_put_contents($migrationPath, $migrationContent);

        $this->info("Created migration: {$migrationFileName}");
        $this->newLine();
        $this->info('Done! Don\'t forget to run: php artisan migrate');

        return self::SUCCESS;
    }

    private function buildMigration(string $tableName, string $parentModel): string
    {
        $foreignKey = '';
        $foreignKeyField = '';

        if ($parentModel) {
            $parentModel = str_replace('/', '\\', $parentModel);
            $parentTable = Str::snake(Str::pluralStudly(class_basename($parentModel)));
            $foreignKeyName = Str::snake(class_basename($parentModel)) . '_id';

            $foreignKeyField = <<<PHP
            \$table->foreignId('{$foreignKeyName}')->constrained('{$parentTable}')->cascadeOnDelete();
PHP;
        }

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            {$foreignKeyField}
            \$table->string('text_address')->nullable();
            \$table->string('street_number')->nullable();
            \$table->string('route')->nullable();
            \$table->string('postal_code')->nullable();
            \$table->string('locality')->nullable();
            \$table->string('administrative_area_level_1')->nullable();
            \$table->string('administrative_area_level_1_short')->nullable();
            \$table->string('administrative_area_level_2')->nullable();
            \$table->string('country')->nullable();
            \$table->string('country_code', 10)->nullable();
            \$table->decimal('lat', 10, 7)->nullable();
            \$table->decimal('lon', 10, 7)->nullable();
            \$table->string('place_id')->nullable();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};

PHP;
    }
}
