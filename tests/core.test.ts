import { TaskManager } from '../src/core/task_manager';
import { AgentRegistry } from '../src/core/agent_registry';
import { Dispatcher } from '../src/core/dispatcher';
import { DatabaseService } from '../src/infrastructure/db';
import { EthicalReviewer } from '../src/core/ethics';
import { AgentRole } from '../src/common/types';
import * as fs from 'fs';
import * as path from 'path';

const TEST_DB_DIR = path.join(__dirname, 'test_data');

describe('Core Modules', () => {
    let dbService: DatabaseService;
    let taskManager: TaskManager;
    let agentRegistry: AgentRegistry;
    let dispatcher: Dispatcher;
    let ethicalReviewer: EthicalReviewer;

    beforeAll(() => {
        if (fs.existsSync(TEST_DB_DIR)) {
            fs.rmSync(TEST_DB_DIR, { recursive: true, force: true });
        }
        fs.mkdirSync(TEST_DB_DIR, { recursive: true });
        dbService = new DatabaseService(TEST_DB_DIR);
        taskManager = new TaskManager(dbService);
        agentRegistry = new AgentRegistry(dbService);
        ethicalReviewer = new EthicalReviewer();
        dispatcher = new Dispatcher(taskManager, agentRegistry, ethicalReviewer);
    });

    afterAll(() => {
        if (fs.existsSync(TEST_DB_DIR)) {
            fs.rmSync(TEST_DB_DIR, { recursive: true, force: true });
        }
    });

    test('TaskManager: Create and Retrieve Task', async () => {
        const task = await taskManager.createTask({
            name: 'Test Task',
            description: 'A simple test task',
            type: 'TAS',
            priority: 1,
            dependencies: [],
            assignedTo: AgentRole.WePlan,
            artifacts: [],
            metadata: {}
        });

        expect(task).toBeDefined();
        expect(task.id).toBeDefined();
        expect(task.status).toBe('Pending');

        const retrieved = await taskManager.getTask(task.id);
        expect(retrieved).toBeDefined();
        expect(retrieved?.name).toBe('Test Task');
    });

    test('AgentRegistry: Register and Retrieve Agent', () => {
        const agentId = 'agent-test-001';
        agentRegistry.register({
            id: agentId,
            role: AgentRole.WePlan,
            capabilities: ['planning'],
            status: 'Idle',
            lastActive: new Date(),
            isEphemeral: false
        });

        const agent = agentRegistry.getAgent(agentId);
        expect(agent).toBeDefined();
        expect(agent?.role).toBe(AgentRole.WePlan);
        expect(agent?.status).toBe('Idle');
    });

    test('Dispatcher: Dispatch Task to Agent', async () => {
        // Create a task
        const task = await taskManager.createTask({
            name: 'Dispatchable Task',
            description: 'Task for dispatch test',
            type: 'TAS',
            priority: 1,
            dependencies: [],
            assignedTo: AgentRole.WePlan,
            artifacts: [],
            metadata: {}
        });

        // Ensure agent is available (registered in previous test)
        // Register a second agent to handle potential concurrency with previous test tasks
        agentRegistry.register({
            id: 'agent-test-002',
            role: AgentRole.WePlan,
            capabilities: ['planning'],
            status: 'Idle',
            lastActive: new Date(),
            isEphemeral: false
        });

        const result = await dispatcher.dispatch(task);
        expect(result.success).toBe(true);
        expect(result.agentId).toBe('agent-test-001');

        const updatedTask = await taskManager.getTask(task.id);
        expect(updatedTask?.status).toBe('InProgress');

        const updatedAgent = agentRegistry.getAgent('agent-test-001');
        expect(updatedAgent?.status).toBe('Busy');
    });
});
