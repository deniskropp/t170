import { Message, AgentRole } from '../common/types';

type MessageHandler = (message: Message) => void;

export class MessageBus {
    private subscribers: Map<string, MessageHandler[]> = new Map();

    public subscribe(channel: string, handler: MessageHandler): void {
        if (!this.subscribers.has(channel)) {
            this.subscribers.set(channel, []);
        }
        this.subscribers.get(channel)!.push(handler);
    }

    public publish(message: Message): void {
        // 1. Channel-specific subscribers
        const channelHandlers = this.subscribers.get(message.channel) || [];
        channelHandlers.forEach(handler => handler(message));

        // 2. Broadcast subscribers (if any specific logic needed)
        // For now, we assume agents subscribe to specific channels or wildcards (not impl here)

        // 3. Role-based routing (simulated)
        if (message.receiver !== 'Broadcast') {
            // In a real system, we'd route to the specific agent instance
            // Here we just log it for demonstration
            // console.log(\`[MessageBus] Routing to \${message.receiver}: \${message.content}\`);
        }
    }

    public createMessage(
        sender: AgentRole,
        receiver: AgentRole | 'Broadcast',
        type: Message['type'],
        channel: string,
        content: string,
        correlationId?: string
    ): Message {
        return {
            id: Math.random().toString(36).substring(2, 15),
            timestamp: new Date(),
            sender,
            receiver,
            type,
            channel,
            content,
            correlationId
        };
    }
}
