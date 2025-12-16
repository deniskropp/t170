# Task Management & State Tracking Module Plan

⫻task/module:task-state/001
## Module Overview
**Purpose**: To provide a robust mechanism for creating, tracking, updating, and querying tasks within the MultiPersona system. It ensures that the Orchestrator always has an accurate view of the system's state.

**Key Responsibilities**:
-   **Task Lifecycle Management**: CRUD operations for tasks.
-   **State Persistence**: Saving and loading task states from the database.
-   **Dependency Resolution**: Determining which tasks are ready for execution.
-   **History Tracking**: maintaining a log of state transitions.

⫻task/module:task-state/002
## Data Structures

### Task Record
```typescript
interface TaskRecord {
  id: string;              // UUID
  name: string;            // Human-readable name
  description: string;     // Detailed description
  type: TaskType;          // 'TAS', 'Meta', 'System'
  status: TaskStatus;      // 'Pending', 'Ready', 'InProgress', 'Completed', 'Failed', 'Blocked'
  priority: number;        // 1 (Low) to 5 (Critical)
  dependencies: string[];  // List of parent Task IDs
  assignedTo?: string;     // Agent Role
  artifacts: string[];     // Paths to generated artifacts
  createdAt: Date;
  updatedAt: Date;
  metadata: Record<string, any>;
}
```

### State Snapshot
```typescript
interface StateSnapshot {
  timestamp: Date;
  activeTasks: number;
  completedTasks: number;
  failedTasks: number;
  agentLoad: Record<string, number>; // Tasks per agent
}
```

⫻task/module:task-state/003
## Core Interfaces

### TaskManager
```typescript
interface TaskManager {
  createTask(task: CreateTaskDTO): Promise<TaskRecord>;
  updateTask(id: string, updates: Partial<TaskRecord>): Promise<TaskRecord>;
  getTask(id: string): Promise<TaskRecord | null>;
  listTasks(filter: TaskFilter): Promise<TaskRecord[]>;
  getReadyTasks(): Promise<TaskRecord[]>; // Returns tasks with status 'Pending' and all dependencies 'Completed'
}
```

### StateTracker
```typescript
interface StateTracker {
  logTransition(taskId: string, from: TaskStatus, to: TaskStatus, reason?: string): Promise<void>;
  getHistory(taskId: string): Promise<StateTransitionLog[]>;
  captureSnapshot(): Promise<StateSnapshot>;
}
```

⫻task/module:task-state/004
## Key Algorithms

### Dependency Resolution (Topological Sort / Check)
*   **Trigger**: When `getReadyTasks()` is called or a task completes.
*   **Logic**:
    1.  Fetch all `Pending` tasks.
    2.  For each task, check if all `dependencies` exist and have `status === 'Completed'`.
    3.  If yes, mark/return as `Ready`.

### State Transition Validation
*   **Logic**: Enforce valid transitions (e.g., cannot go from `Completed` to `Pending` without explicit reset).
    *   `Pending` -> `Ready`
    *   `Ready` -> `InProgress`
    *   `InProgress` -> `Completed` | `Failed` | `Blocked`
    *   `Blocked` -> `Ready` (if unblocked)

⫻task/module:task-state/005
## Integration Points
*   **Orchestrator**: Calls `TaskManager.getReadyTasks()` to find work. Calls `TaskManager.updateTask()` to assign agents and complete tasks.
*   **Persistence Layer**: Uses SQLite/Postgres adapter to store `TaskRecord`s.
*   **SystemMonitor**: Queries `StateTracker` for snapshots and health metrics.
