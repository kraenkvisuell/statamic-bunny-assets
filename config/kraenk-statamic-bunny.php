<?php

return [

    /*
    | Asset listing changes take a lock and re-read the listing first, and queue workers forget
    | their memoized listing before every job (Assets\LockingAssetContainerContents). Without it,
    | parallel uploads drop each other's files from the listing and their meta ends up empty.
    | Only with Statamic's file-based assets (not the Eloquent driver's asset table).
    */
    'locking_listing' => true,

    /*
    | CP thumbnails link the generated file on the Glide cache's CDN instead of Statamic's
    | /cp/thumbnails route (Assets\CdnThumbnailAsset). Only when Glide serves its cache directly
    | (a cache disk with a URL) and with Statamic's file-based assets.
    */
    'cdn_thumbnails' => true,

    /*
    | Animated GIFs are served as the original file instead of a Glide copy
    | (Imaging\OriginalGifManipulator). Asking for a format (`fm`) still goes through Glide.
    */
    'original_gifs' => true,

    /*
    | Disks with `'driver' => 'bunny'` are S3 disks whose reads are retried
    | (Filesystem\RetryingReads): `retries` and `retry_delay` (ms, doubled per retry) can be set
    | per disk; these are the defaults.
    */
    'retries' => 3,
    'retry_delay' => 500,

];
