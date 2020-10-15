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
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhrasePrefixQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhraseQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MultiMatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\FullText\SimpleQueryStringQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoPolygonQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoShapeQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\FuzzyQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\PrefixQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RegexpQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use ONGR\ElasticsearchDSL\Search;
use ReflectionMethod;

/**
 * @method $this mustWhere($field, $operator, $value, $parameters = [])
 * @method $this shouldWhere($field, $operator, $value, $parameters = [])
 * @method $this notWhere($field, $operator, $value, $parameters = [])
 * @method $this mustWhereIn(string $field, array $values, array $parameters = [])
 * @method $this shouldWhereIn(string $field, array $values, array $parameters = [])
 * @method $this notWhereIn(string $field, array $values, array $parameters = [])
 * @method $this mustWhereBetween(string $field, array $values, array $parameters = [])
 * @method $this shouldWhereBetween(string $field, array $values, array $parameters = [])
 * @method $this notWhereBetween(string $field, array $values, array $parameters = [])
 * @method $this mustWhereExists(string $field)
 * @method $this shouldWhereExists(string $field)
 * @method $this notWhereExists(string $field)
 * @method $this mustWhereRegexp(string $field, string $value, string $flags = 'ALL')
 * @method $this shouldWhereRegexp(string $field, string $value, string $flags = 'ALL')
 * @method $this notWhereRegexp(string $field, string $value, string $flags = 'ALL')
 * @method $this mustWhereLike(string $field, string $value, array $parameters = [])
 * @method $this shouldWhereLike(string $field, string $value, array $parameters = [])
 * @method $this notWhereLike(string $field, string $value, array $parameters = [])
 * @method $this mustWherePrefix(string $field, string $value, array $parameters = [])
 * @method $this shouldWherePrefix(string $field, string $value, array $parameters = [])
 * @method $this notWherePrefix(string $field, string $value, array $parameters = [])
 * @method $this mustWhereFuzzy(string $field, string $value, array $parameters = [])
 * @method $this shouldWhereFuzzy(string $field, string $value, array $parameters = [])
 * @method $this notWhereFuzzy(string $field, string $value, array $parameters = [])
 * @method $this mustWhereIdsIn(array $values, array $parameters = [])
 * @method $this shouldWhereIdsIn(array $values, array $parameters = [])
 * @method $this notWhereIdsIn(array $values, array $parameters = [])
 * @method $this mustWhereGeoDistance(string $field, string $distance, $location)
 * @method $this shouldWhereGeoDistance(string $field, string $distance, $location)
 * @method $this notWhereGeoDistance(string $field, string $distance, $location)
 * @method $this mustWhereGeoBoundingBox(string $field, array $values, array $parameters = [])
 * @method $this shouldWhereGeoBoundingBox(string $field, array $values, array $parameters = [])
 * @method $this notWereGeoBoundingBox(string $field, array $values, array $parameters = [])
 * @method $this mustWhereGeoPolygon(string $field, array $points, array $parameters = [])
 * @method $this shouldWhereGeoPolygon(string $field, array $points, array $parameters = [])
 * @method $this notWereGeoPolygon(string $field, array $points, array $parameters = [])
 * @method $this mustWhereGeoShape(Closure $closure, array $parameters = ['relation' => 'INTERSECTS'])
 * @method $this shouldWhereGeoShape(Closure $closure, array $parameters = ['relation' => 'INTERSECTS'])
 * @method $this notWereGeoShape(Closure $closure, array $parameters = ['relation' => 'INTERSECTS'])
 * @method $this mustWhereMatchAll(array $parameters = [])
 * @method $this shouldWhereMatchAll(array $parameters = [])
 * @method $this notWereMatchAll(array $parameters = [])
 * @method $this mustWhereMatch(string $field, string $value, array $parameters = [])
 * @method $this shouldWhereMatch(string $field, string $value, array $parameters = [])
 * @method $this notWereMatch(string $field, string $value, array $parameters = [])
 * @method $this mustWhereMultiMatch(array $fields, string $value, array $parameters = [])
 * @method $this shouldWhereMultiMatch(array $fields, string $value, array $parameters = [])
 * @method $this notWereMultiMatch(array $fields, string $value, array $parameters = [])
 * @method $this mustWhereMatchPhrase(string $field, string $value, array $parameters = [])
 * @method $this shouldWhereMatchPhrase(string $field, string $value, array $parameters = [])
 * @method $this notWereMatchPhrase(string $field, string $value, array $parameters = [])
 * @method $this mustWhereMatchPhrasePrefix(string $field, string $value, array $parameters = [])
 * @method $this shouldWhereMatchPhrasePrefix(string $field, string $value, array $parameters = [])
 * @method $this notWereMatchPhrasePrefix(string $field, string $value, array $parameters = [])
 * @method $this mustWhereQueryString(string $value, array $parameters = [])
 * @method $this shouldWhereQueryString(string $value, array $parameters = [])
 * @method $this notWereQueryString(string $value, array $parameters = [])
 * @method $this mustWhereSimpleQueryString(string $value, array $parameters = [])
 * @method $this shouldWhereSimpleQueryString(string $value, array $parameters = [])
 * @method $this notWereSimpleQueryString(string $value, array $parameters = [])
 * @method $this mustWhereNested(string $path, Closure $callback, array $parameters = [])
 * @method $this shouldWhereNested(string $path, Closure $callback, array $parameters = [])
 * @method $this notWereNested(string $path, Closure $callback, array $parameters = [])
 */
class QueryBuilder
{
    /**
     * @var \ONGR\ElasticsearchDSL\Query\Compound\BoolQuery|\ONGR\ElasticsearchDSL\Search
     */
    protected $query;

    public function __construct(?Search $query = null)
    {
        $this->query = $query ?: new BoolQuery();
    }

    public function __call(string $name, array $arguments)
    {
        if (stripos($name, 'where') !== false) {
            switch (true) {
                case strpos($name, 'must') === 0:
                    return $this->magicCallWhereCondition(lcfirst(substr($name, 4)), $arguments, BoolQuery::MUST);
                case strpos($name, 'should') === 0:
                    return $this->magicCallWhereCondition(lcfirst(substr($name, 6)), $arguments, BoolQuery::SHOULD);
                case strpos($name, 'not') === 0:
                    return $this->magicCallWhereCondition(lcfirst(substr($name, 3)), $arguments, BoolQuery::MUST_NOT);
            }
        }
        throw new BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }

    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Add a where condition.
     *
     * Supported operators are =, &gt;, &lt;, &gt;=, &lt;=, between, in, like, regexp, prefix, exists.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/term-level-queries.html Term-level queries
     *
     * @example QueryBuilder::where('brand', 'elastic') Term query
     * @example QueryBuilder::where('price', '>', 100) Range query
     * @example QueryBuilder::where('price', '<', 500) Range query
     * @example QueryBuilder::where('price', '>=', 100) Range query
     * @example QueryBuilder::where('price', '<=', 500) Range query
     * @example QueryBuilder::where('price', 'between', [100, 500]) Range query
     * @example QueryBuilder::where('language', 'in', ['php', 'go', 'python']) Terms query
     * @example QueryBuilder::where('_id', 'in', ['1', '4', '100']) IDs query
     * @example QueryBuilder::where('brand', 'like', 'elas*c') Wildcard query
     * @example QueryBuilder::where('brand', 'regexp', 'elas.*c') Regexp query
     * @example QueryBuilder::where('brand', 'fuzzy', 'ela') Fuzzy query
     * @example QueryBuilder::where('bio', 'exists') Exists query
     *
     * @param array|\Closure|string $field
     * @param null|mixed $operator
     * @param null|mixed $value
     * @param null|array $parameters
     * @param null|string $occur default is `filter`
     * @return $this
     */
    public function where($field, $operator = null, $value = null, $parameters = [], $occur = BoolQuery::FILTER)
    {
        if ($field instanceof Closure) {
            $field($query = new static());
            $this->addQuery($query->getQuery(), $operator ?: $occur);

            return $this;
        }

        $args = func_get_args();

        if (count($args) === 2) {
            [$field, $value] = $args;
            $operator = '=';
        }

        $parameters = $parameters ?: [];
        $occur = $occur ?: BoolQuery::FILTER;

        switch ($operator) {
            case '=':
                $this->addQuery(new TermQuery($field, $value, $parameters), $occur);
                break;
            case '>':
                $this->addQuery(new RangeQuery($field, array_merge([RangeQuery::GT => $value], $parameters)), $occur);
                break;
            case '<':
                $this->addQuery(new RangeQuery($field, array_merge([RangeQuery::LT => $value], $parameters)), $occur);
                break;
            case '>=':
                $this->addQuery(new RangeQuery($field, array_merge([RangeQuery::GTE => $value], $parameters)), $occur);
                break;
            case '<=':
                $this->addQuery(new RangeQuery($field, array_merge([RangeQuery::LTE => $value], $parameters)), $occur);
                break;
            case 'between':
                $this->addQuery(new RangeQuery($field, array_merge([RangeQuery::GTE => $value[0], RangeQuery::LTE => $value[1]], $parameters)), $occur);
                break;
            case 'like':
                $this->addQuery(new WildcardQuery($field, $value, $parameters), $occur);
                break;
            case 'regexp':
                $this->addQuery(new RegexpQuery($field, $value, array_merge(['flags' => 'ALL'], $parameters)), $occur);
                break;
            case 'prefix':
                $this->addQuery(new PrefixQuery($field, $value, $parameters), $occur);
                break;
            case 'fuzzy':
                $this->addQuery(new FuzzyQuery($field, $value, $parameters), $occur);
                break;
            case 'in':
                $this->addQuery(
                    $field === '_id'
                        ? new IdsQuery($value, $parameters)
                        : new TermsQuery($field, $value, $parameters),
                    $occur
                );
                break;
            case 'exists':
                $this->addQuery(new ExistsQuery($field), $occur);
                break;
        }

        return $this;
    }

    /**
     * @see \HyperfExt\Scout\QueryBuilder::where()
     *
     * @return $this
     */
    public function whereIn(string $field, array $values, array $parameters = [], ?string $occur = null)
    {
        $this->where($field, 'in', $values, $parameters, $occur);

        return $this;
    }

    /**
     * @see \HyperfExt\Scout\QueryBuilder::where()
     *
     * @return $this
     */
    public function whereBetween(string $field, array $values, array $parameters = [], ?string $occur = null)
    {
        $this->where($field, 'between', $values, $parameters, $occur);

        return $this;
    }

    /**
     * @see \HyperfExt\Scout\QueryBuilder::where()
     *
     * @return $this
     */
    public function whereExists(string $field, ?string $occur = null)
    {
        $this->where($field, 'exists', null, null, $occur);

        return $this;
    }

    /**
     * @see \HyperfExt\Scout\QueryBuilder::where()
     *
     * @return $this
     */
    public function whereRegexp(string $field, string $value, string $flags = 'ALL', ?string $occur = null)
    {
        $this->where($field, 'regexp', $value, ['flags' => $flags], $occur);

        return $this;
    }

    /**
     * @see \HyperfExt\Scout\QueryBuilder::where()
     *
     * @return $this
     */
    public function whereLike(string $field, string $value, array $parameters = [], ?string $occur = null)
    {
        $this->where($field, 'like', $value, $parameters, $occur);

        return $this;
    }

    /**
     * @see \HyperfExt\Scout\QueryBuilder::where()
     *
     * @return $this
     */
    public function wherePrefix(string $field, string $value, array $parameters = [], ?string $occur = null)
    {
        $this->where($field, 'prefix', $value, $parameters, $occur);

        return $this;
    }

    /**
     * @see \HyperfExt\Scout\QueryBuilder::where()
     *
     * @return $this
     */
    public function whereFuzzy(string $field, string $value, array $parameters = [], ?string $occur = null)
    {
        $this->where($field, 'fuzzy', $value, $parameters, $occur);

        return $this;
    }

    /**
     * @see \HyperfExt\Scout\QueryBuilder::where()
     *
     * @return $this
     */
    public function whereIdsIn(array $values, array $parameters = [], ?string $occur = null)
    {
        $this->where('_id', 'in', $values, $parameters, $occur);

        return $this;
    }

    /**
     * Add a whereGeoDistance condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-distance-query.html Geo distance query
     *
     * @param mixed $location
     * @return $this
     */
    public function whereGeoDistance(string $field, string $distance, $location, array $parameters = [], ?string $occur = null)
    {
        $this->addQuery(new GeoDistanceQuery($field, $distance, $location, $parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    /**
     * Add a whereGeoBoundingBox condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-bounding-box-query.html Geo bounding box query
     *
     * @return $this
     */
    public function whereGeoBoundingBox(string $field, array $values, array $parameters = [], ?string $occur = null)
    {
        $this->addQuery(new GeoBoundingBoxQuery($field, $values, $parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    /**
     * Add a whereGeoPolygon condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-polygon-query.html Geo polygon query
     *
     * @return $this
     */
    public function whereGeoPolygon(string $field, array $points, array $parameters = [], ?string $occur = null)
    {
        $this->addQuery(new GeoPolygonQuery($field, $points, $parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    /**
     * Add a GeoShape condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-shape-query.html Querying Geo Shapes
     *
     * @return $this
     */
    public function whereGeoShape(Closure $closure, array $parameters = ['relation' => 'INTERSECTS'], ?string $occur = null)
    {
        $this->addQuery($query = new GeoShapeQuery($parameters), $occur ?: BoolQuery::FILTER);
        $closure($query);

        return $this;
    }

    /**
     * Add a MatchAll condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-all-query.html Match all query
     *
     * @return $this
     */
    public function whereMatchAll(array $parameters = [], ?string $occur = null)
    {
        $this->addQuery(new MatchAllQuery($parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    /**
     * Add a Match condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html Match query
     *
     * @return $this
     */
    public function whereMatch(string $field, string $value, array $parameters = [], ?string $occur = null)
    {
        $this->addQuery(new MatchQuery($field, $value, $parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    /**
     * Add a MultiMatch condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html Multi-match query
     *
     * @return $this
     */
    public function whereMultiMatch(array $fields, string $value, array $parameters = [], ?string $occur = null)
    {
        $this->addQuery(new MultiMatchQuery($fields, $value, $parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    /**
     * Add a MatchPhrase condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase.html Match phrase query
     *
     * @return $this
     */
    public function whereMatchPhrase(string $field, string $value, array $parameters = [], ?string $occur = null)
    {
        $this->addQuery(new MatchPhraseQuery($field, $value, $parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    /**
     * Add a MatchPhrasePrefix condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase-prefix.html Match phrase prefix query
     *
     * @return $this
     */
    public function whereMatchPhrasePrefix(string $field, string $value, array $parameters = [], ?string $occur = null)
    {
        $this->addQuery(new MatchPhrasePrefixQuery($field, $value, $parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    /**
     * Add a QueryString condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html Query string query
     *
     * @return $this
     */
    public function whereQueryString(string $value, array $parameters = [], ?string $occur = null)
    {
        $this->addQuery(new QueryStringQuery($value, $parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    /**
     * Add a QueryString condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-simple-query-string-query.html Simple query string query
     *
     * @return $this
     */
    public function whereSimpleQueryString(string $value, array $parameters = [], ?string $occur = null)
    {
        $this->addQuery(new SimpleQueryStringQuery($value, $parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    /**
     * Add a Nested condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-nested-query.html Nested query
     *
     * @return $this
     */
    public function whereNested(string $path, Closure $callback, array $parameters = [], ?string $occur = null)
    {
        $callback($query = new static());
        $this->addQuery(new NestedQuery($path, $query->getQuery(), $parameters), $occur ?: BoolQuery::FILTER);

        return $this;
    }

    public function addQuery(BuilderInterface $query, $type = BoolQuery::MUST, $key = null)
    {
        return $this->query->{$this->query instanceof Search ? 'addQuery' : 'add'}($query, $type, $key);
    }

    /**
     * @throws \ReflectionException
     * @return $this
     */
    protected function magicCallWhereCondition(string $name, array $arguments, string $occur)
    {
        if (! method_exists(QueryBuilder::class, $name)) {
            throw new BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
        }
        $method = new ReflectionMethod($this, $name);
        $arguments += array_map(function ($parameter) {
            return $parameter->isOptional() ? $parameter->getDefaultValue() : null;
        }, $method->getParameters());
        $arguments[count($arguments) - 1] = $occur;
        return call_user_func_array([$this, $name], $arguments);
    }
}
