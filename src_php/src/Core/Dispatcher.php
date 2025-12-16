<?php

namespace MultiPersona\Core;

use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\AgentRole;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Common\Message;
use MultiPersona\Infrastructure\DatabaseServiceInterface;
use MultiPersona\Infrastructure\EventifyQueue;

class Dispatcher
{
    private TaskManager $taskManager;
    private AgentRegistry $agentRegistry;
    private EventifyQueue $messageBus;
    private DatabaseServiceInterface $database;
    private bool $isRunning = false;
    private int $dispatchInterval = 5; // seconds

    public function __construct(
        TaskManager $taskManager,
        AgentRegistry $agentRegistry,
        EventifyQueue $messageBus,
        DatabaseServiceInterface $database
    ) {
        $this->taskManager = $taskManager;
        $this->agentRegistry = $agentRegistry;
        $this->messageBus = $messageBus;
        $this->database = $database;
    }

    public function startProcessing(): void
    {
        if ($this->isRunning) {
            return;
        }

        $this->isRunning = true;
        error_log("Dispatcher started processing tasks...");

        while ($this->isRunning) {
            try {
                $this->processReadyTasks();
                sleep($this->dispatchInterval);
            } catch (\Exception $e) {
                error_log("Dispatcher error: " . $e->getMessage());
                sleep(1); // Short delay before retry
            }
        }

        error_log("Dispatcher stopped processing tasks.");
    }

    public function stopProcessing(): void
    {
        $this->isRunning = false;
    }

    public function setDispatchInterval(int $seconds): void
    {
        $this->dispatchInterval = max(1, $seconds);
    }

    public function processReadyTasks(): int
    {
        $readyTasks = $this->taskManager->getReadyTasks();
        $dispatchedCount = 0;

        foreach ($readyTasks as $task) {
            try {
                $result = $this->dispatchTask($task);
                if ($result['success']) {
                    $dispatchedCount++;
                }
            } catch (\Exception $e) {
                error_log("Failed to dispatch task " . $task->id . ": " . $e->getMessage());
                
                // Mark task as failed
                $this->taskManager->failTask($task->id, $e->getMessage());
            }
        }

        return $dispatchedCount;
    }

    public function dispatchTask(TaskRecord $task): array
    {
        // Find available agent
        $agent = $this->findAgentForTask($task);
        
        if (!$agent) {
            return [
                'success' => false,
                'taskId' => $task->id,
                'reason' => 'No available agent found'
            ];
        }

        try {
            // Assign task to agent
            $this->taskManager->assignTask($task->id, $agent->role);
            
            // Create and send dispatch message
            $message = new Message(
                'msg-' . uniqid(),
                new \DateTime(),
                AgentRole::Orchestrator,
                $agent->role,
                'Command',
                'task-dispatch',
                json_encode([
                    'command' => 'execute',
                    'taskId' => $task->id,
                    'priority' => $task->priority
                ])
            );

            $this->messageBus->publishToAgent($message, $agent->role);

            // Update agent status
            $agent->status = 'Busy';
            $agent->currentTaskId = $task->id;
            $agent->lastActive = new \DateTime();
            $this->agentRegistry->update($agent);

            // Log dispatch metric
            $this->logDispatchMetric($task, $agent);

            return [
                'success' => true,
                'taskId' => $task->id,
                'agentId' => $agent->id,
                'agentRole' => $agent->role->value
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'taskId' => $task->id,
                'reason' => $e->getMessage()
            ];
        }
    }

    public function dispatchBatch(int $batchSize = 5): array
    {
        $results = [];
        $readyTasks = $this->taskManager->getReadyTasks();
        
        // Sort by priority (highest first)
        usort($readyTasks, function ($a, $b) {
            return $b->priority <=> $a->priority;
        });

        $processed = 0;
        foreach ($readyTasks as $task) {
            if ($processed >= $batchSize) {
                break;
            }

            $result = $this->dispatchTask($task);
            $results[] = $result;
            
            if ($result['success']) {
                $processed++;
            }
        }

        return $results;
    }

    private function findAgentForTask(TaskRecord $task): ?AgentProfile
    {
        $requirements = [
            'role' => $task->assignedTo?->value,
            'capabilities' => $this->getRequiredCapabilitiesForTask($task)
        ];

        return $this->agentRegistry->findAgentForTask($requirements);
    }

    private function getRequiredCapabilitiesForTask(TaskRecord $task): array
    {
        // Map task types to required capabilities
        $taskTypeCapabilities = [
            'TAS' => ['task_execution'],
            'planning' => ['strategic_planning'],
            'code' => ['code_implementation'],
            'ethics' => ['ethical_review'],
            'translation' => ['kicklang_translation'],
            'monitoring' => ['system_analysis']
        ];

        return $taskTypeCapabilities[$task->type] ?? ['general_execution'];
    }

    private function logDispatchMetric(TaskRecord $task, AgentProfile $agent): void
    {
        $metric = new \MultiPersona\Common\MetricPoint(
            'task_dispatch',
            1,
            [
                'taskId' => $task->id,
                'taskType' => $task->type,
                'agentRole' => $agent->role->value,
                'agentId' => $agent->id,
                'priority' => $task->priority
            ],
            new \DateTime()
        );

        $this->database->recordMetric($metric);
    }

    public function handleTaskCompletion(string $taskId, array $result): void
    {
        $task = $this->taskManager->getTask($taskId);
        if (!$task) {
            error_log("Task completion: Task not found: " . $taskId);
            return;
        }

        try {
            // Update task status
            $this->taskManager->completeTask($taskId, $result);
            
            // Update agent status
            if ($task->assignedTo) {
                $agents = $this->agentRegistry->getAgentsByRole($task->assignedTo);
                foreach ($agents as $agent) {
                    if ($agent->currentTaskId === $taskId) {
                        $agent->status = 'Idle';
                        $agent->currentTaskId = null;
                        $agent->lastActive = new \DateTime();
                        $this->agentRegistry->update($agent);
                        break;
                    }
                }
            }

            // Log completion metric
            $this->logCompletionMetric($task, $result);

            // Check if this task completion unblocks other tasks
            $this->checkForUnblockedTasks($taskId);

        } catch (\Exception $e) {
            error_log("Error handling task completion: " . $e->getMessage());
        }
    }

    public function handleTaskFailure(string $taskId, string $error): void
    {
        $task = $this->taskManager->getTask($taskId);
        if (!$task) {
            error_log("Task failure: Task not found: " . $taskId);
            return;
        }

        try {
            // Update task status
            $this->taskManager->failTask($taskId, $error);
            
            // Update agent status
            if ($task->assignedTo) {
                $agents = $this->agentRegistry->getAgentsByRole($task->assignedTo);
                foreach ($agents as $agent) {
                    if ($agent->currentTaskId === $taskId) {
                        $agent->status = 'Idle';
                        $agent->currentTaskId = null;
                        $agent->lastActive = new \DateTime();
                        $this->agentRegistry->update($agent);
                        break;
                    }
                }
            }

            // Log failure metric
            $this->logFailureMetric($task, $error);

        } catch (\Exception $e) {
            error_log("Error handling task failure: " . $e->getMessage());
        }
    }

    private function checkForUnblockedTasks(string $completedTaskId): void
    {
        $allTasks = $this->taskManager->getAllTasks();
        
        foreach ($allTasks as $task) {
            if ($task->status === \MultiPersona\Common\TaskStatus::Pending 
                && in_array($completedTaskId, $task->dependencies)) {
                
                // Check if all dependencies are now satisfied
                $allSatisfied = true;
                foreach ($task->dependencies as $depId) {
                    $depTask = $this->taskManager->getTask($depId);
                    if (!$depTask || $depTask->status !== \MultiPersona\Common\TaskStatus::Completed) {
                        $allSatisfied = false;
                        break;
                    }
                }

                if ($allSatisfied) {
                    $task->status = \MultiPersona\Common\TaskStatus::Ready;
                    $this->taskManager->updateTask($task);
                }
            }
        }
    }

    private function logCompletionMetric(TaskRecord $task, array $result): void
    {
        $metric = new \MultiPersona\Common\MetricPoint(
            'task_completion',
            1,
            [
                'taskId' => $task->id,
                'taskType' => $task->type,
                'status' => 'success',
                'duration' => isset($result['duration']) ? $result['duration'] : 0
            ],
            new \DateTime()
        );

        $this->database->recordMetric($metric);
    }

    private function logFailureMetric(TaskRecord $task, string $error): void
    {
        $metric = new \MultiPersona\Common\MetricPoint(
            'task_completion',
            1,
            [
                'taskId' => $task->id,
                'taskType' => $task->type,
                'status' => 'failure',
                'error' => substr($error, 0, 100) // Truncate long errors
            ],
            new \DateTime()
        );

        $this->database->recordMetric($metric);
    }

    public function getSystemStatus(): array
    {
        return [
            'isRunning' => $this->isRunning,
            'dispatchInterval' => $this->dispatchInterval,
            'taskCounts' => $this->taskManager->getTaskCountByStatus(),
            'agentStatus' => $this->agentRegistry->getAgentStatusSummary(),
            'queueSizes' => $this->getQueueSizes()
        ];
    }

    private function getQueueSizes(): array
    {
        $sizes = [];
        foreach ($this->messageBus->getAllQueues() as $queueName) {
            $sizes[$queueName] = $this->messageBus->getQueueSize($queueName);
        }
        return $sizes;
    }

    public function processHighPriorityTasks(): int
    {
        $highPriorityTasks = array_filter($this->taskManager->getReadyTasks(), function ($task) {
            return $task->priority >= 8; // High priority threshold
        });

        $dispatchedCount = 0;
        foreach ($highPriorityTasks as $task) {
            $result = $this->dispatchTask($task);
            if ($result['success']) {
                $dispatchedCount++;
            }
        }

        return $dispatchedCount;
    }

    public function rebalanceWorkload(): int
    {
        $busyAgents = $this->agentRegistry->getBusyAgents();
        $idleAgents = $this->agentRegistry->getIdleAgents();

        if (count($busyAgents) <= 1 || empty($idleAgents)) {
            return 0; // No rebalancing needed
        }

        $reassignedCount = 0;
        
        // Find tasks that could be reassigned
        $readyTasks = $this->taskManager->getReadyTasks();
        
        foreach ($readyTasks as $task) {
            if ($task->status === \MultiPersona\Common\TaskStatus::Ready) {
                // Try to find a more suitable agent
                $currentAgent = null;
                if ($task->assignedTo) {
                    $agents = $this->agentRegistry->getAgentsByRole($task->assignedTo);
                    foreach ($agents as $agent) {
                        if ($agent->currentTaskId === $task->id) {
                            $currentAgent = $agent;
                            break;
                        }
                    }
                }

                if ($currentAgent) {
                    // Look for a better suited idle agent
                    $betterAgent = $this->findBetterAgentForTask($task, $currentAgent);
                    if ($betterAgent) {
                        // Reassign task
                        $task->assignedTo = $betterAgent->role;
                        $this->taskManager->updateTask($task);
                        
                        // Notify new agent
                        $message = new Message(
                            'msg-' . uniqid(),
                            new \DateTime(),
                            AgentRole::Orchestrator,
                            $betterAgent->role,
                            'Command',
                            'task-reassign',
                            json_encode([
                                'taskId' => $task->id,
                                'reason' => 'workload_rebalancing'
                            ])
                        );

                        $this->messageBus->publishToAgent($message, $betterAgent->role);
                        $reassignedCount++;
                    }
                }
            }
        }

        return $reassignedCount;
    }

    private function findBetterAgentForTask(TaskRecord $task, AgentProfile $currentAgent): ?AgentProfile
    {
        $idleAgents = $this->agentRegistry->getIdleAgents();
        
        // Filter agents that can handle this task
        $suitableAgents = array_filter($idleAgents, function ($agent) use ($task) {
            return $agent->role === $task->assignedTo;
        });

        if (empty($suitableAgents)) {
            return null;
        }

        // Find agent with most relevant capabilities
        usort($suitableAgents, function ($a, $b) use ($task) {
            $aScore = $this->calculateAgentSuitabilityScore($a, $task);
            $bScore = $this->calculateAgentSuitabilityScore($b, $task);
            return $bScore <=> $aScore; // Higher score first
        });

        // Only reassign if the new agent has significantly better suitability
        $bestAgent = $suitableAgents[0];
        $currentScore = $this->calculateAgentSuitabilityScore($currentAgent, $task);
        $bestScore = $this->calculateAgentSuitabilityScore($bestAgent, $task);

        if ($bestScore > $currentScore * 1.2) { // At least 20% better
            return $bestAgent;
        }

        return null;
    }

    private function calculateAgentSuitabilityScore(AgentProfile $agent, TaskRecord $task): float
    {
        $score = 0;
        
        // Base score for role match
        if ($agent->role === $task->assignedTo) {
            $score += 10;
        }

        // Capability matching
        $requiredCapabilities = $this->getRequiredCapabilitiesForTask($task);
        foreach ($requiredCapabilities as $capability) {
            if (in_array($capability, $agent->capabilities)) {
                $score += 5;
            }
        }

        // Recent activity (more recent = better)
        $timeSinceActive = time() - $agent->lastActive->getTimestamp();
        $score += max(0, 10 - ($timeSinceActive / 3600)); // Max 10 points, decreases over time

        return $score;
    }
}