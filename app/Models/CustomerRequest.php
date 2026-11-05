<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CustomerRequest extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'uuid',
        'type',
        'status',
        'name',
        'phone',
        'email',
        'city_id',
        'brand_id',
        'vehicle_model_id',
        'vehicle_variant_id',
        'year',
        'km_driven',
        'expected_price',
        'condition',
        'colour',
        'message',
        'consent',
        'otp_verified_at',
        'admin_notes',
        'assigned_to',
        'source',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'type' => RequestType::class,
        'status' => RequestStatus::class,
        'year' => 'integer',
        'km_driven' => 'integer',
        'expected_price' => 'integer',
        'consent' => 'boolean',
        'otp_verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerRequest $request) {
            $request->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function vehicleModel()
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function variant()
    {
        return $this->belongsTo(VehicleVariant::class, 'vehicle_variant_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes()
    {
        return $this->hasMany(RequestNote::class)->latest();
    }

    public function otpVerifications()
    {
        return $this->morphMany(OtpVerification::class, 'verifiable');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('request-photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('request_thumb')
            ->fit(Manipulations::FIT_CROP, 500, 380)
            ->format('webp')
            ->quality(78)
            ->performOnCollections('request-photos');
    }

    public function isVerified(): bool
    {
        return $this->otp_verified_at !== null;
    }

    public function getFormattedExpectedPriceAttribute(): string
    {
        return $this->expected_price ? '₹'.number_format($this->expected_price) : 'Not specified';
    }

    public function getVehicleSummaryAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->year,
            $this->brand?->name,
            $this->vehicleModel?->name,
            $this->variant?->name,
        ]))) ?: 'Vehicle not specified';
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('otp_verified_at');
    }
}
