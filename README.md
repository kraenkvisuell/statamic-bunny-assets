# Kraenk Statamic Bunny

Fixes and tools for Statamic 6 sites whose assets live on [Bunny Storage](https://bunny.net) (via its
S3 API) behind a Bunny pull zone, with the Glide cache on the same storage. Every part can be switched
off in the config.

```bash
composer require kraenkvisuell/kraenk-statamic-bunny
php artisan vendor:publish --tag=kraenk-statamic-bunny-config   # only to change the defaults
```

## What it does

- **Parallel uploads keep each other's files** (`locking_listing`). Statamic keeps a container's
  whole file listing as one cache item; saving or deleting an asset reads it, adds or removes one
  path and writes the whole listing back. Two of those at once (several files dropped into the CP,
  an upload next to a queued alt text job) overwrite each other, and the loser's file drops out of
  the listing. Statamic then treats it as missing: its meta is rewritten without size, dimensions
  or mime type (`data: {}`, the CP shows "B" and a 1970 date), Glide reports *"does not actually
  appear to be a valid image"*, the file can't be deleted (*"not authorized"*), and every listing of
  its folder rewrites the empty meta again (dozens of storage writes, 15–30 s per request).
  `Assets\LockingAssetContainerContents` takes a cache lock for every change, re-reads the listing
  and writes it back right away. Queue workers also forget their memoized listing before every job
  (Statamic's Blink cache isn't reset between jobs, so a worker saw everything uploaded after its
  first job as missing).
- **CP thumbnails from the Glide CDN** (`cdn_thumbnails`). `/cp/thumbnails/…/small` is a PHP request
  per image even when the thumbnail exists, and a Bunny pull zone sends it to the browser as
  `no-cache` (no file extension), so every visit loads every thumbnail again. Once the route has
  generated a thumbnail, `Assets\CdnThumbnailAsset` links the file on the Glide cache's CDN instead,
  with `?v=<modified>`. Thumbnails not generated yet still go through the route. Needs a Glide cache
  disk with a URL (`statamic.assets.image_manipulation.cache` = a disk name).
- **Original GIFs** (`original_gifs`). Glide re-encodes every frame of an animated GIF, takes
  minutes, upscales small ones and produces files too large for a single upload to Bunny (cURL 56).
  `Imaging\OriginalGifManipulator` serves the original file; asking for a format (`fm`) still goes
  through Glide.
- **`'driver' => 'bunny'` disks**: the S3 driver with retried reads (`Filesystem\RetryingReads`).
  Bunny's replicas are sometimes out of step and answer 404 or an error for a few seconds; one such
  read would abort a Glide render or a meta write. Only failures are retried (`retries`,
  `retry_delay` per disk or in the config); writes pass through.
- **`php artisan bunny:warm-asset-meta`** loads the `.meta/*.yaml` files of all assets into
  Statamic's meta cache, in parallel on S3 disks. Uncached, every image a page renders costs one
  storage request. Run it after a deploy that cleared the cache, on a new server, or after
  `cache:clear asset_meta`.

The listing and thumbnail parts apply to Statamic's file-based assets only; with the Eloquent
driver's asset table (`statamic.eloquent-driver.assets.driver = eloquent`) they stay off.

## Repairing a site that already has broken assets

Files that dropped out of the listing are still on the storage, only their meta is empty. Rebuild
the listing by forgetting its cache key (`Cache::forget('asset-list-contents-<container>')`; not
`assets:clear-cache`, which flushes the whole default cache store when the site has no
`asset_container_contents` store) and regenerate the meta of every file whose `.meta` file has no `mime_type`
(`$asset->writeMeta($asset->generateMeta())`). Their alt texts are missing too, if the site
generates them on upload.

## Releases

Versions are git tags (`v0.1.0`, …). Under 1.0 a minor bump (`v0.2.0`) means sites need changes, a
patch (`v0.1.1`) is a fix. Release: commit, run the tests (`composer test`) and the sites' tests
against the change, `git tag -a v0.x.y -m v0.x.y && git push --follow-tags`; then per site
`composer update kraenkvisuell/kraenk-statamic-bunny`, test, commit `composer.lock`.

## License

MIT, see [LICENSE](LICENSE).
