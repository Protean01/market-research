<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['admin_id', 'export_type', 'filters', 'row_count', 'created_at'];

    protected $casts = [
        'filters'    => 'array',
        'created_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
