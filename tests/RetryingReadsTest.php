<?php

namespace Kraenkvisuell\StatamicBunnyAssets\Tests;

use Illuminate\Support\Facades\Storage;
use Kraenkvisuell\StatamicBunnyAssets\Filesystem\RetryingReads;
use League\Flysystem\Config;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToReadFile;

class RetryingReadsTest extends TestCase
{
    public function test_a_failed_read_is_retried(): void
    {
        $flaky = new class extends InMemoryFilesystemAdapter
        {
            public int $failures = 2;

            public function read(string $path): string
            {
                if ($this->failures-- > 0) {
                    throw UnableToReadFile::fromLocation($path, 'replica out of step');
                }

                return parent::read($path);
            }
        };
        $flaky->write('a.txt', 'hello', new Config);

        $this->assertSame('hello', (new RetryingReads($flaky, retries: 3, delay: 1))->read('a.txt'));
        $this->assertSame(-1, $flaky->failures);
    }

    public function test_reads_give_up_after_the_last_retry(): void
    {
        $this->expectException(UnableToReadFile::class);

        (new RetryingReads(new InMemoryFilesystemAdapter, retries: 2, delay: 1))->read('missing.txt');
    }

    public function test_bunny_disks_are_s3_disks_with_retried_reads(): void
    {
        config(['filesystems.disks.cdn' => [
            'driver' => 'bunny',
            'key' => 'key',
            'secret' => 'secret',
            'region' => 'de',
            'bucket' => 'zone',
            'endpoint' => 'https://storage.example.com',
            'url' => 'https://cdn.example.com',
        ]]);

        $disk = Storage::disk('cdn');

        $this->assertSame('https://cdn.example.com/a.jpg', $disk->url('a.jpg'));
        $this->assertInstanceOf(RetryingReads::class, (fn () => $this->adapter)->call($disk->getDriver()));
    }
}
