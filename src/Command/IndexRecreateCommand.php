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

class IndexRecreateCommand extends AbstractCommand
{
    use RequiresModelArgument;

    public function __construct()
    {
        parent::__construct('scout:index:recreate');
        $this->setDescription('Recreate an Elasticsearch index based on the given model');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model = $this->input->getArgument('model');

        $this->call('scout:index:drop', [
            'mode' => $model,
        ]);

        $this->call('scout:index:create', [
            'mode' => $model,
        ]);
    }
}
