# KickLang Schema Definitions

⫻task/schema:kicklang/001
## Core Entity: Task (TAS)
```kicklang
entity Task {
  id: String;
  name: String;
  description: String;
  state: Enum(Pending, InProgress, Completed, Cancelled);
  dependencies: List<String>; // List of Task IDs
  assigned_agent: String; // Agent Role Name
  output_artifact: String; // Path to artifact
}
```

⫻task/schema:kicklang/002
## Core Entity: Agent
```kicklang
entity Agent {
  role: String;
  mission: String;
  responsibilities: List<String>;
  constraints: List<String>;
  system_prompt: String;
}
```

⫻task/schema:kicklang/003
## Core Entity: Protocol
```kicklang
entity Protocol {
  name: String;
  description: String;
  rules: List<String>;
  format_spec: String; // Regex or Format String
}
```

⫻task/schema:kicklang/004
## Core Entity: EthicalGuideline
```kicklang
entity EthicalGuideline {
  principle: String; // e.g., Beneficence
  description: String;
  implementation_practices: List<String>;
}
```

⫻task/schema:kicklang/005
## Core Entity: Workflow
```kicklang
entity Workflow {
  name: String;
  steps: List<Task>;
  trigger: String; // Event or Condition
  outcome: String;
}
```

⫻task/schema:kicklang/006
## Core Entity: Message
```kicklang
entity Message {
  sender: String; // Agent Role
  receiver: String; // Agent Role or Broadcast
  content: String;
  timestamp: DateTime;
  type: Enum(Command, Query, Info, Alert);
}
```
