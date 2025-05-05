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
     *
     * @var string
     */
    protected string $namespace;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $events = array_column(WebhookEvent::cases(), 'value');

        if ($this->option('all')) {
            $selectedEvents = $events;
            $this->info('Generating job classes for all webhook events...');
        } else {
            $selectedEvents = $this->choice(
                'Select webhook events to generate job classes for (multiple choices allowed, comma separated):',
                $events,
                null,
                null,
                true
            );
        }

        $basePath = $this->option('path') ?? app_path('Jobs/Livepeer');
        $this->namespace = $this->option('namespace') ?? $this->determineNamespace($basePath);

        foreach ($selectedEvents as $event) {
            $this->createJobClass($event, $basePath);
        }

        $this->info('Job classes generated successfully!');

        if ($this->option('auto-update-config') || $this->confirm('Do you want to automatically update the livepeer.php config file?',
                true)) {
            $this->updateConfig($selectedEvents);
        } else {
            $this->showManualConfigInstructions($selectedEvents);
        }

        return self::SUCCESS;
    }

    /**
     * Create a job class for the given webhook event.
     *
     * @param  string  $event
     * @param  string  $basePath
     * @return void
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
     * Get the job class name for the given webhook event.
     *
     * @param  string  $event
     * @return string
     */
    protected function getJobClassName(string $event): string
    {
        $parts = explode('.', $event);
        $formattedParts = array_map(function ($part) {
            return ucfirst(Str::camel($part));
        }, $parts);

        return 'Handle'.implode('', $formattedParts).'Job';
    }

    /**
     * Show manual config update instructions.
     *
     * @param  array  $selectedEvents
     * @return void
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
     * @param  array  $selectedEvents
     * @return void
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
     * @param  string  $basePath
     * @return string
     */
    protected function determineNamespace(string $basePath): string
    {
        $namespace = 'App\\';

        $relPath = str_replace(app_path(), '', $basePath);
        $relPath = trim($relPath, '/\\');

        if (!empty($relPath)) {
            $namespacePath = str_replace('/', '\\', $relPath);
            $namespace .= $namespacePath;
        }

        return rtrim($namespace, '\\').'\\';
    }

    /**
     * Get the fully qualified class name.
     *
     * @param  string  $className
     * @return string
     */
    protected function getFullyQualifiedClassName(string $className): string
    {
        return $this->namespace.$className;
    }
}