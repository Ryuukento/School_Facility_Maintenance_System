# School Facility Maintenance System - Laravel Conversion Notes

## What is already converted

- Core authentication API (login, register, logout, session check)
- User management API (list users, deactivate, activate, approve)
- Reports API (list, create, view, update)
- Dashboard stats API for cards (total, today, pending, in_progress, completed, low_stock)
- Core schema migrations for users, departments, reports, facility data, activity logs, notifications
- Legacy route bridge so old frontend endpoint style can still work

## New Laravel API endpoints

- POST /api/auth/login
- POST /api/auth/register
- POST /api/auth/logout
- GET /api/auth/check
- GET /api/users
- PATCH /api/users/{user}/deactivate
- PATCH /api/users/{user}/activate
- PATCH /api/users/{user}/approve
- GET /api/reports
- POST /api/reports
- GET /api/reports/{report}
- PATCH /api/reports/{report}
- GET /api/dashboard/stats
- GET /api/items

## Legacy-compatible bridge endpoints

- /backend/api/auth.php?action=login|register|logout|check
- /backend/api/users-api.php?action=list|deactivate|activate|approve
- /backend/api/reports-api.php?action=list|create|get|update
- /backend/api/maintenance-dashboard-api.php?action=stats

## How to run

1. cd laravel_app
2. php artisan migrate
3. php artisan db:seed
4. php artisan serve

## Default seeded accounts

- superadmin@sfms.local / Admin@1234
- maintenance.admin@sfms.local / Admin@1234

## Important note

If your existing database already has production tables/data, back it up first before running migrate.
