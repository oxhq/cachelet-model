<?php

namespace Oxhq\Cachelet\Builders;

use Illuminate\Database\Eloquent\Model;
use Oxhq\Cachelet\Events\CacheletInvalidated;
use Oxhq\Cachelet\ValueObjects\CacheScope;

class ModelCacheletBuilder extends CacheletBuilder
{
    protected ?Model $model = null;

    protected array $excludedFields = [];

    protected array $onlyFields = [];

    protected array $includedDateFields = [];

    protected bool $includeAllDates = false;

    protected bool $includeTimestamps = false;

    protected bool $hasExplicitScope = false;

    public function setModel(Model $model): static
    {
        $this->model = $model;
        $this->asModule('model');

        $this->withMetadata([
            'model_class' => get_class($model),
            'model_key' => $model->getKey(),
        ])->withTags([
            'model:'.get_class($model),
            'model_id:'.$model->getKey(),
        ]);

        $this->applyInferredScope();

        return $this;
    }

    public function scope(CacheScope $scope): static
    {
        $this->hasExplicitScope = true;

        return $this->applyScope($scope, 'explicit');
    }

    public function only(array $fields): static
    {
        $this->onlyFields = array_values($fields);
        $this->excludedFields = [];
        $this->resetComputedValues();

        return parent::only($fields);
    }

    public function exclude(array $fields): static
    {
        $this->excludedFields = array_values(array_unique(array_merge($this->excludedFields, $fields)));
        $this->resetComputedValues();

        return parent::exclude($fields);
    }

    public function withDates(array $fields = []): static
    {
        if ($fields === []) {
            $this->includeAllDates = true;
        } else {
            $this->includedDateFields = array_values(array_unique(array_merge($this->includedDateFields, $fields)));
        }

        $this->resetComputedValues();

        return $this;
    }

    public function withTimestamps(): static
    {
        $this->includeTimestamps = true;
        $this->resetComputedValues();

        return $this;
    }

    public function invalidate(): void
    {
        $this->invalidatePrefix();
    }

    protected function payloadForKey(): mixed
    {
        if (! $this->model) {
            return parent::payloadForKey();
        }

        $attributes = $this->getModelAttributes();
        $payload = [];
        $dates = $this->dateAttributeNames();

        foreach ($attributes as $key => $value) {
            if (! $this->shouldIncludeAttribute($key, $dates)) {
                continue;
            }

            $payload[$key] = $this->serializeAttributeValue($key, $value, $dates);
        }

        return $payload;
    }

    protected function getModelAttributes(): array
    {
        $attributes = method_exists($this->model, 'getCacheableAttributes')
            ? $this->model->getCacheableAttributes()
            : $this->model->getAttributes();

        $only = $this->onlyFields !== []
            ? $this->onlyFields
            : array_values($this->config['serialization']['default_only'] ?? []);

        if ($only !== []) {
            return array_intersect_key($attributes, array_flip($only));
        }

        return $attributes;
    }

    protected function dateAttributeNames(): array
    {
        $dates = method_exists($this->model, 'getDates')
            ? $this->model->getDates()
            : [];

        foreach ($this->model->getCasts() as $attribute => $cast) {
            if ($this->isDateCast($cast)) {
                $dates[] = $attribute;
            }
        }

        foreach (['created_at', 'updated_at'] as $attribute) {
            if (array_key_exists($attribute, $this->model->getAttributes())) {
                $dates[] = $attribute;
            }
        }

        return array_values(array_unique($dates));
    }

    protected function shouldIncludeAttribute(string $key, array $dates): bool
    {
        if ($this->onlyFields !== []) {
            return in_array($key, $this->onlyFields, true);
        }

        if (in_array($key, $this->excludedFields, true)) {
            return false;
        }

        if (in_array($key, $this->config['serialization']['default_excludes'] ?? [], true)) {
            return false;
        }

        if (! in_array($key, $dates, true)) {
            return true;
        }

        if (! ($this->config['serialization']['exclude_dates'] ?? true)) {
            return true;
        }

        if ($this->includeAllDates || in_array($key, $this->includedDateFields, true)) {
            return true;
        }

        return $this->includeTimestamps && in_array($key, ['created_at', 'updated_at'], true);
    }

    protected function serializeAttributeValue(string $key, mixed $value, array $dates): mixed
    {
        if (! in_array($key, $dates, true)) {
            return $value;
        }

        $resolved = $this->model?->getAttribute($key) ?? $value;

        return $resolved instanceof \DateTimeInterface
            ? $resolved->format(\DateTimeInterface::ATOM)
            : $resolved;
    }

    protected function isDateCast(string $cast): bool
    {
        $normalized = strtolower(trim(strtok($cast, ':')));

        return in_array($normalized, [
            'custom_datetime',
            'date',
            'datetime',
            'immutable_custom_datetime',
            'immutable_date',
            'immutable_datetime',
            'timestamp',
        ], true);
    }

    protected function makeInvalidatedEvent(array $keys, string $reason): CacheletInvalidated
    {
        return new CacheletInvalidated(
            prefix: $this->prefix,
            keys: array_values($keys),
            reason: $reason,
            modelClass: $this->model ? get_class($this->model) : null,
            modelKey: $this->model?->getKey(),
        );
    }

    protected function applyInferredScope(): void
    {
        if ($this->hasExplicitScope) {
            return;
        }

        $scope = $this->makeInferredScope($this->inferredScopeIdentifier());

        if ($scope) {
            $this->applyScope($scope, 'inferred');
        }
    }

    protected function applyScope(CacheScope $scope, string $source): static
    {
        if ($source === 'inferred') {
            parent::withInferredScope($scope);

            return $this;
        }

        return parent::scope($scope);
    }

    protected function makeInferredScope(string $identifier): ?CacheScope
    {
        return CacheScope::inferred($identifier);
    }

    protected function inferredScopeIdentifier(): string
    {
        if ($this->model && method_exists($this->model, 'getCacheletScope')) {
            return (string) $this->model->getCacheletScope();
        }

        if ($this->model) {
            return $this->model->getTable();
        }

        return $this->prefix;
    }
}
