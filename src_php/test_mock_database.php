<?php

// Mock database service for testing when SQLite is not available

namespace MultiPersona\Infrastructure;

use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Common\TaskStatus;
use MultiPersona\Common\AgentRole;
use MultiPersona\Common\Message;
use MultiPersona\Common\MetricPoint;

class MockDatabaseService implements DatabaseServiceInterface
{
    private array $tasks = [];
    private array $agents = [];
    private array $messages = [];
    private array $metrics = [];

    public function __construct(string $storageDir)
    {
        // Mock constructor - don't actually create files
    }

    // Task methods
    public function createTask(TaskRecord $task): TaskRecord
    {
        $this->tasks[$task->id] = $task;
        return $task;
    }

    public function getTask(string $taskId): ?TaskRecord
    {
        return $this->tasks[$taskId] ?? null;
    }

    public function updateTask(TaskRecord $task): TaskRecord
    {
        $this->tasks[$task->id] = $task;
        return $task;
    }

    public function getTasksByStatus(TaskStatus $status): array
    {
        return array_filter($this->tasks, function ($task) use ($status) {
            return $task->status->value === $status->value;
        });
    }

    // Agent methods
    public function registerAgent(AgentProfile $agent): AgentProfile
    {
        $this->agents[$agent->id] = $agent;
        return $agent;
    }

    public function getAgent(string $agentId): ?AgentProfile
    {
        return $this->agents[$agentId] ?? null;
    }

    public function updateAgent(AgentProfile $agent): AgentProfile
    {
        $this->agents[$agent->id] = $agent;
        return $agent;
    }

    public function getAvailableAgents(AgentRole $role): array
    {
        return array_filter($this->agents, function ($agent) use ($role) {
            return $agent->role->value === $role->value && $agent->status === 'Idle';
        });
    }

    // Message methods
    public function addMessage(Message $message): Message
    {
        $this->messages[$message->id] = $message;
        return $message;
    }

    public function getMessagesForAgent(AgentRole $agent, int $limit = 100): array
    {
        $agentMessages = array_filter($this->messages, function ($message) use ($agent) {
            return $message->receiver === $agent->value || $message->receiver === 'Broadcast';
        });
        
        return array_slice($agentMessages, 0, $limit);
    }

    // Metric methods
    public function recordMetric(MetricPoint $metric): MetricPoint
    {
        $this->metrics[] = $metric;
        return $metric;
    }

    public function getMetrics(string $name, int $limit = 1000): array
    {
        $filteredMetrics = array_filter($this->metrics, function ($metric) use ($name) {
            return $metric->name === $name;
        });
        
        return array_slice($filteredMetrics, 0, $limit);
    }

    public function getConnection()
    {
        // Return null for mock - this is acceptable for testing
        return null;
    }

    public function beginTransaction(): void
    {
        // Mock transaction
    }

    public function commit(): void
    {
        // Mock commit
    }

    public function rollback(): void
    {
        // Mock rollback
    }
}