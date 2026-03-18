<?php

declare(strict_types=1);

namespace Mfd\ContainerCleanup\Service;

use Mfd\ContainerCleanup\Domain\Model\OrphanRecord;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanupService
{
    /**
     * Soft-delete orphan records via DataHandler so deletions are recorded in sys_history.
     *
     * @param OrphanRecord[] $orphans
     * @return int Number of records soft-deleted
     */
    public function softDelete(array $orphans): int
    {
        if (empty($orphans)) {
            return 0;
        }

        $cmd = ['tt_content' => []];
        foreach ($orphans as $orphan) {
            $cmd['tt_content'][$orphan->uid] = ['delete' => 1];
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        return count($orphans) - count($dataHandler->errorLog);
    }
}
