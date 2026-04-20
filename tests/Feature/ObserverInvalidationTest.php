<?php

declare(strict_types=1);

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Oxhq\Cachelet\Events\CacheletInvalidated;
use Tests\Models\Dummy;

beforeEach(function () {
    Cache::flush();
    Event::fake([CacheletInvalidated::class]);
    config(['cachelet.observability.events.enabled' => true]);
});

it('flushes all cache variants for a model prefix on update', function () {
    $model = Dummy::create(['name' => 'Tom', 'role' => 'admin']);

    $first = $model->cachelet()->remember(fn () => 'one');
    $firstKey = $model->cachelet()->key();

    $variant = $model->cachelet()->exclude(['role']);
    $variant->remember(fn () => 'two');
    $secondKey = $variant->key();
    $tags = $variant->coordinate()->tags;
    $store = Cache::store();

    expect($first)->toBe('one')
        ->and(
            $store->getStore() instanceof TaggableStore
                ? $store->tags($tags)->has($firstKey)
                : Cache::has($firstKey)
        )->toBeTrue()
        ->and(
            $store->getStore() instanceof TaggableStore
                ? $store->tags($tags)->has($secondKey)
                : Cache::has($secondKey)
        )->toBeTrue();

    $model->update(['name' => 'Tommy']);

    expect(
        $store->getStore() instanceof TaggableStore
            ? $store->tags($tags)->has($firstKey)
            : Cache::has($firstKey)
    )->toBeFalse()
        ->and(
            $store->getStore() instanceof TaggableStore
                ? $store->tags($tags)->has($secondKey)
                : Cache::has($secondKey)
        )->toBeFalse();

    Event::assertDispatchedTimes(CacheletInvalidated::class, 1);
});

it('dispatches a typed invalidated event on delete', function () {
    $model = Dummy::create(['name' => 'Jerry']);
    $model->cachelet()->remember(fn () => 'value');

    $model->delete();

    Event::assertDispatched(CacheletInvalidated::class, function (CacheletInvalidated $event) use ($model) {
        return $event->reason === 'deleted'
            && $event->modelClass === Dummy::class
            && $event->modelKey === $model->getKey();
    });
});
