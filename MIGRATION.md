# Bilty Management System - MVC Migration Guide

## Overview
This document maps the legacy PHP files to their new MVC structure equivalents.

## Directory Structure

```
bilty/
├── app/
│   ├── controllers/          # All controller classes
│   │   ├── HomeController.php
│   │   ├── BiltyController.php
│   │   ├── CompanyController.php
│   │   ├── BillController.php
│   │   ├── MaintenanceController.php
│   │   └── ReportController.php
│   ├── models/               # All model classes
│   │   ├── Consignment.php   # Bilty/Consignment model
│   │   ├── Company.php
│   │   ├── Bill.php
│   │   └── VehicleMaintenance.php
│   └── views/                # All view templates
│       ├── layouts/
│       │   ├── main.php      # Main layout template
│       │   └── header.php    # Navigation header
│       ├── home/
│       │   └── index.php
│       ├── bilty/
│       ├── bill/
│       ├── maintenance/
│       └── report/
├── core/                     # Core framework classes
│   ├── Database.php          # Database connection (Singleton)
│   ├── Model.php             # Base model class
│   ├── Controller.php        # Base controller class
│   └── Router.php            # URL routing system
├── public/                   # Public web root
│   ├── index.php             # Application entry point
│   ├── .htaccess             # URL rewriting rules
│   └── assets/
│       ├── css/
│       │   └── output.css
│       └── fonts/
│           └── fontawesome/
└── [legacy files remain for reference]
```

## File Migration Map

### Core Configuration
| Legacy File | New MVC Equivalent |
|------------|-------------------|
| config.php | core/Database.php (Singleton pattern) |
| head.php | app/views/layouts/main.php (head section) |
| header.php | app/views/layouts/header.php |

### Home & Search
| Legacy File | New Controller | New View | Route |
|------------|---------------|----------|-------|
| index.php | HomeController@index | home/index.php | GET/POST / |

### Bilty Management
| Legacy File | New Controller | New View | Route |
|------------|---------------|----------|-------|
| add_bilty.php | BiltyController@add | bilty/add.php | GET/POST /bilty/add |
| view_bilty.php | BiltyController@index | bilty/index.php | GET /bilty |
| view_bilty_details.php | BiltyController@details | bilty/details.php | GET /bilty/details/:id |
| view_bilty_print.php | BiltyController@print | bilty/print.php | GET /bilty/print/:id |
| print_bulk.php | BiltyController@bulkPrint | bilty/bulk-print.php | GET /bilty/bulk-print |
| update_bilty_payment.php | BiltyController@updatePayment | (JSON response) | POST /bilty/update-payment |

### Company Management
| Legacy File | New Controller | New View | Route |
|------------|---------------|----------|-------|
| company_save.php | CompanyController@save | (JSON response) | POST /company/save |

### Bill Management
| Legacy File | New Controller | New View | Route |
|------------|---------------|----------|-------|
| manage_bills.php | BillController@index | bill/index.php | GET /bill |
| update_bill_payment.php | BillController@updatePayment | (JSON response) | POST /bill/update-payment |

### Vehicle Maintenance
| Legacy File | New Controller | New View | Route |
|------------|---------------|----------|-------|
| vehicle_maintenance.php | MaintenanceController@index | maintenance/index.php | GET /maintenance |
| vehicle_maintenance_save.php | MaintenanceController@save | (JSON response) | POST /maintenance/save |

### Reports
| Legacy File | New Controller | New View | Route |
|------------|---------------|----------|-------|
| reports.php | ReportController@index | report/index.php | GET /report |

### PDF Generation
| Legacy File | Status | Notes |
|------------|--------|-------|
| save_pdf.php | To be migrated | Needs integration with BillController |
| save_bilty_pdf.php | To be migrated | Needs integration with BiltyController |
| finalize_bill.php | To be migrated | Needs integration with BillController |

## Models and Database

### Models Created
1. **Consignment** (Bilty) - Handles all consignment/bilty database operations
2. **Company** - Manages companies
3. **Bill** - Handles billing operations
4. **VehicleMaintenance** - Manages vehicle maintenance records

### Database Tables
- `consignments` - Bilty records
- `companies` - Company information
- `bills` - Bill records
- `vehicle_maintenance` - Maintenance records

## URL Structure

### Old URL Pattern
```
http://example.com/add_bilty.php
http://example.com/view_bilty.php?id=123
```

### New URL Pattern
```
http://example.com/bilty/add
http://example.com/bilty/details/123
```

## Key Changes

1. **Single Entry Point**: All requests go through `public/index.php`
2. **Clean URLs**: `.htaccess` handles URL rewriting
3. **Separation of Concerns**: 
   - Controllers handle request logic
   - Models handle database operations
   - Views handle presentation
4. **No Direct Database Access in Views**: All data comes from controllers
5. **Layout System**: Common elements (header, footer) are in layouts
6. **Autoloading**: Core classes are loaded automatically
7. **Security**: Input sanitization and prepared statements
8. **RESTful Routes**: Logical, hierarchical URL structure

## Migration Steps

### For Developers

1. **Set up the new structure**:
   - All core classes are in `core/`
   - All models are in `app/models/`
   - All controllers are in `app/controllers/`

2. **Update your web server**:
   - Point document root to `public/` directory
   - Ensure mod_rewrite is enabled
   - .htaccess file handles routing

3. **Database remains unchanged**:
   - No schema modifications required
   - Existing data works as-is

4. **Complete remaining views**:
   - Create view files for each controller action
   - Use layouts for consistent structure
   - Copy existing HTML/CSS from legacy files

5. **Test each feature**:
   - Compare with legacy version
   - Verify all CRUD operations
   - Test AJAX endpoints

## Benefits of MVC Structure

1. **Maintainability**: Code is organized logically
2. **Scalability**: Easy to add new features
3. **Reusability**: Models and controllers can be reused
4. **Testing**: Easier to write unit tests
5. **Security**: Centralized input handling
6. **Clean URLs**: Professional, SEO-friendly URLs
7. **Separation**: Business logic separated from presentation

## Next Steps

1. Complete all view templates
2. Migrate PDF generation logic
3. Add form validation
4. Implement authentication/authorization
5. Add unit tests
6. Create API documentation
7. Performance optimization
