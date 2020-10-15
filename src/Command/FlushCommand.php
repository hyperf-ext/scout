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

use HyperfExt\Scout\Command\Concerns\RequiresModelArgument;

class FlushCommand extends AbstractCommand
{
    use RequiresModelArgument;

    public function __construct()
    {
        parent::__construct('scout:flush');
        $this->setDescription('Flush all of the given model\'s records from the elasticsearch index');
    }

    public function handle()
    {
        $model = $this->getModel();
        $class = get_class($model);

        $model::removeAllFromSearch();

        $this->info('All [' . $class . '] records have been flushed.');
    }
}
