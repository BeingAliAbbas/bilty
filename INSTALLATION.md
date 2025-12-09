# Bilty Management System - Installation Guide

## Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## Installation Steps

### 1. Clone or Download the Repository

```bash
git clone <repository-url> bilty
cd bilty
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

For development:
```bash
composer install
```

### 3. Configure Environment

Copy the environment file:
```bash
cp env .env
```

Edit `.env` and configure your settings:

```ini
# Environment
CI_ENVIRONMENT = production

# Base URL
app.baseURL = 'https://yourdomain.com/'

# Database
database.default.hostname = localhost
database.default.database = bilty_db
database.default.username = your_db_user
database.default.password = your_db_password
database.default.DBDriver = MySQLi
database.default.port = 3306

# Encryption Key (IMPORTANT: Generate a new one!)
# Run: php spark key:generate --show
encryption.key = your_encryption_key_here
```

### 4. Generate Encryption Key

```bash
php spark key:generate --show
```

Copy the generated key to your `.env` file under `encryption.key`.

### 5. Create Database

Create a MySQL database:

```sql
CREATE DATABASE bilty_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Run Migrations

```bash
php spark migrate
```

This will create all necessary tables:
- companies
- consignments
- bills
- payments
- vehicle_maintenance
- users
- activity_logs

### 7. Seed Initial Data

Create the initial admin user:

```bash
php spark db:seed InitialUserSeeder
```

Default credentials:
- **Username:** admin
- **Password:** admin123

**⚠️ IMPORTANT:** Change the admin password immediately after first login!

### 8. Set Permissions

Ensure the `writable` directory is writable by the web server:

```bash
chmod -R 755 writable/
chown -R www-data:www-data writable/
```

Create PDF storage directory:
```bash
mkdir -p writable/uploads/pdfs
chmod -R 755 writable/uploads
```

### 9. Configure Web Server

#### Apache

The project includes `.htaccess` files. Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Set your document root to the `public` directory:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/bilty/public
    
    <Directory /path/to/bilty/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/bilty-error.log
    CustomLog ${APACHE_LOG_DIR}/bilty-access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/bilty/public;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

### 10. Test Installation

Visit your site at `https://yourdomain.com` and verify:
- Homepage loads correctly
- You can login with admin credentials
- Navigation works
- No errors in browser console

### 11. Enable HTTPS (Recommended)

Use Let's Encrypt for free SSL:

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

For Nginx:
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

## Post-Installation Security

1. **Change admin password** immediately
2. **Remove or secure** the `/legacy` directory (contains old PHP files)
3. **Enable CSRF protection** (already enabled in production)
4. **Set proper file permissions**:
   ```bash
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chmod 755 spark
   ```
5. **Backup your database** regularly
6. **Keep dependencies updated**: `composer update`

## Troubleshooting

### Database Connection Error
- Verify database credentials in `.env`
- Ensure MySQL service is running
- Check user has proper permissions

### 404 Errors
- Verify document root points to `public` directory
- Check `.htaccess` file exists in `public/`
- Ensure mod_rewrite is enabled (Apache)

### Permission Errors
- Check writable directory permissions
- Verify web server user ownership

### Blank Page
- Check PHP error logs
- Ensure all composer dependencies are installed
- Verify PHP version meets requirements (8.1+)

## Development Setup

For local development:

1. Use the built-in PHP server:
   ```bash
   php spark serve
   ```

2. Access at `http://localhost:8080`

3. Enable debug mode in `.env`:
   ```ini
   CI_ENVIRONMENT = development
   ```

## Next Steps

After installation:
1. Read [MIGRATION.md](MIGRATION.md) for data migration from old system
2. Read [USER_MANUAL.md](USER_MANUAL.md) for usage instructions
3. Configure scheduled tasks (see CRON.md)
4. Review security settings

## Support

For issues and questions:
- Check the documentation in `/docs`
- Review error logs in `writable/logs/`
- Contact: Ali Abbas (+92 348 3469617)
