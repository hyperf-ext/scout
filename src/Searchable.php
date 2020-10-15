<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-ext/scout.
 *
 * @link     https://github.com/hyperf-ext/scout
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/scout/blob/master/LICENSE
 */
namespace HyperfExt\Scout;

use Closure;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\ModelListener\Collector\ListenerCollector;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Hyperf\Utils\Coroutine;

trait Searchable
{
    /**
     * Additional metadata attributes managed by Scout.
     *
     * @var array
     */
    protected $scoutMetadata = [];

    /**
     * Boot the trait.
     */
    public static function bootSearchable()
    {
        static::addGlobalScope(make(SearchableScope::class));
        ListenerCollector::register(static::class, ModelObserver::class);
        (new static())->registerSearchableMacros();
    }

    /**
     * Register the searchable macros.
     */
    public function registerSearchableMacros(): void
    {
        $self = $this;

        Collection::macro('searchable', function () use ($self) {
            /* @var Collection $this */
            $self->asyncMakeSearchable($this);
        });

        Collection::macro('unsearchable', function () use ($self) {
            /* @var Collection $this */
            $self->asyncRemoveFromSearch($this);
        });
    }

    /**
     * Async to make the given models searchable.
     */
    public function asyncMakeSearchable(Collection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        static::dispatchAsyncSearchableJob(function () use ($models) {
            $models->first()->searchableUsing()->update($models);
        });
    }

    /**
     * Async to make the given models unsearchable.
     */
    public function asyncRemoveFromSearch(Collection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        static::dispatchAsyncSearchableJob(function () use ($models) {
            $models->first()->searchableUsing()->delete($models);
        });
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return true;
    }

    /**
     * Perform a search against the model's indexed data.
     */
    public static function search(string $query = '', ?Closure $callback = null): Builder
    {
        return make(Builder::class, [
            'model' => new static(),
            'query' => $query,
            'callback' => $callback,
            'softDelete' => static::usesSoftDelete(),
        ]);
    }

    /**
     * Make all instances of the model searchable.
     */
    public static function makeAllSearchable(?int $chunk = null): void
    {
        $self = new static();

        $softDelete = static::usesSoftDelete();

        $self->newQuery()
            ->when($softDelete, function ($query) {
                $query->withTrashed();
            })
            ->orderBy($self->getKeyName())
            ->searchable($chunk);
    }

    /**
     * Make the given model instance searchable.
     */
    public function searchable(): void
    {
        $this->newCollection([$this])->searchable();
    }

    /**
     * Remove all instances of the model from the search index.
     */
    public static function removeAllFromSearch(): void
    {
        $self = new static();

        $self->searchableUsing()->flush($self);
    }

    /**
     * Remove the given model instance from the search index.
     */
    public function unsearchable(): void
    {
        $this->newCollection([$this])->unsearchable();
    }

    /**
     * Get the requested models from an array of object IDs.
     *
     * @return mixed
     */
    public function getScoutModelsByIds(Builder $builder, array $ids)
    {
        $query = static::usesSoftDelete()
            ? $this->withTrashed() : $this->newQuery();

        if ($builder->queryCallback) {
            call_user_func($builder->queryCallback, $query);
        }

        return $query->whereIn(
            $this->getScoutKeyName(),
            $ids
        )->get();
    }

    /**
     * Enable search syncing for this model.
     */
    public static function enableSearchSyncing(): void
    {
        ModelObserver::enableSyncingFor(get_called_class());
    }

    /**
     * Disable search syncing for this model.
     */
    public static function disableSearchSyncing(): void
    {
        ModelObserver::disableSyncingFor(get_called_class());
    }

    /**
     * Temporarily disable search syncing for the given callback.
     *
     * @return mixed
     */
    public function withoutSyncingToSearch(callable $callback)
    {
        $this->disableSearchSyncing();

        try {
            return $callback($this);
        } finally {
            $this->enableSearchSyncing();
        }
    }

    /**
     * Get the index name for the model.
     */
    public function searchableAs(): string
    {
        return config('scout.prefix') . $this->getTable();
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return $this->toArray();
    }

    /**
     * Get the Scout engine for the model.
     */
    public function searchableUsing(): Engine
    {
        return ApplicationContext::getContainer()->get(Engine::class);
    }

    /**
     * Get the concurrency that should be used when syncing.
     */
    public function syncWithSearchUsingConcurrency(): int
    {
        return (int) config('scout.concurrency', 100);
    }

    /**
     * Sync the soft deleted status for this model into the metadata.
     *
     * @return $this
     */
    public function pushSoftDeleteMetadata()
    {
        return $this->withScoutMetadata('__soft_deleted', $this->trashed() ? 1 : 0);
    }

    /**
     * Get all Scout related metadata.
     */
    public function scoutMetadata(): array
    {
        return $this->scoutMetadata;
    }

    /**
     * Set a Scout related metadata.
     *
     * @param mixed $value
     * @return $this
     */
    public function withScoutMetadata(string $key, $value)
    {
        $this->scoutMetadata[$key] = $value;

        return $this;
    }

    /**
     * Get the value used to index the model.
     *
     * @return mixed
     */
    public function getScoutKey()
    {
        return $this->getKey();
    }

    /**
     * Get the key name used to index the model.
     */
    public function getScoutKeyName(): string
    {
        return $this->getQualifiedKeyName();
    }

    /**
     * Get the elasticsearch index settings.
     */
    public function getScoutSettings(): ?array
    {
        return $this->scoutSettings ?? null;
    }

    /**
     * Get the elasticsearch index mapping properties.
     */
    public function getScoutMapping(): array
    {
        return $this->scoutMapping ?? [];
    }

    /**
     * Determine if the current class should use soft deletes with searching.
     */
    public static function usesSoftDelete(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive(get_called_class())) && config('scout.soft_delete', false);
    }

    /**
     * Dispatch the coroutine job to index the given models.
     */
    protected static function dispatchAsyncSearchableJob(callable $job): void
    {
        if (! Coroutine::inCoroutine()) {
            $job();
            return;
        }

        if (defined('SCOUT_RUNNING_IN_COMMAND')) {
            if (! ($channel = Context::get($channelId = 'scout_async_searchable'))) {
                Context::set($channelId, $channel = new Coroutine\Concurrent((new static())->syncWithSearchUsingConcurrency()));
            }
            $channel->create($job);
        } else {
            Coroutine::defer($job);
        }
    }
}
