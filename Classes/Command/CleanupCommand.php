<?php

declare(strict_types=1);

namespace Mfd\ContainerCleanup\Command;

use Mfd\ContainerCleanup\Domain\Model\OrphanRecord;
use Mfd\ContainerCleanup\Service\CleanupService;
use Mfd\ContainerCleanup\Service\OrphanDetector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;

class CleanupCommand extends Command
{
    public function __construct(
        private readonly OrphanDetector $orphanDetector,
        private readonly CleanupService $cleanupService,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Detect and optionally soft-delete unused container children in tt_content')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report unused CEs without deleting')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Age threshold in days', 180);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initializeBackendAuthentication();
        $io = new SymfonyStyle($input, $output);

        try {
            $days = (int)$input->getOption('days');
            $dryRun = (bool)$input->getOption('dry-run');

            $orphans = $this->orphanDetector->detect($days);
            usort($orphans, static fn(OrphanRecord $a, OrphanRecord $b): int => $a->pid !== $b->pid ? $a->pid <=> $b->pid : $a->uid <=> $b->uid);

            if (empty($orphans)) {
                $io->success('No unused content elements found.');
                return Command::SUCCESS;
            }

            if ($output->isVerbose()) {
                foreach ($orphans as $orphan) {
                    $prefix = $dryRun ? 'Would delete' : 'Deleting';
                    $output->writeln(sprintf(
                        '  %s: uid=%d pid=%d (%s, last modified %s)',
                        $prefix,
                        $orphan->uid,
                        $orphan->pid,
                        $orphan->reason->value,
                        $orphan->tstamp->format('Y-m-d')
                    ));
                }
            }

            $table = new Table($output);
            $table->setHeaders(['UID', 'PID', 'Language', 'ColPos', 'CType', 'Reason', 'Last Modified']);

            foreach ($orphans as $orphan) {
                $table->addRow([
                    $orphan->uid,
                    $orphan->pid,
                    $orphan->sysLanguageUid,
                    $orphan->colPos,
                    $orphan->cType,
                    $orphan->reason->value,
                    $orphan->tstamp->format('Y-m-d H:i:s'),
                ]);
            }

            $table->render();

            if ($dryRun) {
                $io->comment('Dry run — no changes made.');
                return Command::SUCCESS;
            }

            $deleted = $this->cleanupService->softDelete($orphans);
            $io->success(sprintf('Deleted %d unused content element(s).', $deleted));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
