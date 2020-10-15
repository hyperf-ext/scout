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

use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use HyperfTest\Scout\Fixtures\SearchableModel;
use Mockery as m;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class SearchableTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSearchableUsingUpdateIsCalledOnCollection()
    {
        $this->expectNotToPerformAssertions();

        $collection = m::mock(Collection::class);
        $collection->shouldReceive('isEmpty')->andReturn(false);
        $collection->shouldReceive('first->searchableUsing->update')->with($collection);

        $model = new SearchableModel();
        $model->asyncMakeSearchable($collection);
    }

    public function testSearchableUsingUpdateIsNotCalledOnEmptyCollection()
    {
        $this->expectNotToPerformAssertions();

        $collection = m::mock(Collection::class);
        $collection->shouldReceive('isEmpty')->andReturn(true);
        $collection->shouldNotReceive('first->searchableUsing->update');

        $model = new SearchableModel();
        $model->asyncMakeSearchable($collection);
    }

    public function testSearchableUsingDeleteIsCalledOnCollection()
    {
        $this->expectNotToPerformAssertions();

        $collection = m::mock(Collection::class);
        $collection->shouldReceive('isEmpty')->andReturn(false);
        $collection->shouldReceive('first->searchableUsing->delete')->with($collection);

        $model = new SearchableModel();
        $model->asyncRemoveFromSearch($collection);
    }

    public function testSearchableUsingDeleteIsNotCalledOnEmptyCollection()
    {
        $this->expectNotToPerformAssertions();

        $collection = m::mock(Collection::class);
        $collection->shouldReceive('isEmpty')->andReturn(true);
        $collection->shouldNotReceive('first->searchableUsing->delete');

        $model = new SearchableModel();
        $model->asyncRemoveFromSearch($collection);
    }

    public function testMakeAllSearchableUsesOrderBy()
    {
        $this->expectNotToPerformAssertions();

        ModelStubForMakeAllSearchable::makeAllSearchable();
    }
}

class ModelStubForMakeAllSearchable extends SearchableModel
{
    public function newQuery()
    {
        $mock = m::mock(Builder::class);

        $mock->shouldReceive('orderBy')
            ->with('id')
            ->andReturnSelf()
            ->shouldReceive('searchable');

        $mock->shouldReceive('when')->andReturnSelf();

        return $mock;
    }
}

namespace HyperfExt\Scout;

function config($arg)
{
    return false;
}
