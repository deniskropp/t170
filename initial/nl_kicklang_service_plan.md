# Natural Language to KickLang Translation Service Plan

⫻task/service:NL-KickLang/001
## Service Overview
**Purpose**: To bridge the gap between human users (Natural Language) and the formal system (KickLang). It ensures that user intents are accurately translated into executable structured data.

**Key Responsibilities**:
-   **Intent Classification**: Determining what the user wants to do (e.g., Create Task, Query Status).
-   **Entity Extraction**: Identifying key parameters (e.g., Task Name, Agent Role).
-   **Schema Mapping**: Converting extracted data into valid KickLang JSON.
-   **Validation**: Ensuring the output conforms to the KickLang Schema.

⫻task/service:NL-KickLang/002
## Core Components

### TranslatorEngine (Kick La Metta)
**Description**: The core logic utilizing LLMs for translation.
```typescript
interface TranslationRequest {
  input: string; // User's natural language query
  context?: Record<string, any>; // Current conversation state
}

interface TranslationResult {
  success: boolean;
  kicklang?: any; // The structured output
  confidence: number;
  error?: string;
}

interface TranslatorEngine {
  translate(request: TranslationRequest): Promise<TranslationResult>;
}
```

### SchemaRegistry
**Description**: Provides the target schemas for translation.
```typescript
interface SchemaRegistry {
  getSchema(type: string): JSONSchema;
  validate(data: any, type: string): ValidationResult;
}
```

⫻task/service:NL-KickLang/003
## Translation Pipeline

1.  **Pre-processing**: Clean and normalize user input.
2.  **Prompt Construction**:
    *   Inject System Prompt ("You are Kick La Metta...").
    *   Inject relevant KickLang Schemas (e.g., `Task`, `Message`).
    *   Inject User Input.
3.  **LLM Inference**: Generate the KickLang structure.
4.  **Post-processing**: Parse JSON from LLM output.
5.  **Validation**:
    *   Check against `SchemaRegistry`.
    *   If invalid, trigger **Self-Correction Loop** (feed error back to LLM).
6.  **Output**: Return validated KickLang object.

⫻task/service:NL-KickLang/004
## Example Flow

**User Input**: "Create a high priority task for WePlan to design the database schema."

**Translation**:
1.  **Intent**: `CreateTask`
2.  **Entities**:
    *   `name`: "Design database schema"
    *   `priority`: 5 (High)
    *   `assigned_agent`: "WePlan"
3.  **KickLang Output**:
    ```json
    {
      "type": "Task",
      "name": "Design database schema",
      "priority": 5,
      "assigned_agent": "WePlan",
      "status": "Pending"
    }
    ```

⫻task/service:NL-KickLang/005
## Integration
*   **API Gateway**: Intercepts user messages and routes them to `TranslatorEngine` before sending to Orchestrator.
*   **Orchestrator**: Receives structured KickLang commands instead of raw text, reducing ambiguity.
