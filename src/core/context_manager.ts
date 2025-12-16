import { Document, SearchResult } from '../common/types';

export class ContextManager {
    private documents: Document[] = [];

    constructor() { }

    public async addDocument(doc: Omit<Document, 'embedding'>): Promise<void> {
        const embedding = await this.generateEmbedding(doc.content);
        this.documents.push({ ...doc, embedding });
    }

    public async search(query: string, limit: number = 5): Promise<SearchResult[]> {
        const queryEmbedding = await this.generateEmbedding(query);

        const results = this.documents.map(doc => ({
            document: doc,
            score: this.cosineSimilarity(queryEmbedding, doc.embedding!)
        }));

        return results
            .sort((a, b) => b.score - a.score)
            .slice(0, limit);
    }

    private async generateEmbedding(text: string): Promise<number[]> {
        // Mock Embedding Generation (Random Vector for now)
        // In a real system, this would call an Embedding API (e.g., OpenAI, HuggingFace)
        return Array(128).fill(0).map(() => Math.random());
    }

    private cosineSimilarity(vecA: number[], vecB: number[]): number {
        const dotProduct = vecA.reduce((sum, a, i) => sum + a * vecB[i], 0);
        const magnitudeA = Math.sqrt(vecA.reduce((sum, a) => sum + a * a, 0));
        const magnitudeB = Math.sqrt(vecB.reduce((sum, b) => sum + b * b, 0));
        return dotProduct / (magnitudeA * magnitudeB);
    }
}
