<?php

namespace App\Models;

use App\Traits\PublicIdTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use HasFactory, PublicIdTrait, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'title',
        'description',
        'media_type',
        'source',
        'video_id',
        'video_path',
        'hls_url',
        'embed_url',
        'thumbnail_url',
        'views',
        'likes',
        'processed',
        'artist',
        'album',
        'duration_seconds',
    ];

    protected $appends = ['likes_count'];

    public static function findByPublicId(string $publicId): self
    {
        return self::where('public_id', $publicId)->firstOrFail();
    }

    public function getLikesCountAttribute(): int
    {
        return $this->favorites()->count();
    }

    public function scopeFilter($query, array $filters)
    {
        return $query->when($filters['processed'] ?? null, function ($query, $processed) {
            $query->where('processed', $processed);
        }, function ($query) {
            $query->where('processed', true);
        })
        ->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('artist', 'like', '%'.$search.'%');
            });
        })
        ->when($filters['media_type'] ?? null, function ($query, $mediaType) {
            $query->where('media_type', $mediaType);
        });
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'media_favorites', 'media_id', 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class, 'playlist_media')->withTimestamps();
    }
}
