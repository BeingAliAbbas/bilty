# HMVC Modules Structure

This application uses an HMVC (Hierarchical Model-View-Controller) structure for better code organization and modularity.

## Module Structure

Each module is self-contained with its own Controllers, Models, and Views:

```
app/Modules/
├── Auth/                  # User authentication (if needed in future)
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── Consignment/           # Bilty/Consignment management
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── Company/               # Company/Customer management
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── Bill/                  # Bill generation and management
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── Payment/               # Payment tracking
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── VehicleMaintenance/    # Vehicle expense tracking
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── Dashboard/             # Main dashboard with statistics
│   ├── Controllers/
│   ├── Models/
│   └── Views/
└── Report/                # Various reports
    ├── Controllers/
    ├── Models/
    └── Views/
```

## Benefits of HMVC

1. **Modularity**: Each feature is self-contained
2. **Reusability**: Modules can call other modules
3. **Maintainability**: Easier to locate and update code
4. **Scalability**: Add new features without affecting existing code
5. **Team Development**: Different developers can work on different modules

## Using Modules

### Creating a Controller in a Module

```php
<?php
namespace App\Modules\Consignment\Controllers;

use App\Controllers\BaseController;

class ConsignmentController extends BaseController
{
    public function index()
    {
        return view('App\Modules\Consignment\Views\index');
    }
}
```

### Routing to Modules

In `app/Config/Routes.php`:

```php
$routes->group('consignment', ['namespace' => 'App\Modules\Consignment\Controllers'], function($routes) {
    $routes->get('/', 'ConsignmentController::index');
    $routes->get('add', 'ConsignmentController::add');
    $routes->post('save', 'ConsignmentController::save');
});
```

### Loading Module Views

```php
// From within a controller
return view('App\Modules\Consignment\Views\index', $data);

// Or use the module's namespace
return view('\App\Modules\Consignment\Views\index', $data);
```

### Module Models

Models can be placed in the module's Models directory:

```php
<?php
namespace App\Modules\Consignment\Models;

use CodeIgniter\Model;

class ConsignmentModel extends Model
{
    // Model implementation
}
```

Then use it:

```php
$model = new \App\Modules\Consignment\Models\ConsignmentModel();
```

Alternatively, keep shared models in `app/Models/` for use across modules.

## Best Practices

1. **Keep modules focused**: Each module should handle one specific feature area
2. **Shared code**: Common functionality goes in `app/Controllers/BaseController.php`
3. **Shared models**: Database models can stay in `app/Models/` if used by multiple modules
4. **Views**: Keep views within modules for better encapsulation
5. **Helpers**: Create module-specific helpers when needed

## Migration from Legacy

The legacy PHP files have been moved to `/legacy` directory and will be gradually migrated into the appropriate modules:

- `add_bilty.php` → `Consignment/Controllers/ConsignmentController::add()`
- `view_bilty.php` → `Consignment/Controllers/ConsignmentController::index()`
- `manage_bills.php` → `Bill/Controllers/BillController::index()`
- `reports.php` → `Report/Controllers/ReportController::index()`
- etc.

## Next Steps

To continue development:

1. Create controllers in each module directory
2. Move business logic from legacy files into controllers
3. Create views in the module's Views directory
4. Update routes to point to module controllers
5. Test each module independently

## Example: Consignment Module

### Controller: `app/Modules/Consignment/Controllers/ConsignmentController.php`

```php
<?php
namespace App\Modules\Consignment\Controllers;

use App\Controllers\BaseController;
use App\Models\ConsignmentModel;
use App\Models\CompanyModel;

class ConsignmentController extends BaseController
{
    protected $consignmentModel;
    protected $companyModel;

    public function __construct()
    {
        $this->consignmentModel = new ConsignmentModel();
        $this->companyModel = new CompanyModel();
    }

    public function index()
    {
        $data['consignments'] = $this->consignmentModel->getWithCompany();
        return view('App\Modules\Consignment\Views\index', $data);
    }

    public function add()
    {
        $data['companies'] = $this->companyModel->findAll();
        $data['next_bilty_no'] = $this->consignmentModel->getNextBiltyNo();
        return view('App\Modules\Consignment\Views\add', $data);
    }

    public function save()
    {
        // Validation and saving logic
    }
}
```

### Route: `app/Config/Routes.php`

```php
$routes->group('consignment', ['namespace' => 'App\Modules\Consignment\Controllers'], function($routes) {
    $routes->get('/', 'ConsignmentController::index');
    $routes->get('add', 'ConsignmentController::add');
    $routes->post('save', 'ConsignmentController::save');
    $routes->get('edit/(:num)', 'ConsignmentController::edit/$1');
    $routes->post('update/(:num)', 'ConsignmentController::update/$1');
    $routes->get('delete/(:num)', 'ConsignmentController::delete/$1');
});
```

### View: `app/Modules/Consignment/Views/index.php`

```php
<!DOCTYPE html>
<html>
<head>
    <title>View Bilties</title>
</head>
<body>
    <h1>Consignments</h1>
    <table>
        <thead>
            <tr>
                <th>Bilty No</th>
                <th>Date</th>
                <th>Company</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($consignments as $c): ?>
            <tr>
                <td><?= esc($c['bilty_no']) ?></td>
                <td><?= esc($c['date']) ?></td>
                <td><?= esc($c['company_name']) ?></td>
                <td><?= esc($c['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
```

This structure keeps all consignment-related code in one place, making it easier to maintain and extend.
