<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Vehicle */
class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'slug' => $this->slug,
            'url' => $this->public_url,
            'status' => $this->status?->value,
            'price' => [
                'amount' => (int) $this->price,
                'formatted' => $this->formatted_price,
                'compact' => format_inr($this->price, true),
                'original' => $this->original_price ? (int) $this->original_price : null,
            ],
            'year' => (int) $this->year,
            'km_driven' => (int) $this->km_driven,
            'formatted_km' => $this->formatted_km,
            'owners' => [
                'count' => (int) $this->owner_count,
                'label' => $this->owner_label,
            ],
            'transmission' => $this->transmission ? [
                'value' => $this->transmission->value,
                'label' => $this->transmission->label(),
            ] : null,
            'condition' => $this->condition ? [
                'value' => $this->condition->value,
                'label' => $this->condition->label(),
            ] : null,
            'fuel_type' => $this->whenLoaded('fuelType', fn () => [
                'id' => $this->fuelType->id,
                'name' => $this->fuelType->name,
                'slug' => $this->fuelType->slug,
            ]),
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->name,
                'slug' => $this->city->slug,
                'state' => $this->city->state,
            ]),
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'logo_url' => $this->brand->logo_url,
            ]),
            'model' => $this->whenLoaded('vehicleModel', fn () => [
                'id' => $this->vehicleModel->id,
                'name' => $this->vehicleModel->name,
                'slug' => $this->vehicleModel->slug,
            ]),
            'variant' => $this->whenLoaded('variant', fn () => $this->variant ? [
                'id' => $this->variant->id,
                'name' => $this->variant->name,
                'slug' => $this->variant->slug,
            ] : null),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'specification_summary' => $this->specification_summary,
            'seats' => (int) $this->seats,
            'colour' => $this->colour,
            'engine_cc' => $this->engine_cc,
            'power_bhp' => $this->power_bhp,
            'torque_nm' => $this->torque_nm,
            'mileage_kmpl' => $this->mileage_kmpl,
            'description' => $this->description,
            'image' => [
                'url' => $this->primary_image_url,
                'alt' => $this->primary_image_alt,
            ],
            'gallery' => $this->gallery_images,
            'has_360' => $this->has_360,
            'features' => $this->whenLoaded('features', fn () => $this->features
                ->map(fn ($feature) => [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'slug' => $feature->slug,
                    'category' => $feature->category,
                ])
                ->values()
                ->all()),
            'is_featured' => (bool) $this->is_featured,
            'is_popular' => (bool) $this->is_popular,
            'published_at' => $this->published_at?->toIso8601String(),
            'seo' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
                'canonical' => $this->public_url,
            ],
        ];
    }
}
