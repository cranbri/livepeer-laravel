# Livepeer Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cranbri/livepeer-laravel.svg)](https://packagist.org/packages/cranbri/livepeer-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/cranbri/livepeer-laravel.svg)](https://packagist.org/packages/cranbri/livepeer-laravel)
[![License](https://img.shields.io/github/license/cranbri/livepeer-laravel)](LICENSE.md)

A Laravel integration for the [Livepeer Studio API](https://docs.livepeer.org/reference/api/overview). This package provides a convenient way to integrate Livepeer's video streaming and processing services into your Laravel application, including webhook handling.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
    - [Basic Usage](#basic-usage)
    - [Working with Assets](#working-with-assets)
    - [Working with Livestreams](#working-with-livestreams)
    - [Working with Multistreaming](#working-with-multistreaming)
    - [Working with Sessions and Playback](#working-with-sessions-and-playback)
    - [Working with Webhooks](#working-with-webhooks)
    - [Working with Access Control](#working-with-access-control)
    - [Working with Analytics](#working-with-analytics)
- [Webhook Jobs](#webhook-jobs)
    - [Generating Webhook Job Classes](#generating-webhook-job-classes)
    - [Automatic Config Updates](#automatic-config-updates)
    - [Available Webhook Events](#available-webhook-events)
- [Advanced Usage](#advanced-usage)
    - [Stream Profiles](#stream-profiles)
    - [Playback Policies](#playback-policies)
    - [Error Handling](#error-handling)
    - [Custom HTTP Client](#custom-http-client)
- [Testing](#testing)
- [Contributing](#contributing)
- [Security](#security)
- [Credits](#credits)
- [License](#license)

## Installation

You can install the package via composer:

```bash
composer require cranbri/livepeer-laravel
```

The package will automatically register the service provider and facade.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Cranbri\Laravel\Livepeer\LivepeerServiceProvider" --tag="livepeer-config"
```

Add your Livepeer API key to your `.env` file:

```
LIVEPEER_API_KEY=your-api-key
```

If you plan to use webhooks, also add a webhook signing secret:

```
LIVEPEER_WEBHOOK_SECRET=your-webhook-signing-secret
```

## Usage

### Basic Usage

This package provides a Facade for easy access to the Livepeer API:

```php
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

// List all assets
$assets = Livepeer::listAssets();

// Get a specific asset
$asset = Livepeer::getAsset('asset-id');
```

You can also inject the Livepeer client directly:

```php
use Cranbri\Livepeer\Livepeer;

class VideoController extends Controller
{
    public function index(Livepeer $livepeer)
    {
        $assets = $livepeer->listAssets();
        
        return view('videos.index', compact('assets'));
    }
}
```

### Working with Assets

Upload an asset from a URL:

```php
use Cranbri\Livepeer\Data\Asset\UrlUploadAssetData;
use Cranbri\Livepeer\Data\PlaybackPolicyData;
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

$data = new UrlUploadAssetData(
    name: 'My Video',
    url: 'https://example.com/video.mp4',
    playbackPolicy: PlaybackPolicyData::public()
);

$response = Livepeer::uploadAssetFromUrl($data);
```

Request an upload URL for direct upload:

```php
use Cranbri\Livepeer\Data\Asset\UploadAssetData;
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

$data = new UploadAssetData(
    name: 'My Video',
    staticMp4: true
);

$response = Livepeer::requestAssetUpload($data);

// The response contains the upload URL and asset details
$uploadUrl = $response['url'];
$assetId = $response['asset']['id'];
```

Update an asset:

```php
use Cranbri\Livepeer\Data\Asset\UpdateAssetData;
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

$data = new UpdateAssetData(name: 'Updated Video Name');
$response = Livepeer::updateAsset('asset-id', $data);
```

Delete an asset:

```php
$response = Livepeer::deleteAsset('asset-id');
```

### Working with Livestreams

Create a new livestream:

```php
use Cranbri\Livepeer\Data\Livestream\CreateLivestreamData;
use Cranbri\Livepeer\Data\PlaybackPolicyData;
use Cranbri\Livepeer\Data\StreamProfileData;
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

$data = new CreateLivestreamData(
    name: 'My Livestream',
    playbackPolicy: PlaybackPolicyData::public(),
    profiles: [
        StreamProfileData::hd720(),
        StreamProfileData::sd480()
    ],
    record: true
);

$response = Livepeer::createLivestream($data);

// The response contains stream details
$streamId = $response['id'];
$streamKey = $response['streamKey'];
$playbackId = $response['playbackId'];
```

List all livestreams:

```php
$livestreams = Livepeer::listLivestreams();

// With filters
$activeStreams = Livepeer::listLivestreams([
    'streamsonly' => true,
    'filters' => ['active']
]);
```

Update a livestream:

```php
use Cranbri\Livepeer\Data\Livestream\UpdateLivestreamData;
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

$data = new UpdateLivestreamData(
    name: 'Updated Stream Name',
    record: true
);

$response = Livepeer::updateLivestream('stream-id', $data);
```

Terminate a livestream:

```php
$response = Livepeer::terminateLivestream('stream-id');
```

Create a clip from a livestream:

```php
use Cranbri\Livepeer\Data\Livestream\CreateClipData;
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

$data = new CreateClipData(
    playbackId: 'playback-id',
    startTime: 60000,  // in milliseconds
    endTime: 120000,   // in milliseconds
    name: 'My Clip'
);

$response = Livepeer::createClip($data);
```

### Working with Multistreaming

Create a multistream target:

```php
use Cranbri\Livepeer\Data\Multistream\CreateTargetData;
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

$data = new CreateTargetData(
    url: 'rtmp://example.com/live',
    name: 'YouTube Target'
);

$response = Livepeer::createMultistreamTarget($data);
```

Add a multistream target to a livestream:

```php
use Cranbri\Livepeer\Data\AddMultistreamTargetData;
use Cranbri\Livepeer\Data\Livestream\CreateMultistreamTargetData;
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

// Use an existing target
$data = new AddMultistreamTargetData(
    profile: 'source',
    id: 'target-id'
);

// Or create a new target inline
$data = new AddMultistreamTargetData(
    profile: 'source',
    spec: new CreateMultistreamTargetData(
        url: 'rtmp://example.com/live',
        name: 'Facebook Target'
    )
);

$response = Livepeer::addMultistreamTarget('stream-id', $data);
```

Remove a multistream target:

```php
$response = Livepeer::removeMultistreamTarget('stream-id', 'target-id');
```

### Working with Sessions and Playback

Get session details:

```php
$session = Livepeer::getSession('session-id');
```

List all sessions:

```php
$sessions = Livepeer::listSessions();
```

List recorded sessions for a stream:

```php
$recordedSessions = Livepeer::listRecordedSessions('stream-id');
```

Get playback information:

```php
$playbackInfo = Livepeer::getPlaybackInfo('playback-id');
```

### Working with Webhooks

Register a webhook endpoint in your routes:

```php
// routes/web.php or routes/api.php
Route::livepeerWebhooks('webhooks/livepeer');
```

Create a webhook in Livepeer pointing to your endpoint:

```php
use Cranbri\Livepeer\Data\Webhook\CreateWebhookData;
use Cranbri\Livepeer\Enums\WebhookEvent;
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

$data = new CreateWebhookData(
    name: 'My Webhook',
    url: 'https://example.com/webhooks/livepeer',
    events: [
        WebhookEvent::STREAM_STARTED,
        WebhookEvent::STREAM_IDLE,
        WebhookEvent::RECORDING_READY
    ]
);

$response = Livepeer::createWebhook($data);
```

Update a webhook:

```php
use Cranbri\Livepeer\Data\Webhook\UpdateWebhookData;
use Cranbri\Livepeer\Enums\WebhookEvent;

$data = new UpdateWebhookData(
    name: 'Updated Webhook',
    url: 'https://example.com/webhooks/livepeer',
    events: [
        WebhookEvent::STREAM_STARTED,
        WebhookEvent::STREAM_IDLE,
        WebhookEvent::RECORDING_READY,
        WebhookEvent::ASSET_READY
    ]
);

$response = Livepeer::updateWebhook('webhook-id', $data);
```

List all webhooks:

```php
$webhooks = Livepeer::listWebhooks();
```

Delete a webhook:

```php
$response = Livepeer::deleteWebhook('webhook-id');
```

### Working with Access Control

Create a signing key:

```php
$key = Livepeer::createSigningKey();
```

List signing keys:

```php
$keys = Livepeer::listSigningKeys();
```

Update a signing key:

```php
use Cranbri\Livepeer\Data\AccessControl\UpdateSigningKeyData;

$data = new UpdateSigningKeyData(
    name: 'Updated Key',
    disabled: false
);

$response = Livepeer::updateSigningKey('key-id', $data);
```

### Working with Analytics

Query realtime viewership:

```php
$viewers = Livepeer::queryRealtimeViewership([
    'playbackId' => 'playback-id',
    'breakdownBy' => 'country'
]);
```

Query viewership metrics:

```php
$viewershipMetrics = Livepeer::queryViewershipMetrics([
    'fromTime' => '2024-01-01T00:00:00Z',
    'toTime' => '2024-01-31T23:59:59Z',
    'playbackId' => 'playback-id',
    'breakdownBy' => 'browser'
]);
```

Query usage metrics:

```php
$usageMetrics = Livepeer::queryUsageMetrics([
    'fromTime' => '2024-01-01T00:00:00Z',
    'toTime' => '2024-01-31T23:59:59Z',
    'timeStep' => '1d'
]);
```

## Webhook Jobs

This package includes a convenient command to generate job classes for handling Livepeer webhook events.

### Generating Webhook Job Classes

To generate job classes for handling Livepeer webhook events, run:

```bash
php artisan livepeer:webhook-jobs
```

This will prompt you to select which webhook events you want to generate job classes for. You can select multiple events by providing a comma-separated list of numbers.

To generate job classes for all webhook events:

```bash
php artisan livepeer:webhook-jobs --all
```

By default, the job classes will be created in the `app/Jobs/Livepeer` directory. You can specify a custom path using the `--path` option:

```bash
php artisan livepeer:webhook-jobs --path=app/Jobs/Custom/Path
```

You can also specify a custom namespace for the job classes:

```bash
php artisan livepeer:webhook-jobs --namespace="App\\Jobs\\CustomNamespace"
```

### Automatic Config Updates

The command can automatically update your `config/livepeer.php` file to register the generated job classes:

```bash
php artisan livepeer:webhook-jobs --auto-update-config
```

This will add or update the `webhook_jobs` array in your config file with the appropriate class mappings.

You can combine multiple options:

```bash
php artisan livepeer:webhook-jobs --all --auto-update-config --path=app/Jobs/Custom
```

If you don't use the `--auto-update-config` flag, the command will ask if you want to update the config file automatically after generating the job classes.

### Available Webhook Events

Livepeer supports the following webhook events:

- `stream.started` - Fired when a livestream starts
- `stream.idle` - Fired when a livestream becomes idle
- `recording.ready` - Fired when a recording is ready
- `recording.started` - Fired when a recording starts
- `recording.waiting` - Fired when a recording is waiting
- `multistream.connected` - Fired when a multistream target connects
- `multistream.error` - Fired when there's an error with a multistream target
- `multistream.disconnected` - Fired when a multistream target disconnects
- `playback.accessControl` - Fired for playback access control events
- `asset.created` - Fired when an asset is created
- `asset.updated` - Fired when an asset is updated
- `asset.failed` - Fired when asset processing fails
- `asset.ready` - Fired when an asset is ready
- `asset.deleted` - Fired when an asset is deleted
- `task.spawned` - Fired when a task is spawned
- `task.updated` - Fired when a task is updated
- `task.completed` - Fired when a task is completed
- `task.failed` - Fired when a task fails

## Advanced Usage

### Stream Profiles

Livepeer allows you to specify transcoding profiles for your streams:

```php
use Cranbri\Livepeer\Data\StreamProfileData;
use Cranbri\Livepeer\Enums\EncoderType;

// Use predefined profiles
$profiles = [
    StreamProfileData::hd720(),
    StreamProfileData::sd480(),
    StreamProfileData::hd1080(),
    StreamProfileData::uhd4k()
];

// Or create custom profiles
$customProfile = new StreamProfileData(
    bitrate: 2500000,
    name: 'custom-720p',
    width: 1280,
    height: 720,
    fps: 30,
    encoder: EncoderType::H264
);
```

### Playback Policies

Control who can access your content with playback policies:

```php
use Cranbri\Livepeer\Data\PlaybackPolicyData;
use Cranbri\Livepeer\Enums\PlaybackPolicyType;

// Public playback (default)
$publicPolicy = PlaybackPolicyData::public();

// JWT playback (requires signing key)
$jwtPolicy = PlaybackPolicyData::jwt();

// Webhook playback
$webhookPolicy = PlaybackPolicyData::webhook(
    webhookId: 'webhook-id',
    webhookContext: ['user' => 'user-123']
);

// With custom allowed origins
$customPolicy = new PlaybackPolicyData(
    type: PlaybackPolicyType::PUBLIC,
    allowedOrigins: ['https://example.com', 'https://app.example.com']
);
```

### Error Handling

All API errors are converted to `LivepeerException` instances:

```php
use Cranbri\Livepeer\Exceptions\LivepeerException;
use Cranbri\Laravel\Livepeer\Facades\Livepeer;

try {
    $asset = Livepeer::getAsset('invalid-asset-id');
} catch (LivepeerException $e) {
    report($e);
    
    return back()->withError('Unable to retrieve asset: ' . $e->getMessage());
}
```

For webhook errors, the package includes a `WebhookFailed` exception:

```php
use Cranbri\Laravel\Livepeer\Exceptions\WebhookFailed;

try {
    // Process webhook
} catch (WebhookFailed $e) {
    report($e);
    
    return response()->json(['error' => $e->getMessage()], 400);
}
```

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@cranbri.agency instead of using the issue tracker.

## Credits

- [Tom Burman](https://github.com/just-tom)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.