<?php

namespace App\Http\Resources;

use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CustomerRequest */
class CustomerRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'name' => $this->name,
            'phone' => PhoneNumber::masked($this->phone),
            'email' => $this->email,
            'vehicle_summary' => $this->vehicle_summary,
            'city' => $this->whenLoaded('city', fn () => $this->city?->name),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand?->name),
            'model' => $this->whenLoaded('vehicleModel', fn () => $this->vehicleModel?->name),
            'year' => $this->year,
            'km_driven' => $this->km_driven,
            'expected_price' => $this->expected_price
                ? [
                    'amount' => (int) $this->expected_price,
                    'formatted' => $this->formatted_expected_price,
                ]
                : null,
            'condition' => $this->condition,
            'colour' => $this->colour,
            'message' => $this->message,
            'otp_verified' => $this->isVerified(),
            'otp_verified_at' => $this->otp_verified_at?->toIso8601String(),
            'verify_url' => route('requests.verify', $this->uuid),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
