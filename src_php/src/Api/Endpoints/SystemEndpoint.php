<?php

namespace MultiPersona\Api\Endpoints;

use MultiPersona\Api\Http\Request;
use MultiPersona\Api\Http\Response;
use MultiPersona\Core\Dispatcher;

class SystemEndpoint
{
    private Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function handle(Request $request): Response
    {
        $method = $request->getMethod();

        if ($method === 'GET') {
            $status = $this->dispatcher->getSystemStatus();
            return new Response(200, ['Content-Type' => 'application/json'], json_encode($status));
        }

        return new Response(405, ['Content-Type' => 'application/json'], json_encode(['error' => 'Method not allowed']));
    }
}
