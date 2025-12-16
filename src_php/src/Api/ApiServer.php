<?php

namespace MultiPersona\Api;

use MultiPersona\Api\Http\Request;
use MultiPersona\Api\Http\Response;
use MultiPersona\Api\Endpoints\TaskEndpoint;
use MultiPersona\Api\Endpoints\AgentEndpoint;
use MultiPersona\Api\Endpoints\SystemEndpoint;
use MultiPersona\Core\TaskManager;
use MultiPersona\Core\AgentRegistry;
use MultiPersona\Core\Dispatcher;

class ApiServer
{
    private TaskManager $taskManager;
    private AgentRegistry $agentRegistry;
    private Dispatcher $dispatcher;

    public function __construct(
        TaskManager $taskManager,
        AgentRegistry $agentRegistry,
        Dispatcher $dispatcher
    ) {
        $this->taskManager = $taskManager;
        $this->agentRegistry = $agentRegistry;
        $this->dispatcher = $dispatcher;
    }

    public function handleRequest(Request $request): Response
    {
        $path = $request->getPathInfo();

        try {
            if (str_starts_with($path, '/tasks')) {
                return (new TaskEndpoint($this->taskManager))->handle($request);
            } elseif (str_starts_with($path, '/agents')) {
                return (new AgentEndpoint($this->agentRegistry))->handle($request);
            } elseif (str_starts_with($path, '/system')) {
                return (new SystemEndpoint($this->dispatcher))->handle($request);
            } else {
                return new Response(404, ['Content-Type' => 'application/json'], json_encode(['error' => 'Not found']));
            }
        } catch (\Exception $e) {
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => $e->getMessage()]));
        }
    }
}
