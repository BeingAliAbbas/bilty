<?php
require_once 'config.php';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'head.php'; ?>
  <title>🚚 Bilty Management</title>
</head>
<body class="bg-page min-h-screen text-gray-800">
  <?php include 'header.php'; ?>

  <main class="max-w-5xl mx-auto py-12 px-4 md:px-6">
    <section class="bg-white/80 rounded-xl shadow-lg p-8">
      <h1 class="text-3xl md:text-4xl font-bold text-primary mb-4">🚚 Bilty Management System</h1>
      <!-- <p class="text-gray-600 mb-6">Fast, simple way to record and track bilties. Designed for clear screens and quick entry.</p> -->

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <a href="add_bilty.php" class="block p-6 rounded-lg shadow-sm bg-primary text-white hover:shadow-lg transition">
          <div class="text-2xl font-semibold">Add New Bilty</div>
          <div class="mt-1 text-sm opacity-90">Create a bilty quickly</div>
        </a>

        <a href="view_bilty.php" class="block p-6 rounded-lg shadow-sm bg-white hover:shadow-lg transition border border-gray-200">
          <div class="text-2xl font-semibold text-gray-800">View Bilties</div>
          <div class="mt-1 text-sm text-gray-600">Browse all bilties</div>
        </a>

        <a href="reports.php" class="block p-6 rounded-lg shadow-sm bg-white hover:shadow-lg transition border border-gray-200">
          <div class="text-2xl font-semibold text-gray-800">Reports</div>
          <div class="mt-1 text-sm text-gray-600">Summary by company & totals</div>
        </a>
      </div>

      <!-- <div class="mt-8 text-sm text-gray-600">
        For support, contact your system administrator.
      </div> -->
    </section>
  </main>
</body>
</html>