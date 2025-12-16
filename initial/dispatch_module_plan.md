# Agent Dispatch & Turn-Taking Mechanism Plan

⫻task/module:dispatch/001
## Module Overview
**Purpose**: To assign executable tasks to the most appropriate available agents and manage the flow of execution control (turn-taking) to ensure orderly system operation.

**Key Responsibilities**:
-   **Agent Registry**: Maintaining a directory of active agents and their capabilities.
-   **Dispatch Logic**: Matching tasks to agents based on Role and Availability.
-   **Turn Management**: Enforcing sequential or parallel execution limits.
-   **Load Balancing**: Distributing work evenly among capable agents (if multiple instances exist).

⫻task/module:dispatch/002
## Core Components

### AgentRegistry
**Description**: A dynamic store of registered agents.
```typescript
interface AgentProfile {
  id: string;
  role: string; // e.g., 'WePlan', 'Codein'
  capabilities: string[]; // List of supported actions/tools
  status: 'Idle' | 'Busy' | 'Offline';
  currentTaskId?: string;
  lastActive: Date;
}

interface AgentRegistry {
  register(profile: AgentProfile): void;
  updateStatus(id: string, status: AgentProfile['status']): void;
  findAgentsByRole(role: string): AgentProfile[];
  getIdleAgent(role: string): AgentProfile | null;
}
```

### Dispatcher
**Description**: The logic engine for task assignment.
```typescript
interface Dispatcher {
  dispatch(task: TaskRecord): Promise<DispatchResult>;
}

interface DispatchResult {
  success: boolean;
  agentId?: string;
  reason?: string; // e.g., 'No agents available'
}
```

⫻task/module:dispatch/003
## Dispatch Logic & Algorithms

### 1. Role Matching
*   **Input**: Task `assigned_agent` (Role Name).
*   **Process**: Query `AgentRegistry` for agents with `role === task.assigned_agent`.

### 2. Availability Check
*   **Input**: List of candidate agents.
*   **Process**: Filter for `status === 'Idle'`.

### 3. Selection Strategy (Round-Robin / First-Available)
*   **Process**: Select the first idle agent. (Future: Select agent with lowest load or specific context).

### 4. Turn-Taking (Concurrency Control)
*   **Constraint**: The Orchestrator enforces a "Global Turn" or "Topic Lock".
*   **Logic**:
    *   If `SystemMode` is `Sequential`: Only allow 1 task in `InProgress` state at a time.
    *   If `SystemMode` is `Parallel`: Allow N tasks, provided they don't share write-access dependencies (future complexity).
    *   **Initial Implementation**: Strict Sequential execution to ensure safety and coherence.

⫻task/module:dispatch/004
## Interaction Flow

1.  **Orchestrator** identifies a `Ready` task (Task A).
2.  **Orchestrator** calls `Dispatcher.dispatch(Task A)`.
3.  **Dispatcher** queries `AgentRegistry` for Role 'WePlan'.
4.  **AgentRegistry** returns `[Agent_WePlan_1 (Idle)]`.
5.  **Dispatcher** assigns Task A to `Agent_WePlan_1`.
    *   Updates `AgentRegistry`: `Agent_WePlan_1` -> `Busy`.
    *   Updates `TaskRecord`: `status` -> `InProgress`, `assignedTo` -> `Agent_WePlan_1`.
6.  **Dispatcher** sends `⫻command/dispatch` message to `Agent_WePlan_1`.
7.  **Agent** accepts and executes.

⫻task/module:dispatch/005
## Error Handling
*   **No Agent Available**: Task remains `Ready`. Orchestrator retries next cycle.
*   **Agent Timeout**: If Agent doesn't report back within `TimeoutThreshold`, Dispatcher marks task `Failed` (or `Pending` retry) and Agent `Offline`.
