<?php

namespace MultiPersona\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use MultiPersona\Core\AgentRegistry;

class AgentCommand extends Command
{
    protected static $defaultName = 'agent';
    private AgentRegistry $agentRegistry;

    public function __construct(AgentRegistry $agentRegistry)
    {
        parent::__construct();
        $this->agentRegistry = $agentRegistry;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Manage agents')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = $input->getArgument('action');

        if ($action === 'list') {
            return $this->listAgents($output);
        }

        $output->writeln('<error>Unknown action</error>');
        return Command::FAILURE;
    }

    private function listAgents(OutputInterface $output): int
    {
        $agents = $this->agentRegistry->getAllAgents();
        $table = new Table($output);
        $table->setHeaders(['ID', 'Role', 'Status', 'Current Task']);

        foreach ($agents as $agent) {
            $table->addRow([
                $agent->id,
                $agent->role->value,
                $agent->status,
                $agent->currentTaskId ?? 'None'
            ]);
        }

        $table->render();
        return Command::SUCCESS;
    }
}
