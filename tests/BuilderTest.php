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

use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Model;
use Hyperf\Paginator\Paginator;
use HyperfExt\Scout\Builder;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class BuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testPaginationCorrectlyHandlesPaginatedResults()
    {
        $this->expectNotToPerformAssertions();

        Paginator::currentPageResolver(function () {
            return 1;
        });
        Paginator::currentPathResolver(function () {
            return 'http://localhost/foo';
        });

        $builder = new Builder($model = m::mock(Model::class), 'zonda');
        $model->shouldReceive('getPerPage')->andReturn(15);
        $model->shouldReceive('searchableUsing')->andReturn($engine = m::mock());

        $engine->shouldReceive('paginate');
        $engine->shouldReceive('map')->andReturn($results = Collection::make([new stdClass()]));
        $engine->shouldReceive('getTotalCount');

        $model->shouldReceive('newCollection')->andReturn($results);

        $builder->paginate();
    }

    public function testMacroable()
    {
        Builder::macro('foo', function () {
            return 'bar';
        });

        $builder = new Builder($model = m::mock(Model::class), 'zonda');
        $this->assertSame(
            'bar',
            $builder->foo()
        );
    }

    public function testHardDeleteDoesntSetWheres()
    {
        $builder = new Builder($model = m::mock(Model::class), 'zonda', null, false);

        $this->assertArrayNotHasKey('__soft_deleted', $builder->wheres);
    }

    public function testSoftDeleteSetsWheres()
    {
        $builder = new Builder($model = m::mock(Model::class), 'zonda', null, true);

        $this->assertSame(0, $builder->wheres['__soft_deleted']);
    }
}
