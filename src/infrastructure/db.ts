import Database from 'better-sqlite3';
import * as path from 'path';
import * as fs from 'fs';

export class DatabaseService {
  private db: Database.Database;

  constructor(storageDir: string) {
    if (!fs.existsSync(storageDir)) {
      fs.mkdirSync(storageDir, { recursive: true });
    }
    const dbPath = path.join(storageDir, 'live_app.db');
    this.db = new Database(dbPath);
    this.initialize();
  }

  private initialize() {
    // Enable WAL mode for better concurrency
    this.db.pragma('journal_mode = WAL');

    // Create Tables
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS tasks (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        description TEXT,
        type TEXT,
        status TEXT,
        priority INTEGER,
        dependencies TEXT, -- JSON array
        assignedTo TEXT,
        artifacts TEXT, -- JSON array
        metadata TEXT, -- JSON object
        createdAt TEXT,
        updatedAt TEXT
      );

      CREATE TABLE IF NOT EXISTS agents (
        id TEXT PRIMARY KEY,
        role TEXT NOT NULL,
        capabilities TEXT, -- JSON array
        status TEXT,
        currentTaskId TEXT,
        lastActive TEXT,
        isEphemeral INTEGER DEFAULT 0
      );
    `);
  }

  public getDb(): Database.Database {
    return this.db;
  }

  public close() {
    this.db.close();
  }
}
