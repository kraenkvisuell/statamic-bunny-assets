<?php

namespace Kraenkvisuell\StatamicBunny\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Storage;
use Statamic\Assets\AssetContainerContents;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Glide;
use Statamic\Facades\Image;
use Statamic\Imaging\ImageGenerator;

class AssetsTest extends TestCase
{
    public function test_simultaneous_saves_keep_each_others_files_in_the_listing(): void
    {
        $container = AssetContainer::find('assets');

        // two requests that both read the (still empty) listing before either adds its upload
        $first = app(AssetContainerContents::class)->container($container);
        $second = app(AssetContainerContents::class)->container($container);
        $first->all();
        $second->all();

        Storage::disk('assets')->put('uploads/a.jpg', 'a');
        Storage::disk('assets')->put('uploads/b.jpg', 'b');

        $first->add('uploads/a.jpg')->save();
        $second->add('uploads/b.jpg')->save();
        $first->forget('uploads/missing.jpg')->save();

        $listing = $first->cached();

        $this->assertTrue($listing->has('uploads/a.jpg'));
        $this->assertTrue($listing->has('uploads/b.jpg'));
        $this->assertTrue($listing->has('uploads'));
    }

    public function test_a_queue_worker_reads_the_listing_again_for_every_job(): void
    {
        $container = AssetContainer::find('assets');
        $contents = $container->contents();

        $job = new SyncJob(app(), json_encode(['job' => 'test', 'data' => []]), 'redis', 'default');
        event(new JobProcessing('redis', $job));

        $this->assertNotSame($contents, $container->contents());
    }

    public function test_cp_thumbnails_come_from_the_glide_cdn_once_generated(): void
    {
        config([
            'cache.stores.glide' => ['driver' => 'array'],
            'statamic.assets.image_manipulation.cache' => 'glide-test',
        ]);
        Storage::fake('glide-test', ['url' => 'https://cdn.example.com/glide-cache']);

        Storage::disk('assets')->putFileAs('uploads', UploadedFile::fake()->image('photo.jpg', 40, 20), 'photo.jpg');
        $asset = Asset::find('assets::uploads/photo.jpg');

        // not generated yet: Statamic's route, which generates it
        $this->assertStringContainsString('/cp/thumbnails/', $asset->thumbnailUrl('small'));

        $params = ['p' => "cp_thumbnail_small_{$asset->orientation()}"];
        Glide::cacheStore()->forever(
            ImageGenerator::manipulationCacheKey('asset', $asset, $params),
            'containers/assets/uploads/photo.jpg/abc123/photo.jpg',
        );

        $this->assertStringStartsWith(
            'https://cdn.example.com/glide-cache/containers/assets/uploads/photo.jpg/abc123/photo.jpg?v=',
            $asset->thumbnailUrl('small'),
        );
    }

    public function test_gifs_keep_their_original_file_unless_a_format_is_asked_for(): void
    {
        Storage::disk('assets')->put('uploads/anim.gif', 'GIF89a');
        $gif = Asset::find('assets::uploads/anim.gif');

        $this->assertSame('https://assets.example.com/uploads/anim.gif', Image::manipulate($gif, ['w' => 400]));
        $this->assertStringNotContainsString('https://assets.example.com/uploads/anim.gif', Image::manipulate($gif, ['w' => 400, 'fm' => 'jpg']));
    }
}
