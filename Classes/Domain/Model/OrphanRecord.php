<?php

declare(strict_types=1);

namespace Mfd\ContainerCleanup\Domain\Model;

readonly class OrphanRecord
{
    public function __construct(
        public int $uid,
        public int $pid,
        public int $sysLanguageUid,
        public int $colPos,
        public string $cType,
        public OrphanReason $reason,
        public \DateTimeImmutable $tstamp,
    ) {
    }
}
