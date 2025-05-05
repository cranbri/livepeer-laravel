<?php

declare(strict_types=1);

namespace Cranbri\Livepeer\Laravel;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class LivepeerWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        return ! WebhookCall::where('name', 'livepeer')->where('payload->id', $request->get('id'))->exists();
    }
}