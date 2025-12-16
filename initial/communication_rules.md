# Inter-Agent Communication Rules

⫻task/communication:formal/001
## Message Envelope Structure
All inter-agent communications must be encapsulated in a JSON envelope adhering to the `Message` entity defined in the KickLang schema.

```json
{
  "id": "uuid-v4",
  "timestamp": "ISO-8601",
  "sender": "AgentRole",
  "receiver": "AgentRole|Broadcast",
  "type": "Command|Query|Info|Alert",
  "channel": "⫻channel/name",
  "content": "String (Space Format payload)",
  "correlation_id": "uuid-v4 (optional, for response)"
}
```

⫻task/communication:formal/002
## Standard Channels

### 1. Command Channel (`⫻command/*`)
*   **Purpose**: Directives from Orchestrator to Agents.
*   **Pattern**: Request-Response (Async).
*   **Examples**:
    *   `⫻command/dispatch`: Assign a task to an agent.
    *   `⫻command/stop`: Abort current operation.

### 2. Report Channel (`⫻report/*`)
*   **Purpose**: Status updates and results from Agents to Orchestrator.
*   **Pattern**: Push (Fire-and-Forget or Ack).
*   **Examples**:
    *   `⫻report/status`: "In Progress", "Completed".
    *   `⫻report/artifact`: Path to generated artifact.

### 3. Alert Channel (`⫻alert/*`)
*   **Purpose**: High-priority warnings or errors.
*   **Pattern**: Pub-Sub (Broadcast).
*   **Examples**:
    *   `⫻alert/error`: Tool failure or exception.
    *   `⫻alert/violation`: Ethical or protocol violation detected.

### 4. Query Channel (`⫻query/*`)
*   **Purpose**: Information seeking between agents.
*   **Pattern**: Request-Response (Sync/Async).
*   **Examples**:
    *   `⫻query/context`: Requesting state or memory.

⫻task/communication:formal/003
## Interaction Patterns

### Dispatch-Execute-Report Loop
1.  **Orchestrator** sends `⫻command/dispatch` (Task ID, Context).
2.  **Agent** sends `⫻report/status` ("Accepted").
3.  **Agent** executes task.
4.  **Agent** sends `⫻report/artifact` (Result).
5.  **Agent** sends `⫻report/status` ("Completed").

### Escalation Flow
1.  **SystemMonitor** detects anomaly.
2.  **SystemMonitor** broadcasts `⫻alert/violation`.
3.  **Orchestrator** receives alert, pauses relevant tasks.
4.  **Orchestrator** triggers `⫻command/escalate` to Human/Supervisor.

⫻task/communication:formal/004
## Validation Rules
1.  **Schema Compliance**: All `content` payloads must be valid Space Format strings.
2.  **Role Authorization**: Agents can only send on channels appropriate to their role (e.g., only Orchestrator sends `⫻command/dispatch`).
3.  **Correlation**: Responses must include the `correlation_id` of the original request.
