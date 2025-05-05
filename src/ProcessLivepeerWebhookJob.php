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
        $this->onConnection(config('livepeer.webhook_connection'));
        $this->onQueue(config('livepeer.webhook_queue'));
    }

    public function handle()
    {
        if (! isset($this->webhookCall->payload['event']) || $this->webhookCall->payload['event'] === '') {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        event("livepeer-webhooks::{$this->webhookCall->payload['event']}", $this->webhookCall);

        $jobClass = $this->determineJobClass($this->webhookCall->payload['event']);

        if ($jobClass === '') {
            return;
        }

        if (! class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this->webhookCall);
        }

        dispatch(new $jobClass($this->webhookCall));
    }

    protected function determineJobClass(string $eventType): string
    {
        $jobConfigKey = str_replace('.', '_', $eventType);

        $defaultJob = config('livepeer.webhook_default_job', '');

        return config("livepeer.webhook_jobs.{$jobConfigKey}", $defaultJob);
    }
}