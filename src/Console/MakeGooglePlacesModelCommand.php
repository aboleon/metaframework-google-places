<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use MetaFramework\GooglePlaces\Console\Concerns\InteractsWithGooglePlacesFields;

class MakeGooglePlacesModelCommand extends Command
{
    use InteractsWithGooglePlacesFields;

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
        $this->updateModelFillable($modelPath);

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
        $foreignKeyField = '';
        $googlePlacesFields = $this->buildGooglePlacesMigrationFieldsString();

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
{$googlePlacesFields}
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

    private function updateModelFillable(string $modelPath): void
    {
        $modelFilePath = app_path('Models/' . $modelPath . '.php');

        if (!file_exists($modelFilePath)) {
            $this->warn("Could not find generated model file: {$modelFilePath}");

            return;
        }

        $content = file_get_contents($modelFilePath);

        if (!is_string($content) || str_contains($content, '$fillable')) {
            return;
        }

        $fillables = $this->buildGooglePlacesFillableString();

        $fillableBlock = <<<PHP

    protected \$fillable = [
{$fillables}
    ];

PHP;

        $updated = preg_replace(
            '/(class\s+\w+\s+extends\s+Model\s*\{\R(?:\s*use [^;]+;\R)?)/',
            '$1' . $fillableBlock,
            $content,
            1,
            $count
        );

        if ($count === 0 || !is_string($updated)) {
            $lastBracePos = strrpos($content, '}');
            if ($lastBracePos === false) {
                return;
            }

            $updated = substr($content, 0, $lastBracePos) . $fillableBlock . substr($content, $lastBracePos);
        }

        file_put_contents($modelFilePath, $updated);
    }
}
