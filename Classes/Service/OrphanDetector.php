<?php

declare(strict_types=1);

namespace Mfd\ContainerCleanup\Service;

use B13\Container\Tca\Registry;
use Mfd\ContainerCleanup\Domain\Model\OrphanReason;
use Mfd\ContainerCleanup\Domain\Model\OrphanRecord;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OrphanDetector
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly Registry $containerRegistry,
    ) {
    }

    /**
     * Detect unused container children in tt_content.
     *
     * Returns records that are either missing their parent container or placed
     * in a colPos not registered for the parent's CType.
     *
     * @param int $ageDays Only include records not modified within this many days
     * @return OrphanRecord[]
     */
    public function detect(int $ageDays = 180): array
    {
        $cutoff = time() - ($ageDays * 86400);
        $candidates = $this->fetchCandidateChildren($cutoff);

        if (empty($candidates)) {
            return [];
        }

        $parentUids = array_unique(array_column($candidates, 'tx_container_parent'));
        $existingParents = $this->fetchExistingParents(
            array_map(static fn(mixed $v): int => (int)$v, $parentUids)
        );

        $orphans = [];
        foreach ($candidates as $row) {
            $parentUid = (int)$row['tx_container_parent'];

            if (!isset($existingParents[$parentUid])) {
                // DETC-01: Parent UID does not exist (accounting for hidden parents via DeletedRestriction only)
                $orphans[] = $this->toOrphanRecord($row, OrphanReason::MissingParent);
                continue;
            }

            $parentLanguageUid = (int)$existingParents[$parentUid]['sys_language_uid'];
            if ((int)$row['sys_language_uid'] === 0 && $parentLanguageUid > 0) {
                // DETC-NEW: Default-language child whose parent is a non-default-language container
                $orphans[] = $this->toOrphanRecord($row, OrphanReason::WrongLanguageParent);
                continue;
            }

            $parentCType = (string)$existingParents[$parentUid]['CType'];

            // Skip INVALID_COL_POS check if parent's CType is no longer registered
            // (e.g. defining extension was uninstalled — the parent is the problem, not the colPos)
            if (!$this->containerRegistry->isContainerElement($parentCType)) {
                continue;
            }

            // DETC-02: Parent exists but child's colPos is not registered for this container CType
            $validColPos = $this->containerRegistry->getAllAvailableColumnsColPos($parentCType);
            if (!in_array((int)$row['colPos'], $validColPos, true)) {
                $orphans[] = $this->toOrphanRecord($row, OrphanReason::InvalidColPos);
            }
        }

        return $orphans;
    }

    /**
     * Fetch tt_content records that are container children (tx_container_parent > 0),
     * are not workspace drafts (t3ver_wsid = 0), and have not been modified recently (tstamp < cutoff).
     * DeletedRestriction is applied — soft-deleted records are excluded (DETC-04).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchCandidateChildren(int $cutoff): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $qb->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $qb
            ->select('uid', 'pid', 'sys_language_uid', 'colPos', 'CType', 'tx_container_parent', 'tstamp')
            ->from('tt_content')
            ->where(
                // DETC-01: Only container children
                $qb->expr()->gt(
                    'tx_container_parent',
                    $qb->createNamedParameter(0, Connection::PARAM_INT)
                ),
                // DETC-05: Exclude workspace drafts (direct WHERE, not WorkspaceRestriction)
                $qb->expr()->eq(
                    't3ver_wsid',
                    $qb->createNamedParameter(0, Connection::PARAM_INT)
                ),
                // DETC-03: Age guard — only records not recently modified
                $qb->expr()->lt(
                    'tstamp',
                    $qb->createNamedParameter($cutoff, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Fetch parent container records by UID using DeletedRestriction ONLY.
     * HiddenRestriction is intentionally excluded so that hidden-but-valid containers
     * are found and do NOT cause their children to be misclassified as MISSING_PARENT (DETC-06).
     *
     * @param int[] $parentUids
     * @return array<int, array<string, mixed>> Indexed by uid
     */
    private function fetchExistingParents(array $parentUids): array
    {
        if (empty($parentUids)) {
            return [];
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
        // DETC-06: DeletedRestriction ONLY — no HiddenRestriction
        $qb->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $qb
            ->select('uid', 'CType', 'sys_language_uid')
            ->from('tt_content')
            ->where(
                $qb->expr()->in(
                    'uid',
                    $qb->createNamedParameter($parentUids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int)$row['uid']] = $row;
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toOrphanRecord(array $row, OrphanReason $reason): OrphanRecord
    {
        return new OrphanRecord(
            uid: (int)$row['uid'],
            pid: (int)$row['pid'],
            sysLanguageUid: (int)$row['sys_language_uid'],
            colPos: (int)$row['colPos'],
            cType: (string)$row['CType'],
            reason: $reason,
            tstamp: new \DateTimeImmutable('@' . $row['tstamp']),
        );
    }
}
