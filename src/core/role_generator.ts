import { AgentRole, AgentDefinition } from '../common/types';
import { MistralClient } from '../services/mistral_client';
import { getConfig } from '../config';

export class RoleGenerator {
    private mistralClient: MistralClient;
    
    constructor() {
        const config = getConfig();
        this.mistralClient = new MistralClient(config.mistralApiKey, config.mistralBaseUrl);
    }
    
    public async generateRoleForTask(taskDescription: string): Promise<AgentDefinition> {
        try {
            console.log(`[RoleGenerator] Generating new role for task: "${taskDescription}"`);
            
            // Use Mistral AI to generate a new role definition
            const systemPrompt = `You are a Role Generator AI. Your task is to analyze a task description and create a new agent role definition that can handle the task.
            
            The role definition should include:
            - role: A descriptive name for the role (use 'DynamicSpecialist' as base)
            - mission: A clear mission statement
            - responsibilities: Array of specific responsibilities
            - constraints: Array of constraints and limitations
            - systemPrompt: A system prompt that defines the agent's behavior
            - capabilities: Array of capabilities
            - isEphemeral: true (this is a temporary role)
            
            Rules:
            1. Return only valid JSON
            2. Make the role specific to the task requirements
            3. Ensure the system prompt is detailed and constraining
            4. Include ethical considerations in constraints`;
            
            const prompt = `Analyze the following task and generate an appropriate agent role definition:
            
            Task Description: "${taskDescription}"
            
            Return the complete role definition as JSON:`;
            
            const response = await this.mistralClient.generateText(prompt, systemPrompt, 0.5);
            
            // Parse the response
            try {
                const roleDefinition = JSON.parse(response);
                
                // Validate and ensure required fields
                const validatedDefinition: AgentDefinition = {
                    role: roleDefinition.role || 'DynamicSpecialist',
                    mission: roleDefinition.mission || 'Handle specialized tasks that do not fit standard roles.',
                    responsibilities: roleDefinition.responsibilities || ['Analyze task requirements', 'Execute specialized actions', 'Report results'],
                    constraints: roleDefinition.constraints || ['Adhere to ethical guidelines', 'Report anomalies'],
                    systemPrompt: roleDefinition.systemPrompt || 'You are a dynamic specialist created for a specific task. Adapt to the requirements.',
                    capabilities: roleDefinition.capabilities || ['adaptation', 'specialized-execution'],
                    isEphemeral: true
                };
                
                return validatedDefinition;
            } catch (parseError) {
                console.error('Failed to parse LLM response for role generation:', parseError);
                // Fallback to default role
                return {
                    role: 'DynamicSpecialist' as any,
                    mission: 'Handle specialized tasks that do not fit standard roles.',
                    responsibilities: ['Analyze task requirements', 'Execute specialized actions', 'Report results'],
                    constraints: ['Adhere to ethical guidelines', 'Report anomalies'],
                    systemPrompt: 'You are a dynamic specialist created for a specific task. Adapt to the requirements.',
                    capabilities: ['adaptation', 'specialized-execution'],
                    isEphemeral: true
                };
            }
        } catch (error) {
            console.error('Role generation failed:', error);
            // Fallback to default role
            return {
                role: 'DynamicSpecialist' as any,
                mission: 'Handle specialized tasks that do not fit standard roles.',
                responsibilities: ['Analyze task requirements', 'Execute specialized actions', 'Report results'],
                constraints: ['Adhere to ethical guidelines', 'Report anomalies'],
                systemPrompt: 'You are a dynamic specialist created for a specific task. Adapt to the requirements.',
                capabilities: ['adaptation', 'specialized-execution'],
                isEphemeral: true
            };
        }
    }
}
