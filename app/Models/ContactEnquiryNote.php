<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactEnquiryNote extends Model
{
    protected $fillable = ['contact_enquiry_id', 'user_id', 'note', 'follow_up_at'];

    protected $casts = [
        'follow_up_at' => 'date',
    ];

    public function contactEnquiry()
    {
        return $this->belongsTo(ContactEnquiry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
