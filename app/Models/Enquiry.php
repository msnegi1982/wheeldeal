<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Enquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'vehicle_id',
        'name',
        'phone',
        'email',
        'message',
        'status',
        'admin_notes',
        'source',
        'ip_address',
    ];

    protected $casts = [
        'status' => EnquiryStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Enquiry $enquiry) {
            $enquiry->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function notes()
    {
        return $this->hasMany(EnquiryNote::class)->latest();
    }
}
