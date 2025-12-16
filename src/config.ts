import * as dotenv from 'dotenv';

dotenv.config();

export interface AppConfig {
    mistralApiKey: string;
    mistralBaseUrl: string;
    debugMode: boolean;
}

export function getConfig(): AppConfig {
    return {
        mistralApiKey: process.env.MISTRAL_API_KEY || '',
        mistralBaseUrl: process.env.MISTRAL_BASE_URL || 'https://api.mistral.ai/v1',
        debugMode: process.env.DEBUG_MODE === 'true'
    };
}