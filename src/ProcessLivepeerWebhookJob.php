<?php

declare(strict_types=1);

namespace Cranbri\Laravel\Livepeer;

use Cranbri\Laravel\Livepeer\Exceptions\WebhookFailed;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

class ProcessLivepeerWebhookJob extends ProcessWebhookJob
{
    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);

        $connection = $this->convertToString(config('livepeer.webhook_connection'), 'sync');
        $queue = $this->convertToString(config('livepeer.webhook_queue'), 'default');

        $this->onConnection($connection);
        $this->onQueue($queue);
    }

    /**
     * Process the webhook
     *
     * @return void
     */
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;

        if (!is_array($payload)) {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        $event = $this->extractEventFromPayload($payload);

        if ($event === '') {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        event("livepeer-webhooks::{$event}", $this->webhookCall);

        $jobClass = $this->determineJobClass($event);

        if ($jobClass === '') {
            return;
        }

        if (!class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist(
                $jobClass,
                $this->webhookCall,
                $event
            );
        }

        dispatch(new $jobClass($this->webhookCall));
    }

    /**
     * Extract and validate event from payload
     *
     * @param array<string, mixed> $payload
     * @return string
     */
    protected function extractEventFromPayload(array $payload): string
    {
        $event = $payload['event'] ?? '';

        return $this->convertToString($event);
    }

    /**
     * Determine the job class for a given event type
     *
     * @param string $eventType The webhook event type
     * @return string The fully qualified job class name
     */
    protected function determineJobClass(string $eventType): string
    {
        $jobConfigKey = str_replace('.', '_', $eventType);

        $defaultJob = $this->convertToString(
            config('livepeer.webhook_default_job', '')
        );

        $jobClass = $this->convertToString(
            config("livepeer.webhook_jobs.{$jobConfigKey}", $defaultJob)
        );

        return $jobClass;
    }

    /**
     * Convert a mixed value to a string
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    protected function convertToString($value, string $default = ''): string
    {
        if ($value === null) {
            return $default;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        return $default;
    }
}
