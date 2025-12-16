# API Documentation

Base URL: `http://localhost:3000/api`

## Endpoints

### Health Check
-   **GET** `/health`
-   **Description**: Check if the API server is running.
-   **Response**: `200 OK`
    ```json
    { "status": "ok" }
    ```

### Tasks

#### List Tasks
-   **GET** `/tasks`
-   **Description**: Retrieve a list of all tasks.
-   **Response**: `200 OK`
    ```json
    [
      {
        "id": "...",
        "name": "Task Name",
        "status": "Pending",
        ...
      }
    ]
    ```

#### Create Task
-   **POST** `/tasks`
-   **Description**: Create a new task.
-   **Body**:
    ```json
    {
      "name": "Task Name",
      "description": "Task Description",
      "type": "TAS",
      "priority": 1,
      "assignedTo": "WePlan"
    }
    ```
-   **Response**: `201 Created`
    ```json
    {
      "id": "...",
      ...
    }
    ```

### Agents

#### List Agents
-   **GET** `/agents`
-   **Description**: Retrieve a list of all registered agents.
-   **Response**: `200 OK`
    ```json
    [
      {
        "id": "agent-001",
        "role": "WePlan",
        "status": "Idle",
        ...
      }
    ]
    ```

## Error Handling

All errors return a standard JSON format:
```json
{
  "error": "Error message"
}
```

## Rate Limiting

The API is rate-limited to 100 requests per 15 minutes per IP address. Exceeding this limit will result in a `429 Too Many Requests` response.
