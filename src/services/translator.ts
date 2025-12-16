import { TranslationRequest, TranslationResult } from '../common/types';
import { MistralClient } from './mistral_client';
import { getConfig } from '../config';

export class SchemaRegistry {
    private schemas: Map<string, any> = new Map();

    constructor() {
        // Initialize with core schemas (simplified for now)
        this.schemas.set('Task', {
            type: 'object',
            properties: {
                type: { const: 'Task' },
                name: { type: 'string' },
                priority: { type: 'number' },
                assigned_agent: { type: 'string' }
            },
            required: ['type', 'name']
        });
    }

    public getSchema(type: string): any {
        return this.schemas.get(type);
    }

    public validate(data: any, type: string): boolean {
        const schema = this.getSchema(type);
        if (!schema) return false;
        // Simple validation for now
        if (data.type !== type) return false;
        return true;
    }
}

export class TranslatorEngine {
    private mistralClient: MistralClient;
    
    constructor(private schemaRegistry: SchemaRegistry) {
        const config = getConfig();
        this.mistralClient = new MistralClient(config.mistralApiKey, config.mistralBaseUrl);
    }

    public async translate(request: TranslationRequest): Promise<TranslationResult> {
        try {
            console.log(`[Translator] Translating: "${request.input}"`);
            
            // Use Mistral AI to translate natural language to KickLang
            const systemPrompt = `You are a KickLang translator. Your task is to convert natural language requests into structured KickLang JSON format. 
            
            KickLang schema for Task:
            {
                type: 'Task',
                name: string, // extracted task name
                description: string, // detailed description
                priority: number, // 1-10 scale
                assigned_agent: string, // agent role or 'unassigned'
                status: 'Pending' | 'Ready' | 'InProgress' | 'Completed' | 'Failed' | 'Blocked'
            }
            
            Rules:
            1. Always return valid JSON
            2. If you cannot determine the intent, return an error message
            3. Extract as much information as possible from the input`;
            
            const prompt = `Translate the following natural language request to KickLang:
            "${request.input}"
            
            Return only the JSON object, no additional text.`;
            
            const response = await this.mistralClient.generateText(prompt, systemPrompt, 0.3);
            
            // Parse the response
            try {
                const kicklang = JSON.parse(response);
                
                // Validate the schema
                if (this.schemaRegistry.validate(kicklang, kicklang.type)) {
                    return {
                        success: true,
                        confidence: 0.9, // High confidence from LLM
                        kicklang: kicklang
                    };
                } else {
                    return {
                        success: false,
                        confidence: 0.5,
                        error: 'Generated KickLang does not match expected schema'
                    };
                }
            } catch (parseError) {
                console.error('Failed to parse LLM response:', parseError);
                return {
                    success: false,
                    confidence: 0.3,
                    error: 'Failed to parse LLM response as JSON'
                };
            }
        } catch (error) {
            console.error('Translation failed:', error);
            return {
                success: false,
                confidence: 0.0,
                error: 'Translation service error'
            };
        }
    }
}
