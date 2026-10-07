.. include:: ../Includes.txt

.. _configuration:

=====================
Configuration & usage
=====================

The extension has no extension configuration. It is used via a console command
or a scheduler task.

.. important::

	Always run the command with `--dry-run` first and review the result before
	deleting records.

.. _configuration-command:

Console command
===============

.. code-block:: bash

	# Report only, change nothing
	vendor/bin/typo3 container-cleanup:cleanup --dry-run

	# Soft-delete all unused elements not modified within the last 365 days
	vendor/bin/typo3 container-cleanup:cleanup --days=365

	# Print every affected record before the summary table
	vendor/bin/typo3 container-cleanup:cleanup --dry-run -v

.. t3-field-list-table::
   :header-rows: 1

   - :Option: Option
     :Default: Default
     :Description: Description

   - :Option: `--dry-run`
     :Default: off
     :Description: List unused elements without deleting them.

   - :Option: `--days`
     :Default: `180`
     :Description: Only consider records not modified within this many days.

The command prints a table with UID, PID, language, colPos, CType, reason and
last modification date of every affected record.

.. _configuration-scheduler:

Scheduler task
==============

Add the task :guilabel:`Container Cleanup: Orphan soft-delete` in
:guilabel:`System > Scheduler` and configure:

.. container:: table-row

	Property
		Age threshold (days)

	Data type
		integer (at least 1)

	Description
		Only unused elements not modified within this many days are deleted.

	Default
		180

Safety net: if the task finds 500 or more unused elements, it aborts without
deleting anything and logs a warning. Inspect such cases manually with the
:ref:`console command <configuration-command>`.

The task information in the scheduler module shows the summary of the last
run, e.g. `12 deleted — 10 MISSING_PARENT, 2 INVALID_COL_POS`. Errors are
written to the TYPO3 log.
