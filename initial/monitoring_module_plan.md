# Monitoring & Alerting System Plan

⫻task/module:monitoring/001
## Module Overview
**Purpose**: To continuously assess the health, stability, and integrity of the MultiPersona system. It acts as the system's immune system, detecting anomalies and triggering corrective actions or escalations.

**Key Responsibilities**:
-   **Metric Collection**: Gathering data on system performance and behavior.
-   **Anomaly Detection**: Identifying deviations from expected baselines or rules.
-   **Alerting**: Notifying the Orchestrator or Humans of critical issues.
-   **Health Reporting**: Providing a dashboard of system status.

⫻task/module:monitoring/002
## Core Metrics

### 1. Coherence Metrics (Qualitative/Structural)
*   **Protocol Adherence**: % of messages passing schema validation.
*   **Role Alignment**: (Future) Semantic similarity between Agent output and Role Definition.
*   **Task Validity**: % of generated artifacts passing verification checks.

### 2. Resilience Metrics (Quantitative/Operational)
*   **Error Rate**: Number of failed tool calls or exceptions per cycle.
*   **Cycle Time**: Average time to complete a `Dispatch-Execute-Report` loop.
*   **Queue Depth**: Number of `Pending` tasks (backlog growth).
*   **Agent Availability**: % of agents in `Idle` vs `Offline` state.

⫻task/module:monitoring/003
## Core Components

### MetricCollector
**Description**: Aggregates raw data points.
```typescript
interface MetricPoint {
  name: string;
  value: number | string;
  tags: Record<string, string>;
  timestamp: Date;
}

interface MetricCollector {
  record(metric: MetricPoint): void;
  getMetrics(filter: MetricFilter): MetricPoint[];
}
```

### AnomalyDetector
**Description**: Analyzes metrics against rules or thresholds.
```typescript
interface AnomalyRule {
  metricName: string;
  condition: 'GT' | 'LT' | 'EQ';
  threshold: number;
  windowSeconds: number;
  severity: 'Warning' | 'Critical';
}

interface AnomalyDetector {
  check(metrics: MetricPoint[]): Anomaly[];
}
```

### AlertManager
**Description**: Routes anomalies to the appropriate channels.
```typescript
interface AlertManager {
  dispatch(anomaly: Anomaly): Promise<void>;
}
```

⫻task/module:monitoring/004
## Operational Processes

### Continuous Monitoring Loop
1.  **Collect**: `MetricCollector` receives events from Orchestrator and Agents (e.g., "Task Completed", "Tool Failed").
2.  **Analyze**: Every N seconds (or on event), `AnomalyDetector` evaluates rules.
    *   *Example Rule*: If `ErrorRate > 5` in `60s`, trigger `Critical` alert.
3.  **Alert**: If anomaly detected, `AlertManager` publishes to `⫻alert/*` channel.
4.  **React**:
    *   **Orchestrator**: Pauses execution if `Critical`.
    *   **SystemMonitor Agent**: Logs issue, attempts auto-recovery (e.g., restart agent), or escalates.

⫻task/module:monitoring/005
## Integration
*   **Orchestrator**: Pushes lifecycle events to `MetricCollector`. Subscribes to `⫻alert/critical` to pause system.
*   **Agents**: Report tool usage and internal errors to `MetricCollector`.
*   **Dashboard**: Visualizes `MetricCollector` data for human observers.
