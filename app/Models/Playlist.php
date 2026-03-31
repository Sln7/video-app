<?php

namespace App\Models;

use App\Traits\PublicIdTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Playlist extends Model
{
    use HasFactory, PublicIdTrait;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->belongsToMany(Media::class, 'playlist_media')->withTimestamps();
    }

    public function generateShareToken(): string
    {
        $this->share_token = Str::random(32);
        $this->is_public = true;
        $this->save();

        return $this->share_token;
    }

    public function revokeShareToken(): void
    {
        $this->share_token = null;
        $this->is_public = false;
        $this->save();
    }
}
