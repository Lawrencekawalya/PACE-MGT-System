# PACE Management System

Laravel and Vue application for managing FICA students, PACE progression, assessments, stock, and reports.

## Local setup

```bash
composer setup
composer dev
```

Run the release checks with:

```bash
composer ci:check
php artisan system:check
php artisan system:validate-data
php artisan catalogue:reconcile
```

## Operations

- [Deployment and rollback](docs/DEPLOYMENT.md)
- [Backup and recovery](docs/BACKUP_AND_RECOVERY.md)
- [Role and acceptance guide](docs/ROLE_AND_ACCEPTANCE_GUIDE.md)
- [Phase 8 release checklist](docs/PHASE_8_RELEASE_CHECKLIST.md)

The public liveness endpoint is `/up`. The dependency readiness endpoint is `/ready`. Administrators can inspect infrastructure and release checks from **Administration > System status**.
