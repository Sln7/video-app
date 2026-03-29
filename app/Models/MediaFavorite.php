<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaFavorite extends Model
{
    public $timestamps = false;

    protected $table = 'media_favorites';

    protected $fillable = [
        'user_id',
        'media_id',
    ];

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
