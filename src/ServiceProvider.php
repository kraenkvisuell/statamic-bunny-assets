<?php

namespace Kraenkvisuell\StatamicBunnyAssets;

use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kraenkvisuell\StatamicBunnyAssets\Assets\CdnThumbnailAsset;
use Kraenkvisuell\StatamicBunnyAssets\Assets\LockingAssetContainerContents;
use Kraenkvisuell\StatamicBunnyAssets\Filesystem\RetryingReads;
use Kraenkvisuell\StatamicBunnyAssets\Imaging\OriginalGifManipulator;
use League\Flysystem\Filesystem as Flysystem;
use Statamic\Assets\AssetContainerContents;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Imaging\ImageManipulator;
use Statamic\Contracts\Imaging\UrlBuilder;
use Statamic\Facades\Blink;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        Console\Commands\WarmAssetMeta::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/statamic-bunny-assets.php', 'statamic-bunny-assets');
    }

    public function bootAddon(): void
    {
        $this->publishes([
            __DIR__.'/../config/statamic-bunny-assets.php' => config_path('statamic-bunny-assets.php'),
        ], 'statamic-bunny-assets-config');

        $this->registerDisk();

        // the Eloquent driver's asset table has its own listing and asset class
        $fileAssets = config('statamic.eloquent-driver.assets.driver', 'file') !== 'eloquent';

        if ($fileAssets && config('statamic-bunny-assets.locking_listing')) {
            $this->app->bind(AssetContainerContents::class, LockingAssetContainerContents::class);

            // A queue worker keeps the listing it read first (Blink isn't reset between jobs), so
            // files uploaded since then look missing and their meta gets overwritten empty.
            Event::listen(JobProcessing::class, fn () => Blink::flushStartingWith('asset-listing-cache-'));
        }

        // cdn_thumbnails and original_gifs are checked on every call, so they can change at runtime
        if ($fileAssets) {
            $this->app->bind(Asset::class, CdnThumbnailAsset::class);
        }

        $this->app->bind(ImageManipulator::class, fn ($app) => new OriginalGifManipulator($app->make(UrlBuilder::class)));
    }

    // `'driver' => 'bunny'`: the S3 driver with retried reads (see Filesystem\RetryingReads)
    protected function registerDisk(): void
    {
        Storage::extend('bunny', function ($app, array $config) {
            $s3 = Storage::createS3Driver($config);
            $adapter = new RetryingReads(
                $s3->getAdapter(),
                $config['retries'] ?? config('statamic-bunny-assets.retries', 3),
                $config['retry_delay'] ?? config('statamic-bunny-assets.retry_delay', 500),
            );
            $filesystem = new Flysystem($adapter, Arr::only($config, [
                'directory_visibility', 'disable_asserts', 'retain_visibility', 'temporary_url', 'url', 'visibility',
            ]));

            return new AwsS3V3Adapter($filesystem, $s3->getAdapter(), $s3->getConfig(), $s3->getClient());
        });
    }
}
