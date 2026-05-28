<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'email',
        'description',
        'is_active',
    ];

    public function questionTemplates()
    {
        return $this->hasMany(QuestionTemplate::class);
    }
}
