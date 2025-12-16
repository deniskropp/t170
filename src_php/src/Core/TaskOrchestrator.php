<?php

namespace MultiPersona\Core;

use MultiPersona\Common\TaskRecord;

class TaskOrchestrator
{
    private Dispatcher $dispatcher;
    private TaskManager $taskManager;

    public function __construct(Dispatcher $dispatcher, TaskManager $taskManager)
    {
        $this->dispatcher = $dispatcher;
        $this->taskManager = $taskManager;
    }

    public function orchestrate(TaskRecord $task): void
    {
        // Logic to decompose task, assign agents, and track progress
        // For now, simple dispatch
        $this->dispatcher->dispatch($task);
    }
}
