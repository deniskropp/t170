import request from 'supertest';
import { ApiServer } from '../src/server/api';
import { TaskManager } from '../src/core/task_manager';
import { AgentRegistry } from '../src/core/agent_registry';
import { Dispatcher } from '../src/core/dispatcher';
import { DatabaseService } from '../src/infrastructure/db';
import { EthicalReviewer } from '../src/core/ethics';
import { AgentRole } from '../src/common/types';
import * as fs from 'fs';
import * as path from 'path';

const TEST_DB_DIR = path.join(__dirname, 'test_data_api');

describe('API Integration Tests', () => {
    let app: any;
    let dbService: DatabaseService;
    let taskManager: TaskManager;
    let agentRegistry: AgentRegistry;
    let dispatcher: Dispatcher;
    let ethicalReviewer: EthicalReviewer;
    let apiServer: ApiServer;

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
        apiServer = new ApiServer(taskManager, agentRegistry, dispatcher, 3001);
        app = (apiServer as any).app; // Access private app for testing
    });

    afterAll(() => {
        if (fs.existsSync(TEST_DB_DIR)) {
            fs.rmSync(TEST_DB_DIR, { recursive: true, force: true });
        }
    });

    test('GET /api/tasks - Should return empty list initially', async () => {
        const res = await request(app).get('/api/tasks');
        expect(res.status).toBe(200);
        expect(res.body).toEqual([]);
    });

    test('POST /api/tasks - Should create a task', async () => {
        const res = await request(app).post('/api/tasks').send({
            name: 'API Test Task',
            description: 'Created via API',
            type: 'TAS',
            priority: 1,
            assignedTo: AgentRole.WePlan
        });
        expect(res.status).toBe(200);
        expect(res.body.id).toBeDefined();
        expect(res.body.name).toBe('API Test Task');
    });

    test('GET /api/agents - Should return agents', async () => {
        // Register an agent first
        agentRegistry.register({
            id: 'agent-api-001',
            role: AgentRole.WePlan,
            capabilities: ['api-testing'],
            status: 'Idle',
            lastActive: new Date(),
            isEphemeral: false
        });

        const res = await request(app).get('/api/agents');
        expect(res.status).toBe(200);
        expect(res.body.length).toBeGreaterThan(0);
        expect(res.body[0].id).toBe('agent-api-001');
    });

    test('GET /api/health - Should return status ok', async () => {
        const res = await request(app).get('/api/health');
        expect(res.status).toBe(200);
        expect(res.body.status).toBe('ok');
    });
});
