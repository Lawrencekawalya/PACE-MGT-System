# Backup and Recovery

## Policy

- The scheduler creates a checksummed database backup daily at 01:00 Kampala time.
- Backups are kept for `BACKUP_RETENTION_DAYS`, defaulting to 30 days.
- Catalogue files default to 90 days, completed report exports to their individual expiry, notifications to 90 days, and activity logs to 2,555 days.
- Copy backups and their `.sha256` sidecars to encrypted storage outside the application host.
- Access to backups is restricted to authorized administrators and hosting operators.

The application stores personal, academic, and audit data. Confirm the final retention period and deletion process with FICA before production sign-off.

## Manual backup

```bash
php artisan backup:database
```

Record the emitted path, checksum, size, time, database driver, and off-site copy location in the operations log.

## Restore drill

Run this against a disposable environment using a copy of production configuration and the same database engine:

```bash
php artisan backup:restore backups/database-YYYYMMDD-HHMMSS-random.sql --force
php artisan migrate:status
php artisan system:check
php artisan system:validate-data
php artisan catalogue:reconcile
```

For SQLite, the backup extension is `.sqlite`. MySQL/MariaDB restores require the `mysql` client; PostgreSQL restores require `psql`. The command verifies the SHA-256 sidecar and creates a safety backup before replacing data.

After restoration, verify administrator login, student counts, the latest stock balances, recent PACE attempts, and a report export. Record elapsed restore time and the newest restored transaction to establish actual RTO and RPO. Perform this drill before launch and at least quarterly.

## Incident recovery

1. Stop writes with `php artisan down` and stop queue workers.
2. Preserve logs, the affected database, and current uploaded files.
3. Identify the last known-good backup and verify both files exist.
4. Restore into a separate environment first when time permits.
5. Restore production, run every validation command, restart workers, and re-enable traffic.
6. Record the incident, lost transaction window, validation evidence, and approver.
