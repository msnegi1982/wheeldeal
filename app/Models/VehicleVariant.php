<?php

namespace App\Models;

use App\Enums\Transmission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_model_id',
        'fuel_type_id',
        'name',
        'slug',
        'transmission',
        'engine_cc',
        'power_bhp',
        'torque_nm',
        'mileage_kmpl',
        'seats',
        'price_min',
        'price_max',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'transmission' => Transmission::class,
        'engine_cc' => 'integer',
        'power_bhp' => 'float',
        'torque_nm' => 'integer',
        'mileage_kmpl' => 'float',
        'seats' => 'integer',
        'price_min' => 'integer',
        'price_max' => 'integer',
        'is_active' => 'boolean',
    ];

    public function vehicleModel()
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function fuelType()
    {
        return $this->belongsTo(FuelType::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
