# cachelet-model

Read-only split of the Cachelet monorepo package at `packages/cachelet-model`.

Eloquent model integration for Cachelet.

## Install

```bash
composer require oxhq/cachelet-model
```

## Features

- `Cachelet::forModel($model)`
- `$model->cachelet()`
- `scope(...)` for explicit intervention boundaries
- `only()`, `exclude()`, `withDates()`, `withTimestamps()`
- Observer-driven invalidation
- Canonical `module = model` coordinates and telemetry

## Example

```php
use Oxhq\Cachelet\ValueObjects\CacheScope;
use Oxhq\Cachelet\Traits\UsesCachelet;

class User extends Model
{
    use UsesCachelet;
}

$scope = /* CacheScope instance for the intervention boundary */;

$profile = $user->cachelet()
    ->scope($scope)
    ->exclude(['updated_at'])
    ->remember(fn () => $user->fresh());
```

If you do not define a scope explicitly, `cachelet-model` infers one from the model cache prefix boundary it already uses for invalidation.
