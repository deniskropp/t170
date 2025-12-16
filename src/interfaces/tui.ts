import * as blessed from 'blessed';
import * as contrib from 'blessed-contrib';
import { TaskManager } from '../core/task_manager';
import { AgentRegistry } from '../core/agent_registry';
import { AgentRole } from '../common/types';

export class CLIDashboard {
  private screen: blessed.Widgets.Screen;
  private grid: contrib.grid;
  private taskTable: any;
  private agentTable: any;
  private logBox: blessed.Widgets.Log;

  constructor(
    private taskManager: TaskManager,
    private agentRegistry: AgentRegistry
  ) {
    this.screen = blessed.screen({
      smartCSR: true,
      title: 'Fizz La Metta Dashboard'
    });

    this.grid = new contrib.grid({ rows: 12, cols: 12, screen: this.screen });

    // Task List (Top Left)
    this.taskTable = this.grid.set(0, 0, 6, 8, contrib.table, {
      keys: true,
      fg: 'white',
      selectedFg: 'white',
      selectedBg: 'blue',
      interactive: true,
      label: 'Active Tasks',
      width: '30%',
      height: '30%',
      border: { type: "line", fg: "cyan" },
      columnSpacing: 3,
      columnWidth: [10, 30, 10, 15]
    }) as any;

    // Agent Status (Top Right)
    this.agentTable = this.grid.set(0, 8, 6, 4, contrib.table, {
      keys: true,
      fg: 'green',
      label: 'Agent Status',
      columnSpacing: 2,
      columnWidth: [15, 10, 10]
    }) as any;

    // Logs (Bottom)
    this.logBox = this.grid.set(6, 0, 6, 12, blessed.log, {
      fg: 'green',
      selectedFg: 'green',
      label: 'System Logs'
    }) as blessed.Widgets.Log;

    this.screen.key(['escape', 'q', 'C-c'], () => {
      return process.exit(0);
    });
  }

  public start() {
    this.refreshLoop();
    this.screen.render();
    this.log('Dashboard started...');
  }

  public log(msg: string) {
    this.logBox.log(`[${new Date().toISOString()}] ${msg}`);
    this.screen.render();
  }

  private async refreshLoop() {
    setInterval(async () => {
      await this.updateTasks();
      await this.updateAgents();
      this.screen.render();
    }, 2000);
  }

  private async updateTasks() {
    const tasks = await this.taskManager.listTasks();
    const data = tasks.map(t => [
      t.id.substring(0, 8),
      t.name.substring(0, 28),
      t.status,
      t.assignedTo || 'Unassigned'
    ]);
    this.taskTable.setData({
      headers: ['ID', 'Name', 'Status', 'Agent'],
      data: data
    });
  }

  private async updateAgents() {
    // Gather all agents (mocking iteration over roles for now)
    const roles = Object.values(AgentRole);
    const data: string[][] = [];

    for (const role of roles) {
      const agents = this.agentRegistry.findAgentsByRole(role as AgentRole);
      agents.forEach(a => {
        data.push([
          a.id,
          a.role,
          a.status
        ]);
      });
    }

    this.agentTable.setData({
      headers: ['ID', 'Role', 'Status'],
      data: data
    });
  }
}
