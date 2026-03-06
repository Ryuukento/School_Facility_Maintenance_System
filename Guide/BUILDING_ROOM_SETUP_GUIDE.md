# Building &amp; Room Management Setup Guide

## Quick Start

The building and room management feature requires database tables to be set up. Follow these steps:

### Step 1: Open Setup Page
Visit this URL in your browser:
```
http://localhost/School_Facility_Maintenance_System/backend/setup.html
```

### Step 2: Click "Create/Update Tables"
- This will automatically create the `buildings` and `rooms` tables in your database
- The setup page will confirm when complete

### Step 3: Return to Dashboard
- Once setup is complete, you'll be redirected to the dashboard
- The "Add New Building" and "Add New Room" cards will now work

---

## Features

### Add New Building
1. Click the **"Add New Building"** card on the dashboard
2. Enter the building name (required)
3. Add a description (optional)
4. Click **"Save Building"**

### Add New Room
1. Click the **"Add New Room"** card on the dashboard
2. Select a building from the dropdown
3. Enter the room name/number (required)
4. Add capacity (optional)
5. Click **"Save Room"**

---

## Database Tables Created

### buildings
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| name | VARCHAR(255) | Building name (unique) |
| description | TEXT | Building description |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### rooms
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| building_id | INT | Foreign key to buildings |
| name | VARCHAR(255) | Room name/number |
| capacity | INT | Room capacity |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

---

## Troubleshooting

### "Failed to create building"
**Solution:** Run the database setup at `/backend/setup.html`

### "No buildings found"
**Solution:** Add at least one building first using the "Add New Building" card

### "Database connection failed"
**Check:**
- Is XAMPP/database running?
- Database credentials in `/backend/config/database.php`
- Database name: `school_facility_maintenance`

---

## API Endpoints

### Buildings API
- **List all buildings:**
  ```
  GET /backend/api/buildings.php?action=list
  ```

- **Create building:**
  ```
  POST /backend/api/buildings.php?action=create
  ```
  Body:
  ```json
  {
    "name": "Science Wing",
    "description": "Main science building"
  }
  ```

### Rooms API
- **Create room:**
  ```
  POST /backend/api/rooms.php?action=create
  ```
  Body:
  ```json
  {
    "building_id": 1,
    "name": "Room 304",
    "capacity": 50
  }
  ```

---

## Need Help?

If you encounter any issues:
1. Check the browser console (F12) for error messages
2. Check `/logs` folder for server errors
3. Visit the setup page: `/backend/setup.html`
