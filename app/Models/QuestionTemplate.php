<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'name',
        'questions',
        'category',
    ];

    protected $casts = [
        'questions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
