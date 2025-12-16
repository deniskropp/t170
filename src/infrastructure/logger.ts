import * as fs from 'fs';
import * as path from 'path';

export enum LogLevel {
    INFO = 'INFO',
    WARN = 'WARN',
    ERROR = 'ERROR',
    DEBUG = 'DEBUG'
}

export class LoggerService {
    private logDir: string;
    private logFile: string;

    constructor(logDir: string = 'logs') {
        this.logDir = logDir;
        this.logFile = path.join(this.logDir, 'app.log');
        this.ensureLogDir();
    }

    private ensureLogDir() {
        if (!fs.existsSync(this.logDir)) {
            fs.mkdirSync(this.logDir, { recursive: true });
        }
    }

    private formatMessage(level: LogLevel, message: string, context?: Record<string, any>): string {
        const timestamp = new Date().toISOString();
        const contextStr = context ? ` | Context: ${JSON.stringify(context)}` : '';
        return `[${timestamp}] [${level}] ${message}${contextStr}\n`;
    }

    public log(level: LogLevel, message: string, context?: Record<string, any>) {
        const formattedMessage = this.formatMessage(level, message, context);

        // Console output
        switch (level) {
            case LogLevel.ERROR:
                console.error(formattedMessage.trim());
                break;
            case LogLevel.WARN:
                console.warn(formattedMessage.trim());
                break;
            default:
                console.log(formattedMessage.trim());
        }

        // File output
        fs.appendFileSync(this.logFile, formattedMessage);
    }

    public info(message: string, context?: Record<string, any>) {
        this.log(LogLevel.INFO, message, context);
    }

    public warn(message: string, context?: Record<string, any>) {
        this.log(LogLevel.WARN, message, context);
    }

    public error(message: string, context?: Record<string, any>) {
        this.log(LogLevel.ERROR, message, context);
    }

    public debug(message: string, context?: Record<string, any>) {
        this.log(LogLevel.DEBUG, message, context);
    }
}
