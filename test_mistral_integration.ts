import { MistralClient } from './src/services/mistral_client';
import { getConfig } from './src/config';
import { TranslatorEngine, SchemaRegistry } from './src/services/translator';
import { RoleGenerator } from './src/core/role_generator';
import { PromptOptimizer } from './src/core/meta_learning';

async function testMistralIntegration() {
    console.log('Testing Mistral AI Integration...');
    
    // Test configuration
    const config = getConfig();
    console.log('Config loaded:', { 
        hasApiKey: !!config.mistralApiKey,
        baseUrl: config.mistralBaseUrl,
        debugMode: config.debugMode
    });
    
    // Test Mistral Client instantiation
    try {
        const mistralClient = new MistralClient(config.mistralApiKey);
        console.log('✓ MistralClient instantiated successfully');
        
        // Note: We won't actually call the API in this test to avoid requiring real API keys
        // This just tests that the client can be created and methods exist
        console.log('✓ MistralClient methods available:', Object.keys(Object.getPrototypeOf(mistralClient)));
        
    } catch (error) {
        console.error('✗ MistralClient instantiation failed:', error);
        return;
    }
    
    // Test Translator Engine
    try {
        const schemaRegistry = new SchemaRegistry();
        const translator = new TranslatorEngine(schemaRegistry);
        console.log('✓ TranslatorEngine instantiated successfully');
        
        // Test translation (this will use Mistral AI)
        const testTranslation = await translator.translate({
            input: 'Create task to test Mistral integration'
        });
        
        console.log('Translation result:', {
            success: testTranslation.success,
            confidence: testTranslation.confidence,
            error: testTranslation.error
        });
        
        if (testTranslation.success && testTranslation.kicklang) {
            console.log('✓ Translation successful:', testTranslation.kicklang);
        }
        
    } catch (error) {
        console.error('✗ TranslatorEngine test failed:', error);
    }
    
    // Test Role Generator
    try {
        const roleGenerator = new RoleGenerator();
        console.log('✓ RoleGenerator instantiated successfully');
        
        const testRole = await roleGenerator.generateRoleForTask('Test task requiring Mistral AI role generation');
        console.log('✓ Role generation successful:', {
            role: testRole.role,
            mission: testRole.mission,
            isEphemeral: testRole.isEphemeral
        });
        
    } catch (error) {
        console.error('✗ RoleGenerator test failed:', error);
    }
    
    // Test Prompt Optimizer
    try {
        const promptOptimizer = new PromptOptimizer();
        console.log('✓ PromptOptimizer instantiated successfully');
        
        const testSuggestion = await promptOptimizer.generateSuggestion('WePlan' as any, [
            { taskId: 'test-1', rating: 2, comment: 'Agent missed key requirements', timestamp: new Date() },
            { taskId: 'test-2', rating: 1, comment: 'Poor quality output', timestamp: new Date() }
        ]);
        
        if (testSuggestion) {
            console.log('✓ Prompt optimization successful:', {
                reasoning: testSuggestion.reasoning,
                confidence: testSuggestion.confidence
            });
        } else {
            console.log('✓ No optimization suggested (ratings too high)');
        }
        
    } catch (error) {
        console.error('✗ PromptOptimizer test failed:', error);
    }
    
    console.log('Mistral AI integration test completed!');
}

testMistralIntegration().catch(console.error);