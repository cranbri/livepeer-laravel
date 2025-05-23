<?php

declare(strict_types=1);

namespace Cranbri\Laravel\Livepeer;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class LivepeerWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        /**
         * @var \Illuminate\Database\Query\Builder
         */
        $query = WebhookCall::query();

        return !$query
            ->where('name', 'livepeer')
            ->where('payload->id', $request->get('id'))
            ->exists();
    }
}
