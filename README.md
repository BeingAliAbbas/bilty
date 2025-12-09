# Bilty Management System (CodeIgniter 4 MVC)

A complete bilty (consignment note) management system built with CodeIgniter 4 framework. This system helps transport and logistics businesses manage their bilty/consignment operations, billing, payments, and vehicle maintenance.

## Features

### Core Functionality
- **Bilty Management**: Create, view, edit, and search consignment records
- **Company Management**: Manage customer/company information
- **Bill Generation**: Create and manage bills for companies
- **Payment Tracking**: Record and track payments against consignments
- **Vehicle Maintenance**: Track vehicle expenses and maintenance
- **Reporting**: Comprehensive reports with filters and exports
- **PDF Generation**: Print bilty and bills as PDF

### Security & Authentication
- **User Authentication**: Secure login/logout system
- **Role-Based Access**: Admin and user roles with different permissions
- **Password Reset**: Secure password recovery via email/token
- **Activity Logging**: Track all critical actions for audit trail
- **CSRF Protection**: Enabled globally for form security
- **Input Validation**: Server-side validation on all inputs
- **XSS Prevention**: Auto-escaping in views
- **SQL Injection Prevention**: Using Query Builder and prepared statements

### Performance & Scalability
- **Server-Side Pagination**: Efficient handling of large datasets
- **Database Indexes**: Optimized queries with proper indexing
- **Foreign Key Constraints**: Data integrity at database level
- **Query Builder**: Clean, secure database queries
- **Caching**: Query and view caching where appropriate

## Technology Stack

- **Framework**: CodeIgniter 4.6.3
- **PHP**: 8.1+ required
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: Bootstrap 5 (local), FontAwesome (local)
- **PDF**: TCPDF/mPDF for PDF generation

## Quick Start

### For New Installation

1. Install dependencies:
   ```bash
   composer install --no-dev
   ```

2. Configure environment:
   ```bash
   cp env .env
   # Edit .env with your database credentials
   php spark key:generate --show
   # Add generated key to .env
   ```

3. Run migrations:
   ```bash
   php spark migrate
   php spark db:seed InitialUserSeeder
   ```

4. Access the application:
   - URL: `http://localhost:8080` (using `php spark serve`)
   - Username: `admin`
   - Password: `admin123`

See [INSTALLATION.md](INSTALLATION.md) for detailed installation instructions.

### For Migration from Legacy System

If you're migrating from the old PHP system, follow the comprehensive guide in [MIGRATION.md](MIGRATION.md).

## Documentation

- **[INSTALLATION.md](INSTALLATION.md)** - Complete installation guide
- **[MIGRATION.md](MIGRATION.md)** - Migration from legacy PHP system
- **[SECURITY.md](docs/SECURITY.md)** - Security features and best practices

## Key Improvements Over Legacy System

### Architecture
- ✅ **MVC Pattern**: Separation of concerns
- ✅ **HMVC Ready**: Modular structure
- ✅ **PSR-4 Autoloading**: Modern PHP standards

### Security
- ✅ **Authentication System**: Secure user login
- ✅ **Password Hashing**: Bcrypt for password storage
- ✅ **CSRF Protection**: Global CSRF tokens
- ✅ **Input Validation**: Server-side validation
- ✅ **Activity Logging**: Audit trail for critical actions

### Database
- ✅ **Migrations**: Version-controlled schema
- ✅ **Foreign Keys**: Data integrity constraints
- ✅ **Indexes**: Performance optimization
- ✅ **Query Builder**: Clean, maintainable queries

## Database Schema

### Tables

1. **companies**: Customer/company information
2. **consignments**: Bilty/consignment records
3. **bills**: Billing records for companies
4. **payments**: Payment tracking for consignments
5. **vehicle_maintenance**: Vehicle expense tracking
6. **users**: System users with authentication
7. **activity_logs**: Audit trail of actions

## Development

### Running Locally

```bash
# Start development server
php spark serve

# Run migrations
php spark migrate

# Create new migration
php spark make:migration CreateTableName
```

## Production Deployment

1. Set environment to production in `.env`
2. Optimize autoloader: `composer install --no-dev --optimize-autoloader`
3. Set proper permissions
4. Configure web server
5. Enable SSL/HTTPS
6. Set up scheduled tasks
7. Configure backups

## Support & Contact

**Developer**: Ali Abbas  
**Phone**: +92 348 3469617  

## Changelog

### Version 2.0.0 (2025-12-09)
- Complete migration to CodeIgniter 4 MVC
- Implemented authentication and authorization
- Database migrations with proper schema
- Comprehensive security improvements
- Server-side pagination and filtering
- Activity logging for audit trail
- Complete documentation
