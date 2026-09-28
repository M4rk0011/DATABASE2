# PHP Backend API for Concern Report System

This directory contains the PHP backend API for the Incident Report System.

## Setup Instructions

### 1. Database Setup

1. Open phpMyAdmin or MySQL command line
2. Import the database schema from `database/schema.sql`
3. This will create:
   - Database: `incident_report_system`
   - Tables: `users`, `incidents`
   - Default users with password hashes

### 2. Configuration

The database connection is configured in `config/database.php`. Default settings:
- Host: localhost
- Database: incident_report_system
- Username: root
- Password: (empty)

Modify these settings if your MySQL configuration is different.

### 3. API Endpoints

#### Authentication
- `POST /backend/api/auth/login.php` - User login
- `POST /backend/api/auth/register.php` - User registration
- `GET /backend/api/auth/users.php` - Get all users
- `PUT /backend/api/auth/users.php` - Update user
- `DELETE /backend/api/auth/users.php?id={id}` - Delete user

#### Incidents
- `GET /backend/api/incidents/index.php` - Get all incidents
- `GET /backend/api/incidents/index.php?id={id}` - Get specific incident
- `POST /backend/api/incidents/index.php` - Create new incident
- `PUT /backend/api/incidents/index.php` - Update incident
- `DELETE /backend/api/incidents/index.php?id={id}` - Delete incident
- `GET /backend/api/incidents/stats.php` - Get admin statistics

### 4. Default Users

The system comes with default users (password: `admin123`, `security123`, `student123`):
- admin / admin@ub.edu.ph (Admin role)
- security / security@ub.edu.ph (Security role)
- student / student@ub.edu.ph (User role)

## Security Notes

- Passwords are hashed using PHP's `password_hash()` function
- SQL injection protection using PDO prepared statements
- CORS enabled for development (restrict in production)
- Input sanitization using `htmlspecialchars()` and `strip_tags()`

## Development

The API is designed to work with the Angular frontend. The Angular services have been updated to point to:
- `http://localhost/DATABASE2/backend/api/auth`
- `http://localhost/DATABASE2/backend/api/incidents`

## Troubleshooting

1. **Connection refused**: Ensure MySQL/XAMPP is running
2. **Database not found**: Run the schema.sql import
3. **CORS errors**: Check browser console and server headers
4. **404 errors**: Verify file paths and XAMPP configuration
