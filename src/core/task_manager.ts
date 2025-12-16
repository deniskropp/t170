import { TaskRecord, TaskStatus, AgentRole } from '../common/types';
import { DatabaseService } from '../infrastructure/db';

export class TaskManager {
    constructor(private dbService: DatabaseService) { }

    public async createTask(task: Omit<TaskRecord, 'id' | 'createdAt' | 'updatedAt' | 'status'>): Promise<TaskRecord> {
        const id = Math.random().toString(36).substring(2, 15);
        const newTask: TaskRecord = {
            ...task,
            id,
            status: 'Pending',
            createdAt: new Date(),
            updatedAt: new Date(),
            artifacts: [],
            metadata: {}
        };

        const stmt = this.dbService.getDb().prepare(`
      INSERT INTO tasks (id, name, description, type, status, priority, dependencies, assignedTo, artifacts, metadata, createdAt, updatedAt)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `);

        stmt.run(
            newTask.id,
            newTask.name,
            newTask.description,
            newTask.type,
            newTask.status,
            newTask.priority,
            JSON.stringify(newTask.dependencies),
            newTask.assignedTo || null,
            JSON.stringify(newTask.artifacts),
            JSON.stringify(newTask.metadata),
            newTask.createdAt.toISOString(),
            newTask.updatedAt.toISOString()
        );

        return newTask;
    }

    public async updateTask(id: string, updates: Partial<TaskRecord>): Promise<TaskRecord> {
        const current = await this.getTask(id);
        if (!current) {
            throw new Error(`Task with ID ${id} not found`);
        }

        const updatedTask = { ...current, ...updates, updatedAt: new Date() };

        const sets: string[] = [];
        const values: any[] = [];

        // Helper to add field if present in updates
        const addField = (key: keyof TaskRecord, val: any, transform: (v: any) => any = v => v) => {
            if (key in updates || key === 'updatedAt') {
                sets.push(`${key} = ?`);
                values.push(transform(val));
            }
        };

        // We construct the UPDATE query dynamically based on what changed, 
        // but for simplicity in this migration, we'll just update everything that *could* change + updatedAt.
        // Actually, let's just update the specific fields provided in 'updates' + updatedAt.

        const keys = Object.keys(updates) as (keyof TaskRecord)[];
        keys.forEach(key => {
            if (key === 'id' || key === 'createdAt') return; // Immutable
            sets.push(`${key} = ?`);
            let val = updates[key];
            if (['dependencies', 'artifacts', 'metadata'].includes(key)) {
                val = JSON.stringify(val);
            }
            values.push(val);
        });

        sets.push('updatedAt = ?');
        values.push(updatedTask.updatedAt.toISOString());
        values.push(id); // For WHERE clause

        const stmt = this.dbService.getDb().prepare(`UPDATE tasks SET ${sets.join(', ')} WHERE id = ?`);
        stmt.run(...values);

        return updatedTask;
    }

    public async getTask(id: string): Promise<TaskRecord | null> {
        const stmt = this.dbService.getDb().prepare('SELECT * FROM tasks WHERE id = ?');
        const row = stmt.get(id) as any;
        if (!row) return null;
        return this.mapRowToTask(row);
    }

    public async listTasks(): Promise<TaskRecord[]> {
        const stmt = this.dbService.getDb().prepare('SELECT * FROM tasks');
        const rows = stmt.all() as any[];
        return rows.map(row => this.mapRowToTask(row));
    }

    public async getReadyTasks(): Promise<TaskRecord[]> {
        // This logic is complex to do purely in SQL with JSON dependencies, so we fetch and filter.
        // Optimization: Fetch only Pending tasks first.
        const stmt = this.dbService.getDb().prepare("SELECT * FROM tasks WHERE status = 'Pending'");
        const pendingTasks = (stmt.all() as any[]).map(row => this.mapRowToTask(row));

        const readyTasks: TaskRecord[] = [];
        for (const task of pendingTasks) {
            if (!task.dependencies || task.dependencies.length === 0) {
                readyTasks.push(task);
                continue;
            }

            // Check dependencies
            const depPlaceholders = task.dependencies.map(() => '?').join(',');
            const depStmt = this.dbService.getDb().prepare(`SELECT count(*) as count FROM tasks WHERE id IN (${depPlaceholders}) AND status != 'Completed'`);
            const result = depStmt.get(...task.dependencies) as { count: number };

            if (result.count === 0) {
                readyTasks.push(task);
            }
        }
        return readyTasks;
    }

    private mapRowToTask(row: any): TaskRecord {
        return {
            ...row,
            dependencies: JSON.parse(row.dependencies),
            artifacts: JSON.parse(row.artifacts),
            metadata: JSON.parse(row.metadata),
            createdAt: new Date(row.createdAt),
            updatedAt: new Date(row.updatedAt),
            priority: Number(row.priority)
        };
    }
}
