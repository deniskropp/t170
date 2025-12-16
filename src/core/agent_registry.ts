import { AgentProfile, AgentRole } from '../common/types';
import { DatabaseService } from '../infrastructure/db';

export class AgentRegistry {
    constructor(private dbService: DatabaseService) { }

    public register(profile: AgentProfile): void {
        const stmt = this.dbService.getDb().prepare(`
      INSERT OR REPLACE INTO agents (id, role, capabilities, status, currentTaskId, lastActive, isEphemeral)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    `);

        stmt.run(
            profile.id,
            profile.role,
            JSON.stringify(profile.capabilities),
            profile.status,
            profile.currentTaskId || null,
            profile.lastActive.toISOString(),
            profile.isEphemeral ? 1 : 0
        );
    }

    public updateStatus(id: string, status: AgentProfile['status'], taskId?: string): void {
        const stmt = this.dbService.getDb().prepare(`
      UPDATE agents 
      SET status = ?, currentTaskId = ?, lastActive = ?
      WHERE id = ?
    `);

        stmt.run(
            status,
            taskId || null,
            new Date().toISOString(),
            id
        );
    }

    public findAgentsByRole(role: AgentRole): AgentProfile[] {
        const stmt = this.dbService.getDb().prepare('SELECT * FROM agents WHERE role = ?');
        const rows = stmt.all(role) as any[];
        return rows.map(row => this.mapRowToProfile(row));
    }

    public getAgent(id: string): AgentProfile | undefined {
        const stmt = this.dbService.getDb().prepare('SELECT * FROM agents WHERE id = ?');
        const row = stmt.get(id) as any;
        if (!row) return undefined;

        return {
            id: row.id,
            role: row.role as AgentRole,
            capabilities: JSON.parse(row.capabilities),
            status: row.status as any,
            currentTaskId: row.currentTaskId,
            lastActive: new Date(row.lastActive),
            isEphemeral: row.isEphemeral === 1 // SQLite stores booleans as 0/1
        };
    }

    public getIdleAgent(role: AgentRole): AgentProfile | null {
        const stmt = this.dbService.getDb().prepare("SELECT * FROM agents WHERE role = ? AND status = 'Idle' LIMIT 1");
        const row = stmt.get(role) as any;
        return row ? this.mapRowToProfile(row) : null;
    }

    private mapRowToProfile(row: any): AgentProfile {
        return {
            ...row,
            capabilities: JSON.parse(row.capabilities),
            lastActive: new Date(row.lastActive)
        };
    }
}
