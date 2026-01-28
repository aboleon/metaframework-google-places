<?php

declare(strict_types=1);

namespace MetaFramework\GooglePlaces\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeGeoForModelCommand extends Command
{
    protected $signature = 'mfw-google-places:make-geo-for-model {model? : The parent model path}';

    protected $description = 'Create a Google Places address model linked to an existing model';

    public function handle(): int
    {
        $modelInput = $this->argument('model')
            ?? $this->ask('Enter the parent model (e.g., "User", "Account/Client", or "Modules/Hermes/Models/Zone")');

        if (!$modelInput) {
            $this->error('Model name is required.');

            return self::FAILURE;
        }

        // Parse model name and path
        $modelInput = str_replace('\\', '/', $modelInput);
        $parts = explode('/', $modelInput);
        $parentModelName = array_pop($parts);
        $subPath = implode('/', $parts);

        // Determine if this is a full base path or default app/Models path
        $isBasePath = $subPath && !str_starts_with(strtolower($subPath), 'app/models') && str_contains($subPath, '/');

        if ($isBasePath) {
            // Full path from base (e.g., Modules/Hermes/Models/Zone)
            $parentModelPath = base_path($subPath . '/' . $parentModelName . '.php');
            $parentClass = str_replace('/', '\\', $subPath) . '\\' . $parentModelName;
            $newModelPath = $subPath . '/' . $parentModelName . 'Address';
        } else {
            // Default app/Models path
            $parentNamespace = 'App\\Models' . ($subPath ? '\\' . str_replace('/', '\\', $subPath) : '');
            $parentClass = $parentNamespace . '\\' . $parentModelName;
            $parentModelPath = app_path('Models/' . ($subPath ? $subPath . '/' : '') . $parentModelName . '.php');
            $newModelPath = $subPath ? $subPath . '/' . $parentModelName . 'Address' : $parentModelName . 'Address';
        }

        // Check if parent model exists
        if (!file_exists($parentModelPath)) {
            $this->error("Model not found: {$parentClass}");
            $this->error("Expected at: {$parentModelPath}");

            return self::FAILURE;
        }

        // Build new model name: {ParentModel}Address
        $newModelName = $parentModelName . 'Address';

        // Build table names
        // Try to get table name from model, fall back to convention
        $parentTable = $this->getTableNameFromModel($parentClass)
            ?? Str::snake(Str::pluralStudly($parentModelName));
        $newTable = Str::snake(Str::pluralStudly($newModelName));
        $foreignKey = Str::snake($parentModelName) . '_id';

        $this->info("Parent model: {$parentClass}");
        $this->info("Creating: {$newModelName} with foreign key to {$parentTable}");
        $this->newLine();

        // Create the model with belongsTo relationship
        if ($isBasePath) {
            $this->createModelFile($subPath, $newModelName, $parentModelName);
        } else {
            $this->createAppModelFile($subPath, $newModelName, $parentModelName);
        }

        // Add hasOne relationship to parent model
        $this->addHasOneToParentModel($parentModelPath, $newModelName);

        // Create migration
        $migrationContent = $this->buildMigration($newTable, $parentTable, $foreignKey);

        $timestamp = date('Y_m_d_His');
        $migrationFileName = $timestamp . '_create_' . $newTable . '_table.php';
        $migrationPath = database_path('migrations/' . $migrationFileName);

        file_put_contents($migrationPath, $migrationContent);

        $this->info("Created migration: {$migrationFileName}");
        $this->newLine();
        $this->info('Done! Don\'t forget to run: php artisan migrate');

        return self::SUCCESS;
    }

    private function createModelFile(string $path, string $modelName, string $parentModelName): void
    {
        $namespace = str_replace('/', '\\', $path);
        $filePath = base_path($path . '/' . $modelName . '.php');
        $relationMethod = Str::camel($parentModelName);

        $content = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class {$modelName} extends Model
{
    public function {$relationMethod}(): BelongsTo
    {
        return \$this->belongsTo({$parentModelName}::class);
    }
}

PHP;

        file_put_contents($filePath, $content);
        $this->info("Created model: {$filePath}");
    }

    private function createAppModelFile(?string $subPath, string $modelName, string $parentModelName): void
    {
        $namespace = 'App\\Models' . ($subPath ? '\\' . str_replace('/', '\\', $subPath) : '');
        $directory = app_path('Models' . ($subPath ? '/' . $subPath : ''));
        $filePath = $directory . '/' . $modelName . '.php';
        $relationMethod = Str::camel($parentModelName);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $content = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class {$modelName} extends Model
{
    public function {$relationMethod}(): BelongsTo
    {
        return \$this->belongsTo({$parentModelName}::class);
    }
}

PHP;

        file_put_contents($filePath, $content);
        $this->info("Created model: {$filePath}");
    }

    private function addHasOneToParentModel(string $parentModelPath, string $newModelName): void
    {
        $content = file_get_contents($parentModelPath);
        $relationMethod = Str::camel($newModelName);

        // Check if relationship already exists
        if (str_contains($content, "function {$relationMethod}(")) {
            $this->warn("Relationship {$relationMethod}() already exists in parent model.");

            return;
        }

        // Add HasOne import if not present
        if (!str_contains($content, 'use Illuminate\Database\Eloquent\Relations\HasOne;')) {
            $content = preg_replace(
                '/(use Illuminate\\\\Database\\\\Eloquent\\\\[^;]+;)/',
                "$1\nuse Illuminate\\Database\\Eloquent\\Relations\\HasOne;",
                $content,
                1
            );
        }

        // Build the relationship method
        $relationshipMethod = <<<PHP

    public function {$relationMethod}(): HasOne
    {
        return \$this->hasOne({$newModelName}::class);
    }
PHP;

        // Insert before the last closing brace
        $lastBracePos = strrpos($content, '}');
        $content = substr($content, 0, $lastBracePos) . $relationshipMethod . "\n" . substr($content, $lastBracePos);

        file_put_contents($parentModelPath, $content);
        $this->info("Added {$relationMethod}() relationship to parent model.");
    }

    private function getTableNameFromModel(string $parentClass): ?string
    {
        if (!class_exists($parentClass)) {
            return null;
        }

        $model = new $parentClass;

        return $model->getTable();
    }

    private function buildMigration(string $tableName, string $parentTable, string $foreignKey): string
    {
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
            \$table->foreignId('{$foreignKey}')->constrained('{$parentTable}')->cascadeOnDelete();
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
