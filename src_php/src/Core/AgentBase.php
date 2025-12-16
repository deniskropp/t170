<?php

namespace MultiPersona\Core;

use MultiPersona\Common\AgentRole;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\Message;
use MultiPersona\Common\MetricPoint;
use MultiPersona\Infrastructure\DatabaseServiceInterface;
use MultiPersona\Infrastructure\EventifyQueue;

abstract class AgentBase
{
    protected AgentProfile $profile;
    protected DatabaseServiceInterface $database;
    protected EventifyQueue $messageBus;
    protected string $systemPrompt;

    public function __construct(
        AgentProfile $profile,
        DatabaseServiceInterface $database,
        EventifyQueue $messageBus,
        string $systemPrompt
    ) {
        $this->profile = $profile;
        $this->database = $database;
        $this->messageBus = $messageBus;
        $this->systemPrompt = $systemPrompt;
    }

    public function getProfile(): AgentProfile
    {
        return $this->profile;
    }

    public function getRole(): AgentRole
    {
        return $this->profile->role;
    }

    public function getSystemPrompt(): string
    {
        return $this->systemPrompt;
    }

    public function setStatus(string $status): void
    {
        $this->profile->status = $status;
        $this->profile->lastActive = new \DateTime();
        $this->database->updateAgent($this->profile);
    }

    public function setCurrentTask(?string $taskId): void
    {
        $this->profile->currentTaskId = $taskId;
        $this->database->updateAgent($this->profile);
    }

    public function sendMessage(Message $message): string
    {
        return $this->messageBus->publish($message);
    }

    public function receiveMessages(int $limit = 10): array
    {
        return $this->database->getMessagesForAgent($this->profile->role, $limit);
    }

    public function processMessage(Message $message): void
    {
        // This method should be overridden by specific agent implementations
        // Default behavior: log the message
        error_log("Agent " . $this->profile->role->value . " received message: " . $message->content);
    }

    public function canHandleTask(TaskRecord $task): bool
    {
        // Base implementation: check if task is assigned to this agent's role
        return $task->assignedTo === $this->profile->role;
    }

    public function executeTask(TaskRecord $task): array
    {
        $this->setStatus('Busy');
        $this->setCurrentTask($task->id);

        try {
            // Update task status to InProgress
            $task->status = \MultiPersona\Common\TaskStatus::InProgress;
            $task->updatedAt = new \DateTime();
            $this->database->updateTask($task);

            // Execute the task (to be implemented by subclasses)
            $result = $this->performTaskExecution($task);

            // Update task status based on result
            $task->status = $result['success'] 
                ? \MultiPersona\Common\TaskStatus::Completed
                : \MultiPersona\Common\TaskStatus::Failed;
            $task->updatedAt = new \DateTime();
            $task->artifacts = array_merge($task->artifacts, $result['artifacts'] ?? []);
            $task->metadata['execution_result'] = $result;

            $this->database->updateTask($task);

            return [
                'success' => true,
                'taskId' => $task->id,
                'result' => $result
            ];

        } catch (\Exception $e) {
            // Update task status to Failed
            $task->status = \MultiPersona\Common\TaskStatus::Failed;
            $task->updatedAt = new \DateTime();
            $task->metadata['error'] = $e->getMessage();

            $this->database->updateTask($task);

            return [
                'success' => false,
                'taskId' => $task->id,
                'error' => $e->getMessage()
            ];

        } finally {
            $this->setStatus('Idle');
            $this->setCurrentTask(null);
        }
    }

    abstract protected function performTaskExecution(TaskRecord $task): array;

    protected function logMetric(string $name, $value, array $tags = []): void
    {
        $metric = new \MultiPersona\Common\MetricPoint(
            $name,
            $value,
            array_merge($tags, ['agent' => $this->profile->role->value]),
            new \DateTime()
        );

        $this->database->recordMetric($metric);
    }

    protected function createMessage(
        AgentRole|string $receiver,
        string $type,
        string $content,
        string $channel = 'default',
        ?string $correlationId = null
    ): Message {
        return new Message(
            'msg-' . uniqid(),
            new \DateTime(),
            $this->profile->role,
            $receiver,
            $type,
            $channel,
            $content,
            $correlationId
        );
    }

    public function getCapabilities(): array
    {
        return $this->profile->capabilities;
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->profile->capabilities);
    }

    public function isAvailable(): bool
    {
        return $this->profile->status === 'Idle' && $this->profile->currentTaskId === null;
    }
}