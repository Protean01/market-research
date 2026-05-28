<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrizeDrawEntry extends Model
{
    protected $fillable = [
        'survey_id',
        'user_id',
        'points_entered',
        'points_spent',
        'draw_name',
        'is_winner',
        'won_at',
        'prize_index',
        'prize_snapshot',
        'has_spun',
        'prize_delivered',
        'delivered_at',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
        'has_spun' => 'boolean',
        'prize_delivered' => 'boolean',
        'won_at' => 'datetime',
        'delivered_at' => 'datetime',
        'prize_snapshot' => 'array',
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
