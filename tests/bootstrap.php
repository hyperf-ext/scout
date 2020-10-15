<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-ext/scout.
 *
 * @link     https://github.com/hyperf-ext/scout
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/scout/blob/master/LICENSE
 */
require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

use Hyperf\Utils\ApplicationContext;
use Mockery as m;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

$container = m::mock(ContainerInterface::class);
$container->shouldReceive('get')->with(EventDispatcherInterface::class)->andReturn(m::mock());

ApplicationContext::setContainer($container);
