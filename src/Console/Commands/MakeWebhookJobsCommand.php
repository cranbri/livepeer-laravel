<?php

declare(strict_types=1);

namespace Cranbri\Laravel\Livepeer\Console\Commands;

use Cranbri\Livepeer\Enums\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeWebhookJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livepeer:webhook-jobs
                            {--all : Generate jobs for all webhook events}
                            {--path= : The path where to create the job classes}
                            {--auto-update-config : Automatically update livepeer.php config file with job classes}
                            {--namespace= : Custom namespace for job classes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate job classes for Livepeer webhook events';

    /**
     * The namespace for the generated job classes
     */
    protected string $namespace = '';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $events = array_column(WebhookEvent::cases(), 'value');

        $selectedEvents = $this->option('all')
            ? $events
            : $this->normalizeSelectedEvents(
                $this->choice(
                    'Select webhook events to generate job classes for (multiple choices allowed, comma separated):',
                    $events,
                    null,
                    null,
                    true
                )
            );

        $basePath = $this->sanitizePath($this->option('path'));

        $this->namespace = $this->sanitizeNamespace(
            $this->option('namespace')
        );

        foreach ($selectedEvents as $event) {
            $this->createJobClass($event, $basePath);
        }

        $this->info('Job classes generated successfully!');

        if ($this->option('auto-update-config') || $this->confirm(
            'Do you want to automatically update the livepeer.php config file?',
            true
        )) {
            $this->updateConfig($selectedEvents);
        } else {
            $this->showManualConfigInstructions($selectedEvents);
        }

        return self::SUCCESS;
    }

    /**
     * Normalize selected events to ensure it's an array of strings
     *
     * @param mixed $events
     * @return array<int, string>
     */
    protected function normalizeSelectedEvents($events): array
    {
        if ($events === null) {
            return [];
        }

        if (is_string($events)) {
            return [$events];
        }

        if (is_array($events)) {
            return array_values(
                array_filter(
                    array_map(
                        fn ($value): string => $this->convertToString($value),
                        $events
                    )
                )
            );
        }

        return [];
    }

    /**
     * Convert a mixed value to a string, with fallback
     *
     * @param mixed $value
     */
    protected function convertToString($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        return '';
    }

    /**
     * Sanitize and validate the path
     *
     * @param mixed $path
     */
    protected function sanitizePath($path): string
    {
        if ($path === null) {
            return app_path('Jobs/Livepeer');
        }

        if (is_array($path)) {
            return app_path('Jobs/Livepeer');
        }

        $pathString = $this->convertToString($path);

        return $pathString ?: app_path('Jobs/Livepeer');
    }

    /**
     * Sanitize and validate the namespace
     *
     * @param mixed $namespace
     */
    protected function sanitizeNamespace($namespace): string
    {
        if ($namespace === null) {
            return 'App\\Jobs\\Livepeer\\';
        }

        if (is_array($namespace)) {
            return 'App\\Jobs\\Livepeer\\';
        }

        $namespaceString = $this->convertToString($namespace);

        if (empty($namespaceString)) {
            return 'App\\Jobs\\Livepeer\\';
        }

        $sanitized = preg_replace('/[^a-zA-Z0-9_\\\\]/', '', $namespaceString) ?: '';

        return rtrim($sanitized, '\\') . '\\';
    }

    /**
     * Create a job class for the given webhook event.
     *
     * @param string $event The webhook event name
     * @param string $basePath The base path for job class generation
     */
    protected function createJobClass(string $event, string $basePath): void
    {
        $className = $this->getJobClassName($event);
        $directory = $basePath;

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = $directory.'/'.$className.'.php';

        if (File::exists($path)) {
            if (!$this->confirm("The job class {$className} already exists. Do you want to overwrite it?")) {
                $this->info("Skipped {$className}.");

                return;
            }
        }

        $stub = File::get(__DIR__.'/stubs/webhook-job.stub');

        $eventParts = explode('.', $event);
        $eventType = ucfirst($eventParts[0]);
        $eventAction = isset($eventParts[1]) ? ucfirst($eventParts[1]) : '';
        $eventDescription = trim($eventType.' '.$eventAction);

        $content = str_replace(
            ['{{class}}', '{{event}}', '{{eventDescription}}'],
            [$className, $event, $eventDescription],
            $stub
        );

        File::put($path, $content);

        $this->info("Created job class: {$className}");
    }

    /**
     * Show manual config update instructions.
     *
     * @param array<int, string> $selectedEvents List of selected webhook events
     */
    protected function showManualConfigInstructions(array $selectedEvents): void
    {
        $this->info('Please register your webhook job classes in the livepeer.php config file:');
        $this->line("'webhook_jobs' => [");

        foreach ($selectedEvents as $event) {
            $className = $this->getJobClassName($event);
            $escapedEvent = str_replace('.', '_', $event);
            $this->line("    '$escapedEvent' => {$this->getFullyQualifiedClassName($className)}::class,");
        }

        $this->line("],");
    }

    /**
     * Update the livepeer.php config file with the generated job classes.
     *
     * @param array<int, string> $selectedEvents List of selected webhook events
     */
    protected function updateConfig(array $selectedEvents): void
    {
        $configPath = config_path('livepeer.php');

        if (!File::exists($configPath)) {
            $this->error("Config file not found: $configPath");
            $this->warn("Please publish the config file first using:");
            $this->line("php artisan vendor:publish --provider=\"Cranbri\\Laravel\\Livepeer\\LivepeerServiceProvider\" --tag=\"livepeer-config\"");

            return;
        }

        $configContent = File::get($configPath);

        /** @var array<string, string> $jobEntries */
        $jobEntries = [];
        foreach ($selectedEvents as $event) {
            $className = $this->getJobClassName($event);
            $escapedEvent = str_replace('.', '_', $event);
            $jobEntries[$escapedEvent] = $this->getFullyQualifiedClassName($className);
        }

        if (preg_match("/'webhook_jobs'\s*=>\s*\[\s*(.*?)\s*\]/s", $configContent, $matches)) {
            $existingContent = $matches[0];
            $updatedContent = "'webhook_jobs' => [\n";

            foreach ($jobEntries as $event => $className) {
                $updatedContent .= "        '$event' => $className::class,\n";
            }
            $updatedContent .= "    ]";

            $configContent = str_replace($existingContent, $updatedContent, $configContent);
        } else {
            $lastBracketPos = strrpos($configContent, ']');

            if ($lastBracketPos !== false) {
                $jobsConfig = "\n    'webhook_jobs' => [\n";

                foreach ($jobEntries as $event => $className) {
                    $jobsConfig .= "        '$event' => $className::class,\n";
                }

                $jobsConfig .= "    ],\n";

                $configContent = substr_replace($configContent, $jobsConfig, $lastBracketPos, 0);
            } else {
                $this->error("Could not find a suitable place to insert webhook_jobs configuration.");
                $this->showManualConfigInstructions($selectedEvents);

                return;
            }
        }

        File::put($configPath, $configContent);

        $this->info('Config file updated successfully!');
    }

    /**
     * Determine the namespace based on the base path.
     *
     * @param string $basePath The base path for job classes
     * @return string The determined namespace
     */
    protected function determineNamespace(string $basePath): string
    {
        $baseAppPath = app_path();
        $relativePath = str_replace($baseAppPath, '', $basePath);
        $relativePath = trim($relativePath, '/\\');

        $namespace = $relativePath
            ? 'App\\' . str_replace('/', '\\', $relativePath) . '\\'
            : 'App\\Jobs\\Livepeer\\';

        return $namespace;
    }

    /**
     * Get the fully qualified class name.
     *
     * @param string $className The base class name
     * @return string The fully qualified class name
     */
    protected function getFullyQualifiedClassName(string $className): string
    {
        return $this->namespace . $className;
    }

    /**
     * Get the job class name for the given webhook event.
     *
     * @param string $event The webhook event name
     * @return string The generated job class name
     */
    protected function getJobClassName(string $event): string
    {
        $parts = explode('.', $event);
        $formattedParts = array_map(function ($part) {
            return ucfirst(Str::camel($part));
        }, $parts);

        return 'Handle'.implode('', $formattedParts).'Job';
    }
}
