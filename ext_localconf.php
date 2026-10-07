<?php

declare(strict_types=1);

use Mfd\ContainerCleanup\Task\ContainerCleanupTask;
use Mfd\ContainerCleanup\Task\ContainerCleanupTaskAdditionalFieldProvider;
use TYPO3\CMS\Core\Information\Typo3Version;

if (!defined('TYPO3')) {
    return;
}

// TYPO3 v14+ registers the task as native TCA task type, see Configuration/TCA/Overrides/scheduler_container_cleanup_task.php
if ((new Typo3Version())->getMajorVersion() < 14) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][ContainerCleanupTask::class] = [
        'extension'        => 'container_cleanup',
        'title'            => 'Container Cleanup: Orphan soft-delete',
        'description'      => 'Detects and soft-deletes unused container children in tt_content.',
        'additionalFields' => ContainerCleanupTaskAdditionalFieldProvider::class,
    ];
}
