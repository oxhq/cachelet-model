<?php

namespace Oxhq\Cachelet\Traits;

use Oxhq\Cachelet\Builders\ModelCacheletBuilder;
use Oxhq\Cachelet\Facades\Cachelet;
use Oxhq\Cachelet\Observers\CacheletModelObserver;

trait UsesCachelet
{
    public static function bootUsesCachelet(): void
    {
        if (config('cachelet.observe.auto_register', true)) {
            static::updated(static function ($model): void {
                (new CacheletModelObserver)->updated($model);
            });

            static::deleted(static function ($model): void {
                (new CacheletModelObserver)->deleted($model);
            });
        }
    }

    public function cachelet(): ModelCacheletBuilder
    {
        return Cachelet::forModel($this);
    }
}
