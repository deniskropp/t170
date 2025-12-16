<?php

namespace MultiPersona\Api\Endpoints;

use MultiPersona\Api\Http\Request;
use MultiPersona\Api\Http\Response;
use MultiPersona\Core\TaskManager;

class TaskEndpoint
{
    private TaskManager $taskManager;

    public function __construct(TaskManager $taskManager)
    {
        $this->taskManager = $taskManager;
    }

    public function handle(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPathInfo();

        switch ($method) {
            case 'GET':
                return $this->handleGet($request, $path);
            case 'POST':
                return $this->handlePost($request);
            default:
                return new Response(405, ['Content-Type' => 'application/json'], json_encode(['error' => 'Method not allowed']));
        }
    }

    private function handleGet(Request $request, string $path): Response
    {
        if ($path === '/tasks') {
            $tasks = $this->taskManager->getAllTasks();
            return new Response(200, ['Content-Type' => 'application/json'], json_encode($tasks));
        }

        // Extract ID from /tasks/{id}
        if (preg_match('#^/tasks/([^/]+)$#', $path, $matches)) {
            $taskId = $matches[1];
            $task = $this->taskManager->getTask($taskId);
            if ($task) {
                return new Response(200, ['Content-Type' => 'application/json'], json_encode($task));
            }
        }

        return new Response(404, ['Content-Type' => 'application/json'], json_encode(['error' => 'Task not found']));
    }

    private function handlePost(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Invalid JSON']));
        }

        // Basic validation
        if (!isset($data['name'])) {
            return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Missing name']));
        }

        // Pass data as array to match TaskManager::createTask signature
        $task = $this->taskManager->createTask($data);

        return new Response(201, ['Content-Type' => 'application/json'], json_encode($task));
    }
}
