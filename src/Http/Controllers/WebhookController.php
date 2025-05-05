<?php

declare(strict_types=1);

namespace Cranbri\LaravelLivepeer\Http\Controllers;

use Cranbri\Livepeer\Laravel\ProcessLivepeerWebhookJob;
use Cranbri\Livepeer\Laravel\LivepeerSignatureValidator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;

class WebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'livepeer',
            'signing_secret' => config('livepeer.webhook_signing_secret'),
            'signature_header_name' => 'Livepeer-Signature',
            'signature_validator' => LivepeerSignatureValidator::class,
            'webhook_profile' => config('livepeer.webhook_profile'),
            'webhook_model' => config('livepeer.webhook_model'),
            'process_webhook_job' => ProcessLivepeerWebhookJob::class,
        ]);

        return (new WebhookProcessor($request, $webhookConfig))->process();
    }
}