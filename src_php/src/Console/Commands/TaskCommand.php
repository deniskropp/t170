<?php

namespace MultiPersona\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use MultiPersona\Core\TaskManager;

class TaskCommand extends Command
{
    protected static $defaultName = 'task';
    private TaskManager $taskManager;

    public function __construct(TaskManager $taskManager)
    {
        parent::__construct();
        $this->taskManager = $taskManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Manage tasks')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list, create, show')
            ->addArgument('taskId', InputArgument::OPTIONAL, 'Task ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Task name')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Task description')
            ->addOption('priority', null, InputOption::VALUE_REQUIRED, 'Task priority', 5);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = $input->getArgument('action');

        switch ($action) {
            case 'list':
                return $this->listTasks($output);
            case 'create':
                return $this->createTask($input, $output);
            case 'show':
                return $this->showTask($input, $output);
            default:
                $output->writeln('<error>Unknown action</error>');
                return Command::FAILURE;
        }
    }

    private function listTasks(OutputInterface $output): int
    {
        $tasks = $this->taskManager->getAllTasks();
        $table = new Table($output);
        $table->setHeaders(['ID', 'Name', 'Status', 'Priority']);

        foreach ($tasks as $task) {
            $table->addRow([
                $task->id,
                $task->name,
                $task->status->value,
                $task->priority
            ]);
        }

        $table->render();
        return Command::SUCCESS;
    }

    private function createTask(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getOption('name');
        if (!$name) {
            $output->writeln('<error>Name is required for create action</error>');
            return Command::FAILURE;
        }

        $taskData = [
            'name' => $name,
            'description' => $input->getOption('description') ?? '',
            'type' => 'general',
            'priority' => (int) $input->getOption('priority')
        ];

        $task = $this->taskManager->createTask($taskData);

        $output->writeln("Task created with ID: " . $task->id);
        return Command::SUCCESS;
    }

    private function showTask(InputInterface $input, OutputInterface $output): int
    {
        $taskId = $input->getArgument('taskId');
        if (!$taskId) {
            $output->writeln('<error>Task ID is required for show action</error>');
            return Command::FAILURE;
        }

        $task = $this->taskManager->getTask($taskId);
        if (!$task) {
            $output->writeln('<error>Task not found</error>');
            return Command::FAILURE;
        }

        $output->writeln("ID: " . $task->id);
        $output->writeln("Name: " . $task->name);
        $output->writeln("Status: " . $task->status->value);
        $output->writeln("Priority: " . $task->priority);
        $output->writeln("Description: " . $task->description);

        return Command::SUCCESS;
    }
}
