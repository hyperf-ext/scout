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

use Hyperf\Database\Model\Events\Deleted;
use Hyperf\Database\Model\Events\Restored;
use Hyperf\Database\Model\Events\Saved;
use Hyperf\Database\Model\Model;
use HyperfExt\Scout\ModelObserver;
use Mockery as m;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ModelObserverTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSavedHandlerMakesModelSearchable()
    {
        $this->expectNotToPerformAssertions();
        $observer = new ModelObserver();
        $model = m::mock(Model::class);
        $model->shouldReceive('shouldBeSearchable')->andReturn(true);
        $model->shouldReceive('searchable');
        $observer->saved(new Saved($model));
    }

    public function testSavedHandlerDoesntMakeModelSearchableWhenDisabled()
    {
        $this->expectNotToPerformAssertions();
        $observer = new ModelObserver();
        $model = m::mock(Model::class);
        $observer->disableSyncingFor(get_class($model));
        $model->shouldReceive('searchable')->never();
        $observer->saved(new Saved($model));
    }

    public function testSavedHandlerMakesModelUnsearchableWhenDisabledPerModelRule()
    {
        $this->expectNotToPerformAssertions();
        $observer = new ModelObserver();
        $model = m::mock(Model::class);
        $model->shouldReceive('shouldBeSearchable')->andReturn(false);
        $model->shouldReceive('searchable')->never();
        $model->shouldReceive('unsearchable');
        $observer->saved(new Saved($model));
    }

    public function testDeletedHandlerMakesModelUnsearchable()
    {
        $this->expectNotToPerformAssertions();
        $observer = new ModelObserver();
        $model = m::mock(Model::class);
        $model->shouldReceive('unsearchable');
        $observer->deleted(new Deleted($model));
    }

    public function testRestoredHandlerMakesModelSearchable()
    {
        $this->expectNotToPerformAssertions();
        $observer = new ModelObserver();
        $model = m::mock(Model::class);
        $model->shouldReceive('shouldBeSearchable')->andReturn(true);
        $model->shouldReceive('searchable');
        $observer->restored(new Restored($model));
    }
}
