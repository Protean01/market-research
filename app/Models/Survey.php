<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'title',
        'description',
        'reward_amount',
        'reward_points',
        'reward_type',
        'prize_name',
        'status',
        'is_active',
        'response_cap',
        'response_count',
        'questions',
        'enrichment_questions',
        'target_gender',
        'target_age_band',
        'target_location',
        'target_language',
        'target_employment',
        'target_income_band',
        'target_traits',
        'exclude_traits',
        'estimated_time',
        'draw_phase_active',
        'prizes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'draw_phase_active' => 'boolean',
        'questions' => 'array',
        'enrichment_questions' => 'array',
        'target_traits' => 'array',
        'exclude_traits' => 'array',
        'prizes' => 'array',
    ];

    public function getPrizeDrawEnabledAttribute(): bool
    {
        return $this->reward_type === 'prize_draw';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function responses()
    {
        return $this->hasMany(Response::class);
    }

    public function prizeDrawEntries()
    {
        return $this->hasMany(PrizeDrawEntry::class);
    }
}
