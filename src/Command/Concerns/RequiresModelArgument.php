<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-ext/scout.
 *
 * @link     https://github.com/hyperf-ext/scout
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/scout/blob/master/LICENSE
 */
namespace HyperfExt\Scout\Command\Concerns;

use Hyperf\Database\Model\Model;
use HyperfExt\Scout\Searchable;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;

trait RequiresModelArgument
{
    /**
     * Get the model.
     */
    protected function getModel(): Model
    {
        $modelClass = trim($this->input->getArgument('model'));

        $modelInstance = new $modelClass();

        if (
            ! ($modelInstance instanceof Model) ||
            ! in_array(Searchable::class, class_uses_recursive($modelClass))
        ) {
            throw new InvalidArgumentException(sprintf(
                'The %s class must extend %s and use the %s trait.',
                $modelClass,
                Model::class,
                Searchable::class
            ));
        }

        return $modelInstance;
    }

    protected function getArguments()
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model class.'],
        ];
    }
}
