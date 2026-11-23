<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnquiryNote extends Model
{
    protected $fillable = ['enquiry_id', 'user_id', 'note', 'follow_up_at'];

    protected $casts = [
        'follow_up_at' => 'date',
    ];

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
