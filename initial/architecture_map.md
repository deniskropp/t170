# Architecture Map: Playbook to Live App

⫻task/architecture:map/001
## Core Components
| Playbook Concept | Live App Component | Description |
| :--- | :--- | :--- |
| **Orchestrator** | `CoreService` / `EventLoop` | Central service managing the main execution loop, task dispatching, and state updates. |
| **Agents** | `AgentModules` / `Workers` | Individual modules or classes implementing the `Agent` interface (e.g., `WePlanAgent`, `CodeinAgent`). |
| **TAS (Tasks)** | `TaskQueue` & `TaskDB` | Tasks are stored in a persistent database (SQLite/Postgres) and managed via a priority queue (Redis/Memory). |
| **KickLang** | `SchemaValidator` | A library responsible for parsing, validating, and serializing data against KickLang definitions. |
| **Space Format** | `MessageParser` | Middleware that parses incoming/outgoing messages to ensure strict adherence to the `⫻` format. |
| **Memory/Context** | `ContextStore` | Combination of Relational DB (for structured state) and Vector Store (for semantic context/RAG). |

⫻task/architecture:map/002
## Service Architecture
```mermaid
graph TD
    User[User Interface / CLI] -->|Input| API[API Gateway / Controller]
    API -->|Command| Orch[Orchestrator Service]
    Orch -->|Read/Write| DB[(State DB)]
    Orch -->|Dispatch| Dispatcher[Task Dispatcher]
    Dispatcher -->|Assign| AgentPool[Agent Worker Pool]
    AgentPool -->|Execute| Agents[Specific Agents (WePlan, Dima, etc.)]
    Agents -->|Validate| Validator[KickLang Validator]
    Agents -->|Store| Artifacts[Artifact Store]
    Monitor[System Monitor] -->|Watch| Orch
    Monitor -->|Watch| AgentPool
```

⫻task/architecture:map/003
## Data Models (Conceptual)
- **TaskModel**: `id`, `name`, `status`, `priority`, `dependencies`, `assigned_agent`, `payload`
- **AgentModel**: `role`, `status`, `capabilities`, `current_task_id`
- **MessageModel**: `sender`, `receiver`, `content`, `timestamp`, `type`
- **ArtifactModel**: `path`, `type`, `content_hash`, `created_by`

⫻task/architecture:map/004
## Directory Structure Plan
```
/src
  /core
    /orchestrator
    /dispatcher
    /monitor
  /agents
    /base
    /impl (WePlan, Dima, etc.)
  /common
    /schemas (KickLang)
    /protocols (Space Format)
    /utils
  /infrastructure
    /db
    /queue
    /io
```
