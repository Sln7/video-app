<?php

namespace App\Models;

use App\Traits\PublicIdTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory, PublicIdTrait, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'source',
        'video_id',
        'video_path',
        'hls_url',
        'thumbnail_url',
        'views',
        'likes',
        'user_id',
        'embed_url',
        'processed',
    ];

    protected $appends = ['likes_count'];

    public static function findByPublicId($publicId): self
    {
        return self::where('public_id', $publicId)->firstOrFail();
    }

    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['processed'] ?? null, function ($query, $processed) {
            $query->where('processed', $processed);
        }, function ($query) {
            $query->where('processed', true);
        });

        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        });
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, VideoLike::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
