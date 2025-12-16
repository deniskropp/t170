<?php

namespace MultiPersona\Infrastructure;

use MultiPersona\Common\Message;
use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\AgentRole;

class EventifyQueue
{
    private array $queues = [];
    private array $subscribers = [];
    private array $messageHistory = [];
    private int $messageCounter = 0;

    public function __construct()
    {
        // Initialize default queues
        $this->queues['default'] = [];
        $this->queues['high_priority'] = [];
        $this->queues['low_priority'] = [];
        
        // Initialize agent-specific queues
        foreach (AgentRole::cases() as $role) {
            $this->queues[$role->value] = [];
        }
    }

    public function publish(Message $message, string $queueName = 'default'): string
    {
        $messageId = 'msg-' . uniqid() . '-' . (++$this->messageCounter);
        $message->id = $messageId;
        
        if (!isset($this->queues[$queueName])) {
            $this->queues[$queueName] = [];
        }

        $this->queues[$queueName][] = $message;
        $this->messageHistory[$messageId] = $message;

        // Notify subscribers
        $this->notifySubscribers($queueName, $message);

        return $messageId;
    }

    public function subscribe(string $queueName, callable $callback): string
    {
        $subscriberId = 'sub-' . uniqid();
        $this->subscribers[$queueName][$subscriberId] = $callback;
        return $subscriberId;
    }

    public function unsubscribe(string $queueName, string $subscriberId): void
    {
        if (isset($this->subscribers[$queueName][$subscriberId])) {
            unset($this->subscribers[$queueName][$subscriberId]);
        }
    }

    public function consume(string $queueName, callable $handler, int $batchSize = 1): int
    {
        if (!isset($this->queues[$queueName])) {
            return 0;
        }

        $processed = 0;
        while ($processed < $batchSize && !empty($this->queues[$queueName])) {
            $message = array_shift($this->queues[$queueName]);
            
            try {
                $handler($message);
                $processed++;
            } catch (\Exception $e) {
                // Handle failed message processing
                $this->handleFailedMessage($message, $e);
                break;
            }
        }

        return $processed;
    }

    public function peek(string $queueName, int $limit = 1): array
    {
        if (!isset($this->queues[$queueName])) {
            return [];
        }

        return array_slice($this->queues[$queueName], 0, $limit);
    }

    public function getQueueSize(string $queueName): int
    {
        return isset($this->queues[$queueName]) ? count($this->queues[$queueName]) : 0;
    }

    public function getMessage(string $messageId): ?Message
    {
        return $this->messageHistory[$messageId] ?? null;
    }

    public function getAllQueues(): array
    {
        return array_keys($this->queues);
    }

    public function publishToAgent(Message $message, AgentRole $agentRole): string
    {
        return $this->publish($message, $agentRole->value);
    }

    public function broadcast(Message $message): string
    {
        $message->receiver = 'Broadcast';
        
        foreach ($this->queues as $queueName => $queue) {
            if ($queueName !== 'default' && $queueName !== 'high_priority' && $queueName !== 'low_priority') {
                $this->publish(clone $message, $queueName);
            }
        }

        return $message->id;
    }

    private function notifySubscribers(string $queueName, Message $message): void
    {
        if (isset($this->subscribers[$queueName])) {
            foreach ($this->subscribers[$queueName] as $subscriberId => $callback) {
                try {
                    $callback($message);
                } catch (\Exception $e) {
                    // Log subscriber error but don't fail the whole notification
                    error_log("EventifyQueue subscriber error: " . $e->getMessage());
                }
            }
        }
    }

    private function handleFailedMessage(Message $message, \Exception $exception): void
    {
        // Create a failed message queue if it doesn't exist
        if (!isset($this->queues['failed'])) {
            $this->queues['failed'] = [];
        }

        // Add error information to the message
        $failedMessage = clone $message;
        $failedMessage->metadata['error'] = $exception->getMessage();
        $failedMessage->metadata['failed_at'] = (new \DateTime())->format('Y-m-d H:i:s');

        $this->queues['failed'][] = $failedMessage;
        
        // Log the failure
        error_log("EventifyQueue message processing failed: " . $exception->getMessage());
    }

    public function getFailedMessages(): array
    {
        return $this->queues['failed'] ?? [];
    }

    public function retryFailedMessages(): int
    {
        if (!isset($this->queues['failed']) || empty($this->queues['failed'])) {
            return 0;
        }

        $retryCount = 0;
        while (!empty($this->queues['failed'])) {
            $message = array_shift($this->queues['failed']);
            
            // Remove error metadata
            unset($message->metadata['error'], $message->metadata['failed_at']);
            
            // Republish to the original queue or default
            $queueName = $message->metadata['original_queue'] ?? 'default';
            $this->publish($message, $queueName);
            $retryCount++;
        }

        return $retryCount;
    }

    public function clearQueue(string $queueName): int
    {
        if (!isset($this->queues[$queueName])) {
            return 0;
        }

        $count = count($this->queues[$queueName]);
        $this->queues[$queueName] = [];
        return $count;
    }
}