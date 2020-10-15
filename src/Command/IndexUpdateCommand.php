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

use Exception;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use HyperfExt\Scout\Command\Concerns\RequiresModelArgument;
use HyperfExt\Scout\Engine;
use LogicException;

class IndexUpdateCommand extends AbstractCommand
{
    use RequiresModelArgument;

    public function __construct()
    {
        parent::__construct('scout:index:update');
        $this->setDescription('Update an Elasticsearch index based on the given model');
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $indices = ApplicationContext::getContainer()->get(Engine::class)->getClient()->indices();
        $model = $this->getModel();
        $indexName = $model->searchableAs();

        $params = [
            'index' => $indexName,
        ];

        if (! $indices->exists($params)) {
            throw new LogicException(sprintf(
                'The index %s does not exist',
                $indexName
            ));
        }

        try {
            $indices->close($params);
            if ($settings = $model->getScoutIndexSettings()) {
                $indices->putSettings(Arr::add($params, 'body.settings', $settings));
            }
            $indices->open($params);
        } catch (Exception $exception) {
            $indices->open($params);
            throw $exception;
        }

        $this->info(sprintf(
            'The index %s was updated.',
            $indexName
        ));
    }
}
