@~/Code/coding-guidelines/CLAUDE.md

# CLAUDE.md

Statamic addon `kraenkvisuell/kraenk-statamic-bunny`: what every Statamic 6 site on Bunny Storage +
CDN needs that is code rather than config. See README.md for what it provides. Used by kraenk-26 and,
through `kraenkvisuell/kraenk-statamic-reps`, by the artist representation sites.

- Every part can be switched off in `config/kraenk-statamic-bunny.php`. Don't read `env()` outside
  that file (config caching).
- Sites require tagged releases from Packagist (public repo, MIT); releasing is in README.md. To test
  a change before tagging, point a site at `../kraenk-statamic-bunny` as a path repository locally
  (don't commit that) and run its tests.
- Several classes replace Statamic internals (`AssetContainerContents`, the asset class, the image
  manipulator) and rebuild what Statamic does internally (the CP thumbnail params
  `cp_thumbnail_{size}_{orientation}`, the listing cache key `asset-list-contents-<container>`, the
  Blink key `asset-listing-cache-<container>`). After a Statamic update, run the tests; they fail
  when one of those changes.
- Every test must fail with its feature switched off. The cache store in the tests serializes like
  Redis, otherwise two "requests" share one object and the listing race can't happen.
- Site-specific things (TrustProxies for Bunny's edge IPs, purging the pull zone on static cache
  invalidation) are candidates for this addon too; they live in kraenk-26 / reps for now.
