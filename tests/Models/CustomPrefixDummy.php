<?php

declare(strict_types=1);

namespace Tests\Models;

class CustomPrefixDummy extends Dummy
{
    protected $table = 'dummies';

    public function getCacheletPrefix(): string
    {
        return 'custom:'.$this->getKey();
    }
}
