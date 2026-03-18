<?php

declare(strict_types=1);

namespace Mfd\ContainerCleanup\Task;

use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

final class ContainerCleanupTaskAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    private const FIELD_KEY = 'container_cleanup_ageDays';
    private const FIELD_NAME = 'tx_scheduler[' . self::FIELD_KEY . ']';
    private const FIELD_ID = 'task_container_cleanup_ageDays';
    private const DEFAULT_AGE_DAYS = 180;

    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        if ($task instanceof ContainerCleanupTask) {
            $currentValue = $task->ageDays;
        } else {
            $currentValue = self::DEFAULT_AGE_DAYS;
        }

        $fieldHtml = '<input type="number" class="form-control"'
            . ' name="' . self::FIELD_NAME . '"'
            . ' id="' . self::FIELD_ID . '"'
            . ' min="1"'
            . ' value="' . htmlspecialchars((string)$currentValue) . '"'
            . ' />';

        return [
            self::FIELD_ID => [
                'code'  => $fieldHtml,
                'label' => 'Age threshold (days): only orphans older than this are cleaned up',
            ],
        ];
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        $value = (int)($submittedData[self::FIELD_KEY] ?? 0);

        if ($value < 1) {
            $this->addMessage('ageDays must be at least 1');
            return false;
        }

        $submittedData[self::FIELD_KEY] = $value;
        return true;
    }

    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        if ($task instanceof ContainerCleanupTask) {
            $task->ageDays = (int)($submittedData[self::FIELD_KEY] ?? self::DEFAULT_AGE_DAYS);
        }
    }
}
