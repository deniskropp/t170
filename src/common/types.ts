export enum AgentRole {
    Orchestrator = 'Orchestrator',
    RoleDefiner = 'RoleDefiner',
    PromptEngineer = 'PromptEngineer',
    ProtocolEstablisher = 'ProtocolEstablisher',
    SystemMonitor = 'SystemMonitor',
    MetaCommunicator = 'MetaCommunicator',
    FizzLaMetta = 'FizzLaMetta',
    KickLaMetta = 'KickLaMetta',
    Dima = 'Dima',
    AR00L = 'AR-00L',
    QllickBuzzFizz = 'QllickBuzz & QllickFizz',
    WePlan = 'WePlan'
}

export type TaskStatus = 'Pending' | 'Ready' | 'InProgress' | 'Completed' | 'Failed' | 'Blocked';

export interface TaskRecord {
    id: string;
    name: string;
    description: string;
    type: string;
    status: TaskStatus;
    priority: number;
    dependencies: string[];
    assignedTo?: AgentRole;
    artifacts: string[];
    createdAt: Date;
    updatedAt: Date;
    metadata: Record<string, any>;
}

export interface AgentProfile {
    id: string;
    role: AgentRole;
    capabilities: string[];
    status: 'Idle' | 'Busy' | 'Offline';
    currentTaskId?: string;
    lastActive: Date;
    isEphemeral?: boolean;
}

export type MessageType = 'Command' | 'Query' | 'Info' | 'Alert';

export interface Message {
    id: string;
    timestamp: Date;
    sender: AgentRole;
    receiver: AgentRole | 'Broadcast';
    type: MessageType;
    channel: string;
    content: string;
    correlationId?: string;
}

export interface MetricPoint {
    name: string;
    value: number | string;
    tags: Record<string, string>;
    timestamp: Date;
}

export interface Anomaly {
    id: string;
    metricName: string;
    value: number | string;
    threshold: number;
    severity: 'Warning' | 'Critical';
    timestamp: Date;
    message: string;
}

export interface AnomalyRule {
    metricName: string;
    condition: 'GT' | 'LT' | 'EQ';
    threshold: number;
    windowSeconds: number;
    severity: 'Warning' | 'Critical';
}

export interface TranslationRequest {
    input: string;
    context?: Record<string, any>;
}

export interface TranslationResult {
    success: boolean;
    kicklang?: any;
    confidence: number;
    error?: string;
}

export interface EthicalReviewRequest {
    taskId: string;
    context: string;
    stage: 'Pre-Execution' | 'Post-Execution';
}

export interface EthicalReviewResult {
    approved: boolean;
    score: number;
    concerns: string[];
    feedback: string;
}

export interface Document {
    id: string;
    content: string;
    metadata: Record<string, any>;
    embedding?: number[];
}

export interface SearchResult {
    document: Document;
    score: number;
}

export interface FeedbackRecord {
    taskId: string;
    rating: number; // 1-5
    comment: string;
    timestamp: Date;
}

export interface OptimizationSuggestion {
    role: AgentRole;
    currentPrompt: string;
    suggestedPrompt: string;
    reasoning: string;
    confidence: number;
}

export interface AgentDefinition {
    role: AgentRole;
    mission: string;
    responsibilities: string[];
    constraints: string[];
    systemPrompt: string;
    capabilities?: string[];
    isEphemeral?: boolean;
}


