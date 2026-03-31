<?php

namespace App\Models;

use App\Traits\PublicIdTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory, PublicIdTrait;

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->belongsToMany(Media::class, 'playlist_media')->withTimestamps();
    }
}
