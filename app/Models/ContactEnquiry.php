<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactEnquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'phone',
        'email',
        'subject',
        'message',
        'status',
        'admin_notes',
        'ip_address',
    ];

    protected $casts = [
        'status' => EnquiryStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (ContactEnquiry $enquiry) {
            $enquiry->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function notes()
    {
        return $this->hasMany(ContactEnquiryNote::class)->latest();
    }
}
