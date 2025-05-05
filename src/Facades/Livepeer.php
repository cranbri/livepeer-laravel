<?php

declare(strict_types=1);

namespace Cranbri\Livepeer\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed requestAssetUpload(\Cranbri\Livepeer\Data\Asset\UploadAssetData $data)
 * @method static mixed uploadAssetFromUrl(\Cranbri\Livepeer\Data\Asset\UrlUploadAssetData $data)
 * @method static mixed getAsset(string $assetId)
 * @method static mixed updateAsset(string $assetId, \Cranbri\Livepeer\Data\Asset\UpdateAssetData $data)
 * @method static mixed listAssets()
 * @method static mixed deleteAsset(string $assetId)
 * @method static mixed createLivestream(\Cranbri\Livepeer\Data\Livestream\CreateLivestreamData $data)
 * @method static mixed getLivestream(string $streamId)
 * @method static mixed updateLivestream(string $streamId, \Cranbri\Livepeer\Data\Livestream\UpdateLivestreamData $data)
 * @method static mixed listLivestreams(array $filters = [])
 * @method static mixed deleteLivestream(string $streamId)
 * @method static mixed terminateLivestream(string $streamId)
 * @method static mixed addMultistreamTarget(string $streamId, \Cranbri\Livepeer\Data\AddMultistreamTargetData $data)
 * @method static mixed removeMultistreamTarget(string $streamId, string $targetId)
 * @method static mixed createClip(\Cranbri\Livepeer\Data\Livestream\CreateClipData $data)
 * @method static mixed listClips(string $streamId)
 * @method static mixed getTask(string $taskId)
 * @method static mixed listTasks()
 * @method static mixed createMultistreamTarget(\Cranbri\Livepeer\Data\Multistream\CreateTargetData $data)
 * @method static mixed getMultistreamTarget(string $targetId)
 * @method static mixed updateMultistreamTarget(string $targetId, \Cranbri\Livepeer\Data\Multistream\UpdateTargetData $data)
 * @method static mixed listMultistreamTargets(string $userId)
 * @method static mixed deleteMultistreamTarget(string $targetId)
 * @method static mixed getSession(string $sessionId)
 * @method static mixed listSessions()
 * @method static mixed listRecordedSessions(string $parentId)
 * @method static mixed listSessionClips(string $sessionId)
 * @method static mixed createSigningKey()
 * @method static mixed getSigningKey(string $keyId)
 * @method static mixed updateSigningKey(string $keyId, \Cranbri\Livepeer\Data\AccessControl\UpdateSigningKeyData $data)
 * @method static mixed listSigningKeys()
 * @method static mixed deleteSigningKey(string $keyId)
 * @method static mixed createWebhook(\Cranbri\Livepeer\Data\Webhook\CreateWebhookData $data)
 * @method static mixed getWebhook(string $webhookId)
 * @method static mixed updateWebhook(string $webhookId, \Cranbri\Livepeer\Data\Webhook\UpdateWebhookData $data)
 * @method static mixed listWebhooks()
 * @method static mixed deleteWebhook(string $webhookId)
 * @method static mixed getPlaybackInfo(string $playbackId)
 * @method static mixed transcodeVideo(\Cranbri\Livepeer\Data\Transcode\CreateTranscodingData $data)
 * @method static mixed queryRealtimeViewership(array $filters = [])
 * @method static mixed queryViewershipMetrics(array $filters = [])
 * @method static mixed queryUsageMetrics(array $filters = [])
 * @method static mixed queryPublicTotalViewsMetrics(string $playbackId)
 * @method static mixed queryCreatorViewershipMetrics(array $filters = [])
 * @method static \Cranbri\Livepeer\LivepeerConnector connector()
 *
 * @see \Cranbri\Livepeer\Livepeer
 */
class Livepeer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'livepeer';
    }
}