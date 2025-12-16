<?php

namespace MultiPersona\Api\Endpoints;

use MultiPersona\Api\Http\Request;
use MultiPersona\Api\Http\Response;
use MultiPersona\Core\AgentRegistry;

class AgentEndpoint
{
    private AgentRegistry $agentRegistry;

    public function __construct(AgentRegistry $agentRegistry)
    {
        $this->agentRegistry = $agentRegistry;
    }

    public function handle(Request $request): Response
    {
        $method = $request->getMethod();

        if ($method === 'GET') {
            $agents = $this->agentRegistry->getAllAgents();
            return new Response(200, ['Content-Type' => 'application/json'], json_encode($agents));
        }

        return new Response(405, ['Content-Type' => 'application/json'], json_encode(['error' => 'Method not allowed']));
    }
}
