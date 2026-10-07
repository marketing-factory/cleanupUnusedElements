<?php

declare(strict_types=1);

use Mfd\ContainerCleanup\Task\ContainerCleanupTask;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// TYPO3 v13 registers the task via SC_OPTIONS in ext_localconf.php
if ((new Typo3Version())->getMajorVersion() < 14 || !isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    return;
}

// Reuses the core column "number_of_days" of tx_scheduler_task for the age threshold
ExtensionManagementUtility::addRecordType(
    [
        'label' => 'Container Cleanup: Orphan soft-delete',
        'description' => 'Detects and soft-deletes unused container children in tt_content.',
        'value' => ContainerCleanupTask::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'group' => 'container_cleanup',
    ],
    '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
            number_of_days,
        --div--;core.form.tabs:timing,
            --palette--;;execution,
        --div--;core.form.tabs:access,
            disable,
        --div--;core.form.tabs:extended,',
    [
        'columnsOverrides' => [
            'number_of_days' => [
                'label' => 'Age threshold (days): only orphans older than this are cleaned up',
                'config' => [
                    'type' => 'number',
                    'default' => 180,
                    'required' => true,
                    'range' => [
                        'lower' => 1,
                    ],
                ],
            ],
        ],
    ],
    '',
    'tx_scheduler_task'
);
