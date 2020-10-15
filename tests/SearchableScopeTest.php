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
use HyperfExt\Scout\SearchableScope;
use Mockery as m;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class SearchableScopeTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testChunksById()
    {
        $this->expectNotToPerformAssertions();

        $builder = m::mock(Builder::class);
        $builder->shouldReceive('macro')->with('searchable', m::on(function ($callback) use ($builder) {
            $builder->shouldReceive('chunkById')->with(500, m::type(\Closure::class));
            $callback($builder, 500);

            return true;
        }));
        $builder->shouldReceive('macro')->with('unsearchable', m::on(function ($callback) use ($builder) {
            $builder->shouldReceive('chunkById')->with(500, m::type(\Closure::class));
            $callback($builder, 500);

            return true;
        }));

        (new SearchableScope())->extend($builder);
    }
}
