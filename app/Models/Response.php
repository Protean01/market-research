<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    protected $fillable = [
        'survey_id',
        'user_id',
        'answers',
        'enrichment_answers',
        'earned_points',
        'time_taken',
        'is_flagged',
        'flag_reason',
        'quality_score',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'enrichment_answers' => 'array',
            'completed_at' => 'datetime',
            'is_flagged' => 'boolean',
        ];
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function respondent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
