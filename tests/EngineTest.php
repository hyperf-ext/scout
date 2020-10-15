<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-ext/scout.
 *
 * @link     https://github.com/hyperf-ext/scout
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/scout/blob/master/LICENSE
 */
namespace HyperfTest\Scout;

use Elasticsearch\Client;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Model;
use HyperfExt\Scout\Builder;
use HyperfExt\Scout\Engine;
use HyperfTest\Scout\Fixtures\SearchableModel;
use Mockery as m;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class EngineTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testUpdateAddsObjectsToIndex()
    {
        $this->expectNotToPerformAssertions();

        $client = m::mock(Client::class);
        $client->shouldReceive('bulk')->with([
            'refresh' => false,
            'body' => [
                [
                    'update' => [
                        '_index' => 'table',
                        '_id' => 1,
                    ],
                ],
                [
                    'doc' => ['id' => 1],
                    'doc_as_upsert' => true,
                ],
            ],
        ]);

        $engine = new Engine($client);
        $engine->update(Collection::make([new SearchableModel(['id' => 1])]));
    }

    public function testDeleteRemovesObjectsToIndex()
    {
        $this->expectNotToPerformAssertions();

        $client = m::mock(Client::class);
        $client->shouldReceive('bulk')->with([
            'refresh' => false,
            'body' => [
                [
                    'delete' => [
                        '_id' => 1,
                        '_index' => 'table',
                    ],
                ],
            ],
        ]);

        $engine = new Engine($client);
        $engine->delete(Collection::make([new SearchableModel(['id' => 1])]));
    }

    public function testSearchSendsCorrectParametersToElasticsearch()
    {
        $this->expectNotToPerformAssertions();

        $client = m::mock(Client::class);
        $client->shouldReceive('search')->with([
            'index' => 'table',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['query_string' => ['query' => 'zonda']],
                        ],
                        'filter' => [
                            ['term' => ['foo' => 1]],
                            ['terms' => ['bar' => [1, 3]]],
                        ],
                    ],
                ],
                'sort' => [
                    ['id' => ['order' => 'desc']],
                ],
            ],
            'ignore_throttled' => false,
        ]);

        $engine = new Engine($client);
        $builder = new Builder(new SearchableModel(), 'zonda');
        $builder->where('foo', 1);
        $builder->whereIn('bar', [1, 3]);
        $builder->orderBy('id', 'desc');
        $engine->search($builder);
    }

    public function testMapCorrectlyMapsResultsToModels()
    {
        $client = m::mock(Client::class);
        $engine = new Engine($client);

        $model = m::mock(Model::class);
        $model->shouldReceive('getScoutModelsByIds')->andReturn($models = Collection::make([
            new SearchableModel(['id' => 1]),
        ]));

        $builder = m::mock(Builder::class);

        $results = $engine->map($builder, [
            'hits' => [
                'total' => [
                    'value' => 1,
                ],
                'hits' => [
                    ['_id' => '1'],
                ],
            ],
        ], $model);

        $this->assertCount(1, $results);
    }

    public function testMapMethodRespectsOrder()
    {
        $client = m::mock(Client::class);
        $engine = new Engine($client);

        $model = m::mock(Model::class);
        $model->shouldReceive('getScoutModelsByIds')->andReturn($models = Collection::make([
            new SearchableModel(['id' => 1]),
            new SearchableModel(['id' => 2]),
            new SearchableModel(['id' => 3]),
            new SearchableModel(['id' => 4]),
        ]));

        $builder = m::mock(Builder::class);

        $results = $engine->map($builder, [
            'hits' => [
                'total' => [
                    'value' => 1,
                ],
                'hits' => [
                    ['_id' => 1],
                    ['_id' => 2],
                    ['_id' => 4],
                    ['_id' => 3],
                ],
            ],
        ], $model);

        $this->assertCount(4, $results);

        // It's important we assert with array keys to ensure
        // they have been reset after sorting.
        $this->assertEquals([
            0 => ['id' => 1],
            1 => ['id' => 2],
            2 => ['id' => 4],
            3 => ['id' => 3],
        ], $results->toArray());
    }
}
