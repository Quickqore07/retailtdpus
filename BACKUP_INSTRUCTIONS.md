# Daily Backup System

This document explains how to use the automated backup system for your Laravel application.

## Features

- **Database Backup**: Creates a SQL dump of your MySQL database
- **Code Backup**: Creates a backup of all application code (excluding unnecessary files like `node_modules`, `vendor`, `.git`, etc.)
- **Automatic Cleanup**: Keeps backups for 30 days and automatically deletes older ones
- **Compression**: All backups are stored as ZIP files to save space
- **Scheduled Execution**: Runs automatically every day at 2:00 AM

## Storage Location

All backups are stored in: `storage/app/backups/`

Backup files are named: `backup_YYYY-MM-DD_HHMMSS.zip`

Each ZIP file contains:
- `database/database_YYYY-MM-DD_HHMMSS.sql` - Database dump
- `code/` - Complete application code

## Manual Backup Commands

### Full Backup (Database + Code)
```bash
php artisan backup:create
```

### Database Only
```bash
php artisan backup:create --database-only
```

### Code Only
```bash
php artisan backup:create --code-only
```

## Setting Up Automated Daily Backups

### For Windows

1. **Open Task Scheduler**
   - Press `Win + R`, type `taskschd.msc`, and press Enter

2. **Create a New Task**
   - Click "Create Basic Task" in the right panel
   - Name: "Laravel Daily Backup"
   - Description: "Daily backup of database and code"

3. **Set Trigger**
   - Choose "Daily"
   - Set time to 2:00 AM (or your preferred time)
   - Click Next

4. **Set Action**
   - Choose "Start a program"
   - Program/script: `php`
   - Add arguments: `artisan backup:create`
   - Start in: `D:\Workspace\Projects\quick-ob` (your project path)

5. **Finish**
   - Review settings and click Finish

### For Linux/Mac (Using Cron)

1. **Edit crontab**
```bash
crontab -e
```

2. **Add the following line** (runs daily at 2:00 AM)
```bash
0 2 * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

3. **Save and exit**

### Using Laravel Scheduler (Recommended)

The backup is already scheduled in `routes/console.php`. You just need to set up the Laravel scheduler to run.

**For Windows:**
Create a scheduled task that runs every minute:
- Program: `php`
- Arguments: `artisan schedule:run`
- Start in: `D:\Workspace\Projects\quick-ob`

**For Linux/Mac:**
Add to crontab:
```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## Requirements

### For Database Backup to Work

**Windows:**
1. Install MySQL server or have MySQL client tools installed
2. Ensure `mysqldump` is in your system PATH

To add to PATH:
- Right-click "This PC" → Properties → Advanced System Settings
- Click "Environment Variables"
- Under "System Variables", find "Path" and click Edit
- Add: `C:\Program Files\MySQL\MySQL Server 8.0\bin` (adjust version as needed)
- Click OK and restart your terminal

**Linux/Mac:**
MySQL tools are usually already available. If not:
```bash
# Ubuntu/Debian
sudo apt-get install mysql-client

# Mac
brew install mysql-client
```

## Configuration

### Change Backup Time

Edit `routes/console.php`:
```php
Schedule::command('backup:create')
    ->dailyAt('02:00')  // Change time here (24-hour format)
    ->timezone('America/New_York');  // Change timezone
```

### Change Retention Period

Edit `app/Console/Commands/BackupDatabase.php`, find the `cleanOldBackups` method:
```php
$threshold = now()->subDays(30);  // Change 30 to desired number of days
```

### Exclude Additional Files/Directories

Edit `app/Console/Commands/BackupDatabase.php`, find the `addDirectoryToZip` method and modify:
```php
$excludeDirs = [
    'node_modules',
    'vendor',
    'storage/logs',
    // Add more directories here
];

$excludeFiles = [
    '.env',
    'composer.lock',
    // Add more files here
];
```

## Restoring from Backup

### Restore Database
1. Extract the backup ZIP file
2. Navigate to the `database` folder
3. Run:
```bash
mysql -u root -p laravelvite < database_YYYY-MM-DD_HHMMSS.sql
```

### Restore Code
1. Extract the backup ZIP file
2. Copy contents from the `code` folder to your project directory
3. Run:
```bash
composer install
npm install
php artisan key:generate
php artisan migrate
```

## Monitoring

### Check Backup Status

View Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

### List Backups
```bash
# Windows
dir storage\app\backups

# Linux/Mac
ls -lh storage/app/backups/
```

### Test Backup Manually
```bash
php artisan backup:create
```

## Troubleshooting

### "mysqldump: command not found"
- Windows: Add MySQL bin directory to PATH (see Requirements section)
- Linux/Mac: Install MySQL client tools

### "Permission denied" when creating backup
```bash
# Windows (PowerShell as Admin)
icacls storage\app\backups /grant Users:F /T

# Linux/Mac
chmod -R 775 storage/app/backups
chown -R www-data:www-data storage/app/backups
```

### Backup file is too large
Consider using `--database-only` or `--code-only` options, or exclude more directories in the configuration.

### Scheduled task not running
- Windows: Check Task Scheduler history
- Linux/Mac: Check cron logs (`grep CRON /var/log/syslog`)

## Security Recommendations

1. **Never commit `.env` file** (already excluded by default)
2. **Keep backups secure** - the backup folder should not be publicly accessible
3. **Consider encrypting backups** for sensitive data
4. **Use off-site storage** - periodically copy backups to cloud storage or external drives
5. **Test restoration regularly** to ensure backups are working properly

## Additional Notes

- The `.env` file is excluded from code backups for security reasons
- The `storage/backups` directory is excluded from code backups to prevent recursive backups
- Backups older than 30 days are automatically deleted to save space
- Each backup is compressed to minimize storage usage
