# SFMS - Laravel Migration Setup Guide

## Quick Start

### 1. Ensure MySQL is Running (XAMPP)
```powershell
# Start MySQL via XAMPP or manually
mysqld.exe
```

### 2. Start Laravel Development Server
```bash
cd laravel_app
php artisan serve --host 127.0.0.1 --port 8000
```

### 3. Visit the Application
```
http://127.0.0.1:8000/login
```

---

## Application Features

### ✅ Authentication
- Login with email and password
- Register new users (pending super admin approval)
- Session-based authentication
- Secure logout

### ✅ Pages Available
- **Dashboard** - Main user dashboard with stats
- **Reports** - View all maintenance reports
- **Create Report** - Submit new maintenance requests
- **Users** - Super admin user management (admin only)
- **Profile** - User profile settings
- **Inventory** - Item tracking
- **Notifications** - User notifications center

### ✅ API Endpoints
All endpoints accessible at `http://127.0.0.1:8000/api/`

#### Authentication
```
POST   /api/auth/login           - Login user
POST   /api/auth/register        - Register new user
POST   /api/auth/logout          - Logout user
GET    /api/auth/check           - Check auth status
```

#### Reports
```
GET    /api/reports              - Get all reports
POST   /api/reports              - Create new report
GET    /api/reports/{id}         - Get single report
PATCH  /api/reports/{id}         - Update report
```

#### Dashboard
```
GET    /api/dashboard/stats      - Get dashboard statistics
```

#### Users
```
GET    /api/users                - List all users
PATCH  /api/users/{id}/activate  - Activate user (admin only)
PATCH  /api/users/{id}/deactivate - Deactivate user (admin only)
PATCH  /api/users/{id}/approve   - Approve user registration (admin only)
```

#### Items
```
GET    /api/items                - Get inventory items
```

---

## Test Login Credentials

### To test, use any email/password combo:
1. **Register** first if no users exist
2. Once registered, **contact super admin** to approve your account
3. Login after approval

### Or create a user directly in DB:
```sql
INSERT INTO users (full_name, email, password, role, status, department_id)
VALUES ('Test User', 'test@example.com', '$2y$12$...', 'user', 'active', 1);
```

---

## Project Structure

```
laravel_app/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/      ← API controllers
│   │   └── Middleware/           ← Authentication middleware
│   ├── Models/                   ← Eloquent models
│   └── Support/                  ← Helper classes
├── resources/
│   ├── views/                    ← Blade templates (main app code)
│   │   ├── auth/                 ← Login/register pages
│   │   ├── layouts/              ← Main layout templates
│   │   ├── includes/             ← Sidebar, header, footer
│   │   ├── reports/              ← Reports pages
│   │   ├── admin/                ← Admin pages
│   │   └── dashboards/           ← Various dashboards
│   ├── css/                      ← Stylesheets
│   └── js/                       ← JavaScript
├── routes/
│   ├── web.php                   ← Web routes (Blade views)
│   └── api.php                   ← API routes (JSON)
├── database/
│   └── migrations/               ← Database schema
├── public/
│   ├── backend/api/              ← Legacy (can be deleted)
│   └── frontend/                 ← Frontend assets
│        ├── assets/css/
│        ├── assets/js/
│        └── assets/images/
└── bootstrap/                    ← Application bootstrap
```

---

## Configuration

### Environment (.env)
```
APP_NAME=SFMS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_facility_maintenance
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### Database (config/database.php)
Already configured to use:
- Host: `127.0.0.1`
- Port: `3306`
- Database: `school_facility_maintenance`
- User: `root`
- Password: (empty)

---

## Development Workflow

### Run Development Server
```bash
cd laravel_app
php artisan serve
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

### Clear Cache
```bash
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Generate New App Key (if needed)
```bash
php artisan key:generate
```

### Run Database Migrations
```bash
php artisan migrate
```

### Seed Database (optional)
```bash
php artisan db:seed
```

---

## Troubleshooting

### "Connection refused" Error
- **Solution:** Ensure MySQL is running on port 3306
  ```bash
  # Check MySQL status
  mysqladmin -u root ping
  ```

### "View [auth.login] not found"
- **Solution:** Blade templates may not be compiled
  ```bash
  php artisan view:clear
  ```

### "CSRF token mismatch"
- **Solution:** Clear sessions and cache
  ```bash
  php artisan session:table
  php artisan cache:clear
  ```

### Database connection errors
- **Solution:** Verify `.env` file has correct credentials
  ```
  DB_HOST=127.0.0.1
  DB_DATABASE=school_facility_maintenance
  DB_USERNAME=root
  DB_PASSWORD=
  ```

---

## Next Steps

1. **Test the main flow**: Login → Dashboard → Create Report → Logout
2. **Check logs** for any errors: `storage/logs/laravel.log`
3. **Verify API endpoints** using Postman or curl
4. **Create sample data** through the UI
5. **Deploy** when ready (production setup)

---

## Files Summary

### New Blade Templates Created
- ✅ `resources/views/auth/login.blade.php`
- ✅ `resources/views/dashboard.blade.php`
- ✅ `resources/views/reports/index.blade.php`
- ✅ `resources/views/reports/create.blade.php`
- ✅ `resources/views/reports/show.blade.php`
- ✅ `resources/views/admin/users.blade.php`
- ✅ `resources/views/profile.blade.php`
- ✅ `resources/views/notifications.blade.php`
- ✅ `resources/views/inventory/index.blade.php`
- ✅ `resources/views/layouts/app.blade.php`
- ✅ `resources/views/layouts/auth.blade.php`
- ✅ `resources/views/includes/sidebar.blade.php`
- ✅ `resources/views/includes/header.blade.php`
- ✅ `resources/views/includes/footer.blade.php`

### Updated Routes
- ✅ `routes/web.php` - All web routes configured
- ✅ `routes/api.php` - API routes working
- ✅ Middleware configured for auth checks

### Working API
- ✅ Controllers in `app/Http/Controllers/Api/`
- ✅ Models in `app/Models/`
- ✅ Session-based authentication

---

## Support Integration Notes

**What still works from legacy:**
- ✅ All API endpoints function as before
- ✅ Database models and migrations
- ✅ Frontend assets (CSS, JS, images)
- ✅ Session storage

**What's new (Laravel):**
- ✅ Blade templates instead of PHP files
- ✅ Clean routing structure
- ✅ Built-in middleware
- ✅ CSRF token protection
- ✅ Service providers
- ✅ Better error handling

---

**Status: ✅ FULLY MIGRATED AND READY**

The application is now running as a pure Laravel application with all legacy PHP pages converted to Blade templates.
