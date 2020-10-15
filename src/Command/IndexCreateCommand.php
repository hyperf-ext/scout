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

use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use HyperfExt\Scout\Command\Concerns\RequiresModelArgument;
use HyperfExt\Scout\Engine;
use LogicException;

class IndexCreateCommand extends AbstractCommand
{
    use RequiresModelArgument;

    public function __construct()
    {
        parent::__construct('scout:index:create');
        $this->setDescription('Create an Elasticsearch index based on the given model');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $indices = ApplicationContext::getContainer()->get(Engine::class)->getClient()->indices();
        $model = $this->getModel();
        $indexName = $model->searchableAs();
        $settings = $model->getScoutSettings();
        $mapping = $model->getScoutMapping();

        $params = [
            'index' => $indexName,
        ];

        if ($indices->exists($params)) {
            throw new LogicException(sprintf(
                'The index %s is already existed',
                $indexName
            ));
        }

        if (! empty($settings)) {
            $params = Arr::add($params, 'body.settings', $settings);
        }

        if (! empty($mapping)) {
            $params = Arr::add($params, 'body.mappings', $mapping);
        }

        $indices->create($params);

        $this->info(sprintf(
            'The index %s was created.',
            $indexName
        ));
    }
}
