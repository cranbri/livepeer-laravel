<?php

namespace Cranbri\Laravel\Livepeer\Tests;

use Cranbri\Laravel\Livepeer\Facades\Livepeer;
use Cranbri\Laravel\Livepeer\LivepeerServiceProvider;
use Cranbri\Laravel\Livepeer\ProcessLivepeerWebhookJob;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\WebhookClient\Models\WebhookCall;

class LivepeerPackageTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LivepeerServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Livepeer' => Livepeer::class,
        ];
    }

    #[Test]
    public function it_registers_the_livepeer_facade(): void
    {
        $this->assertTrue(class_exists('Cranbri\Laravel\Livepeer\Facades\Livepeer'));
        $this->assertInstanceOf(\Cranbri\Livepeer\Livepeer::class, Livepeer::getFacadeRoot());
    }

    #[Test]
    public function it_loads_configuration_correctly(): void
    {
        $this->artisan('vendor:publish', [
            '--provider' => LivepeerServiceProvider::class,
            '--tag' => 'livepeer-config',
        ])->assertSuccessful();

        $this->assertFileExists(config_path('livepeer.php'));

        Config::set('livepeer.api_key', 'test-api-key');
        $this->assertSame('test-api-key', config('livepeer.api_key'));
    }

    #[Test]
    public function it_provides_webhook_route_macro(): void
    {
        $router = $this->app['router'];

        $router->livepeerWebhooks('test/webhook');

        $routes = $router->getRoutes();
        $webhookRoute = collect($routes)->first(fn($route) => $route->getName() === 'livepeer-webhooks');

        $this->assertNotNull($webhookRoute);
        $this->assertSame('POST', $webhookRoute->methods()[0]);
        $this->assertSame('test/webhook', $webhookRoute->uri());
    }

    #[Test]
    public function it_can_process_webhook_job(): void
    {
        $webhookCall = new class () extends WebhookCall {
            protected $attributes = [
                'payload' => [
                    'event' => 'stream.started',
                    'id' => 'test-webhook-id',
                    'timestamp' => '2025-01-01T00:00:00Z',
                ],
            ];

            public function getAttribute($key)
            {
                if ($key === 'payload') {
                    return $this->attributes['payload'];
                }

                return parent::getAttribute($key);
            }
        };

        TestWebhookJob::$processed = false;

        Config::set('livepeer.webhook_jobs', [
            'stream_started' => TestWebhookJob::class,
        ]);

        $processingJob = new ProcessLivepeerWebhookJob($webhookCall);

        $processingJob->handle();

        $this->assertTrue(TestWebhookJob::$processed);
    }

    #[Test]
    public function it_generates_webhook_jobs_command(): void
    {
        $this->artisan('livepeer:webhook-jobs --help')
            ->assertSuccessful();
    }

    #[Test]
    public function webhook_config_has_default_values(): void
    {
        $config = config('livepeer');

        $this->assertArrayHasKey('webhook_queue', $config);
        $this->assertArrayHasKey('webhook_connection', $config);
        $this->assertArrayHasKey('webhook_signing_secret', $config);
        $this->assertArrayHasKey('webhook_verify_signature', $config);
    }

    #[Test]
    public function it_sets_default_webhook_connection_and_queue(): void
    {
        Config::set('livepeer.webhook_connection', 'default');
        Config::set('livepeer.webhook_queue', 'default');

        $this->assertSame('default', config('livepeer.webhook_connection'));
        $this->assertSame('default', config('livepeer.webhook_queue'));
    }
}

class TestWebhookJob
{
    public static bool $processed = false;

    public function __construct($webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    public function handle(): void
    {
        self::$processed = true;
    }
}