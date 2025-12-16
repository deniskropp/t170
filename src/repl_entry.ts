import * as path from 'path';
import * as fs from 'fs';
import { TaskManager } from './core/task_manager';
import { AgentRegistry } from './core/agent_registry';
import { Dispatcher } from './core/dispatcher';
import { MessageBus } from './core/message_bus';
import { InteractiveShell } from './interfaces/repl';

async function main() {
    // 1. Setup Infrastructure
    const storageDir = path.join(__dirname, '../data');
    if (!fs.existsSync(storageDir)) {
        fs.mkdirSync(storageDir, { recursive: true });
    }

    const { DatabaseService } = require('./infrastructure/db');
    const dbService = new DatabaseService(storageDir);

    const taskManager = new TaskManager(dbService);
    const agentRegistry = new AgentRegistry(dbService);
    const messageBus = new MessageBus();

    // Ethics Setup
    const { EthicalReviewer } = require('./core/ethics');
    const ethicalReviewer = new EthicalReviewer();

    const dispatcher = new Dispatcher(taskManager, agentRegistry, ethicalReviewer);

    // Context Setup
    const { ContextManager } = require('./core/context_manager');
    const contextManager = new ContextManager();

    // Start REPL
    const shell = new InteractiveShell(dispatcher, contextManager, taskManager, agentRegistry);
    shell.start();
}

if (require.main === module) {
    main().catch(console.error);
}
