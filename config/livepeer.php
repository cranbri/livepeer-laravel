<?php

declare(strict_types=1);

use Cranbri\Livepeer\Laravel\LivepeerWebhookProfile;

return [
    /*
    |--------------------------------------------------------------------------
    | Livepeer API Key
    |--------------------------------------------------------------------------
    |
    | This is the API key for the Livepeer service. You can get this from
    | your Livepeer dashboard at https://livepeer.studio/dashboard
    |
    */
    'api_key' => env('LIVEPEER_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Livepeer Webhook Secret
    |--------------------------------------------------------------------------
    |
    | This is the webhook signing secret used to verify the webhook payload.
    | If you've set a secret when creating your webhook on Livepeer, you
    | should set this value to the same secret.
    |
    */
    'webhook_signing_secret' => env('LIVEPEER_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Event Mapping
    |--------------------------------------------------------------------------
    |
    | This array maps Livepeer webhook events to jobs that handle them.
    | By default, all events use the ProcessLivepeerWebhookJob.
    |
    | For example:
    | 'stream.started' => App\Jobs\HandleStreamStartedJob::class,
    |
    */
    'webhook_jobs' => [
        // Map Livepeer webhook events to jobs here
        // 'stream.started' => App\Jobs\HandleStreamStartedJob::class,
        // 'stream.idle' => App\Jobs\HandleStreamIdleJob::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    |
    | The queue connection to use for processing webhook jobs.
    |
    */
    'webhook_queue' => env('LIVEPEER_WEBHOOK_QUEUE', 'default'),
    'webhook_connection' => env('LIVEPEER_WEBHOOK_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model for storing webhook calls.
    | You can extend or replace this model if needed.
    |
    */
    'webhook_model' => Spatie\WebhookClient\Models\WebhookCall::class,

    /**
     * This class determines if the webhook call should be stored and processed.
     */
    'webhook_profile' => LivepeerWebhookProfile::class,

    /*
    |--------------------------------------------------------------------------
    | Verify Signature
    |--------------------------------------------------------------------------
    |
    | Set this to false if you don't want to verify the signature of
    | incoming webhook calls.
    |
    */
    'webhook_verify_signature' => env('LIVEPEER_SIGNATURE_VERIFY', true),
];