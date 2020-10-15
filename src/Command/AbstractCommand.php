<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-ext/scout.
 *
 * @link     https://github.com/hyperf-ext/scout
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/scout/blob/master/LICENSE
 */
namespace HyperfExt\Scout\Command;

use Hyperf\Command\Command;

abstract class AbstractCommand extends Command
{
    public function __construct(string $name = null)
    {
        if (! defined('SCOUT_RUNNING_IN_COMMAND')) {
            define('SCOUT_RUNNING_IN_COMMAND', true);
        }
        parent::__construct($name);
    }
}
