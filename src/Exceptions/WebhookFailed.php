<?php

declare(strict_types=1);

namespace Cranbri\Laravel\Livepeer\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;

class WebhookFailed extends Exception
{
    /**
     * Create an exception for non-existent job class
     *
     * @param string $jobClass The job class that does not exist
     * @param WebhookCall $webhookCall The webhook call instance
     * @param string $event The webhook event
     * @return self
     */
    public static function jobClassDoesNotExist(string $jobClass, WebhookCall $webhookCall, string $event): self
    {
        return new self(
            sprintf(
                "Could not process webhook id `%s` of event `%s` because the configured jobclass `%s` does not exist.",
                $webhookCall->id ?? 'unknown',
                $event,
                $jobClass
            )
        );
    }

    /**
     * Create an exception for missing webhook type
     *
     * @param WebhookCall $webhookCall The webhook call instance
     * @return self
     */
    public static function missingType(WebhookCall $webhookCall): self
    {
        return new self(
            sprintf(
                "Webhook call id `%s` did not contain a type. Valid Livepeer webhook calls should always contain a type.",
                $webhookCall->id ?? 'unknown'
            )
        );
    }

    /**
     * Render the exception as an HTTP response
     *
     * @param Request $request The incoming HTTP request
     * @return JsonResponse
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json(
            ['error' => $this->getMessage()],
            400
        );
    }
}
