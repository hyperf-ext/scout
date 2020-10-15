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

use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations\HasManyThrough;
use Hyperf\Database\Model\Scope;
use Hyperf\Utils\ApplicationContext;
use HyperfExt\Scout\Event\ModelsFlushed;
use HyperfExt\Scout\Event\ModelsImported;
use Psr\EventDispatcher\EventDispatcherInterface;

class SearchableScope implements Scope
{
    /**
     * Apply the scope to a given Model query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder)
    {
        $builder->macro('searchable', function (Builder $builder, $chunk = null) {
            $eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
            $builder->chunkById($chunk ?: config('scout.chunk.searchable', 500), function ($models) use ($eventDispatcher) {
                $models->filter->shouldBeSearchable()->searchable();

                $eventDispatcher->dispatch(new ModelsImported($models));
            });
        });

        $builder->macro('unsearchable', function (Builder $builder, $chunk = null) {
            $eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
            $builder->chunkById($chunk ?: config('scout.chunk.unsearchable', 500), function ($models) use ($eventDispatcher) {
                $models->unsearchable();

                $eventDispatcher->dispatch(new ModelsFlushed($models));
            });
        });

        HasManyThrough::macro('searchable', function ($chunk = null) {
            $eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
            /* @var HasManyThrough $this */
            $this->chunkById($chunk ?: config('scout.chunk.searchable', 500), function ($models) use ($eventDispatcher) {
                $models->filter->shouldBeSearchable()->searchable();

                $eventDispatcher->dispatch(new ModelsImported($models));
            });
        });

        HasManyThrough::macro('unsearchable', function ($chunk = null) {
            $eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
            /* @var HasManyThrough $this */
            $this->chunkById($chunk ?: config('scout.chunk.searchable', 500), function ($models) use ($eventDispatcher) {
                $models->filter->shouldBeSearchable()->searchable();

                $eventDispatcher->dispatch(new ModelsImported($models));
            });
        });
    }
}
