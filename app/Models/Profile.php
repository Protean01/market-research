<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'birth_year',
        'age_band',
        'gender',
        'location',
        'employment',
        'income_band',
        'marital_status',
        'education',
        'occupation',
        'food_preference',
        'language',
        'enriched_attributes',
        'is_complete',
        'current_streak',
        'longest_streak',
        'last_survey_at',
        'trust_score',
    ];

    protected $casts = [
        'birth_year' => 'integer',
        'enriched_attributes' => 'array',
        'last_survey_at' => 'datetime',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'trust_score' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function ($profile) {
            if ($profile->birth_year) {
                $age = date('Y') - $profile->birth_year;
                if ($age < 18) {
                    $profile->age_band = 'Under 18';
                } elseif ($age <= 24) {
                    $profile->age_band = '18-24';
                } elseif ($age <= 34) {
                    $profile->age_band = '25-34';
                } elseif ($age <= 44) {
                    $profile->age_band = '35-44';
                } elseif ($age <= 54) {
                    $profile->age_band = '45-54';
                } else {
                    $profile->age_band = '55+';
                }
            }

            // Auto-complete if all fields are filled
            if ($profile->calculateCompletionPercentage() >= 100) {
                $profile->is_complete = true;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function calculateCompletionPercentage(): int
    {
        $fields = [
            'birth_year',
            'gender',
            'location',
            'employment',
            'income_band',
            'marital_status',
            'education',
            'occupation',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (! empty($this->{$field})) {
                $filled++;
            }
        }

        return (int) round(($filled / count($fields)) * 100);
    }
}
