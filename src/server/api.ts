import express from 'express';
import cors from 'cors';
import { TaskManager } from '../core/task_manager';
import { AgentRegistry } from '../core/agent_registry';
import { Dispatcher } from '../core/dispatcher';
import { AgentRole } from '../common/types';
import { LoggerService } from '../infrastructure/logger';
import rateLimit from 'express-rate-limit';

export class ApiServer {
    private app: express.Application;
    private port: number;
    private logger: LoggerService;

    constructor(
        private taskManager: TaskManager,
        private agentRegistry: AgentRegistry,
        private dispatcher: Dispatcher,
        port: number = 3000
    ) {
        this.app = express();
        this.port = port;
        this.logger = new LoggerService();
        this.setupMiddleware();
        this.setupRoutes();
        this.setupErrorHandling();
    }

    private setupMiddleware() {
        this.app.use(cors());
        this.app.use(express.json());

        // Rate Limiting
        const limiter = rateLimit({
            windowMs: 15 * 60 * 1000, // 15 minutes
            max: 100, // Limit each IP to 100 requests per windowMs
            message: 'Too many requests from this IP, please try again later.'
        });
        this.app.use(limiter);

        // Request Logging
        this.app.use((req, res, next) => {
            this.logger.info(`${req.method} ${req.url}`, { ip: req.ip });
            next();
        });
    }

    private setupRoutes() {
        // Health Check
        this.app.get('/api/health', (req, res) => {
            res.json({ status: 'ok' });
        });

        // Tasks
        this.app.get('/api/tasks', async (req, res) => {
            const tasks = await this.taskManager.listTasks();
            res.json(tasks);
        });

        this.app.get('/api/tasks/:id', async (req, res) => {
            const task = await this.taskManager.getTask(req.params.id);
            if (task) res.json(task);
            else res.status(404).send('Task not found');
        });

        this.app.post('/api/tasks', async (req, res) => {
            try {
                const task = await this.taskManager.createTask(req.body);
                // Auto-dispatch attempt
                this.dispatcher.dispatch(task).catch(console.error);
                res.json(task);
            } catch (e: any) {
                res.status(500).send(e.message);
            }
        });

        // Agents
        this.app.get('/api/agents', (req, res) => {
            // Aggregate all agents
            const roles = Object.values(AgentRole);
            let allAgents: any[] = [];
            for (const role of roles) {
                allAgents = allAgents.concat(this.agentRegistry.findAgentsByRole(role as AgentRole));
            }
            res.json(allAgents);
        });

        this.app.get('/api/agents/:role', (req, res) => {
            const agents = this.agentRegistry.findAgentsByRole(req.params.role as AgentRole);
            res.json(agents);
        });
    }

    private setupErrorHandling() {
        this.app.use((err: any, req: express.Request, res: express.Response, next: express.NextFunction) => {
            this.logger.error('Unhandled API Error', { error: err.message, stack: err.stack });
            res.status(500).json({ error: 'Internal Server Error' });
        });
    }

    public start() {
        this.app.listen(this.port, () => {
            this.logger.info(`API Server running on port ${this.port}`);
        });
    }
}
