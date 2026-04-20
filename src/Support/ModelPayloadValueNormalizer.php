<?php

namespace Oxhq\Cachelet\Support;

use Illuminate\Database\Eloquent\Model;
use Oxhq\Cachelet\Contracts\PayloadValueNormalizer;

class ModelPayloadValueNormalizer implements PayloadValueNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof Model;
    }

    public function normalize(mixed $value, PayloadNormalizer $normalizer): mixed
    {
        $attributes = $value->getAttributes();
        unset($attributes['created_at'], $attributes['updated_at']);

        return $normalizer->normalize($attributes);
    }
}
