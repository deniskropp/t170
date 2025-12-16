<?php

namespace MultiPersona\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MultiPersona\Core\Dispatcher;

class SystemCommand extends Command
{
    protected static $defaultName = 'system';
    private Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        parent::__construct();
        $this->dispatcher = $dispatcher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('System operations')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = $input->getArgument('action');

        if ($action === 'status') {
            $status = $this->dispatcher->getSystemStatus();
            $output->writeln("System Status:");
            $output->writeln("Running: " . ($status['isRunning'] ? 'Yes' : 'No'));
            $output->writeln("Dispatch Interval: " . $status['dispatchInterval'] . "s");

            $output->writeln("\nTask Counts:");
            foreach ($status['taskCounts'] as $statusName => $count) {
                $output->writeln("  $statusName: $count");
            }

            return Command::SUCCESS;
        }

        $output->writeln('<error>Unknown action</error>');
        return Command::FAILURE;
    }
}
