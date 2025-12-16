import * as readline from 'readline';
import { Dispatcher } from '../core/dispatcher';
import { ContextManager } from '../core/context_manager';
import { TaskManager } from '../core/task_manager';
import { AgentRegistry } from '../core/agent_registry';
import { AgentRole } from '../common/types';

export class InteractiveShell {
    private rl: readline.Interface;

    constructor(
        private dispatcher: Dispatcher,
        private contextManager: ContextManager,
        private taskManager: TaskManager,
        private agentRegistry: AgentRegistry
    ) {
        this.rl = readline.createInterface({
            input: process.stdin,
            output: process.stdout,
            prompt: 'FizzLaMetta> '
        });
    }

    public start() {
        console.log('Welcome to Fizz La Metta Interactive Shell.');
        console.log('Type "help" for a list of commands.');
        this.rl.prompt();

        this.rl.on('line', async (line) => {
            const input = line.trim();
            if (input) {
                await this.handleCommand(input);
            }
            this.rl.prompt();
        }).on('close', () => {
            console.log('Goodbye!');
            process.exit(0);
        });
    }

    private async handleCommand(input: string) {
        const [cmd, ...args] = input.split(' ');

        try {
            switch (cmd.toLowerCase()) {
                case 'help':
                    this.showHelp();
                    break;
                case 'status':
                    await this.showStatus();
                    break;
                case 'agents':
                    await this.listAgents();
                    break;
                case 'tasks':
                    await this.listTasks();
                    break;
                case 'create':
                    await this.createTask(args.join(' '));
                    break;
                case 'search':
                    await this.searchContext(args.join(' '));
                    break;
                case 'exit':
                case 'quit':
                    this.rl.close();
                    break;
                default:
                    console.log(`Unknown command: ${cmd}`);
                    break;
            }
        } catch (error: any) {
            console.error(`Error executing command: ${error.message}`);
        }
    }

    private showHelp() {
        console.log(`
Available Commands:
  help                  Show this help message
  status                Show system status summary
  agents                List all agents and their status
  tasks                 List all tasks
  create <description>  Create a new task from natural language description
  search <query>        Search the context/knowledge base
  exit, quit            Exit the shell
    `);
    }

    private async showStatus() {
        const tasks = await this.taskManager.listTasks();
        const agents = this.agentRegistry.findAgentsByRole(AgentRole.WePlan); // Just checking one role for now as example
        // In real app we'd query all.

        const pending = tasks.filter(t => t.status === 'Pending').length;
        const inProgress = tasks.filter(t => t.status === 'InProgress').length;
        const completed = tasks.filter(t => t.status === 'Completed').length;

        console.log(`
System Status:
  Tasks: ${tasks.length} Total (${pending} Pending, ${inProgress} Running, ${completed} Done)
  Agents: [Querying specific roles would go here]
    `);
    }

    private async listAgents() {
        // We need a way to list ALL agents. 
        // For now, let's iterate known roles or add a method to registry to list all.
        // Assuming we can just list a few for demo.
        const roles = Object.values(AgentRole);
        for (const role of roles) {
            const agents = this.agentRegistry.findAgentsByRole(role as AgentRole);
            if (agents.length > 0) {
                console.log(`Role: ${role}`);
                agents.forEach(a => console.log(`  - ${a.id} [${a.status}]`));
            }
        }
    }

    private async listTasks() {
        const tasks = await this.taskManager.listTasks();
        if (tasks.length === 0) {
            console.log('No tasks found.');
            return;
        }
        tasks.forEach(t => {
            console.log(`[${t.id}] ${t.name} (${t.status}) - Assigned: ${t.assignedTo || 'Unassigned'}`);
        });
    }

    private async createTask(description: string) {
        if (!description) {
            console.log('Usage: create <description>');
            return;
        }
        console.log(`Creating task from: "${description}"...`);

        // Here we would use the Translator service.
        // For now, we'll do a simple mock creation or use the logic from index.ts if we had access to Translator.
        // Let's assume we create a generic task.

        const task = await this.taskManager.createTask({
            name: 'Manual Task',
            description: description,
            type: 'TAS',
            priority: 5,
            dependencies: [],
            assignedTo: AgentRole.WePlan, // Defaulting for now
            artifacts: [],
            metadata: {}
        });

        console.log(`Task created: ${task.id}`);

        // Attempt dispatch
        const result = await this.dispatcher.dispatch(task);
        if (result.success) {
            console.log(`Task dispatched to ${result.agentId}`);
        } else {
            console.log(`Task queued (Dispatch result: ${result.reason})`);
        }
    }

    private async searchContext(query: string) {
        if (!query) {
            console.log('Usage: search <query>');
            return;
        }
        const results = await this.contextManager.search(query);
        if (results.length === 0) {
            console.log('No results found.');
        } else {
            results.forEach(r => {
                console.log(`[Score: ${r.score.toFixed(2)}] ${r.document.content.substring(0, 100)}...`);
            });
        }
    }
}
