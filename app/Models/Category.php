<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function vehicleModels()
    {
        return $this->hasMany(VehicleModel::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function seoPages()
    {
        return $this->hasMany(SeoPage::class);
    }

    public function getCanonicalPathAttribute(): string
    {
        return route('categories.show', $this->slug, false);
    }

    public function getIconNameAttribute(): string
    {
        return $this->icon ?: Str::slug($this->name);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
