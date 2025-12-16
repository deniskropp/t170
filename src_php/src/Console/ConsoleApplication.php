<?php

namespace MultiPersona\Console;

use Symfony\Component\Console\Application;
use MultiPersona\Console\Commands\TaskCommand;
use MultiPersona\Console\Commands\AgentCommand;
use MultiPersona\Console\Commands\SystemCommand;
use MultiPersona\Core\TaskManager;
use MultiPersona\Core\AgentRegistry;
use MultiPersona\Core\Dispatcher;

class ConsoleApplication extends Application
{
    public function __construct(
        TaskManager $taskManager,
        AgentRegistry $agentRegistry,
        Dispatcher $dispatcher
    ) {
        parent::__construct('MultiPersona CLI', '1.0.0');

        $this->addCommands([
            new TaskCommand($taskManager),
            new AgentCommand($agentRegistry),
            new SystemCommand($dispatcher)
        ]);
    }
}
