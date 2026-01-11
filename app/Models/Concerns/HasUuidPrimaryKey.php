<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasUuidPrimaryKey
{
    protected static function bootHasUuidPrimaryKey(): void
    {
        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
