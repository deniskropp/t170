import * as path from 'path';
import * as fs from 'fs';
import { TaskManager } from './core/task_manager';
import { AgentRegistry } from './core/agent_registry';
import { CLIDashboard } from './interfaces/tui';

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

    // Start TUI
    const dashboard = new CLIDashboard(taskManager, agentRegistry);
    dashboard.start();
}

if (require.main === module) {
    main().catch(console.error);
}
