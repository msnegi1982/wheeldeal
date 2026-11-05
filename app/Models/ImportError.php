<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportError extends Model
{
    protected $fillable = ['import_batch_id', 'row_number', 'column', 'message', 'row_data'];

    protected $casts = ['row_data' => 'array'];

    public function batch()
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
