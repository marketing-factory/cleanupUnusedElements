.. include:: ../Includes.txt

.. _introduction:

============
Introduction
============

What does it do?
================

This extension detects child content elements of
`b13/container <https://github.com/b13/container>`__ containers in
:sql:`tt_content` that are no longer used, and soft-deletes them.

Only records with :sql:`tx_container_parent > 0` are checked. Workspace
versions (:sql:`t3ver_wsid != 0`), already deleted records and records modified
within a configurable age threshold (default: 180 days) are ignored. A
candidate is reported with one of these reasons:

.. t3-field-list-table::
   :header-rows: 1

   - :Reason: Reason
     :Meaning: Meaning

   - :Reason: `MISSING_PARENT`
     :Meaning: The parent container (:sql:`tx_container_parent`) does not exist
               or is deleted. Hidden containers count as existing.

   - :Reason: `WRONG_LANGUAGE_PARENT`
     :Meaning: The child is in the default language
               (:sql:`sys_language_uid = 0`), but its parent container is a
               translation.

   - :Reason: `INVALID_COL_POS`
     :Meaning: The child's :sql:`colPos` is not a column of the parent's
               container type, e.g. after the grid of a container was changed.

If the parent's :sql:`CType` is no longer registered as a container (for
example because the defining extension was removed), `INVALID_COL_POS` is not
evaluated for its children, since the parent is the problem in this case.

Records are deleted through the DataHandler: they are only marked as deleted
(:sql:`deleted = 1`), every deletion is recorded in :sql:`sys_history`, and
records can be restored with the recycler.

Why is this needed?
===================

Over time, container children can lose their place in the page: their parent
container gets deleted, the grid configuration of a container type changes, or
translations get mixed up. Such records are not visible in the page module, but
they remain in the database, show up in searches, exports and reference
lists, and make the content harder to maintain. This extension finds and
removes them reliably, either on demand via a console command or regularly via
a scheduler task.

Why not use the commands of b13/container?
==========================================

b13/container ships its own cleanup commands:

.. code-block:: bash

	vendor/bin/typo3 container:deleteChildrenWithWrongPid
	vendor/bin/typo3 container:deleteChildrenWithNonExistingParent
	vendor/bin/typo3 container:deleteChildrenWithUnusedColPos

They delete immediately, and you cannot restrict them to records of a certain
age. We wrote this extension because we needed exactly that option: records
that were edited recently may still be work in progress (e.g. an editor is
rebuilding a container), so only elements that have not been modified for a
configurable number of days are removed. In addition, this extension offers:

* a `--dry-run` mode that lists affected records before anything is deleted
* a scheduler task with a safety limit (aborts at 500+ findings)
* detection of default-language children inside translated containers
  (`WRONG_LANGUAGE_PARENT`)

Screenshots
===========

This extension has no backend module. It provides a console command and a
scheduler task.
