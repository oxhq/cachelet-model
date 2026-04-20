<?php

namespace Oxhq\Cachelet\Support;

use Illuminate\Database\Eloquent\Model;

class ModelPrefixResolver
{
    public function resolve(Model $model): string
    {
        if (method_exists($model, 'getCacheletPrefix')) {
            return (string) $model->getCacheletPrefix();
        }

        return $model->getTable().':'.$model->getKey();
    }
}
