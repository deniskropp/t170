<?php

namespace MultiPersona\Core;

use MultiPersona\Common\TaskRecord;

class TaskScheduler
{
    private array $scheduledTasks = [];

    public function schedule(TaskRecord $task, int $timestamp): void
    {
        $this->scheduledTasks[] = [
            'task' => $task,
            'time' => $timestamp
        ];

        // Sort by time
        usort($this->scheduledTasks, fn($a, $b) => $a['time'] <=> $b['time']);
    }

    public function getDueTasks(int $currentTimestamp): array
    {
        $due = [];
        foreach ($this->scheduledTasks as $key => $item) {
            if ($item['time'] <= $currentTimestamp) {
                $due[] = $item['task'];
                unset($this->scheduledTasks[$key]);
            }
        }
        return $due;
    }
}
