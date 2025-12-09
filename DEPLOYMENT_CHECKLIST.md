# Production Deployment Checklist

Use this checklist when deploying the Bilty Management System to production.

## Pre-Deployment

### Code & Dependencies
- [ ] All code committed to version control
- [ ] Latest code pulled to production server
- [ ] Composer dependencies installed: `composer install --no-dev --optimize-autoloader`
- [ ] No development dependencies in production
- [ ] Code reviewed and tested

### Environment Configuration
- [ ] `.env` file created and configured
- [ ] `CI_ENVIRONMENT = production` set
- [ ] Correct `app.baseURL` configured
- [ ] Database credentials verified
- [ ] Strong encryption key set
- [ ] Error display disabled (CI4 handles this in production mode)

### Database
- [ ] Database created with proper character set (utf8mb4)
- [ ] Database user created with appropriate permissions
- [ ] Migrations run: `php spark migrate`
- [ ] Initial seeder run: `php spark db:seed InitialUserSeeder`
- [ ] Database backup configured
- [ ] Connection tested

### Web Server
- [ ] Document root points to `public/` directory
- [ ] `.htaccess` file present (Apache)
- [ ] `mod_rewrite` enabled (Apache)
- [ ] PHP-FPM configured properly (Nginx)
- [ ] Virtual host/server block configured
- [ ] Domain DNS configured

### File Permissions
- [ ] Writable directory is writable: `chmod -R 755 writable/`
- [ ] Correct ownership: `chown -R www-data:www-data writable/`
- [ ] Upload directory created: `mkdir -p writable/uploads/pdfs`
- [ ] Other files are not writable by web server
- [ ] `spark` file is executable: `chmod +x spark`

### SSL/HTTPS
- [ ] SSL certificate installed
- [ ] HTTPS enforced (redirect from HTTP)
- [ ] Mixed content issues resolved
- [ ] SSL certificate auto-renewal configured (Let's Encrypt)

### Security
- [ ] Default admin password changed
- [ ] CSRF protection enabled (verify in `app/Config/Security.php`)
- [ ] Session security configured
- [ ] File upload restrictions in place
- [ ] Database backups secured
- [ ] `.env` file not accessible via web
- [ ] `vendor/` directory not accessible via web
- [ ] Error logs not publicly accessible

## Deployment Process

### 1. Backup Current System (if upgrading)
```bash
# Backup database
mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/app

# Store backups securely
```

### 2. Deploy Code
```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php spark cache:clear
```

### 3. Run Migrations
```bash
# Check migration status
php spark migrate:status

# Run migrations
php spark migrate

# Verify migrations
```

### 4. Set Permissions
```bash
# Set writable permissions
chmod -R 755 writable/
chown -R www-data:www-data writable/

# Ensure other files are not writable
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod +x spark
```

### 5. Test Deployment
- [ ] Visit homepage - loads correctly
- [ ] Login works
- [ ] Create test consignment
- [ ] Generate PDF
- [ ] Run a report
- [ ] Check error logs for issues

## Post-Deployment

### Monitoring (First 24 Hours)
- [ ] Monitor error logs: `tail -f writable/logs/log-*.log`
- [ ] Check web server error logs
- [ ] Monitor database performance
- [ ] Check SSL certificate validity
- [ ] Monitor disk space
- [ ] Verify backup ran successfully

### Week 1 Tasks
- [ ] Daily log review
- [ ] User feedback collection
- [ ] Performance monitoring
- [ ] Database optimization if needed
- [ ] Fix any reported issues

### Ongoing Maintenance
- [ ] Weekly log reviews
- [ ] Monthly security updates
- [ ] Quarterly dependency updates
- [ ] Regular database backups
- [ ] Disk space monitoring
- [ ] SSL certificate renewal (auto with Let's Encrypt)

## Performance Optimization

### Database
- [ ] Indexes created on frequently queried columns (done in migrations)
- [ ] Query caching enabled where appropriate
- [ ] Connection pooling configured
- [ ] Slow query log enabled for monitoring

### Application
- [ ] Opcache enabled and configured
- [ ] Query caching enabled
- [ ] View caching for static pages
- [ ] Asset minification
- [ ] Gzip compression enabled

### Server
- [ ] Adequate PHP memory limit (256M recommended)
- [ ] PHP max execution time appropriate
- [ ] MySQL query cache configured
- [ ] Web server keep-alive enabled

## Backup Strategy

### Automated Backups
```bash
# Add to crontab
# Daily database backup at 2 AM
0 2 * * * /usr/bin/mysqldump -u backup_user -p'password' bilty_db | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz

# Weekly file backup on Sunday at 3 AM
0 3 * * 0 tar -czf /backups/files_$(date +\%Y\%m\%d).tar.gz /path/to/bilty/writable/uploads

# Delete backups older than 30 days
0 4 * * * find /backups -name "*.gz" -mtime +30 -delete
```

### Backup Verification
- [ ] Test restore procedure monthly
- [ ] Verify backup file integrity
- [ ] Store off-site backups
- [ ] Document restore procedure

## Rollback Plan

If issues arise after deployment:

### 1. Immediate Rollback
```bash
# Restore previous code version
git reset --hard [previous-commit-hash]

# Restore database backup
mysql -u [user] -p [database] < backup_[timestamp].sql

# Clear caches
php spark cache:clear

# Restart web server
sudo systemctl restart apache2
```

### 2. Communication
- [ ] Notify users of rollback
- [ ] Document issues encountered
- [ ] Plan fix and re-deployment

## Troubleshooting

### Issue: 500 Internal Server Error
- Check: PHP error logs
- Check: Web server error logs
- Check: `.htaccess` file exists
- Check: File permissions
- Check: PHP extensions installed

### Issue: Database Connection Failed
- Verify: Database credentials in `.env`
- Check: Database service is running
- Verify: Database user permissions
- Test: Connection with `mysql` command

### Issue: Blank Page
- Enable: Debug mode temporarily
- Check: PHP error logs
- Verify: All dependencies installed
- Check: File permissions

### Issue: Login Not Working
- Verify: Session configuration
- Check: Database connection
- Verify: User seeder ran
- Check: Cookie settings

## Security Checklist

### Application Security
- [ ] SQL injection prevention (Query Builder ✓)
- [ ] XSS prevention (auto-escaping ✓)
- [ ] CSRF protection enabled ✓
- [ ] Input validation on all forms
- [ ] Output encoding
- [ ] Secure session configuration
- [ ] Password hashing (bcrypt ✓)
- [ ] Rate limiting on auth endpoints
- [ ] File upload validation

### Server Security
- [ ] Firewall configured
- [ ] SSH key-based authentication
- [ ] Unnecessary services disabled
- [ ] Server software updated
- [ ] Strong passwords for all accounts
- [ ] Fail2ban or similar configured
- [ ] Regular security updates applied

### Monitoring
- [ ] Error logging enabled
- [ ] Failed login attempts logged
- [ ] File integrity monitoring
- [ ] Intrusion detection system
- [ ] Log analysis for suspicious activity

## Compliance & Legal

- [ ] Privacy policy updated
- [ ] Terms of service current
- [ ] GDPR compliance (if applicable)
- [ ] Data retention policy defined
- [ ] Backup retention policy defined
- [ ] User data export capability
- [ ] User data deletion capability

## Documentation

- [ ] API documentation updated
- [ ] User manual current
- [ ] Admin guide available
- [ ] Runbook for common tasks
- [ ] Emergency contact list
- [ ] Deployment procedures documented

## Sign-Off

### Deployment Team
- [ ] Developer Sign-off: _________________ Date: _______
- [ ] QA Sign-off: _________________ Date: _______
- [ ] System Admin Sign-off: _________________ Date: _______
- [ ] Business Owner Sign-off: _________________ Date: _______

### Production Verification
- [ ] All systems operational
- [ ] No critical errors in logs
- [ ] Performance acceptable
- [ ] Security measures in place
- [ ] Backups configured and tested
- [ ] Monitoring active
- [ ] Documentation complete

**Deployment Date**: _________________
**Deployed By**: _________________
**Version**: _________________

---

## Emergency Contacts

**Developer**: Ali Abbas - +92 348 3469617
**Hosting Provider**: _________________
**Database Admin**: _________________
**On-Call Support**: _________________

## Additional Resources

- Installation Guide: [INSTALLATION.md](INSTALLATION.md)
- Migration Guide: [MIGRATION.md](MIGRATION.md)
- Project Status: [PROJECT_STATUS.md](PROJECT_STATUS.md)
- User Manual: [docs/USER_MANUAL.md](docs/USER_MANUAL.md)
