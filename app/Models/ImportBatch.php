<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'original_name',
        'stored_path',
        'disk',
        'status',
        'total_rows',
        'processed_rows',
        'success_rows',
        'error_rows',
        'error_report_path',
        'options',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => ImportStatus::class,
        'options' => 'array',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'success_rows' => 'integer',
        'error_rows' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ImportBatch $batch) {
            $batch->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function errors()
    {
        return $this->hasMany(ImportError::class);
    }

    public function getProgressAttribute(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }

    public function isFinished(): bool
    {
        return $this->status instanceof ImportStatus
            ? $this->status->isFinished()
            : in_array($this->status, ['completed', 'failed'], true);
    }
}
