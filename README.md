# Container Cleanup

TYPO3 extension that detects and soft-deletes unused child content elements of
[b13/container](https://github.com/b13/container) containers in `tt_content`.

Over time, container children can lose their place in the page: their parent
container gets deleted, the container's grid configuration changes, or
translations get mixed up. These records are invisible in the page module but
remain in the database. This extension finds them and removes them through the
DataHandler, so every deletion is recorded in `sys_history` and can be restored
from the recycler.

## Why not use the commands of b13/container?

b13/container ships its own cleanup commands:

```bash
vendor/bin/typo3 container:deleteChildrenWithWrongPid
vendor/bin/typo3 container:deleteChildrenWithNonExistingParent
vendor/bin/typo3 container:deleteChildrenWithUnusedColPos
```

They delete immediately, and you cannot restrict them to records of a certain
age. We wrote this extension because we needed exactly that option: records
that were edited recently may still be work in progress (e.g. an editor is
rebuilding a container), so only elements that have not been modified for a
configurable number of days are removed. In addition, this extension offers:

- a `--dry-run` mode that lists affected records before anything is deleted
- a scheduler task with a safety limit (aborts at 500+ findings)
- detection of default-language children inside translated containers
  (`WRONG_LANGUAGE_PARENT`)

## Compatibility

| Extension | TYPO3        | b13/container    | PHP   |
|-----------|--------------|------------------|-------|
| 1.x       | 13.4, 14.3   | 3.1.10+, 4.x     | 8.2+  |

## Installation

```bash
composer require mfd/container-cleanup
```

The extension requires `typo3/cms-scheduler` and `b13/container`.

## What counts as "unused"?

Only records with `tx_container_parent > 0` are checked. Workspace versions
(`t3ver_wsid != 0`), already deleted records and records modified within the age
threshold are ignored. A candidate is reported with one of these reasons:

| Reason                  | Meaning                                                                                                                      |
|-------------------------|------------------------------------------------------------------------------------------------------------------------------|
| `MISSING_PARENT`        | The parent container (`tx_container_parent`) does not exist or is deleted. Hidden containers count as existing.              |
| `WRONG_LANGUAGE_PARENT` | The child is in the default language (`sys_language_uid = 0`), but its parent container is a translation.                     |
| `INVALID_COL_POS`       | The child's `colPos` is not a column of the parent's container type (e.g. after the grid of a container was changed).       |

If the parent's `CType` is no longer registered as a container (for example because
the defining extension was removed), `INVALID_COL_POS` is not evaluated for its
children, since the parent is the problem in this case.

Records are **soft-deleted** (`deleted = 1`), never removed from the database.

## Usage

### Console command

```bash
# Report only, change nothing
vendor/bin/typo3 container-cleanup:cleanup --dry-run

# Soft-delete all unused elements not modified within the last 365 days
vendor/bin/typo3 container-cleanup:cleanup --days=365

# Print every affected record before the summary table
vendor/bin/typo3 container-cleanup:cleanup --dry-run -v
```

| Option      | Default | Description                                               |
|-------------|---------|-----------------------------------------------------------|
| `--dry-run` | off     | List unused elements without deleting them                |
| `--days`    | `180`   | Only consider records not modified within this many days  |

The command prints a table with UID, PID, language, colPos, CType, reason and
last modification date of every affected record.

Always run with `--dry-run` first and review the result.

### Scheduler task

Add the task **Container Cleanup: Orphan soft-delete** in the Scheduler module and
configure the **age threshold (days)** (default 180).

Safety net: if the task finds 500 or more unused elements, it aborts without
deleting anything and logs a warning. Inspect such cases manually with the
console command.

The task description in the Scheduler module shows the summary of the last run,
e.g. `12 deleted — 10 MISSING_PARENT, 2 INVALID_COL_POS`.

## Upgrading from TYPO3 13 to 14

In TYPO3 v14 the task is registered as a native TCA task type (registration via
`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']` is deprecated).
The age threshold is stored in the core field `number_of_days` of
`tx_scheduler_task`. Existing tasks, including their configured age threshold, are
migrated by the TYPO3 core upgrade wizard for scheduler tasks.

## License

GPL-2.0-or-later, see [LICENSE.txt](LICENSE.txt).

## Security

See [SECURITY.md](SECURITY.md).
