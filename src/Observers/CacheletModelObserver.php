<?php

namespace Oxhq\Cachelet\Observers;

use Illuminate\Database\Eloquent\Model;

class CacheletModelObserver
{
    public function deleted(Model $model): void
    {
        $this->invalidate($model, 'deleted');
    }

    public function updated(Model $model): void
    {
        $this->invalidate($model, 'updated');
    }

    protected function invalidate(Model $model, string $reason): void
    {
        $model->cachelet()->invalidatePrefix($reason);
    }
}
