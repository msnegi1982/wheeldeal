<?php

namespace App\Http\Resources;

use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Enquiry */
class EnquiryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'name' => $this->name,
            'phone' => PhoneNumber::masked($this->phone),
            'email' => $this->email,
            'message' => $this->message,
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->vehicle ? [
                'uuid' => $this->vehicle->uuid,
                'title' => $this->vehicle->title,
                'url' => $this->vehicle->public_url,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
