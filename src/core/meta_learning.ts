import { TaskRecord, FeedbackRecord, OptimizationSuggestion, AgentRole } from '../common/types';
import { TaskManager } from './task_manager';
import { MistralClient } from '../services/mistral_client';
import { getConfig } from '../config';

export class FeedbackAnalyzer {
    constructor(private taskManager: TaskManager) { }

    public async analyzeTask(taskId: string, feedback: FeedbackRecord): Promise<number> {
        // Mock Analysis Logic
        // In a real system, this would analyze the task artifacts and feedback sentiment
        console.log(`[FeedbackAnalyzer] Analyzing task ${taskId} with rating ${feedback.rating}`);
        return feedback.rating / 5.0; // Normalized score
    }
}

export class PromptOptimizer {
    private mistralClient: MistralClient;
    
    constructor() {
        const config = getConfig();
        this.mistralClient = new MistralClient(config.mistralApiKey, config.mistralBaseUrl);
    }
    
    public async generateSuggestion(role: AgentRole, performanceHistory: FeedbackRecord[]): Promise<OptimizationSuggestion | null> {
        try {
            // Simple heuristic: If average rating is low, suggest a change
            const avgRating = performanceHistory.reduce((sum, f) => sum + f.rating, 0) / performanceHistory.length;

            if (avgRating < 3.0) {
                // Use Mistral AI to generate optimization suggestions
                const systemPrompt = `You are a Prompt Optimization AI. Your task is to analyze agent performance feedback and suggest improvements to the system prompt.
                
                Context:
                - Agent Role: ${role}
                - Average Rating: ${avgRating.toFixed(1)}/5.0
                - Recent Feedback: ${performanceHistory.slice(0, 3).map(f => f.comment).join(' | ')}
                
                Rules:
                1. Analyze the feedback to identify common issues
                2. Suggest specific improvements to the system prompt
                3. Provide clear reasoning for your suggestions
                4. Return only valid JSON`;
                
                const prompt = `Analyze the following performance data and suggest prompt optimizations:
                
                Current System Prompt: "Current System Prompt..."
                
                Performance Issues:
                - Average rating is ${avgRating.toFixed(1)}/5.0
                - Recent feedback includes: ${performanceHistory.slice(0, 3).map(f => `"${f.comment}"`).join(', ')}
                
                Provide your optimization suggestion as JSON with fields:
                - reasoning: Detailed analysis of the issues
                - suggestedPrompt: The improved system prompt
                - confidence: Your confidence score (0.0-1.0)`;
                
                const response = await this.mistralClient.generateText(prompt, systemPrompt, 0.7);
                
                // Parse the response
                try {
                    const suggestionData = JSON.parse(response);
                    
                    return {
                        role: role,
                        currentPrompt: "Current System Prompt...",
                        suggestedPrompt: suggestionData.suggestedPrompt || "Improved System Prompt with more specific constraints...",
                        reasoning: suggestionData.reasoning || "Agent consistently fails to meet quality standards in recent tasks.",
                        confidence: suggestionData.confidence || 0.85
                    };
                } catch (parseError) {
                    console.error('Failed to parse LLM response for prompt optimization:', parseError);
                    // Fallback to simple suggestion
                    return {
                        role: role,
                        currentPrompt: "Current System Prompt...",
                        suggestedPrompt: "Improved System Prompt with more specific constraints...",
                        reasoning: "Agent consistently fails to meet quality standards in recent tasks.",
                        confidence: 0.85
                    };
                }
            }

            return null;
        } catch (error) {
            console.error('Prompt optimization failed:', error);
            // Fallback to simple heuristic
            const avgRating = performanceHistory.reduce((sum, f) => sum + f.rating, 0) / performanceHistory.length;

            if (avgRating < 3.0) {
                return {
                    role: role,
                    currentPrompt: "Current System Prompt...",
                    suggestedPrompt: "Improved System Prompt with more specific constraints...",
                    reasoning: "Agent consistently fails to meet quality standards in recent tasks.",
                    confidence: 0.85
                };
            }

            return null;
        }
    }
}
