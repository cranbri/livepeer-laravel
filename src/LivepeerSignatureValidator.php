<?php

declare(strict_types=1);

namespace Cranbri\Laravel\Livepeer;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\Exceptions\InvalidWebhookSignature;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class LivepeerSignatureValidator implements SignatureValidator
{
    protected const MAX_TIMESTAMP_DIFF = 300;

    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header($config->signatureHeaderName);

        if (!is_string($signature)) {
            throw new InvalidWebhookSignature('The signature header is missing or invalid.');
        }

        $signingSecret = $config->signingSecret;

        if (empty($signingSecret)) {
            throw InvalidConfig::signingSecretNotSet();
        }

        $sigArray = explode(',', (string)$signature);

        if (count($sigArray) < 2) {
            throw new InvalidWebhookSignature('Invalid signature format');
        }

        $timestampPart = null;
        $signaturePart = null;

        foreach ($sigArray as $part) {
            if (Str::startsWith($part, 't=')) {
                $timestampPart = $part;
            } elseif (Str::startsWith($part, 'v1=')) {
                $signaturePart = $part;
            }
        }

        if (!$timestampPart || !$signaturePart) {
            throw new InvalidWebhookSignature('Missing timestamp or signature parts');
        }

        $timestamp = (int) Str::replaceFirst('t=', '', $timestampPart);
        $signature = Str::replaceFirst('v1=', '', $signaturePart);

        if (!$this->isValidTimestamp($timestamp)) {
            throw new InvalidWebhookSignature('Timestamp out of range');
        }

        $payload = $request->getContent();
        $computedSignature = hash_hmac('sha256', $payload, $signingSecret);

        if (!hash_equals($computedSignature, $signature)) {
            throw new InvalidWebhookSignature('Invalid signature');
        }

        return true;
    }

    protected function isValidTimestamp(int $timestamp): bool
    {
        $now = time();

        return ($now - $timestamp) < static::MAX_TIMESTAMP_DIFF
            && ($timestamp - $now) < 60;
    }
}
