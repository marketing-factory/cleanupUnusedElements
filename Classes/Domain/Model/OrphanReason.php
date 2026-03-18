<?php

declare(strict_types=1);

namespace Mfd\ContainerCleanup\Domain\Model;

enum OrphanReason: string
{
    case MissingParent = 'MISSING_PARENT';
    case InvalidColPos = 'INVALID_COL_POS';
    /** Child is sys_language_uid=0 but its tx_container_parent container has sys_language_uid > 0. */
    case WrongLanguageParent = 'WRONG_LANGUAGE_PARENT';
}
