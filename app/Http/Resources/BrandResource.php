<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Brand */
class BrandResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'initials' => $this->initials,
            'url' => route('brands.show', $this->slug),
            'vehicles_count' => isset($this->published_vehicles_count) ? (int) $this->published_vehicles_count : null,
            'is_featured' => (bool) $this->is_featured,
        ];
    }
}
