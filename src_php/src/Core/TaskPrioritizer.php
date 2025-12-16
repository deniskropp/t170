<?php

namespace MultiPersona\Core;

use MultiPersona\Common\TaskRecord;

class TaskPrioritizer
{
    public function prioritize(array $tasks): array
    {
        // Sort tasks by priority (higher integer = higher priority)
        usort($tasks, function (TaskRecord $a, TaskRecord $b) {
            return $b->priority <=> $a->priority;
        });

        return $tasks;
    }
}
