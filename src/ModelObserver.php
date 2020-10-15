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

use Hyperf\Database\Model\Events\Deleted;
use Hyperf\Database\Model\Events\ForceDeleted;
use Hyperf\Database\Model\Events\Restored;
use Hyperf\Database\Model\Events\Saved;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Utils\Context;

class ModelObserver
{
    /**
     * Enable syncing for the given class.
     */
    public static function enableSyncingFor(string $class): void
    {
        Context::override('scout_syncing_disabled', function ($syncingDisabled) use ($class) {
            unset($syncingDisabled[$class]);
            return $syncingDisabled;
        });
    }

    /**
     * Disable syncing for the given class.
     */
    public static function disableSyncingFor(string $class): void
    {
        Context::override('scout_syncing_disabled', function ($syncingDisabled) use ($class) {
            $syncingDisabled[$class] = true;
            return $syncingDisabled;
        });
    }

    /**
     * Determine if syncing is disabled for the given class or model.
     *
     * @param object|string $class
     */
    public static function syncingDisabledFor($class): bool
    {
        $class = is_object($class) ? get_class($class) : $class;
        $syncingDisabled = (array) Context::get('scout_syncing_disabled', []);
        return array_key_exists($class, $syncingDisabled);
    }

    /**
     * Handle the saved event for the model.
     */
    public function saved(Saved $event): void
    {
        $model = $event->getModel();

        if (static::syncingDisabledFor($model)) {
            return;
        }

        if (! $model->shouldBeSearchable()) {
            $model->unsearchable();
            return;
        }
        $model->searchable();
    }

    /**
     * Handle the deleted event for the model.
     */
    public function deleted(Deleted $event)
    {
        $model = $event->getModel();

        if (static::syncingDisabledFor($model)) {
            return;
        }
        if ($this->usesSoftDelete($model)) {
            $this->saved(new Saved($model));
        } else {
            $model->unsearchable();
        }
    }

    /**
     * Handle the force deleted event for the model.
     */
    public function forceDeleted(ForceDeleted $event)
    {
        $model = $event->getModel();

        if (static::syncingDisabledFor($model)) {
            return;
        }
        $model->unsearchable();
    }

    /**
     * Handle the restored event for the model.
     */
    public function restored(Restored $event)
    {
        $model = $event->getModel();
        $this->saved(new Saved($model));
    }

    /**
     * Determine if the given model uses soft deletes.
     */
    protected function usesSoftDelete(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model)) && config('scout.soft_delete', false);
    }
}
