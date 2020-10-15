<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-ext/scout.
 *
 * @link     https://github.com/hyperf-ext/scout
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/scout/blob/master/LICENSE
 */
return [
    'prefix' => env('SCOUT_PREFIX', ''),
    'concurrency' => env('SCOUT_CONCURRENCY', 100),
    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],
    'soft_delete' => true,
    'document_refresh' => true,
];
