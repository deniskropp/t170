# SQLite Setup Guide for MultiPersona PHP

## ✅ SQLite Status: **PROPERLY CONFIGURED**

Your system has SQLite properly installed and configured. Both `pdo_sqlite` and `sqlite3` extensions are available and working correctly.

## Verification Results

### Extensions Available
- ✅ **pdo_sqlite** - Available and working
- ✅ **sqlite3** - Available and working

### Functionality Tested
- ✅ Database creation
- ✅ Table creation
- ✅ Data insertion
- ✅ Data retrieval
- ✅ Transaction support
- ✅ Error handling

## How to Use SQLite with MultiPersona

### Basic Usage

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use MultiPersona\Infrastructure\DatabaseService;

// Initialize database service
$storageDir = __DIR__ . '/data';
$dbService = new DatabaseService($storageDir);

// This will automatically:
// 1. Create the storage directory if it doesn't exist
// 2. Create 'data/multipersona.db' SQLite database
// 3. Create all required tables (tasks, agents, messages, metrics)
// 4. Set up indexes for performance
```

### Database File Location

The database service will create and manage a SQLite database file at:
```
{storageDir}/multipersona.db
```

For example, if you use `/path/to/storage`, the database will be at:
```
/path/to/storage/multipersona.db
```

### Automatic Schema Management

The `DatabaseService` automatically handles:
- **Database creation** - Creates the database file if it doesn't exist
- **Table creation** - Creates all required tables with proper schema
- **Index creation** - Sets up indexes for performance optimization
- **Schema updates** - Handles schema changes gracefully

### Tables Created

The database includes these tables:

1. **tasks** - Stores all task records
2. **agents** - Stores all agent profiles
3. **messages** - Stores inter-agent messages
4. **metrics** - Stores system metrics and performance data

### Performance Optimization

The database service automatically creates indexes for:
- Task status (for quick status-based queries)
- Task priority (for priority-based scheduling)
- Agent role and status (for efficient agent discovery)
- Message timestamps (for chronological message retrieval)
- Metric names and timestamps (for analytics)

## Configuration Options

### Storage Directory

You can specify any writable directory for database storage:

```php
// Use current directory
$dbService = new DatabaseService(__DIR__);

// Use specific data directory
$dbService = new DatabaseService('/var/www/data');

// Use relative path
$dbService = new DatabaseService('./storage');
```

### Database File Permissions

Ensure the storage directory has proper permissions:
```bash
mkdir -p /path/to/storage
chmod 755 /path/to/storage
chown www-data:www-data /path/to/storage  # For web servers
```

## Troubleshooting

### Common Issues and Solutions

**Issue: SQLite extension not loaded**
```bash
# Check if extension is loaded
php -m | grep sqlite

# Install SQLite extension (if needed)
sudo apt-get install php-sqlite3
sudo systemctl restart apache2  # or your web server
```

**Issue: Permission denied**
```bash
# Check directory permissions
ls -la /path/to/storage

# Fix permissions
chmod 755 /path/to/storage
chown $(whoami):$(whoami) /path/to/storage
```

**Issue: Database file corrupted**
```bash
# Remove corrupted database (data will be lost)
rm /path/to/storage/multipersona.db

# The system will create a new one automatically
```

## Backup and Maintenance

### Backup Database

```bash
# Create backup
cp /path/to/storage/multipersona.db /path/to/backup/multipersona_$(date +%Y%m%d).db

# Compress backup
sqlite3 /path/to/storage/multipersona.db ".backup '/path/to/backup/multipersona_$(date +%Y%m%d).backup"
```

### Optimize Database

```bash
# Vacuum database to reduce size
sqlite3 /path/to/storage/multipersona.db "VACUUM;"

# Analyze database for better query planning
sqlite3 /path/to/storage/multipersona.db "ANALYZE;"
```

### Monitor Database Size

```bash
# Check database size
du -h /path/to/storage/multipersona.db

# Check table sizes
sqlite3 /path/to/storage/multipersona.db ".schema"
```

## Advanced Configuration

### Custom Database Name

To use a custom database name, you can extend the `DatabaseService` class:

```php
class CustomDatabaseService extends DatabaseService
{
    protected function getDatabasePath(): string
    {
        return $this->storageDir . '/custom_name.db';
    }
}
```

### Multiple Database Instances

You can create multiple database instances for different environments:

```php
// Development database
$devDb = new DatabaseService(__DIR__ . '/data/dev');

// Production database
$prodDb = new DatabaseService('/var/db/production');

// Testing database
$testDb = new DatabaseService(__DIR__ . '/data/test');
```

## Security Considerations

### Database File Security

1. **Keep database files secure** - Store in non-web-accessible directories
2. **Set proper permissions** - Database files should not be world-readable
3. **Use separate directories** - Different environments should use different databases
4. **Backup regularly** - Implement automated backup procedures

### Best Practices

```bash
# Secure directory permissions
chmod 700 /path/to/storage
chown www-data:www-data /path/to/storage

# Prevent web access to database files
# Add to your .htaccess or web server config:
<Files "*.db">
    Require all denied
</Files>
```

## Performance Tips

### Optimize Queries

The system is already optimized, but for large datasets:
- Use `getTasksByStatus()` instead of `getAllTasks()` when possible
- Use specific agent queries rather than loading all agents
- Batch operations when dealing with many tasks

### Connection Management

The `DatabaseService` handles connection management automatically:
- Connections are created on demand
- Connections are reused when possible
- Transactions are supported for atomic operations

## Migration from Other Systems

### From JSON Files (TypeScript version)

If migrating from the TypeScript version that uses JSON files:

```php
// Create a migration script
$jsonData = json_decode(file_get_contents('tasks.json'), true);
foreach ($jsonData as $taskData) {
    $dbService->createTask($taskData);
}
```

### From Other Database Systems

For migration from MySQL, PostgreSQL, etc., use SQLite's import capabilities or write a custom migration script.

## Conclusion

✅ **SQLite is properly configured and ready to use**

Your MultiPersona PHP implementation can now use the real `DatabaseService` with SQLite persistence. No additional configuration is needed - the system will automatically create and manage the database file.

**Next Steps:**
1. ✅ SQLite is configured and working
2. 🚀 Start using the real DatabaseService
3. 📊 Monitor database performance
4. 🔄 Set up regular backups

The system is ready for production use with full database persistence!