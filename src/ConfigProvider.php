<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-ext/scout.
 *
 * @link     https://github.com/hyperf-ext/scout
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/scout/blob/master/LICENSE
 */
namespace HyperfExt\Scout;

use HyperfExt\Scout\Command\FlushCommand;
use HyperfExt\Scout\Command\ImportCommand;
use HyperfExt\Scout\Command\IndexCreateCommand;
use HyperfExt\Scout\Command\IndexDropCommand;
use HyperfExt\Scout\Command\IndexRecreateCommand;
use HyperfExt\Scout\Command\IndexUpdateCommand;
use HyperfExt\Scout\Command\MappingUpdateCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'commands' => [
                FlushCommand::class,
                ImportCommand::class,
                IndexCreateCommand::class,
                IndexDropCommand::class,
                IndexUpdateCommand::class,
                IndexRecreateCommand::class,
                MappingUpdateCommand::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for hyperf-ext/scout.',
                    'source' => __DIR__ . '/../publish/scout.php',
                    'destination' => BASE_PATH . '/config/autoload/scout.php',
                ],
            ],
        ];
    }
}
