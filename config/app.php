<?php

/**
 * Application Configuration
 * Set environment-specific settings here
 */

// Environment: 'development' or 'production'
define('ENVIRONMENT', 'development');

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bilty_db');

// Application Settings
define('APP_NAME', 'Bilty Management System');
define('APP_URL', 'http://localhost');

// Security Settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_ENABLED', true);

// Error Reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}

// Timezone
date_default_timezone_set('Asia/Karachi');
