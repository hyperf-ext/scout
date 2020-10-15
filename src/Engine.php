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

use Elasticsearch\Client;
use Elasticsearch\Client as Elasticsearch;
use Hyperf\Database\Model\Collection as ModelCollection;
use Hyperf\Database\Model\Model;
use Hyperf\Utils\Collection as BaseCollection;

class Engine
{
    /**
     * The Elasticsearch client instance.
     *
     * @var \Elasticsearch\Client
     */
    protected $elasticsearch;

    /**
     * Create a new engine instance.
     */
    public function __construct(Elasticsearch $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Update the given model in the index.
     */
    public function update(ModelCollection $models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $body = [];

        foreach ($models as $model) {
            $doc = $model->toSearchableArray();
            if (empty($doc)) {
                continue;
            }
            $body[] = [
                'update' => [
                    '_index' => $model->searchableAs(),
                    '_id' => $model->getScoutKey(),
                ],
            ];
            $body[] = [
                'doc' => $doc,
                'doc_as_upsert' => true,
            ];
        }

        $this->elasticsearch->bulk([
            'refresh' => config('scout.doc_refresh', true),
            'body' => $body,
        ]);
    }

    /**
     * Remove the given model from the index.
     */
    public function delete(ModelCollection $models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $body = [];

        foreach ($models as $model) {
            $body[] = [
                'delete' => [
                    '_index' => $model->searchableAs(),
                    '_id' => $model->getScoutKey(),
                ],
            ];
        }

        $this->elasticsearch->bulk([
            'refresh' => config('scout.document_refresh', true),
            'body' => $body,
        ]);
    }

    /**
     * Perform the given search on the engine.
     *
     * @return mixed
     */
    public function search(Builder $builder)
    {
        $from = $builder->getSearch()->getFrom();
        $size = $builder->getSearch()->getSize();
        return $this->performSearch(
            $builder,
            ! is_null($from)
                ? ['size' => $size, 'from' => $from]
                : []
        );
    }

    /**
     * Perform the given search on the engine.
     *
     * @return mixed
     */
    public function paginate(Builder $builder, int $perPage, int $page)
    {
        return $this->performSearch($builder, [
            'from' => ($page - 1) * $perPage,
            'size' => $perPage,
        ]);
    }

    /**
     * Perform the given search count on the engine.
     */
    public function count(Builder $query): int
    {
        $result = $this->performCount($query);

        return isset($result['count']) ? (int) $result['count'] : 0;
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param mixed $results
     */
    public function mapIds($results): BaseCollection
    {
        return collect($results['hits']['hits'])->pluck('_id');
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param mixed $results
     */
    public function map(Builder $builder, $results, Model $model): ModelCollection
    {
        if ($this->getTotalCount($results) === 0) {
            return $model->newCollection();
        }

        $ids = $this->mapIds($results)->values()->all();

        $idPositions = array_flip($ids);

        return $model->getScoutModelsByIds(
            $builder,
            $ids
        )->filter(function ($model) use ($ids) {
            return in_array($model->getScoutKey(), $ids);
        })->sortBy(function ($model) use ($idPositions) {
            return $idPositions[$model->getScoutKey()];
        })->values();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param mixed $results
     */
    public function getTotalCount($results): int
    {
        return (int) $results['hits']['total']['value'];
    }

    /**
     * Flush all of the model's records from the engine.
     */
    public function flush(Model $model)
    {
        $this->elasticsearch->deleteByQuery([
            'refresh' => config('scout.document_refresh', true),
            'index' => $model->searchableAs(),
            'body' => [
                'query' => ['match_all' => []],
            ],
        ]);
    }

    /**
     * Get the results of the query as a Collection of primary keys.
     */
    public function keys(Builder $builder): BaseCollection
    {
        return $this->mapIds($this->search($builder));
    }

    /**
     * Get the results of the given query mapped onto models.
     *
     * @return \Hyperf\Database\Model\Collection
     */
    public function get(Builder $builder)
    {
        return $this->map(
            $builder,
            $this->search($builder),
            $builder->model
        );
    }

    public function getClient(): Client
    {
        return $this->elasticsearch;
    }

    /**
     * Perform the given search count on the engine.
     *
     * @return mixed
     */
    protected function performCount(Builder $builder, array $options = [])
    {
        $query = [
            'index' => $builder->index ?? $builder->model->searchableAs(),
            'body' => $builder->toArray(),
            'ignore_throttled' => false,
        ];

        return $this->elasticsearch->count($query);
    }

    /**
     * Perform the given search on the engine.
     *
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $query = [
            'index' => $builder->index ?: $builder->model->searchableAs(),
            'body' => $builder->toArray(),
            'ignore_throttled' => false,
        ];

        if (isset($options['size'])) {
            $query['size'] = $options['size'];
        }

        if (isset($options['from'])) {
            $query['from'] = $options['from'];
        }

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $this->elasticsearch,
                $query
            );
        }

        return $this->elasticsearch->search($query);
    }
}
