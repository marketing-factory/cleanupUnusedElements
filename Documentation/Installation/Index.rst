.. include:: ../Includes.txt

.. _installation:

============
Installation
============

Install the extension via Composer::

	composer require mfd/container-cleanup

Requirements
============

* PHP 8.2 or higher
* TYPO3 13.4 or 14.3
* `b13/container <https://github.com/b13/container>`__ 3.1.10 or higher
  (including 4.x)
* TYPO3 system extension `scheduler`

No database updates are required. The command and the scheduler task are
available right after installation.

Upgrading from TYPO3 13 to 14
=============================

In TYPO3 v14 the scheduler task is registered as a native TCA task type
(the registration via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']` is
deprecated). The age threshold is stored in the core field
:sql:`number_of_days` of :sql:`tx_scheduler_task`.

Existing tasks, including their configured age threshold, are migrated by the
TYPO3 core upgrade wizard for scheduler tasks. Run the upgrade wizards after
the TYPO3 update::

	vendor/bin/typo3 upgrade:run
