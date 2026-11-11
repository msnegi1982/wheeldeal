<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Vehicle360Test extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_with_24_frames_exposes_an_interactive_360_viewer(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->addMedia($vehicle, 'gallery', 1, 'gallery');
        $this->addMedia($vehicle, '360-frames', 24, 'frame');

        $response = $this->get($this->vehicleUrl($vehicle));

        $response->assertOk()
            ->assertSee('data-360-viewer', false)
            ->assertSee('data-frames=', false)
            ->assertSee('data-360-canvas', false)
            ->assertSee('data-360-prev', false)
            ->assertSee('data-360-next', false)
            ->assertSee('data-360-loading', false)
            ->assertSee('data-360-status', false)
            ->assertSee('data-360-retry', false)
            ->assertSee('360° view', false)
            ->assertSee('/storage/', false);
    }

    public function test_vehicle_with_fewer_than_24_frames_does_not_render_the_360_tab(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->addMedia($vehicle, 'gallery', 1, 'gallery');
        $this->addMedia($vehicle, '360-frames', 23, 'frame');

        $response = $this->get($this->vehicleUrl($vehicle));

        $response->assertOk()
            ->assertDontSee('data-360-viewer', false)
            ->assertDontSee('role="tablist"', false);
    }

    public function test_frame_urls_fall_back_to_the_original_when_a_conversion_is_missing(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->addMedia($vehicle, '360-frames', 24, 'frame');

        $frames = $vehicle->fresh()->frame_urls;

        $this->assertCount(24, $frames);
        $this->assertStringNotContainsString('/conversions/', $frames[0]);
        $this->assertStringEndsWith('.jpg', $frames[0]);
    }

    public function test_listing_queries_load_gallery_media_and_a_360_count_only(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->addMedia($vehicle, 'gallery', 1, 'gallery');
        $this->addMedia($vehicle, '360-frames', 24, 'frame');

        $listingVehicle = app(VehicleRepositoryInterface::class)->latest(10)->first();

        $this->assertNotNull($listingVehicle);
        $this->assertSame(1, $listingVehicle->media->count());
        $this->assertSame('gallery', $listingVehicle->media->first()->collection_name);
        $this->assertSame(24, (int) $listingVehicle->getAttribute('frames_360_count'));
        $this->assertTrue($listingVehicle->has_360);
    }

    private function addMedia(Vehicle $vehicle, string $collection, int $count, string $prefix): void
    {
        for ($index = 1; $index <= $count; $index += 1) {
            $vehicle->media()->create([
                'collection_name' => $collection,
                'name' => $prefix.'-'.$index,
                'file_name' => $prefix.'-'.$index.'.jpg',
                'mime_type' => 'image/jpeg',
                'disk' => 'public',
                'conversions_disk' => 'public',
                'size' => 1024,
                'manipulations' => [],
                'custom_properties' => [],
                'generated_conversions' => [],
                'responsive_images' => [],
                'order_column' => $index,
            ]);
        }
    }

    private function vehicleUrl(Vehicle $vehicle): string
    {
        return route('vehicles.show', [
            'brand' => $vehicle->brand->slug,
            'model' => $vehicle->vehicleModel->slug,
            'vehicle' => $vehicle->slug,
        ]);
    }
}
