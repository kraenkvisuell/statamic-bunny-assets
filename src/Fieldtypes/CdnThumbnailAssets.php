<?php

namespace Kraenkvisuell\StatamicBunnyAssets\Fieldtypes;

use Illuminate\Support\Collection;
use Statamic\Contracts\Assets\Asset;
use Statamic\Fieldtypes\Assets\Assets;

/**
 * The assets fieldtype with its listing cells' thumbnails from the Glide CDN.
 *
 * Statamic's preProcessIndex() (the thumbnails in an entry listing's assets column) writes the
 * /cp/thumbnails/…/small route into each cell itself instead of asking the asset, so
 * Assets\CdnThumbnailAsset never got a say there: an artist list loaded every thumbnail through PHP,
 * on every visit. This takes each cell's thumbnail from the asset's thumbnailUrl('small'): the CDN
 * file once it exists, otherwise the same route as before.
 *
 * Fieldtypes extending Statamic's assets fieldtype (a listing cell of their own) can extend this
 * one instead to get the same.
 */
class CdnThumbnailAssets extends Assets
{
    /** @var array<string, Asset> the assets of the current preProcessIndex() */
    protected array $indexAssets = [];

    public function preProcessIndex($data)
    {
        $this->indexAssets = [];

        try {
            $index = parent::preProcessIndex($data);
        } finally {
            $assets = $this->indexAssets;
            $this->indexAssets = [];
        }

        $index['assets'] = collect($index['assets'])->map(function (array $item) use ($assets) {
            if (isset($item['thumbnail'], $assets[$item['id']])) {
                $item['thumbnail'] = $assets[$item['id']]->thumbnailUrl('small');
            }

            return $item;
        })->values()->all();

        return $index;
    }

    // the assets parent::preProcessIndex() has loaded anyway, so no second lookup per cell
    protected function getItemsForPreProcessIndex($values): Collection
    {
        return tap(parent::getItemsForPreProcessIndex($values), function (Collection $assets) {
            foreach ($assets as $asset) {
                $this->indexAssets[$asset->id()] = $asset;
            }
        });
    }
}
