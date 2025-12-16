# T170 - Live App Transformation: Meta-AI Orchestrator

This project implements a live, operational Meta-AI Orchestrator based on the "Live App Transformation Plan". It features a robust agentic system with task management, parallel execution, context management (RAG), and self-evolution capabilities.

## Features

-   **Core Modules**: Task Manager, Agent Registry, Dispatcher, Message Bus.
-   **Advanced Capabilities**:
    -   **Persistent Storage**: SQLite database for tasks and agents.
    -   **Parallel Execution**: Concurrent task dispatching.
    -   **Context Management**: Vector-based document search (RAG).
    -   **Meta-Learning**: Feedback analysis and prompt optimization.
    -   **Dynamic Role Adaptation**: On-the-fly agent role creation using LLM simulation.
-   **Interfaces**:
    -   **REPL**: Interactive Command-Line Shell.
    -   **TUI**: Terminal User Interface Dashboard.
    -   **Web**: React-based Web Dashboard.
-   **Production Readiness**:
    -   Structured Logging.
    -   Global Error Handling.
    -   API Rate Limiting.

## Installation

1.  **Prerequisites**: Node.js (v16+), npm.
2.  **Install Dependencies**:
    ```bash
    npm install
    cd web && npm install && cd ..
    ```

## Usage

### 1. Run the Verification Script (End-to-End Demo)
```bash
npm start
```
This script initializes the system, runs a series of verification steps (including parallel dispatch, context search, meta-learning, and dynamic roles), and outputs the results.

### 2. Run the Interactive Shell (REPL)
```bash
npx ts-node src/repl_entry.ts
```
Commands:
-   `help`: Show available commands.
-   `status`: Show system status.
-   `tasks`: List all tasks.
-   `agents`: List all agents.
-   `create <task description>`: Create a new task.
-   `search <query>`: Search context documents.

### 3. Run the CLI Dashboard (TUI)
```bash
npx ts-node src/tui_entry.ts
```
Displays real-time tables for Tasks and Agents, and a scrolling log view.

### 4. Run the Web Interface
Start the API Server and Web Client:
```bash
# Terminal 1: API Server
npx ts-node src/server/api.ts

# Terminal 2: Web Client
npm run start:web
```
Open [http://localhost:5173](http://localhost:5173) in your browser.

## Testing

Run the comprehensive test suite (Unit & Integration):
```bash
npm test
```

## Documentation

-   [API Documentation](docs/api.md)
-   [Live App Transformation Plan](docs/Live App Transformation Plan.md)
