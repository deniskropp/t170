<?php

namespace MultiPersona\Core;

use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\TaskStatus;
use MultiPersona\Common\AgentRole;
use MultiPersona\Infrastructure\DatabaseServiceInterface;
use MultiPersona\Infrastructure\EventifyQueue;

class TaskManager
{
    private DatabaseServiceInterface $database;
    private EventifyQueue $messageBus;

    public function __construct(DatabaseServiceInterface $database, EventifyQueue $messageBus)
    {
        $this->database = $database;
        $this->messageBus = $messageBus;
    }

    public function createTask(array $taskData): TaskRecord
    {
        $taskId = 'task-' . uniqid();
        $now = new \DateTime();

        $task = new TaskRecord(
            $taskId,
            $taskData['name'],
            $taskData['description'],
            $taskData['type'] ?? 'TAS',
            TaskStatus::Pending,
            $taskData['priority'] ?? 5,
            $taskData['dependencies'] ?? [],
            isset($taskData['assignedTo']) ? AgentRole::from($taskData['assignedTo']) : null,
            $taskData['artifacts'] ?? [],
            $now,
            $now,
            $taskData['metadata'] ?? []
        );

        return $this->database->createTask($task);
    }

    public function getTask(string $taskId): ?TaskRecord
    {
        return $this->database->getTask($taskId);
    }

    public function updateTask(TaskRecord $task): TaskRecord
    {
        $task->updatedAt = new \DateTime();
        return $this->database->updateTask($task);
    }

    public function getTasksByStatus(TaskStatus $status): array
    {
        return $this->database->getTasksByStatus($status);
    }

    public function getReadyTasks(): array
    {
        $pendingTasks = $this->database->getTasksByStatus(TaskStatus::Pending);
        $readyTasks = [];

        foreach ($pendingTasks as $task) {
            if ($this->areDependenciesSatisfied($task)) {
                $task->status = TaskStatus::Ready;
                $this->database->updateTask($task);
                $readyTasks[] = $task;
            }
        }

        return $readyTasks;
    }

    public function getTasksForAgent(AgentRole $agentRole): array
    {
        $readyTasks = $this->getReadyTasks();
        $agentTasks = [];

        foreach ($readyTasks as $task) {
            if ($task->assignedTo === $agentRole) {
                $agentTasks[] = $task;
            }
        }

        return $agentTasks;
    }

    public function assignTask(string $taskId, AgentRole $agentRole): TaskRecord
    {
        $task = $this->getTask($taskId);
        if (!$task) {
            throw new \Exception("Task not found: " . $taskId);
        }

        $task->assignedTo = $agentRole;
        $task->status = TaskStatus::Ready;
        $task->updatedAt = new \DateTime();

        return $this->database->updateTask($task);
    }

    public function completeTask(string $taskId, array $result): TaskRecord
    {
        $task = $this->getTask($taskId);
        if (!$task) {
            throw new \Exception("Task not found: " . $taskId);
        }

        $task->status = TaskStatus::Completed;
        $task->updatedAt = new \DateTime();
        $task->metadata['completion_result'] = $result;

        return $this->database->updateTask($task);
    }

    public function failTask(string $taskId, string $error): TaskRecord
    {
        $task = $this->getTask($taskId);
        if (!$task) {
            throw new \Exception("Task not found: " . $taskId);
        }

        $task->status = TaskStatus::Failed;
        $task->updatedAt = new \DateTime();
        $task->metadata['error'] = $error;

        return $this->database->updateTask($task);
    }

    public function getTaskDependencies(string $taskId): array
    {
        $task = $this->getTask($taskId);
        if (!$task) {
            return [];
        }

        $dependencyTasks = [];
        foreach ($task->dependencies as $dependencyId) {
            $dependencyTask = $this->getTask($dependencyId);
            if ($dependencyTask) {
                $dependencyTasks[] = $dependencyTask;
            }
        }

        return $dependencyTasks;
    }

    public function getTasksByType(string $type): array
    {
        $allTasks = [];
        foreach (TaskStatus::cases() as $status) {
            $tasks = $this->database->getTasksByStatus($status);
            foreach ($tasks as $task) {
                if ($task->type === $type) {
                    $allTasks[] = $task;
                }
            }
        }

        return $allTasks;
    }

    public function getTasksByPriority(int $minPriority): array
    {
        $allTasks = [];
        foreach (TaskStatus::cases() as $status) {
            $tasks = $this->database->getTasksByStatus($status);
            foreach ($tasks as $task) {
                if ($task->priority >= $minPriority) {
                    $allTasks[] = $task;
                }
            }
        }

        usort($allTasks, function ($a, $b) {
            return $b->priority <=> $a->priority; // Higher priority first
        });

        return $allTasks;
    }

    private function areDependenciesSatisfied(TaskRecord $task): bool
    {
        if (empty($task->dependencies)) {
            return true;
        }

        foreach ($task->dependencies as $dependencyId) {
            $dependencyTask = $this->getTask($dependencyId);
            if (!$dependencyTask || $dependencyTask->status !== TaskStatus::Completed) {
                return false;
            }
        }

        return true;
    }

    public function getTaskCountByStatus(): array
    {
        $counts = [];
        foreach (TaskStatus::cases() as $status) {
            $tasks = $this->database->getTasksByStatus($status);
            $counts[$status->value] = count($tasks);
        }
        return $counts;
    }

    public function getAllTasks(): array
    {
        $allTasks = [];
        foreach (TaskStatus::cases() as $status) {
            $tasks = $this->database->getTasksByStatus($status);
            $allTasks = array_merge($allTasks, $tasks);
        }
        return $allTasks;
    }

    public function deleteTask(string $taskId): bool
    {
        try {
            $this->database->getConnection()->exec("DELETE FROM tasks WHERE id = ?", [$taskId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createTaskWithDependencies(array $taskData, array $dependencyIds): TaskRecord
    {
        $task = $this->createTask($taskData);
        
        // Verify dependencies exist
        foreach ($dependencyIds as $depId) {
            $depTask = $this->getTask($depId);
            if (!$depTask) {
                throw new \Exception("Dependency task not found: " . $depId);
            }
        }

        $task->dependencies = $dependencyIds;
        $task->status = $this->areDependenciesSatisfied($task) 
            ? TaskStatus::Ready 
            : TaskStatus::Pending;

        return $this->database->updateTask($task);
    }

    public function getTaskGraph(): array
    {
        $allTasks = $this->getAllTasks();
        $graph = [];

        foreach ($allTasks as $task) {
            $graph[$task->id] = [
                'task' => $task,
                'dependencies' => []
            ];

            foreach ($task->dependencies as $depId) {
                $depTask = $this->getTask($depId);
                if ($depTask) {
                    $graph[$task->id]['dependencies'][] = $depTask;
                }
            }
        }

        return $graph;
    }
}