<!doctype html>
<html lang="en">
<head>
  <?php include 'app/views/layout/head.php'; ?>
  <title><?php echo isset($title) ? htmlspecialchars($title) . ' — Bilty Management' : 'Bilty Management'; ?></title>
</head>
<body class="bg-page min-h-screen text-gray-800">
  <?php include 'app/views/layout/header.php'; ?>

  <main class="max-w-5xl mx-auto py-12 px-4 md:px-6">
    <section class="bg-white/80 rounded-xl shadow-lg p-8">
      <h1 class="text-3xl md:text-4xl font-bold text-primary mb-4"><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : '🚚 Bilty Management System'; ?></h1>
      
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <a href="/consignments/create" class="block p-6 rounded-lg shadow-sm bg-primary text-white hover:shadow-lg transition">
          <div class="text-2xl font-semibold">Add New Bilty</div>
          <div class="mt-1 text-sm opacity-90">Create a bilty quickly</div>
        </a>

        <a href="/consignments" class="block p-6 rounded-lg shadow-sm bg-white hover:shadow-lg transition border border-gray-200">
          <div class="text-2xl font-semibold text-gray-800">View Bilties</div>
          <div class="mt-1 text-sm text-gray-600">Browse all bilties</div>
        </a>

        <a href="/reports" class="block p-6 rounded-lg shadow-sm bg-white hover:shadow-lg transition border border-gray-200">
          <div class="text-2xl font-semibold text-gray-800">Reports</div>
          <div class="mt-1 text-sm text-gray-600">Summary by company & totals</div>
        </a>
      </div>
    </section>
  </main>
</body>
</html>