import { TaskManager } from './task_manager';
import { AgentRegistry } from './agent_registry';
import { TaskRecord } from '../common/types';
import { EthicalReviewer } from './ethics';

import { RoleGenerator } from './role_generator';

export interface DispatchResult {
    success: boolean;
    agentId?: string;
    reason?: string;
}

export class Dispatcher {
    private roleGenerator: RoleGenerator;

    constructor(
        private taskManager: TaskManager,
        private agentRegistry: AgentRegistry,
        private ethicalReviewer: EthicalReviewer
    ) {
        this.roleGenerator = new RoleGenerator();
    }

    public async dispatchBatch(): Promise<DispatchResult[]> {
        const readyTasks = await this.taskManager.getReadyTasks();
        const results: DispatchResult[] = [];

        for (const task of readyTasks) {
            // Check if task is already assigned or running (double check)
            if (task.status !== 'Pending') continue;

            // Ethical Review Checkpoint (Pre-Execution)
            const review = await this.ethicalReviewer.review({
                taskId: task.id,
                context: `Task: ${task.name} - ${task.description}`,
                stage: 'Pre-Execution'
            });

            if (!review.approved) {
                await this.taskManager.updateTask(task.id, {
                    status: 'Blocked',
                    metadata: { ethicalConcerns: review.concerns }
                });
                results.push({ success: false, reason: `Ethical Review Failed: ${review.feedback}` });
                continue;
            }

            if (!task.assignedTo) {
                results.push({ success: false, reason: 'Task has no assigned role' });
                continue;
            }

            const agent = this.agentRegistry.getIdleAgent(task.assignedTo);
            if (!agent) {
                // Try to generate a dynamic role if no agent exists and task is high priority
                if (task.priority >= 4) {
                    console.log(`[Dispatcher] No agent found for ${task.assignedTo}. Attempting dynamic role generation...`);
                    const newRoleDef = await this.roleGenerator.generateRoleForTask(task.description || task.name);

                    // Register new ephemeral agent
                    const newAgentId = `agent-dynamic-${Date.now()}`;
                    this.agentRegistry.register({
                        id: newAgentId,
                        role: newRoleDef.role,
                        capabilities: newRoleDef.capabilities || [],
                        status: 'Idle',
                        lastActive: new Date(),
                        isEphemeral: true
                    });

                    // Retry assignment
                    const newAgent = this.agentRegistry.getAgent(newAgentId);
                    if (newAgent) {
                        await this.taskManager.updateTask(task.id, {
                            status: 'InProgress',
                            assignedTo: newAgent.role
                        });
                        this.agentRegistry.updateStatus(newAgent.id, 'Busy', task.id);
                        results.push({ success: true, agentId: newAgent.id });
                        continue;
                    }
                }

                // No agent available for this task, skip to next
                continue;
            }

            // Assign task
            await this.taskManager.updateTask(task.id, {
                status: 'InProgress',
                assignedTo: agent.role
            });

            this.agentRegistry.updateStatus(agent.id, 'Busy', task.id);
            results.push({ success: true, agentId: agent.id });
        }

        return results;
    }

    // Keep single dispatch for backward compatibility or specific overrides
    public async dispatch(task: TaskRecord): Promise<DispatchResult> {
        return (await this.dispatchBatch())[0] || { success: false, reason: 'Batch dispatch failed or no tasks ready' };
    }
    
    // Allow external setting of role generator (for testing or custom implementations)
    public setRoleGenerator(roleGenerator: RoleGenerator): void {
        this.roleGenerator = roleGenerator;
    }
}
