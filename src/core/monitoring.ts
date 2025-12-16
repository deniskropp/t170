import { MetricPoint, Anomaly, AnomalyRule } from '../common/types';
import { MessageBus } from './message_bus';
import { AgentRole } from '../common/types';

export class MetricCollector {
    private metrics: MetricPoint[] = [];

    constructor(private messageBus: MessageBus) { }

    public record(metric: MetricPoint): void {
        this.metrics.push(metric);
        // Prune old metrics (simple in-memory management)
        if (this.metrics.length > 1000) {
            this.metrics.shift();
        }
    }

    public getMetrics(name: string, since?: Date): MetricPoint[] {
        return this.metrics.filter(m => {
            if (m.name !== name) return false;
            if (since && m.timestamp < since) return false;
            return true;
        });
    }
}

export class AnomalyDetector {
    private rules: AnomalyRule[] = [];

    constructor(
        private collector: MetricCollector,
        private messageBus: MessageBus
    ) { }

    public addRule(rule: AnomalyRule): void {
        this.rules.push(rule);
    }

    public check(): Anomaly[] {
        const anomalies: Anomaly[] = [];
        const now = new Date();

        for (const rule of this.rules) {
            const windowStart = new Date(now.getTime() - rule.windowSeconds * 1000);
            const metrics = this.collector.getMetrics(rule.metricName, windowStart);

            if (metrics.length === 0) continue;

            // Simple check: average value over window (assuming numeric)
            // For string values, we'd need different logic (e.g., count)
            const numericValues = metrics
                .map(m => typeof m.value === 'number' ? m.value : parseFloat(m.value as string))
                .filter(v => !isNaN(v));

            if (numericValues.length === 0) continue;

            const avg = numericValues.reduce((a, b) => a + b, 0) / numericValues.length;
            let isAnomaly = false;

            if (rule.condition === 'GT' && avg > rule.threshold) isAnomaly = true;
            if (rule.condition === 'LT' && avg < rule.threshold) isAnomaly = true;
            if (rule.condition === 'EQ' && avg === rule.threshold) isAnomaly = true;

            if (isAnomaly) {
                const anomaly: Anomaly = {
                    id: Math.random().toString(36).substring(2, 15),
                    metricName: rule.metricName,
                    value: avg,
                    threshold: rule.threshold,
                    severity: rule.severity,
                    timestamp: now,
                    message: `Anomaly detected: ${rule.metricName} (${avg}) is ${rule.condition} ${rule.threshold}`
                };
                anomalies.push(anomaly);
                this.reportAnomaly(anomaly);
            }
        }

        return anomalies;
    }

    private reportAnomaly(anomaly: Anomaly): void {
        this.messageBus.publish({
            id: Math.random().toString(36).substring(2, 15),
            timestamp: new Date(),
            sender: AgentRole.SystemMonitor,
            receiver: 'Broadcast',
            type: 'Alert',
            channel: '⫻alert/violation',
            content: JSON.stringify(anomaly)
        });
    }
}
