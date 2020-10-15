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

class MappingUpdateCommand extends AbstractCommand
{
    use RequiresModelArgument;

    public function __construct()
    {
        parent::__construct('scout:mapping:update');
        $this->setDescription('Update an Elasticsearch index mappings based on the given model');
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $indices = ApplicationContext::getContainer()->get(Engine::class)->getClient()->indices();
        $model = $this->getModel();
        $indexName = $model->searchableAs();
        $mapping = $model->getScoutMapping();

        if (empty($mapping)) {
            throw new LogicException('Nothing to update: the mapping is not specified.');
        }

        $params = [
            'index' => $indexName,
        ];

        if (! $indices->exists($params)) {
            throw new LogicException(sprintf(
                'The index %s does not exist',
                $indexName
            ));
        }

        $params = Arr::add($params, 'body', $mapping);

        $indices->putMapping($params);

        $this->info(sprintf(
            'The %s mapping was updated.',
            $indexName
        ));
    }
}
