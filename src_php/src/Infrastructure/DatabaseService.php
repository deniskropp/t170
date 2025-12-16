<?php

namespace MultiPersona\Infrastructure;

use PDO;
use PDOException;
use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Common\TaskStatus;
use MultiPersona\Common\AgentRole;
use MultiPersona\Common\Message;
use MultiPersona\Common\MetricPoint;

class DatabaseService implements DatabaseServiceInterface
{
    private PDO $pdo;
    private string $storageDir;

    public function __construct(string $storageDir)
    {
        $this->storageDir = rtrim($storageDir, '/');
        if (!file_exists($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }

        $dbPath = $this->storageDir . '/multipersona.db';
        $this->pdo = new PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initializeDatabase();
    }

    private function initializeDatabase(): void
    {
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        
        // Tasks table
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS tasks (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            description TEXT NOT NULL,
            type TEXT NOT NULL,
            status TEXT NOT NULL,
            priority INTEGER NOT NULL,
            dependencies TEXT NOT NULL,
            assigned_to TEXT,
            artifacts TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            metadata TEXT NOT NULL
        )');

        // Agents table
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS agents (
            id TEXT PRIMARY KEY,
            role TEXT NOT NULL,
            capabilities TEXT NOT NULL,
            status TEXT NOT NULL,
            current_task_id TEXT,
            last_active TEXT NOT NULL,
            is_ephemeral INTEGER NOT NULL DEFAULT 0
        )');

        // Messages table
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS messages (
            id TEXT PRIMARY KEY,
            timestamp TEXT NOT NULL,
            sender TEXT NOT NULL,
            receiver TEXT NOT NULL,
            type TEXT NOT NULL,
            channel TEXT NOT NULL,
            content TEXT NOT NULL,
            correlation_id TEXT
        )');

        // Metrics table
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            value TEXT NOT NULL,
            tags TEXT NOT NULL,
            timestamp TEXT NOT NULL
        )');

        // Create indexes
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_priority ON tasks(priority)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_agents_role ON agents(role)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_agents_status ON agents(status)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_messages_timestamp ON messages(timestamp)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_metrics_name ON metrics(name)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_metrics_timestamp ON metrics(timestamp)');
    }

    // Task methods
    public function createTask(TaskRecord $task): TaskRecord
    {
        $stmt = $this->pdo->prepare('INSERT INTO tasks (
            id, name, description, type, status, priority, dependencies, assigned_to, 
            artifacts, created_at, updated_at, metadata
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        $stmt->execute([
            $task->id,
            $task->name,
            $task->description,
            $task->type,
            $task->status->value,
            $task->priority,
            json_encode($task->dependencies),
            $task->assignedTo?->value,
            json_encode($task->artifacts),
            $task->createdAt->format('Y-m-d H:i:s'),
            $task->updatedAt->format('Y-m-d H:i:s'),
            json_encode($task->metadata)
        ]);

        return $task;
    }

    public function getTask(string $taskId): ?TaskRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE id = ?');
        $stmt->execute([$taskId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new TaskRecord(
            $row['id'],
            $row['name'],
            $row['description'],
            $row['type'],
            TaskStatus::from($row['status']),
            (int)$row['priority'],
            json_decode($row['dependencies'], true),
            $row['assigned_to'] ? AgentRole::from($row['assigned_to']) : null,
            json_decode($row['artifacts'], true),
            new \DateTime($row['created_at']),
            new \DateTime($row['updated_at']),
            json_decode($row['metadata'], true)
        );
    }

    public function updateTask(TaskRecord $task): TaskRecord
    {
        $stmt = $this->pdo->prepare('UPDATE tasks SET 
            name = ?,
            description = ?,
            type = ?,
            status = ?,
            priority = ?,
            dependencies = ?,
            assigned_to = ?,
            artifacts = ?,
            updated_at = ?,
            metadata = ?
            WHERE id = ?');

        $stmt->execute([
            $task->name,
            $task->description,
            $task->type,
            $task->status->value,
            $task->priority,
            json_encode($task->dependencies),
            $task->assignedTo?->value,
            json_encode($task->artifacts),
            $task->updatedAt->format('Y-m-d H:i:s'),
            json_encode($task->metadata),
            $task->id
        ]);

        return $task;
    }

    public function getTasksByStatus(TaskStatus $status): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE status = ?');
        $stmt->execute([$status->value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tasks = [];
        foreach ($rows as $row) {
            $tasks[] = new TaskRecord(
                $row['id'],
                $row['name'],
                $row['description'],
                $row['type'],
                TaskStatus::from($row['status']),
                (int)$row['priority'],
                json_decode($row['dependencies'], true),
                $row['assigned_to'] ? AgentRole::from($row['assigned_to']) : null,
                json_decode($row['artifacts'], true),
                new \DateTime($row['created_at']),
                new \DateTime($row['updated_at']),
                json_decode($row['metadata'], true)
            );
        }

        return $tasks;
    }

    // Agent methods
    public function registerAgent(AgentProfile $agent): AgentProfile
    {
        $stmt = $this->pdo->prepare('INSERT OR REPLACE INTO agents (
            id, role, capabilities, status, current_task_id, last_active, is_ephemeral
        ) VALUES (?, ?, ?, ?, ?, ?, ?)');

        $stmt->execute([
            $agent->id,
            $agent->role->value,
            json_encode($agent->capabilities),
            $agent->status,
            $agent->currentTaskId,
            $agent->lastActive->format('Y-m-d H:i:s'),
            $agent->isEphemeral ? 1 : 0
        ]);

        return $agent;
    }

    public function getAgent(string $agentId): ?AgentProfile
    {
        $stmt = $this->pdo->prepare('SELECT * FROM agents WHERE id = ?');
        $stmt->execute([$agentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new AgentProfile(
            $row['id'],
            AgentRole::from($row['role']),
            json_decode($row['capabilities'], true),
            $row['status'],
            $row['current_task_id'],
            new \DateTime($row['last_active']),
            (bool)$row['is_ephemeral']
        );
    }

    public function updateAgent(AgentProfile $agent): AgentProfile
    {
        $stmt = $this->pdo->prepare('UPDATE agents SET 
            role = ?,
            capabilities = ?,
            status = ?,
            current_task_id = ?,
            last_active = ?,
            is_ephemeral = ?
            WHERE id = ?');

        $stmt->execute([
            $agent->role->value,
            json_encode($agent->capabilities),
            $agent->status,
            $agent->currentTaskId,
            $agent->lastActive->format('Y-m-d H:i:s'),
            $agent->isEphemeral ? 1 : 0,
            $agent->id
        ]);

        return $agent;
    }

    public function getAvailableAgents(AgentRole $role): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM agents WHERE role = ? AND status = ?');
        $stmt->execute([$role->value, 'Idle']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $agents = [];
        foreach ($rows as $row) {
            $agents[] = new AgentProfile(
                $row['id'],
                AgentRole::from($row['role']),
                json_decode($row['capabilities'], true),
                $row['status'],
                $row['current_task_id'],
                new \DateTime($row['last_active']),
                (bool)$row['is_ephemeral']
            );
        }

        return $agents;
    }

    // Message methods
    public function addMessage(Message $message): Message
    {
        $stmt = $this->pdo->prepare('INSERT INTO messages (
            id, timestamp, sender, receiver, type, channel, content, correlation_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

        $stmt->execute([
            $message->id,
            $message->timestamp->format('Y-m-d H:i:s'),
            $message->sender->value,
            $message->receiver instanceof AgentRole ? $message->receiver->value : $message->receiver,
            $message->type,
            $message->channel,
            $message->content,
            $message->correlationId
        ]);

        return $message;
    }

    public function getMessagesForAgent(AgentRole $agent, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM messages WHERE receiver = ? ORDER BY timestamp DESC LIMIT ?');
        $stmt->execute([$agent->value, $limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = new Message(
                $row['id'],
                new \DateTime($row['timestamp']),
                AgentRole::from($row['sender']),
                $row['receiver'] === 'Broadcast' ? 'Broadcast' : AgentRole::from($row['receiver']),
                $row['type'],
                $row['channel'],
                $row['content'],
                $row['correlation_id']
            );
        }

        return $messages;
    }

    // Metric methods
    public function recordMetric(MetricPoint $metric): MetricPoint
    {
        $stmt = $this->pdo->prepare('INSERT INTO metrics (name, value, tags, timestamp) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $metric->name,
            is_numeric($metric->value) ? $metric->value : json_encode($metric->value),
            json_encode($metric->tags),
            $metric->timestamp->format('Y-m-d H:i:s')
        ]);

        return $metric;
    }

    public function getMetrics(string $name, int $limit = 1000): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM metrics WHERE name = ? ORDER BY timestamp DESC LIMIT ?');
        $stmt->execute([$name, $limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $metrics = [];
        foreach ($rows as $row) {
            $value = json_decode($row['value'], true) ?? $row['value'];
            $metrics[] = new MetricPoint(
                $row['name'],
                $value,
                json_decode($row['tags'], true),
                new \DateTime($row['timestamp'])
            );
        }

        return $metrics;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }
}