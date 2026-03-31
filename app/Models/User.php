<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_CONSUMER = 'consumer';

    public const ROLE_UPLOADER = 'uploader';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canUploadMedia(): bool
    {
        return in_array($this->role, [self::ROLE_UPLOADER, self::ROLE_ADMIN], true) || $this->role === null;
    }

    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }
}
