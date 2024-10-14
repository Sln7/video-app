<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait PublicIdTrait
{
    protected static function bootPublicIdTrait()
    {
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = Str::uuid()->toString();
            }
        });
    }
}
