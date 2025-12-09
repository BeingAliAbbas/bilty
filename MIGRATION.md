# Migration Guide - Legacy to CodeIgniter 4

This guide helps you migrate from the legacy PHP Bilty system to the new CodeIgniter 4 MVC application.

## Overview

The migration process involves:
1. Backing up existing data
2. Setting up the new system
3. Migrating database records
4. Testing functionality
5. Going live
6. Decommissioning old system

## Pre-Migration Checklist

- [ ] Backup current database completely
- [ ] Backup all files (especially uploaded PDFs)
- [ ] Document current server configuration
- [ ] Test backup restoration
- [ ] Inform users of scheduled migration window
- [ ] Prepare rollback plan

## Step 1: Backup Current System

### Database Backup

```bash
# Full database backup
mysqldump -u root -p bilty_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Compress the backup
gzip backup_$(date +%Y%m%d_%H%M%S).sql
```

### File Backup

```bash
# Backup old application directory
tar -czf bilty_old_$(date +%Y%m%d).tar.gz /path/to/old/bilty/

# Backup uploaded files
tar -czf bilty_pdfs_$(date +%Y%m%d).tar.gz /path/to/old/bilty/bilty_pdfs/
```

## Step 2: Install New System

Follow the [INSTALLATION.md](INSTALLATION.md) guide to set up the new CodeIgniter application.

**Important:** Install on a separate domain/subdomain first for testing (e.g., new.yourdomain.com)

## Step 3: Database Migration

The new system's schema includes several enhancements:
- Foreign key constraints for data integrity
- Indexes for better performance
- Additional fields for tracking
- Normalized structure

### Schema Differences

#### Companies Table
**Old Schema:**
```sql
id, name, address
```

**New Schema:**
```sql
id, name, address, phone, email, created_at, updated_at
```

#### Consignments Table
**Old Schema:**
```sql
id, company_id, bilty_no, date, vehicle_no, driver_name, vehicle_type,
sender_name, from_city, to_city, qty, details, km, rate, amount, advance, balance
```

**New Schema (adds):**
```sql
+ rate_type (ENUM: 'Fixed', 'PerKM')
+ created_at, updated_at
```

#### New Tables
- `users` - For authentication
- `activity_logs` - For audit trail
- Enhanced `bills` and `payments` tables

### Migration Script

Create a migration script to transfer existing data:

```php
<?php
// migration_script.php - Run from command line

require 'vendor/autoload.php';

$oldDb = new mysqli('localhost', 'root', '', 'bilty_db_old');
$newDb = new mysqli('localhost', 'root', '', 'bilty_db');

// 1. Migrate Companies
echo "Migrating companies...\n";
$result = $oldDb->query("SELECT * FROM companies");
while ($row = $result->fetch_assoc()) {
    $stmt = $newDb->prepare("INSERT INTO companies (id, name, address, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->bind_param('iss', $row['id'], $row['name'], $row['address']);
    $stmt->execute();
}
echo "Companies migrated.\n";

// 2. Migrate Consignments
echo "Migrating consignments...\n";
$result = $oldDb->query("SELECT * FROM consignments");
while ($row = $result->fetch_assoc()) {
    // Determine rate_type based on rate value
    $rateType = ($row['rate'] == 0) ? 'Fixed' : 'PerKM';
    
    $stmt = $newDb->prepare("
        INSERT INTO consignments (
            id, company_id, bilty_no, date, vehicle_no, driver_name, vehicle_type,
            sender_name, from_city, to_city, qty, details, km, rate, rate_type,
            amount, advance, balance, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->bind_param(
        'iissssssssisssddd',
        $row['id'], $row['company_id'], $row['bilty_no'], $row['date'],
        $row['vehicle_no'], $row['driver_name'], $row['vehicle_type'],
        $row['sender_name'], $row['from_city'], $row['to_city'],
        $row['qty'], $row['details'], $row['km'], $row['rate'], $rateType,
        $row['amount'], $row['advance'], $row['balance']
    );
    $stmt->execute();
}
echo "Consignments migrated.\n";

// 3. Migrate Bills (if exists)
if ($oldDb->query("SHOW TABLES LIKE 'bills'")->num_rows > 0) {
    echo "Migrating bills...\n";
    $result = $oldDb->query("SELECT * FROM bills");
    while ($row = $result->fetch_assoc()) {
        $stmt = $newDb->prepare("
            INSERT INTO bills (
                id, company_id, bill_no, issue_date, gross_amount, tax_amount,
                net_amount, payment_status, payment_date, payment_note,
                status, pdf_path, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param(
            'iissdddssss',
            $row['id'], $row['company_id'], $row['bill_no'], $row['issue_date'],
            $row['gross_amount'], $row['tax_amount'], $row['net_amount'],
            $row['payment_status'], $row['payment_date'], $row['payment_note'],
            $row['status'] ?? 'draft', $row['pdf_path']
        );
        $stmt->execute();
    }
    echo "Bills migrated.\n";
}

// 4. Migrate Payments (if exists)
if ($oldDb->query("SHOW TABLES LIKE 'payments'")->num_rows > 0) {
    echo "Migrating payments...\n";
    $result = $oldDb->query("SELECT * FROM payments");
    while ($row = $result->fetch_assoc()) {
        $stmt = $newDb->prepare("
            INSERT INTO payments (
                id, consignment_id, payment_date, amount, payment_method,
                notes, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param(
            'iisds',
            $row['id'], $row['consignment_id'], $row['payment_date'],
            $row['amount'], $row['payment_method'] ?? 'cash', $row['notes'] ?? ''
        );
        $stmt->execute();
    }
    echo "Payments migrated.\n";
}

// 5. Migrate Vehicle Maintenance (if exists)
if ($oldDb->query("SHOW TABLES LIKE 'vehicle_maintenance'")->num_rows > 0) {
    echo "Migrating vehicle maintenance...\n";
    $result = $oldDb->query("SELECT * FROM vehicle_maintenance");
    while ($row = $result->fetch_assoc()) {
        $stmt = $newDb->prepare("
            INSERT INTO vehicle_maintenance (
                id, entry_date, vehicle_no, expense_type, amount, narration,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param(
            'isssds',
            $row['id'], $row['entry_date'], $row['vehicle_no'],
            $row['expense_type'], $row['amount'], $row['narration']
        );
        $stmt->execute();
    }
    echo "Vehicle maintenance migrated.\n";
}

echo "\nMigration complete!\n";
echo "Please verify data integrity before going live.\n";
```

### Run Migration Script

```bash
php migration_script.php
```

## Step 4: Migrate Files

Copy uploaded PDF files:

```bash
# Copy bilty PDFs
cp -r /path/to/old/bilty/bilty_pdfs/* /path/to/new/bilty/writable/uploads/pdfs/

# Set proper permissions
chmod -R 755 /path/to/new/bilty/writable/uploads/pdfs/
chown -R www-data:www-data /path/to/new/bilty/writable/uploads/pdfs/
```

## Step 5: Data Verification

Run these SQL queries to verify data integrity:

```sql
-- Check record counts match
SELECT 'companies' as table_name, COUNT(*) as old_count FROM old_db.companies
UNION
SELECT 'companies', COUNT(*) FROM bilty_db.companies;

SELECT 'consignments' as table_name, COUNT(*) as old_count FROM old_db.consignments
UNION
SELECT 'consignments', COUNT(*) FROM bilty_db.consignments;

-- Verify foreign keys
SELECT COUNT(*) as orphaned_consignments 
FROM consignments c 
LEFT JOIN companies cp ON c.company_id = cp.id 
WHERE cp.id IS NULL;

-- Should return 0
```

## Step 6: Testing

Test all functionality on the new system:

### Authentication
- [ ] Login with admin account
- [ ] Password reset functionality
- [ ] Session timeout

### Core Features
- [ ] Create new bilty
- [ ] Edit existing bilty
- [ ] Search/filter bilties
- [ ] View bilty details
- [ ] Print bilty
- [ ] Create bills
- [ ] Manage payments
- [ ] Vehicle maintenance records
- [ ] Reports generation

### Data Integrity
- [ ] All bilties visible and correct
- [ ] Company names display correctly
- [ ] Calculations (amount, balance) are accurate
- [ ] Dates are correct
- [ ] PDF files are accessible

## Step 7: Go Live

### Cutover Steps

1. **Schedule maintenance window** (low-traffic time)

2. **Final backup** of old system
   ```bash
   mysqldump -u root -p bilty_db > final_backup_$(date +%Y%m%d_%H%M%S).sql
   ```

3. **Put old system in read-only mode**
   - Prevent new data entry
   - Display maintenance notice

4. **Run final incremental migration**
   - Migrate any data created since initial migration

5. **Switch DNS/virtual host** to point to new system

6. **Monitor for issues**
   - Check error logs
   - Monitor user feedback
   - Verify key transactions

7. **Keep old system** accessible (renamed/subdomain) for reference

## Rollback Plan

If issues arise:

1. **Switch back** DNS/virtual host to old system
2. **Restore old database** if modified
3. **Communicate** status to users
4. **Document issues** for resolution
5. **Schedule new migration** after fixes

## Post-Migration

### Week 1
- Monitor error logs daily
- Collect user feedback
- Fix critical issues immediately
- Keep old system running (read-only)

### Week 2-4
- Address minor issues
- Optimize performance
- User training if needed
- Document any process changes

### Month 2
- Full decommission of old system (after final backup)
- Archive old code for reference
- Update all documentation
- Celebrate success! 🎉

## Support During Migration

Contact: Ali Abbas
- Phone: +92 348 3469617
- Keep backup contact of database administrator

## Checklist Summary

- [ ] Complete backup of old system
- [ ] New system installed and tested
- [ ] Database migrated and verified
- [ ] Files copied and accessible
- [ ] All features tested
- [ ] Users notified of changes
- [ ] Rollback plan ready
- [ ] Go-live scheduled
- [ ] Monitoring in place
- [ ] Old system archived
