<?php

namespace App\Models;

use App\Enums\Transmission;
use App\Enums\VehicleCondition;
use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Vehicle extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'brand_id',
        'vehicle_model_id',
        'vehicle_variant_id',
        'category_id',
        'fuel_type_id',
        'city_id',
        'year',
        'price',
        'original_price',
        'km_driven',
        'owner_count',
        'transmission',
        'seats',
        'colour',
        'registration_state',
        'registration_year',
        'engine_cc',
        'power_bhp',
        'torque_nm',
        'mileage_kmpl',
        'description',
        'condition',
        'status',
        'is_featured',
        'is_popular',
        'popularity_score',
        'views_count',
        'meta_title',
        'meta_description',
        'og_image',
        'published_at',
        'sold_at',
        'created_by',
    ];

    protected $casts = [
        'status' => VehicleStatus::class,
        'transmission' => Transmission::class,
        'condition' => VehicleCondition::class,
        'year' => 'integer',
        'price' => 'integer',
        'original_price' => 'integer',
        'km_driven' => 'integer',
        'owner_count' => 'integer',
        'seats' => 'integer',
        'registration_year' => 'integer',
        'engine_cc' => 'integer',
        'power_bhp' => 'float',
        'torque_nm' => 'integer',
        'mileage_kmpl' => 'float',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
        'popularity_score' => 'integer',
        'views_count' => 'integer',
        'published_at' => 'datetime',
        'sold_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Vehicle $vehicle) {
            $vehicle->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function fuelType()
    {
        return $this->belongsTo(FuelType::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enquiries()
    {
        return $this->hasMany(Enquiry::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::PUBLISHED->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->published()->where('is_featured', true);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->published()->orderByDesc('popularity_score')->orderByDesc('views_count');
    }

    public function scopeSimilarTo(Builder $query, Vehicle $vehicle): Builder
    {
        return $query->published()
            ->whereKeyNot($vehicle->getKey())
            ->where(function (Builder $query) use ($vehicle) {
                $query->where('vehicle_model_id', $vehicle->vehicle_model_id)
                    ->orWhere(function (Builder $query) use ($vehicle) {
                        $query->where('category_id', $vehicle->category_id)
                            ->whereBetween('price', [
                                max(0, $vehicle->price - 500000),
                                $vehicle->price + 500000,
                            ]);
                    });
            });
    }

    public function getPublicUrlAttribute(): string
    {
        return route('vehicles.show', [
            'brand' => $this->brand?->slug,
            'model' => $this->vehicleModel?->slug,
            'vehicle' => $this->slug,
        ]);
    }

    public function getCanonicalPathAttribute(): string
    {
        return parse_url($this->public_url, PHP_URL_PATH) ?: '/cars/'.$this->slug;
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->year,
            $this->brand?->name,
            $this->vehicleModel?->name,
            $this->variant?->name,
        ])));
    }

    public function getFormattedPriceAttribute(): string
    {
        return '₹'.number_format((int) $this->price);
    }

    public function getFormattedKmAttribute(): string
    {
        return number_format((int) $this->km_driven).' km';
    }

    public function getOwnerLabelAttribute(): string
    {
        return match ((int) $this->owner_count) {
            1 => 'First owner',
            2 => 'Second owner',
            3 => 'Third owner',
            default => $this->owner_count.' owners',
        };
    }

    public function getSpecificationSummaryAttribute(): string
    {
        return implode(' · ', array_filter([
            $this->year,
            $this->fuelType?->name,
            $this->transmission?->label(),
            $this->formatted_km,
        ]));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('360-frames')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_CROP, 420, 260)
            ->format('webp')
            ->quality(82)
            ->performOnCollections('gallery');

        $this->addMediaConversion('card')
            ->fit(Manipulations::FIT_CROP, 700, 440)
            ->format('webp')
            ->quality(84)
            ->performOnCollections('gallery');

        $this->addMediaConversion('large')
            ->fit(Manipulations::FIT_MAX, 1280, 800)
            ->format('webp')
            ->quality(86)
            ->performOnCollections('gallery');

        $this->addMediaConversion('og')
            ->fit(Manipulations::FIT_CROP, 1200, 630)
            ->format('webp')
            ->quality(84)
            ->performOnCollections('gallery');

        $this->addMediaConversion('360')
            ->fit(Manipulations::FIT_MAX, 1200, 800)
            ->format('webp')
            ->quality(84)
            ->performOnCollections('360-frames');
    }

    public function getGalleryImagesAttribute(): array
    {
        return $this->getMedia('gallery')->map(function (Media $media) {
            return [
                'id' => $media->id,
                'url' => $this->mediaConversionUrl($media, 'large'),
                'thumb' => $this->mediaConversionUrl($media, 'card'),
                'thumb_srcset' => $this->mediaSrcset($media, ['thumb' => 420, 'card' => 700]),
                'srcset' => $this->mediaSrcset($media, ['thumb' => 420, 'card' => 700, 'large' => 1280]),
                'alt' => $media->getCustomProperty('alt', $this->title.' — exterior view'),
                'width' => (int) $media->getCustomProperty('width', 1280),
                'height' => (int) $media->getCustomProperty('height', 800),
            ];
        })->all();
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('gallery');

        return $media ? $this->mediaConversionUrl($media, 'card') : null;
    }

    public function getPrimaryImageSrcsetAttribute(): ?string
    {
        $media = $this->getFirstMedia('gallery');

        return $media ? $this->mediaSrcset($media, ['thumb' => 420, 'card' => 700, 'large' => 1280]) : null;
    }

    public function getPrimaryImageAltAttribute(): string
    {
        $media = $this->getFirstMedia('gallery');

        return $media?->getCustomProperty('alt') ?: $this->full_name.' — exterior view';
    }

    protected function mediaConversionUrl(Media $media, string $conversion): string
    {
        return $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }

    /**
     * Build a WebP srcset from the generated conversion sizes.
     *
     * @param  array<string, int>  $conversions
     */
    protected function mediaSrcset(Media $media, array $conversions): string
    {
        $sources = [];

        foreach ($conversions as $conversion => $width) {
            if ($media->hasGeneratedConversion($conversion)) {
                $sources[] = $media->getUrl($conversion).' '.$width.'w';
            }
        }

        return implode(', ', $sources);
    }

    public function getFrameUrlsAttribute(): array
    {
        return $this->getMedia('360-frames')
            ->sortBy(fn (Media $media) => $media->order_column ?? $media->id)
            ->map(fn (Media $media) => $this->mediaConversionUrl($media, '360'))
            ->values()
            ->all();
    }

    public function getHas360Attribute(): bool
    {
        $count = $this->attributes['frames_360_count'] ?? null;

        if ($count !== null) {
            return (int) $count >= 24;
        }

        return $this->getMedia('360-frames')->count() >= 24;
    }
}
