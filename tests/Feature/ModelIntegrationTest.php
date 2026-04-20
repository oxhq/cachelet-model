<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Oxhq\Cachelet\Builders\ModelCacheletBuilder;
use Oxhq\Cachelet\Facades\Cachelet;
use Tests\Models\CustomPrefixDummy;
use Tests\Models\Dummy;

it('registers the forModel core extension seam', function () {
    $model = Dummy::create(['name' => 'Alice']);

    expect(Cachelet::forModel($model))
        ->toBeInstanceOf(ModelCacheletBuilder::class)
        ->and(Cachelet::forModel($model)->coordinate()->prefix)->toBe('dummies:'.$model->getKey());
});

it('uses a model supplied prefix when available', function () {
    $model = CustomPrefixDummy::create(['name' => 'Alice']);

    expect(Cachelet::forModel($model)->coordinate()->prefix)
        ->toBe('custom:'.$model->getKey());
});

it('ignores date fields by default when deriving model keys', function () {
    $first = new Dummy([
        'id' => 1,
        'name' => 'Alice',
        'email_verified_at' => Carbon::now()->addDay(),
        'created_at' => Carbon::now()->subDay(),
        'updated_at' => Carbon::now()->subHours(2),
    ]);

    $second = new Dummy([
        'id' => 1,
        'name' => 'Alice',
        'email_verified_at' => Carbon::now()->addDays(2),
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()->addHour(),
    ]);

    expect($first->cachelet()->key())->toBe($second->cachelet()->key());
});

it('supports only and exclude filters for model payloads', function () {
    $model = new Dummy(['id' => 2, 'name' => 'Bob', 'role' => 'admin']);

    $base = $model->cachelet()->key();
    $excluded = $model->cachelet()->exclude(['role'])->key();
    $onlyId = $model->cachelet()->only(['id'])->key();
    $onlyIdChanged = (new Dummy(['id' => 2, 'name' => 'Bobby', 'role' => 'staff']))->cachelet()->only(['id'])->key();

    expect($excluded)->not->toBe($base)
        ->and($onlyId)->toBe($onlyIdChanged);
});

it('can opt dates and timestamps into the model payload', function () {
    $first = new Dummy([
        'id' => 3,
        'name' => 'Carol',
        'email_verified_at' => Carbon::now()->addDay(),
        'created_at' => Carbon::now()->subDay(),
        'updated_at' => Carbon::now()->subDay(),
    ]);

    $second = new Dummy([
        'id' => 3,
        'name' => 'Carol',
        'email_verified_at' => Carbon::now()->addDays(3),
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    expect($first->cachelet()->withDates(['email_verified_at'])->key())
        ->not->toBe($second->cachelet()->withDates(['email_verified_at'])->key())
        ->and($first->cachelet()->withTimestamps()->key())
        ->not->toBe($second->cachelet()->withTimestamps()->key());
});
