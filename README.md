# Bilty Management System - MVC Version

A complete PHP MVC refactoring of the Bilty (Transport Consignment) Management System.

## Features

- **Bilty Management**: Create, view, search, and print transport consignments
- **Company Management**: Manage company information with address support
- **Bill Management**: Track bills, payment status, and financial records
- **Vehicle Maintenance**: Record and track vehicle maintenance expenses
- **Reporting**: Comprehensive reports and analytics
- **Clean URLs**: Professional, SEO-friendly URL structure
- **Responsive Design**: Mobile-friendly interface
- **AJAX Operations**: Fast, seamless user experience

## Technology Stack

- **PHP** (Native, no framework)
- **MySQL** (Database)
- **Custom MVC** (Clean architecture)
- **Tailwind CSS** (Utility-first CSS)
- **Font Awesome** (Icons)
- **JavaScript** (Vanilla JS for interactivity)

## Directory Structure

```
bilty/
├── app/
│   ├── controllers/       # Application controllers
│   ├── models/           # Database models
│   └── views/            # View templates
├── core/                 # Core framework classes
│   ├── Database.php      # Database connection
│   ├── Model.php         # Base model
│   ├── Controller.php    # Base controller
│   └── Router.php        # URL routing
├── public/               # Public web root
│   ├── index.php         # Entry point
│   ├── .htaccess         # URL rewriting
│   └── assets/           # CSS, JS, fonts
└── MIGRATION.md          # Migration guide
```

## Installation

### Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- Web server with .htaccess support

### Setup Steps

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd bilty
   ```

2. **Configure database**:
   Edit `core/Database.php` and update the database credentials:
   ```php
   private $host = '127.0.0.1';
   private $user = 'root';
   private $pass = '';
   private $dbname = 'bilty_db';
   ```

3. **Import database**:
   - Create a MySQL database named `bilty_db`
   - Import the existing database schema (if available)
   - Required tables: `consignments`, `companies`, `bills`, `vehicle_maintenance`

4. **Configure web server**:
   - Point your document root to the `public/` directory
   - Ensure mod_rewrite is enabled in Apache
   - The `.htaccess` file handles URL rewriting

5. **Set permissions**:
   ```bash
   chmod -R 755 public/
   chmod -R 755 app/views/
   ```

6. **Access the application**:
   Navigate to `http://your-domain.com` in your browser

## URL Structure

| Feature | URL | Method |
|---------|-----|--------|
| Home Page | `/` | GET/POST |
| Add Bilty | `/bilty/add` | GET/POST |
| View All Bilties | `/bilty` | GET |
| Bilty Details | `/bilty/details/:id` | GET |
| Print Bilty | `/bilty/print/:id` | GET |
| Bulk Print | `/bilty/bulk-print` | GET |
| Manage Bills | `/bill` | GET |
| Vehicle Maintenance | `/maintenance` | GET |
| Reports | `/report` | GET |
| Save Company (AJAX) | `/company/save` | POST |
| Update Bill Payment (AJAX) | `/bill/update-payment` | POST |
| Update Bilty Payment (AJAX) | `/bilty/update-payment` | POST |
| Save Maintenance (AJAX) | `/maintenance/save` | POST |

## MVC Architecture

### Models
- **Consignment**: Manages bilty/consignment records
- **Company**: Manages company information
- **Bill**: Handles bill records and payments
- **VehicleMaintenance**: Tracks vehicle maintenance

### Controllers
- **HomeController**: Home page and search
- **BiltyController**: All bilty operations
- **CompanyController**: Company management
- **BillController**: Bill management
- **MaintenanceController**: Vehicle maintenance
- **ReportController**: Reports and analytics

### Views
- Organized by feature (bilty/, bill/, maintenance/, etc.)
- Layouts system for consistent UI
- Separation of HTML and PHP logic

## Features in Detail

### Bilty Management
- Auto-generate bilty numbers
- Search by bilty number, company, driver, route
- Filter by company
- Print individual or bulk bilties
- Track payment advances and balances
- Support for fixed and per-km rates

### Bill Management
- Track payment status (PAID/UNPAID)
- Filter by status and search
- Pagination support
- Quick payment status updates
- Statistics dashboard

### Vehicle Maintenance
- Record maintenance expenses
- Filter by date range, vehicle, expense type
- Pagination support
- Track total maintenance costs

### Reports
- Date range filtering
- Company-wise breakdowns
- Route analysis
- Vehicle type summaries
- Daily and monthly aggregations

## Security Features

- Prepared statements for all database queries
- Input validation and sanitization
- XSS protection with htmlspecialchars()
- CSRF protection ready (to be implemented)
- SQL injection prevention

## Code Style

- PSR-like coding standards
- Meaningful variable and function names
- Comments for complex logic
- Separation of concerns
- DRY (Don't Repeat Yourself) principles

## Legacy Files

Legacy PHP files remain in the root directory for reference during migration. These can be removed once all features are fully migrated and tested.

## Development

### Adding a New Feature

1. **Create a Model** (if needed):
   ```php
   // app/models/YourModel.php
   class YourModel extends Model {
       // Your database operations
   }
   ```

2. **Create a Controller**:
   ```php
   // app/controllers/YourController.php
   class YourController extends Controller {
       public function index() {
           $model = $this->model('YourModel');
           $data = $model->getData();
           $this->view('your/index', ['data' => $data]);
       }
   }
   ```

3. **Create Views**:
   ```php
   // app/views/your/index.php
   <h1>Your Page</h1>
   <!-- Your HTML -->
   ```

4. **Add Routes**:
   ```php
   // public/index.php
   $router->get('/your-route', 'YourController@index');
   ```

### Database Queries

Always use prepared statements through the Model class:
```php
// In your model
$sql = "SELECT * FROM table WHERE id = ?";
$result = $this->fetchOne($sql, [$id]);
```

## Troubleshooting

### 404 Errors
- Ensure `.htaccess` is in the `public/` directory
- Verify mod_rewrite is enabled
- Check that document root points to `public/`

### Database Connection Errors
- Verify database credentials in `core/Database.php`
- Ensure MySQL server is running
- Check database name and permissions

### Blank Pages
- Enable error reporting in PHP
- Check PHP error logs
- Verify all required files exist

## Migration from Legacy

See [MIGRATION.md](MIGRATION.md) for detailed migration guide and file mappings.

## Contributing

1. Follow existing code style
2. Test all changes thoroughly
3. Update documentation
4. Create meaningful commit messages

## License

[Specify your license]

## Credits

Developed by **Ali Abbas**
Phone: +92 348 3469617

## Support

For issues or questions:
- Create an issue in the repository
- Contact the developer

---

**Note**: This is a complete MVC refactoring. All legacy files remain for reference but are not used in the new system.
