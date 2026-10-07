<?php

declare(strict_types=1);

namespace Mfd\ContainerCleanup\Task;

use Mfd\ContainerCleanup\Service\CleanupService;
use Mfd\ContainerCleanup\Service\OrphanDetector;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

final class ContainerCleanupTask extends AbstractTask
{
    public int $ageDays = 180;

    public function execute(): bool
    {
        /** @var OrphanDetector $detector */
        $detector = GeneralUtility::getContainer()->get(OrphanDetector::class);
        /** @var CleanupService $cleaner */
        $cleaner = GeneralUtility::getContainer()->get(CleanupService::class);
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registryKey = 'lastRunSummary_' . $this->getTaskUid();

        try {
            $orphans = $detector->detect($this->ageDays);

            if (count($orphans) >= 500) {
                $summary = 'Aborted — 500+ orphans detected, manual cleanup required';
                $logger->warning($summary, ['count' => count($orphans)]);
                $registry->set('tx_container_cleanup', $registryKey, $summary);
                return true;
            }

            if (count($orphans) === 0) {
                $registry->set('tx_container_cleanup', $registryKey, 'Nothing to clean up');
                return true;
            }

            $deleted = $cleaner->softDelete($orphans);

            $reasonCounts = [];
            foreach ($orphans as $orphan) {
                $reasonValue = $orphan->reason->value;
                if (!isset($reasonCounts[$reasonValue])) {
                    $reasonCounts[$reasonValue] = 0;
                }

                $reasonCounts[$reasonValue]++;
            }

            $breakdownParts = [];
            foreach ($reasonCounts as $reason => $count) {
                $breakdownParts[] = $count . ' ' . $reason;
            }

            $summary = $deleted . ' deleted — ' . implode(', ', $breakdownParts);
            $registry->set('tx_container_cleanup', $registryKey, $summary);
            return true;
        } catch (\Throwable $e) {
            $registry->set('tx_container_cleanup', $registryKey, 'Task failed — see TYPO3 log');
            $logger->error($e->getMessage(), ['exception' => $e]);
            return false;
        }
    }

    public function getAdditionalInformation(): string
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        return (string)$registry->get('tx_container_cleanup', 'lastRunSummary_' . $this->getTaskUid(), '');
    }

    /**
     * TYPO3 v14+: maps the task property to the TCA field "number_of_days" of tx_scheduler_task.
     * Also used by the core upgrade wizard to migrate serialized v13 tasks.
     */
    public function getTaskParameters(): array
    {
        return [
            'number_of_days' => $this->ageDays,
        ];
    }

    /**
     * TYPO3 v14+: "ageDays" is the property name of serialized v13 tasks, "number_of_days" the TCA field.
     */
    public function setTaskParameters(array $parameters): void
    {
        $ageDays = (int)($parameters['ageDays'] ?? $parameters['number_of_days'] ?? 180);
        $this->ageDays = $ageDays > 0 ? $ageDays : 180;
    }
}
