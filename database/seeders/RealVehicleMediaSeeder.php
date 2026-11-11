<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class RealVehicleMediaSeeder extends Seeder
{
    /**
     * Real studio photos used for the two demo vehicles that expose the 360 viewer.
     *
     * The source frames are 36-angle exterior spins downloaded from CarDekho.
     * They are kept locally so seeding never depends on a third-party request.
     * Replace these with your own photography before using the demo in production.
     */
    private array $vehicles = [
        [
            'slug' => '2022-toyota-fortuner-zx-1',
            'folder' => 'fortuner',
            'colour' => 'Attitude Black',
            'start' => 33,
        ],
        [
            'slug' => '2021-toyota-glanza-vxi-2',
            'folder' => 'glanza',
            'colour' => 'Sparkle Black',
            'start' => 0,
        ],
    ];

    public function run(): void
    {
        foreach ($this->vehicles as $demo) {
            $vehicle = Vehicle::query()->where('slug', $demo['slug'])->first();

            if (! $vehicle) {
                continue;
            }

            $frames = $this->frameFiles($demo['folder']);

            if (count($frames) < 24) {
                $this->command?->warn("No real 360 frames found for {$demo['slug']}.");

                continue;
            }

            // Put the showroom's chosen front view at index zero so the
            // gallery hero and the first 360 frame show the same angle.
            $frames = $this->rotateFrames($frames, $demo['start']);

            $vehicle->clearMediaCollection('gallery');
            $vehicle->clearMediaCollection('360-frames');

            $this->attachGallery($vehicle, $frames);
            $this->attachFrames($vehicle, $frames);

            $vehicle->forceFill(['colour' => $demo['colour']])->save();
        }
    }

    /**
     * @return array<int, string>
     */
    private function frameFiles(string $folder): array
    {
        $files = glob(database_path("seeders/assets/demo-cars/{$folder}/360/frame-*.jpg")) ?: [];
        sort($files, SORT_NATURAL);

        return $files;
    }

    /**
     * @param  array<int, string>  $frames
     * @return array<int, string>
     */
    private function rotateFrames(array $frames, int $start): array
    {
        return array_merge(array_slice($frames, $start), array_slice($frames, 0, $start));
    }

    /**
     * @param  array<int, string>  $frames
     */
    private function attachGallery(Vehicle $vehicle, array $frames): void
    {
        $positions = [0, 6, 12, 18, 24, 30];

        foreach ($positions as $galleryIndex => $offset) {
            $frame = $frames[$offset % count($frames)];

            $media = $vehicle->addMedia($frame)
                ->preservingOriginal()
                ->usingFileName($vehicle->slug.'-'.($galleryIndex + 1).'.jpg')
                ->withCustomProperties([
                    'alt' => "{$vehicle->title} - exterior view ".($galleryIndex + 1),
                    'width' => 930,
                    'height' => 620,
                    'source' => 'cardekho-demo',
                ])
                ->toMediaCollection('gallery', 'public');

            $media->order_column = $galleryIndex + 1;
            $media->save();
        }
    }

    /**
     * @param  array<int, string>  $frames
     */
    private function attachFrames(Vehicle $vehicle, array $frames): void
    {
        foreach ($frames as $index => $frame) {
            $number = $index + 1;

            $media = $vehicle->addMedia($frame)
                ->preservingOriginal()
                ->usingFileName($vehicle->slug.'-frame-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).'.jpg')
                ->withCustomProperties([
                    'alt' => "{$vehicle->title} 360 degree view - frame {$number}",
                    'width' => 930,
                    'height' => 620,
                    'source' => 'cardekho-demo',
                ])
                ->toMediaCollection('360-frames', 'public');

            $media->order_column = $number;
            $media->save();
        }
    }
}
