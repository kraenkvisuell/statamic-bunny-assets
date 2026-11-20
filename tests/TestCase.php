<?php

namespace Kraenkvisuell\StatamicBunnyAssets\Tests;

use Illuminate\Support\Facades\Storage;
use Kraenkvisuell\StatamicBunnyAssets\ServiceProvider;
use Statamic\Facades\AssetContainer;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

abstract class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('assets', ['url' => 'https://assets.example.com']);
        AssetContainer::make('assets')->disk('assets')->save();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // like Redis: every read gets its own copy, not the object another request changes
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array.serialize', true);
        $app['config']->set('statamic.editions.pro', true);
    }
}
