import axios from 'axios';

export interface MistralRequest {
    model: string;
    messages: Array<{
        role: 'user' | 'assistant' | 'system';
        content: string;
    }>;
    temperature?: number;
    max_tokens?: number;
}

export interface MistralResponse {
    id: string;
    object: string;
    created: number;
    model: string;
    choices: Array<{
        index: number;
        message: {
            role: string;
            content: string;
        };
        finish_reason: string;
    }>;
    usage: {
        prompt_tokens: number;
        completion_tokens: number;
        total_tokens: number;
    };
}

export class MistralClient {
    private apiKey: string;
    private baseUrl: string;
    
    constructor(apiKey: string, baseUrl: string = 'https://api.mistral.ai/v1') {
        this.apiKey = apiKey;
        this.baseUrl = baseUrl;
    }
    
    public async chatCompletion(request: MistralRequest): Promise<MistralResponse> {
        try {
            const response = await axios.post(`${this.baseUrl}/chat/completions`, request, {
                headers: {
                    'Authorization': `Bearer ${this.apiKey}`,
                    'Content-Type': 'application/json'
                }
            });
            return response.data as MistralResponse;
        } catch (error) {
            console.error('Mistral API Error:', error instanceof Error ? error.message : String(error));
            throw error;
        }
    }
    
    public async generateText(prompt: string, systemPrompt: string = '', temperature: number = 0.7): Promise<string> {
        const messages: Array<{role: 'user' | 'assistant' | 'system', content: string}> = [];
        
        if (systemPrompt) {
            messages.push({role: 'system', content: systemPrompt});
        }
        
        messages.push({role: 'user', content: prompt});
        
        const request: MistralRequest = {
            model: 'mistral-tiny', // or 'mistral-small', 'mistral-medium', etc.
            messages: messages,
            temperature: temperature
        };
        
        const response = await this.chatCompletion(request);
        return response.choices[0].message.content;
    }
}