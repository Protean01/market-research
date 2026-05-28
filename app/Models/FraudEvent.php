<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudEvent extends Model
{
    protected $fillable = [
        'device_id',
        'user_id',
        'ip',
        'action',
        'level',
        'message',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
