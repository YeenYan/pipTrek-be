<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Create a new DDD Module';

    public function handle()
    {
        $name = $this->argument('name');

        // Normalize name (New_Module → NewModule)
        $moduleName = Str::studly($name);

        $basePath = base_path("src/Modules/{$moduleName}");

        if (File::exists($basePath)) {
            $this->error("Module already exists!");
            return;
        }

        // 🔹 CREATE DIRECTORIES
        $directories = [
            "Domain",
            "Application/Exceptions",
            "Application/Services",
            "GraphQL/Resolvers",
            "Infrastructure/Database/migrations",
            "Infrastructure/Repositories",
        ];

        foreach ($directories as $dir) {
            File::makeDirectory("{$basePath}/{$dir}", 0755, true);
        }

        // 🔹 CREATE FILES
        $this->createServiceProvider($basePath, $moduleName);
        $this->createGraphQLFiles($basePath);
        $this->createDomainStub($basePath, $moduleName);

        $this->info("✅ Module {$moduleName} created successfully.");
    }

    // =========================
    // SERVICE PROVIDER
    // =========================
    protected function createServiceProvider($basePath, $moduleName)
    {
        $path = "{$basePath}/{$moduleName}ServiceProvider.php";

        File::put($path, <<<PHP
<?php

namespace Src\Modules\\{$moduleName};

use Illuminate\Support\ServiceProvider;

class {$moduleName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
PHP);
    }

    // =========================
    // GRAPHQL FILES
    // =========================
    protected function createGraphQLFiles($basePath)
    {
        $files = [
            "inputs.graphql",
            "types.graphql",
            "queries.graphql",
            "mutations.graphql",
        ];

        foreach ($files as $file) {
            File::put("{$basePath}/GraphQL/{$file}", "# {$file}");
        }
    }

    // =========================
    // DOMAIN STUB
    // =========================
    protected function createDomainStub($basePath, $moduleName)
    {
        $className = 'SampleEntity';

        File::put("{$basePath}/Domain/{$className}.php", <<<PHP
            <?php

                namespace Src\Modules\\{$moduleName}\Domain;

            class {$className}
            {
                //
            }
            PHP);
                }
}