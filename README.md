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
- `only()`, `exclude()`, `withDates()`, `withTimestamps()`
- Observer-driven invalidation

## Example

```php
use Oxhq\Cachelet\Traits\UsesCachelet;

class User extends Model
{
    use UsesCachelet;
}

$profile = $user->cachelet()
    ->exclude(['updated_at'])
    ->remember(fn () => $user->fresh());
```
