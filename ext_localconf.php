<?php

declare(strict_types=1);

use Mfd\ContainerCleanup\Task\ContainerCleanupTask;
use Mfd\ContainerCleanup\Task\ContainerCleanupTaskAdditionalFieldProvider;

if (!defined('TYPO3')) {
    return;
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][ContainerCleanupTask::class] = [
    'extension'        => 'container_cleanup',
    'title'            => 'Container Cleanup: Orphan soft-delete',
    'description'      => 'Detects and soft-deletes unused container children in tt_content.',
    'additionalFields' => ContainerCleanupTaskAdditionalFieldProvider::class,
];
