# livepeer


## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Cranbri\Livepeer\Laravel\LivepeerServiceProvider" --tag="config"
```

Add your Livepeer API key to your `.env` file:

```
LIVEPEER_API_KEY=your-api-key
```

## Usage

### Basic Usage

This package provides a Facade for easy access to the Livepeer API:

```php
use Cranbri\Livepeer\Laravel\Facades\Livepeer;

// List all assets
$assets = Livepeer::listAssets();

// Get a specific asset
$asset = Livepeer::getAsset('asset-id');
```

### Working with Assets

Upload an asset from a URL:

```php
use Cranbri\Livepeer\Data\Asset\UrlUploadAssetData;
use Cranbri\Livepeer\Data\PlaybackPolicyData;
use Cranbri\Livepeer\Laravel\Facades\Livepeer;

$data = new UrlUploadAssetData(
    name: 'My Video',
    url: 'https://example.com/video.mp4',
    playbackPolicy: PlaybackPolicyData::public()
);

$response = Livepeer::uploadAssetFromUrl($data);
```

Update an asset:

```php
use Cranbri\Livepeer\Data\Asset\UpdateAssetData;
use Cranbri\Livepeer\Laravel\Facades\Livepeer;

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
use Cranbri\Livepeer\Laravel\Facades\Livepeer;

$data = new CreateLivestreamData(
    name: 'My Livestream',
    playbackPolicy: PlaybackPolicyData::public(),
    record: true
);

$response = Livepeer::createLivestream($data);
```

List all livestreams:

```php
$livestreams = Livepeer::listLivestreams();
```

Update a livestream:

```php
use Cranbri\Livepeer\Data\Livestream\UpdateLivestreamData;
use Cranbri\Livepeer\Laravel\Facades\Livepeer;

$data = new UpdateLivestreamData(name: 'Updated Stream Name');
$response = Livepeer::updateLivestream('stream-id', $data);
```

Terminate a livestream:

```php
$response = Livepeer::terminateLivestream('stream-id');
```

### Working with Multistreaming

Create a multistream target:

```php
use Cranbri\Livepeer\Data\Multistream\CreateTargetData;
use Cranbri\Livepeer\Laravel\Facades\Livepeer;

$data = new CreateTargetData(
    url: 'rtmp://example.com/live',
    name: 'My Target'
);

$response = Livepeer::createMultistreamTarget($data);
```

Add a multistream target to a livestream:

```php
use Cranbri\Livepeer\Data\AddMultistreamTargetData;
use Cranbri\Livepeer\Laravel\Facades\Livepeer;

$data = new AddMultistreamTargetData(
    source: 'source',
    id: 'target-id'
);

$response = Livepeer::addMultistreamTarget('stream-id', $data);
```

### Working with Webhooks

Create a webhook:

```php
use Cranbri\Livepeer\Data\Webhook\CreateWebhookData;
use Cranbri\Livepeer\Enums\WebhookEvent;
use Cranbri\Livepeer\Laravel\Facades\Livepeer;

$data = new CreateWebhookData(
    name: 'My Webhook',
    url: 'https://example.com/webhook',
    events: [WebhookEvent::STREAM_STARTED, WebhookEvent::STREAM_IDLE]
);

$response = Livepeer::createWebhook($data);
```

### Working with Analytics

Query viewership metrics:

```php
$filters = [
    'fromTime' => '2024-01-01T00:00:00Z',
    'toTime' => '2024-01-31T23:59:59Z',
    'playbackId' => 'playback-id'
];

$metrics = Livepeer::queryViewershipMetrics($filters);
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.