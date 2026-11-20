# Changelog

## v0.2.5 – 2026-11-20

- Tested on Statamic 6.0.0 as well as the latest 6.x: the listing cells test matches cells by asset
  id (older 6.x versions put no basename into them). No change to the addon itself.
- GitHub Actions run the tests on the newest and the oldest supported versions.

## v0.2.4 – 2026-11-20

- Fix for v0.2.3: the replaced assets fieldtype keeps the handle `assets`. With the handle from its
  class name the CP found no component for it, and listing cells showed the raw JSON.

## v0.2.3 – 2026-11-20

- CP thumbnails from the CDN in entry listings' assets cells too (`Fieldtypes\CdnThumbnailAssets`):
  Statamic writes the thumbnail route into them without asking the asset, so a list with an image
  column loaded every thumbnail through PHP. Fieldtypes extending Statamic's assets fieldtype can
  extend `CdnThumbnailAssets` to get the same.

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
