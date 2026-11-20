# Changelog

## v0.2.2 – 2026-11-20

- CP thumbnails from the CDN work on every Statamic 6 version again; before, they threw on versions
  older than 6.34 ("Call to undefined method … manipulationCacheKey()").
- Changelog and support note in the README.

## v0.2.1 – 2026-11-20

- README: what the addon adds over a plain S3 disk, and the disk driver change under the install
  command.

## v0.2.0 – 2026-11-20

Renamed from `kraenkvisuell/kraenk-statamic-bunny`. Sites need to change:

- the package: `composer remove kraenkvisuell/kraenk-statamic-bunny && composer require kraenkvisuell/statamic-bunny-assets`
- a published config file: `config/kraenk-statamic-bunny.php` → `config/statamic-bunny-assets.php`
  (config key `statamic-bunny-assets`, publish tag `statamic-bunny-assets-config`)
- the command: `bunny:warm-asset-meta` → `bunny-assets:warm-asset-meta`
- the namespace, where a site references a class: `Kraenkvisuell\StatamicBunnyAssets`

## v0.1.1 – 2026-11-20

- `cdn_thumbnails` and `original_gifs` are checked on every call, so they can be changed at runtime
  (e.g. in tests).

## v0.1.0 – 2026-11-20

First release: locking asset listing, CDN thumbnails, original GIFs, `'driver' => 'bunny'` disks
with retried reads, `bunny:warm-asset-meta` command.
