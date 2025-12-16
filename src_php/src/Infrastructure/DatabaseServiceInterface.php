<?php

namespace MultiPersona\Infrastructure;

use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Common\TaskStatus;
use MultiPersona\Common\AgentRole;
use MultiPersona\Common\Message;
use MultiPersona\Common\MetricPoint;

interface DatabaseServiceInterface
{
    public function createTask(TaskRecord $task): TaskRecord;
    public function getTask(string $taskId): ?TaskRecord;
    public function updateTask(TaskRecord $task): TaskRecord;
    public function getTasksByStatus(TaskStatus $status): array;
    public function registerAgent(AgentProfile $agent): AgentProfile;
    public function getAgent(string $agentId): ?AgentProfile;
    public function updateAgent(AgentProfile $agent): AgentProfile;
    public function getAvailableAgents(AgentRole $role): array;
    public function addMessage(Message $message): Message;
    public function getMessagesForAgent(AgentRole $agent, int $limit = 100): array;
    public function recordMetric(MetricPoint $metric): MetricPoint;
    public function getMetrics(string $name, int $limit = 1000): array;
    public function getConnection();
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
}