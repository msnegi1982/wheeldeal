<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestNote extends Model
{
    protected $fillable = ['customer_request_id', 'user_id', 'note', 'is_internal'];

    protected $casts = ['is_internal' => 'boolean'];

    public function customerRequest()
    {
        return $this->belongsTo(CustomerRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
