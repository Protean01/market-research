<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyNotificationLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['survey_id', 'user_id', 'sent_at'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
