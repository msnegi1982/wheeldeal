<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = [
        'old_path',
        'new_path',
        'model_type',
        'model_id',
        'status_code',
        'hits',
        'last_hit_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    public static function findForPath(string $path): ?self
    {
        $normalized = '/'.ltrim(parse_url($path, PHP_URL_PATH) ?: '/', '/');

        return static::query()->where('old_path', $normalized)->first();
    }

    public function recordHit(): void
    {
        $this->increment('hits', 1, ['last_hit_at' => now()]);
    }
}
