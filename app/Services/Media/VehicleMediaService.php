<?php

namespace App\Services\Media;

use App\Models\CustomerRequest;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VehicleMediaService
{
    /**
     * @param  array<int, UploadedFile|string>  $files
     * @param  array<int, string>  $altTexts
     */
    public function addGallery(Vehicle $vehicle, array $files, array $altTexts = []): void
    {
        $this->addToCollection($vehicle, $files, 'gallery', $altTexts);
    }

    /**
     * Frames are stored in the same medialibrary collection and ordered by
     * order_column so the viewer can load them deterministically.
     *
     * @param  array<int, UploadedFile|string>  $files
     */
    public function addFrames(Vehicle $vehicle, array $files): void
    {
        $this->addToCollection($vehicle, $files, '360-frames');
    }

    /**
     * @param  array<int, UploadedFile|string>  $files
     */
    public function addRequestPhotos(CustomerRequest $request, array $files): void
    {
        $order = (int) $request->getMedia('request-photos')->max('order_column');

        foreach ($files as $file) {
            $media = $request->addMedia($file)->toMediaCollection('request-photos');
            [$width, $height] = $this->dimensions($file);

            $media->setCustomProperty('width', $width);
            $media->setCustomProperty('height', $height);
            $media->order_column = ++$order;
            $media->save();
        }
    }

    /**
     * @param  array<int, int|string>  $orderedIds
     */
    public function reorder(Vehicle $vehicle, string $collection, array $orderedIds): void
    {
        $media = $vehicle->getMedia($collection)->keyBy('id');

        foreach (array_values($orderedIds) as $position => $id) {
            $item = $media->get((int) $id);

            if (! $item) {
                continue;
            }

            $item->order_column = $position + 1;
            $item->save();
        }
    }

    public function delete(Vehicle|CustomerRequest $model, int $mediaId): void
    {
        /** @var Media|null $media */
        $media = $model->media()->whereKey($mediaId)->first();

        if ($media) {
            $media->delete();
        }
    }

    /**
     * @param  array<int, UploadedFile|string>  $files
     * @param  array<int, string>  $altTexts
     */
    private function addToCollection(Vehicle $vehicle, array $files, string $collection, array $altTexts = []): void
    {
        $order = (int) $vehicle->getMedia($collection)->max('order_column');

        foreach (array_values($files) as $index => $file) {
            $media = $vehicle->addMedia($file)->toMediaCollection($collection);
            [$width, $height] = $this->dimensions($file);

            $media->setCustomProperty(
                'alt',
                $altTexts[$index] ?? $this->defaultAlt($vehicle, $collection)
            );
            $media->setCustomProperty('width', $width);
            $media->setCustomProperty('height', $height);
            $media->order_column = ++$order;
            $media->save();
        }
    }

    private function defaultAlt(Vehicle $vehicle, string $collection): string
    {
        $subject = $vehicle->full_name ?: $vehicle->title;

        return $collection === '360-frames'
            ? $subject.' — 360 degree view'
            : $subject.' — exterior view';
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function dimensions(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! $path || ! is_file($path)) {
            return [null, null];
        }

        $size = @getimagesize($path);

        return $size ? [(int) $size[0], (int) $size[1]] : [null, null];
    }
}
