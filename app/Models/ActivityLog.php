<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_id',
        'actor_key',
        'action',
        'method',
        'path',
        'status_code',
        'request_payload',
        'query_params',
        'ip_address',
        'user_agent',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'query_params' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
