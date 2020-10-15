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

use BadMethodCallException;
use Closure;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Database\Model\Model;
use Hyperf\Paginator\LengthAwarePaginator;
use Hyperf\Paginator\Paginator;
use Hyperf\Utils\Traits\Macroable;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

/**
 * @mixin \HyperfExt\Scout\QueryBuilder
 */
class Builder
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The model instance.
     *
     * @var \Hyperf\Database\Model\Model
     */
    public $model;

    /**
     * The query expression.
     *
     * @var string
     */
    public $query;

    /**
     * Optional callback before search execution.
     *
     * @var string
     */
    public $callback;

    /**
     * Optional callback before model query execution.
     *
     * @var null|\Closure
     */
    public $queryCallback;

    /**
     * The custom index specified for the search.
     *
     * @var string
     */
    public $index;

    /**
     * @var array
     */
    public $wheres = [];

    /**
     * @var array
     */
    public $raw = [];

    /**
     * The with parameter.
     *
     * @var array
     */
    protected $with = [];

    /**
     * @var \ONGR\ElasticsearchDSL\Search
     */
    protected $search;

    /**
     * @var \HyperfExt\Scout\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * Create a new search builder instance.
     */
    public function __construct(Model $model, ?string $query, ?Closure $callback = null, bool $softDelete = false)
    {
        $this->model = $model;
        $this->query = $query;
        $this->callback = $callback;

        $this->search = new Search();

        $this->queryBuilder = new QueryBuilder($this->search);

        if (! empty($query)) {
            $this->queryBuilder->mustWhereQueryString($query);
        }

        if ($softDelete) {
            $this->wheres['__soft_deleted'] = 0;
        }
    }

    public function __call(string $method, array $parameters)
    {
        try {
            call_user_func_array([$this->queryBuilder, $method], $parameters);
        } catch (BadMethodCallException $e) {
            return $this->macroCall($method, $parameters);
        }
        return $this;
    }

    public function toArray(): array
    {
        if (empty($this->raw)) {
            if (isset($this->wheres['__soft_deleted'])) {
                $search = clone $this->search;
                $search->addQuery(
                    new ExistsQuery($this->model->getDeletedAtColumn()),
                    $this->wheres['__soft_deleted'] === 0 ? BoolQuery::MUST_NOT : BoolQuery::FILTER
                );
                return $search->toArray();
            }

            return $this->search->toArray();
        }

        return $this->raw;
    }

    public function getSearch(): Search
    {
        return $this->search;
    }

    /**
     * @return $this
     */
    public function dsl(callable $callable)
    {
        $callable($this->search);

        return $this;
    }

    /**
     * @return $this
     */
    public function raw(array $value)
    {
        $this->raw = $value;

        return $this;
    }

    /**
     * Specify a custom index to perform this search on.
     *
     * @return $this
     */
    public function within(string $index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Include soft deleted records in the results.
     *
     * @return $this
     */
    public function withTrashed()
    {
        unset($this->wheres['__soft_deleted']);

        return $this;
    }

    /**
     * Include only soft deleted records in the results.
     *
     * @return $this
     */
    public function onlyTrashed()
    {
        return tap($this->withTrashed(), function () {
            $this->wheres['__soft_deleted'] = 1;
        });
    }

    /**
     * Alias to set the "from" value of the query.
     *
     * @see \HyperfExt\Scout\Builder::from()
     * @return $this
     */
    public function skip(int $value)
    {
        return $this->from($value);
    }

    /**
     * Alias to set the "from" value of the query.
     *
     * @see \HyperfExt\Scout\Builder::from()
     * @return $this
     */
    public function offset(int $value)
    {
        return $this->from($value);
    }

    /**
     * Set the "from" value of the query.
     *
     * @return $this
     */
    public function from(int $value)
    {
        $this->search->setFrom(max(0, $value));

        return $this;
    }

    /**
     * Alias to set the "size" for the search query.
     *
     * @see \HyperfExt\Scout\Builder::size()
     * @return $this
     */
    public function take(int $value)
    {
        return $this->limit($value);
    }

    /**
     * Alias to set the "size" value of the query.
     *
     * @see \HyperfExt\Scout\Builder::size()
     * @return $this
     */
    public function limit(int $value)
    {
        return $this->size($value);
    }

    /**
     * Set the "size" value of the query.
     *
     * @return $this
     */
    public function size(int $value)
    {
        if ($value >= 0) {
            $this->search->setSize($value);
        }

        return $this;
    }

    /**
     * Set the "min_score" value of the query.
     *
     * @return $this
     */
    public function minScore(float $value)
    {
        if ($value >= 0) {
            $this->search->setMinScore($value);
        }

        return $this;
    }

    /**
     * Add an "sort" for the search query.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html.
     *
     * @param null|string $direction 'asc'|'desc'|null
     * @param array $options nested,missing,unmapped_type,mode(min|max|sum|avg|median)
     *
     * @return $this
     */
    public function orderBy(string $column, ?string $direction = null, array $options = [])
    {
        $this->search->addSort(new FieldSort($column, $direction, $options));

        return $this;
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function when($value, callable $callback, ?callable $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        }
        if ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Pass the query to a given callback.
     *
     * @return $this
     */
    public function tap(Closure $callback)
    {
        return $this->when(true, $callback);
    }

    /**
     * Set the callback that should have an opportunity to modify the database query.
     *
     * @return $this
     */
    public function query(callable $callback)
    {
        $this->queryCallback = $callback;

        return $this;
    }

    /**
     * Eager load some relations.
     *
     * @param array|string $relations
     * @return $this
     */
    public function with($relations)
    {
        if (is_string($relations)) {
            $this->with[] = $relations;
        } elseif (is_array($relations)) {
            $this->with = array_merge($this->with, $relations);
        }

        return $this;
    }

    /**
     * Get the raw results of the search.
     *
     * @return mixed
     */
    public function getRaw()
    {
        return $this->engine()->search($this);
    }

    /**
     * Get the keys of search results.
     *
     * @return \Hyperf\Utils\Collection
     */
    public function keys()
    {
        return $this->engine()->keys($this);
    }

    /**
     * Get the first result from the search.
     *
     * @return \Hyperf\Database\Model\Model
     */
    public function first()
    {
        return $this->limit(1)->get()->first();
    }

    /**
     * Get the results of the search.
     *
     * @return \Hyperf\Database\Model\Collection
     */
    public function get()
    {
        $results = $this->engine()->get($this);

        if (count($this->with) > 0 && $results->count() > 0) {
            $results->load($this->with);
        }

        return $results;
    }

    /**
     * Get the count from the search.
     */
    public function count(): int
    {
        return $this->engine()->count($this);
    }

    /**
     * Paginate the given query into a simple paginator.
     */
    public function paginate(?int $perPage = null, string $pageName = 'page', ?int $page = null): LengthAwarePaginatorInterface
    {
        $engine = $this->engine();

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        $results = $this->model->newCollection($engine->map(
            $this,
            $rawResults = $engine->paginate($this, $perPage, $page),
            $this->model
        )->all());

        if (count($this->with) > 0 && $results->count() > 0) {
            $results->load($this->with);
        }

        $paginator = make(LengthAwarePaginator::class, [
            'items' => $results,
            'total' => $engine->getTotalCount($rawResults),
            'perPage' => $perPage,
            'currentPage' => $page,
            'options' => [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        ]);

        return $paginator->appends('query', $this->query);
    }

    /**
     * Paginate the given query into a simple paginator with raw data.
     */
    public function paginateRaw(?int $perPage = null, string $pageName = 'page', ?int $page = null): LengthAwarePaginatorInterface
    {
        $engine = $this->engine();

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        $results = $engine->paginate($this, $perPage, $page);

        $paginator = make(LengthAwarePaginator::class, [
            'items' => $results,
            'total' => $engine->getTotalCount($results),
            'perPage' => $perPage,
            'currentPage' => $page,
            'options' => [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        ]);

        return $paginator->appends('query', $this->query);
    }

    /**
     * Get the engine that should handle the query.
     *
     * @return \HyperfExt\Scout\Engine
     */
    protected function engine()
    {
        return $this->model->searchableUsing();
    }
}
