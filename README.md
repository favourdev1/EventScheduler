<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Event Scheduling System API

A robust RESTful API for managing events and registrations built with Laravel. The system allows organizers to create events, users to register for events, and administrators to oversee all operations.

## Features

- User authentication with roles (Admin, Organizer, User)
- Event management with categories
- Registration system with conflict detection
- Email notifications for various actions
- Comprehensive admin dashboard
- Input validation and error handling
- Soft delete support
- Time zone handling
- Statistical reporting

## Requirements

- PHP >= 8.1
- MySQL/MariaDB
- Composer
- Laravel 10.x
- SMTP server for emails

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd SchedulingSystemApi
```

2. Install dependencies:
```bash
composer install
```

3. Create environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=schedulingsystemapi
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run migrations and seed the database:
```bash
php artisan migrate:fresh --seed
```

This will create your first admin user:
- Email: admin@example.com
- Password: admin123

## Email Configuration

Configure your email settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

For local development, you can use [Mailtrap](https://mailtrap.io) or local email testing tools.

## Local Email Testing with Mailpit

For local development, we recommend using Mailpit. It's an email testing tool that catches all outgoing emails and provides a web interface to view them.

### Installing Mailpit on macOS:
```bash
brew install axllent/apps/mailpit
```

### Starting Mailpit:
```bash
mailpit
```

Access the Mailpit interface at: http://localhost:8025

Update your .env file with these settings for Mailpit:
```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## API Endpoints

### Authentication

| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| POST | `/api/register` | Register new user | Public |
| POST | `/api/login` | Login user | Public |
| POST | `/api/logout` | Logout user | Authenticated |
| GET | `/api/me` | Get current user profile | Authenticated |

---

### Events

| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/api/events` | List events | Authenticated |
| POST | `/api/events` | Create event | Admin/Organizer |
| GET | `/api/events/{id}` | View event details | Authenticated |
| PUT | `/api/events/{id}` | Update event | Admin/Event Organizer |
| DELETE | `/api/events/{id}` | Delete event | Admin |
| POST | `/api/events/{id}/register` | Register for event | Authenticated |
| POST | `/api/events/{id}/cancel-registration` | Cancel registration | Registered User |
| GET | `/api/events/{id}/participants` | View participants | Admin/Event Organizer |
---

### Categories

| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/api/categories` | List categories | Authenticated |
| POST | `/api/categories` | Create category | Admin |
| GET | `/api/categories/{id}` | View category | Authenticated |
| PUT | `/api/categories/{id}` | Update category | Admin |
| DELETE | `/api/categories/{id}` | Delete category | Admin |

---


## Admin Routes

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET    | `/api/admin/users` | List all users | Admin |
| POST   | `/api/admin/users` | Create user | Admin |
| PUT    | `/api/admin/users/{id}` | Update user | Admin |
| DELETE | `/api/admin/users/{id}` | Delete user | Admin |
| POST   | `/api/admin/users/{id}/toggle-active` | Toggle user status | Admin |
| POST   | `/api/admin/events/{id}/force-register` | Force register user | Admin |
| DELETE | `/api/admin/events/{id}/remove-participant` | Remove participant | Admin |
| GET    | `/api/admin/statistics` | Get system statistics | Admin |

---

## Email Notifications

The system sends emails for the following events:

1. **User Registration:**
    - Welcome email to new user
    - Notification to all admins
2. **Event Registration:**
    - Confirmation to participant
    - Notification to event organizer

---

## Role-Based Access

- **Admin**
  - Full system access
  - Can manage users, events, and categories
  - Can override normal registration rules
  - Access to statistics and reports
- **Organizer**
  - Can create and manage their own events
  - Update their event details
  - View participants for their events
- **User**
  - Register for events
  - View available events
  - Manage their registrations
  - View their schedule

---

## Error Handling

The API uses a standardized response format:

```json
{
     "status": true/false,
     "message": "Operation message",
     "data": {}, // For successful responses
     "error": {} // For error responses
}
```

HTTP status codes are properly used:

- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

---

## Validation Rules

### Events

- Name is required
- Start time must be in the future
- End time must be after start time
- Maximum participants must be at least 1
- Time zone must be valid

### Registrations

- One registration per event per user
- Cannot register for past events
- No overlapping events allowed
- Cannot exceed maximum participants

---

## Development

Start the development server:

```bash
php artisan serve
```

Run the queue worker for email processing:

```bash
php artisan queue:work
```

---

## Testing

Run the test suite:

```bash
php artisan test
```

---

## Security

- API authentication using Laravel Sanctum
- Role-based access control
- Input validation
- SQL injection protection
- XSS protection
- CSRF protection for web routes

