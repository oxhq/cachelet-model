<?php

namespace Oxhq\Cachelet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Oxhq\Cachelet\Builders\ModelCacheletBuilder;
use Oxhq\Cachelet\Support\ModelPayloadValueNormalizer;
use Oxhq\Cachelet\Support\ModelPrefixResolver;
use Oxhq\Cachelet\Support\PayloadNormalizerRegistry;

class CacheletModelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModelPrefixResolver::class, fn () => new ModelPrefixResolver);
        $this->app->afterResolving(PayloadNormalizerRegistry::class, function (PayloadNormalizerRegistry $registry): void {
            $registry->prepend(new ModelPayloadValueNormalizer);
        });
    }

    public function boot(): void
    {
        if (! CacheletManager::hasMacro('forModel')) {
            CacheletManager::macro('forModel', function (Model $model, ?string $prefix = null): ModelCacheletBuilder {
                $builder = new ModelCacheletBuilder(
                    $prefix ?? app(ModelPrefixResolver::class)->resolve($model),
                    (array) config('cachelet', [])
                );

                return $builder->setModel($model);
            });
        }

        if (! CacheletManager::hasMacro('prefixForModel')) {
            CacheletManager::macro('prefixForModel', function (Model $model): string {
                return app(ModelPrefixResolver::class)->resolve($model);
            });
        }
    }
}
