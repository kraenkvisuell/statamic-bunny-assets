<?php

namespace Kraenkvisuell\StatamicBunny\Assets;

use Closure;
use Statamic\Assets\AssetContainerContents;

/**
 * Statamic keeps a container's whole file listing as one cache item. Saving or deleting an asset
 * reads that item (once per request or worker), adds or removes its own path and writes the whole
 * listing back. Two of those at once – parallel uploads in the CP, an upload next to the alt text
 * job – overwrite each other, and the loser's file drops out of the listing. Statamic then treats
 * it as missing: it rewrites its meta without size, dimensions or mime type (`data: {}`, shown as
 * "B" / "vor 56 Jahren"), Glide calls it "not a valid image" and it can't be deleted.
 *
 * Here every change takes a lock, re-reads the listing from the cache and writes it back right
 * away, so no change gets lost. `save()` has nothing left to do.
 */
class LockingAssetContainerContents extends AssetContainerContents
{
    protected bool $locked = false;

    public function add($path)
    {
        return $this->locked(fn () => parent::add($path));
    }

    public function forget($path)
    {
        return $this->locked(fn () => parent::forget($path));
    }

    public function save()
    {
        //
    }

    protected function locked(Closure $change): static
    {
        // add() calls itself for the parent folders
        if ($this->locked) {
            $change();

            return $this;
        }

        $this->cacheStore()->lock('asset-list-contents-lock-'.$this->container->handle(), 30)
            ->block(20, function () use ($change) {
                $this->locked = true;

                try {
                    $this->files = null;
                    $change();
                    parent::save();
                } finally {
                    $this->locked = false;
                }
            });

        return $this;
    }
}
