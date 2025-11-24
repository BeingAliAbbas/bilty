# View Template Guide

This guide shows how to create view files by copying from legacy PHP files.

## General Pattern

Each view file should:
1. Contain only HTML, CSS, and JavaScript
2. Use variables passed from the controller
3. Not directly access database
4. Use the layout system

## Template Structure

```php
<!-- app/views/feature/action.php -->

<!-- Optional: Page-specific styles -->
<style>
  /* Copy styles from legacy file */
  /* Keep only the styles specific to this page */
</style>

<!-- Main content -->
<main class="max-w-6xl mx-auto py-8 px-4">
  
  <!-- Page header -->
  <div class="mb-6">
    <h1 class="text-2xl font-bold">Page Title</h1>
  </div>
  
  <!-- Page content -->
  <!-- Copy HTML from legacy file -->
  <!-- Use variables from controller: -->
  <!-- <?php echo htmlspecialchars($variable); ?> -->
  
</main>

<!-- Optional: Page-specific scripts -->
<script>
  /* Copy JavaScript from legacy file */
  /* Update URLs to new MVC format */
  /* Update AJAX endpoints */
</script>
```

## Examples

### Example 1: List Page (view_bilty.php → bilty/index.php)

```php
<!-- Variables available from controller:
  - $rows (array of bilties)
  - $companies (array)
  - $search (string)
  - $company_filter (int)
-->

<style>
  /* Copy table styles from view_bilty.php */
</style>

<main class="max-w-7xl mx-auto py-8 px-4">
  <h1>All Bilties</h1>
  
  <!-- Copy filter form -->
  <form method="get" action="/bilty">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>">
    <select name="company">
      <option value="0">All Companies</option>
      <?php foreach($companies as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $company_filter == $c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Filter</button>
  </form>
  
  <!-- Copy table HTML -->
  <table>
    <?php foreach($rows as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['bilty_no']) ?></td>
        <!-- etc -->
      </tr>
    <?php endforeach; ?>
  </table>
</main>

<script>
  // Copy JavaScript
  // Update URLs: view_bilty_details.php?id=123 → /bilty/details/123
</script>
```

### Example 2: Form Page (add_bilty.php → bilty/add.php)

```php
<!-- Variables from controller:
  - $companies (array)
  - $auto_bilty_no (string)
  - $errors (array)
  - $success (string)
  - $post_data (array)
-->

<style>
  /* Copy form styles */
</style>

<main class="max-w-5xl mx-auto p-6">
  <h1>Add New Bilty</h1>
  
  <!-- Show errors -->
  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <?php foreach($errors as $error): ?>
        <p><?= htmlspecialchars($error) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <!-- Show success -->
  <?php if ($success): ?>
    <div class="alert alert-success">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>
  
  <!-- Copy form HTML -->
  <form method="post" action="/bilty/add">
    <input type="text" name="bilty_no" value="<?= htmlspecialchars($auto_bilty_no) ?>" readonly>
    
    <select name="company">
      <?php foreach($companies as $company): ?>
        <option value="<?= $company['id'] ?>">
          <?= htmlspecialchars($company['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    
    <!-- More fields... -->
    
    <button type="submit">Save Bilty</button>
  </form>
</main>

<script>
  // Copy JavaScript
  // Update AJAX URLs: company_save.php → /company/save
</script>
```

### Example 3: AJAX Form (maintenance)

```php
<!-- Variables: $rows, $stats, $start_date, $end_date -->

<style>
  /* Modal and table styles */
</style>

<main>
  <!-- Filters -->
  <form method="get" action="/maintenance">
    <input type="date" name="start" value="<?= $start_date ?>">
    <input type="date" name="end" value="<?= $end_date ?>">
    <button>Apply</button>
  </form>
  
  <!-- Stats -->
  <div class="stats">
    <span>Total: <?= $stats['cnt'] ?></span>
    <span>Amount: <?= number_format($stats['total_amt'], 2) ?></span>
  </div>
  
  <!-- Table -->
  <table>
    <?php foreach($rows as $row): ?>
      <tr>
        <td><?= $row['entry_date'] ?></td>
        <td><?= $row['vehicle_no'] ?></td>
        <td><?= $row['expense_type'] ?></td>
        <td><?= number_format($row['amount'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  
  <!-- Modal -->
  <div id="modal" class="modal">
    <form id="addForm">
      <input type="date" name="entry_date">
      <input type="text" name="vehicle_no">
      <!-- etc -->
      <button type="submit">Save</button>
    </form>
  </div>
</main>

<script>
  // AJAX submission
  document.getElementById('addForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // Update URL
    const response = await fetch('/maintenance/save', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    if (data.ok) {
      // Success
      window.location.reload();
    }
  });
</script>
```

## Key Changes When Converting

### URLs
```php
// Old
<a href="view_bilty_details.php?id=123">View</a>
<form action="company_save.php">

// New
<a href="/bilty/details/123">View</a>
<form action="/company/save">
```

### AJAX Endpoints
```javascript
// Old
fetch('update_bill_payment.php', {...})

// New
fetch('/bill/update-payment', {...})
```

### File Includes
```php
// Old
include 'header.php';
include 'head.php';

// New
// Not needed - layout handles this
```

### Database Queries
```php
// Old
$result = $conn->query("SELECT...");
while($row = $result->fetch_assoc()) {...}

// New
// Remove - data comes from controller as variables
// Just use: <?php foreach($rows as $row): ?>
```

## Checklist for Each View

- [ ] Copy HTML structure
- [ ] Copy CSS styles
- [ ] Copy JavaScript code
- [ ] Replace database code with controller variables
- [ ] Update all URLs to new format
- [ ] Update AJAX endpoints
- [ ] Remove includes (header.php, head.php)
- [ ] Test the page
- [ ] Test form submission (if applicable)
- [ ] Test AJAX calls (if applicable)

## Variables Available in Views

### All Views (from layout)
- `$title` - Page title
- `$current` - Current page for nav highlighting

### Bilty Views
- `$rows` - List of bilties
- `$bilty` - Single bilty details
- `$companies` - List of companies
- `$auto_bilty_no` - Next bilty number
- `$errors` - Validation errors
- `$success` - Success message

### Bill Views
- `$bills` - List of bills
- `$stats` - Statistics
- `$status_filter`, `$search`, `$sort`
- `$page`, `$total_pages`

### Maintenance Views
- `$rows` - Maintenance records
- `$stats` - Count and totals
- `$start_date`, `$end_date`
- `$vehicle`, `$expense`

## Tips

1. **Start Simple**: Convert home page style views first
2. **Copy Carefully**: Don't lose CSS or JavaScript
3. **Test Often**: Test after each view
4. **Use Helpers**: htmlspecialchars() for all output
5. **Stay Consistent**: Follow the established pattern

## Common Mistakes to Avoid

❌ Accessing `$conn` directly in view
✅ Use variables from controller

❌ Including config.php or other files
✅ Layout handles all includes

❌ Putting business logic in view
✅ Logic goes in controller

❌ Direct $_POST access
✅ Controller handles POST, view gets clean data

## Time Estimate

- Simple view (no forms): 15-30 min
- Form view: 30-60 min
- Complex view with AJAX: 1-2 hours
- All remaining views: 4-6 hours total
