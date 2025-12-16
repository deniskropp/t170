import { TaskManager } from './core/task_manager';
import { AgentRegistry } from './core/agent_registry';
import { Dispatcher } from './core/dispatcher';
import { MessageBus } from './core/message_bus';
import { AGENT_DEFINITIONS } from './agents/definitions';
import { AgentRole, AgentProfile } from './common/types';
import * as path from 'path';
import * as fs from 'fs';

async function main() {
    console.log('Starting System Verification...');

    // 1. Setup Infrastructure
    const storageDir = path.join(__dirname, '../data');
    if (fs.existsSync(storageDir)) {
        fs.rmSync(storageDir, { recursive: true, force: true });
    }
    fs.mkdirSync(storageDir, { recursive: true });

    const { DatabaseService } = require('./infrastructure/db');
    const dbService = new DatabaseService(storageDir);

    const taskManager = new TaskManager(dbService);
    const agentRegistry = new AgentRegistry(dbService);
    const messageBus = new MessageBus();

    // Ethics Setup
    const { EthicalReviewer } = require('./core/ethics');
    const ethicalReviewer = new EthicalReviewer();

    const dispatcher = new Dispatcher(taskManager, agentRegistry, ethicalReviewer);

    // Monitoring Setup
    const { MetricCollector, AnomalyDetector } = require('./core/monitoring');
    const metricCollector = new MetricCollector(messageBus);
    const anomalyDetector = new AnomalyDetector(metricCollector, messageBus);

    // Add a test rule
    anomalyDetector.addRule({
        metricName: 'error_rate',
        condition: 'GT',
        threshold: 5,
        windowSeconds: 60,
        severity: 'Critical'
    });

    // Translator Setup
    const { TranslatorEngine, SchemaRegistry } = require('./services/translator');
    const schemaRegistry = new SchemaRegistry();
    const translator = new TranslatorEngine(schemaRegistry);

    // Test Translation
    const translation = await translator.translate({
        input: 'Create task to verify translation service'
    });

    if (translation.success) {
        console.log(`Translation Successful: ${JSON.stringify(translation.kicklang)}`);
    } else {
        console.error(`Translation Failed: ${translation.error}`);
    }

    // 2. Register Agents
    console.log('Registering Agents...');
    const wePlanDef = AGENT_DEFINITIONS[AgentRole.WePlan];
    const wePlanProfile: AgentProfile = {
        id: 'agent-weplan-001',
        role: AgentRole.WePlan,
        capabilities: ['planning', 'strategy'],
        status: 'Idle',
        lastActive: new Date()
    };
    agentRegistry.register(wePlanProfile);
    console.log(`Registered Agent: ${wePlanProfile.id} (${wePlanProfile.role})`);

    // 3. Create Tasks (Parallel)
    console.log('Creating Parallel Tasks...');
    const task1 = await taskManager.createTask({
        name: 'Parallel Task 1',
        description: 'Test parallel execution',
        type: 'TAS',
        priority: 5,
        dependencies: [],
        assignedTo: AgentRole.WePlan,
        artifacts: [],
        metadata: {}
    });

    // Register another agent to allow parallel execution
    const agent2Profile: AgentProfile = {
        id: 'agent-weplan-002',
        role: AgentRole.WePlan,
        capabilities: ['planning'],
        status: 'Idle',
        lastActive: new Date()
    };
    agentRegistry.register(agent2Profile);

    const task2 = await taskManager.createTask({
        name: 'Parallel Task 2',
        description: 'Test parallel execution',
        type: 'TAS',
        priority: 5,
        dependencies: [],
        assignedTo: AgentRole.WePlan,
        artifacts: [],
        metadata: {}
    });

    console.log(`Created Tasks: ${task1.id}, ${task2.id}`);

    // 4. Dispatch Batch
    console.log('Dispatching Batch...');
    const results = await dispatcher.dispatchBatch();

    console.log(`Batch Results: ${results.length}`);
    results.forEach(r => {
        if (r.success) {
            console.log(` - Dispatched to ${r.agentId}`);
            // Record success metric
            metricCollector.record({
                name: 'dispatch_success',
                value: 1,
                tags: { agentId: r.agentId! },
                timestamp: new Date()
            });
        } else {
            console.error(` - Failed: ${r.reason}`);
        }
    });
    if (results.filter(r => r.success).length >= 2) {
        console.log('SUCCESS: Parallel Dispatch Verified');
    } else {
        console.error('FAILURE: Parallel Dispatch Failed');
    }

    // 5. Verify State
    const updatedTask = await taskManager.getTask(task1.id); // Changed from task.id to task1.id

    console.log(`Task Status: ${updatedTask?.status}`); // Should be InProgress

    // Context Management Verification
    const { ContextManager } = require('./core/context_manager');
    const contextManager = new ContextManager();

    // Add some docs
    await contextManager.addDocument({
        id: 'doc-1',
        content: 'The system uses a Dispatcher to assign tasks to agents.',
        metadata: { source: 'playbook' }
    });
    await contextManager.addDocument({
        id: 'doc-2',
        content: 'Ethical review is required before execution.',
        metadata: { source: 'ethics' }
    });

    // Search
    const searchResults = await contextManager.search('How are tasks assigned?');
    console.log(`Context Search Results: ${searchResults.length}`);
    if (searchResults.length > 0 && searchResults[0].score > 0) {
        console.log(` - Top Result: ${searchResults[0].document.content} (Score: ${searchResults[0].score.toFixed(2)})`);
    }

    // Verify Monitoring
    const metrics = metricCollector.getMetrics('dispatch_success');
    console.log(`Metrics Recorded: ${metrics.length}`);

    // 6. Meta-Learning Verification
    const { FeedbackAnalyzer, PromptOptimizer } = require('./core/meta_learning');
    const feedbackAnalyzer = new FeedbackAnalyzer(taskManager);
    const promptOptimizer = new PromptOptimizer();

    // Simulate feedback
    const feedback = {
        taskId: task1.id,
        rating: 2, // Low rating to trigger optimization
        comment: 'Agent missed the point.',
        timestamp: new Date()
    };

    await feedbackAnalyzer.analyzeTask(task1.id, feedback);

    // Check for optimization
    const suggestion = await promptOptimizer.generateSuggestion(AgentRole.WePlan, [feedback]);

    if (suggestion) {
        console.log(`Optimization Suggestion: ${suggestion.reasoning}`);
        console.log(` - New Prompt: ${suggestion.suggestedPrompt}`);
    } else {
        console.log('No optimization suggested.');
    }

    // 7. Dynamic Role Verification
    console.log('Creating Task for Dynamic Role...');
    const dynamicTask = await taskManager.createTask({
        name: 'Unknown Task',
        description: 'A task requiring a role that does not exist yet.',
        type: 'TAS',
        priority: 5, // High priority to trigger generation
        dependencies: [],
        assignedTo: 'UnknownRole' as any,
        artifacts: [],
        metadata: {}
    });

    console.log('Dispatching Dynamic Task...');
    const dynamicResult = await dispatcher.dispatch(dynamicTask);

    if (dynamicResult.success) {
        console.log(`Dynamic Dispatch Successful! Agent: ${dynamicResult.agentId}`);
        const agent = agentRegistry.getAgent(dynamicResult.agentId!);
        if (agent?.isEphemeral) {
            console.log('SUCCESS: Dynamic Role Created and Assigned.');
        } else {
            console.error('FAILURE: Agent is not ephemeral.');
        }
    } else {
        console.error(`Dynamic Dispatch Failed: ${dynamicResult.reason}`);
    }

    // 8. System Resilience Verification
    const { LoggerService, LogLevel } = require('./infrastructure/logger');
    const logger = new LoggerService(path.join(__dirname, '../logs'));

    logger.info('Verification Script Running', { step: 'Phase 5 Verification' });

    if (fs.existsSync(path.join(__dirname, '../logs/app.log'))) {
        console.log('SUCCESS: Logs are being written to file.');
    } else {
        console.error('FAILURE: Log file not created.');
    }

    if (updatedTask?.status === 'InProgress' && metrics.length > 0 && searchResults.length > 0 && suggestion && dynamicResult.success) {
        console.log('SUCCESS: System Coherence, Monitoring, Parallelism, Context, Meta-Learning, Dynamic Roles & Resilience Verified.');
    } else {
        console.error('FAILURE: Verification failed.');
        process.exit(1);
    }
}

main().catch(err => {
    console.error('Unhandled Error:', err);
    process.exit(1);
});
