<?php
require_once 'config.php';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'head.php'; ?>
  <!-- Offline Font Awesome (make sure webfonts/ exists at fontawesome/webfonts) -->

  <title>🚚 Bilty Management</title>

  <style>
    /* Page background to match the lilac tone */
    body.bg-page {
      background:
        radial-gradient(1200px 420px at 50% 0%, rgba(153, 27, 65, 0.10), transparent 60%),
        #efe6f3;
    }

    /* Themed surface for the hero/section (soft primary tint, subtle border + shadow) */
    .theme-surface {
      position: relative;
      overflow: hidden;
      background:
        linear-gradient(0deg, rgba(255,255,255,.95), rgba(255,255,255,.92)),
        radial-gradient(900px 280px at -10% -10%, rgba(159, 18, 57, .10), transparent 60%),
        radial-gradient(900px 280px at 110% -10%, rgba(159, 18, 57, .08), transparent 60%),
        radial-gradient(600px 200px at 50% 110%, rgba(159, 18, 57, .05), transparent 70%);
      border: 1px solid rgba(159, 18, 57, .12);
      box-shadow:
        0 10px 20px rgba(16,24,40,.06),
        0 30px 60px rgba(16,24,40,.10);
    }
    .theme-surface::after {
      content: "";
      position: absolute;
      inset: 0;
      pointer-events: none;
      background:
        radial-gradient(160px 60px at 20% 110%, rgba(159, 18, 57, .08), transparent 60%),
        radial-gradient(120px 50px at 80% 115%, rgba(159, 18, 57, .06), transparent 60%);
    }

    /* Card elevation and hover */
    .card {
      transition: transform .15s ease, box-shadow .2s ease, background-color .2s ease;
      box-shadow: 0 2px 6px rgba(16,24,40,.06);
    }
    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(16,24,40,.10);
    }

    /* Larger icon chips on cards */
    .icon-chip {
      width: 3rem;
      height: 3rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: .75rem;
    }

    /* Footer plate look */
    .footer-plate {
      background: rgba(255,255,255,.88);
      border: 1px solid rgba(2,6,23,.08);
      box-shadow:
        0 2px 4px rgba(16,24,40,.04),
        0 8px 24px rgba(16,24,40,.10);
    }
  </style>
</head>
<body class="bg-page min-h-screen text-gray-800">
  <?php include 'header.php'; ?>

  <main class="max-w-6xl mx-auto py-10 px-4 md:px-6">
    <section class="theme-surface rounded-2xl p-6 md:p-10">
      <h1 class="text-3xl md:text-5xl font-extrabold text-primary tracking-tight mb-6">
        Bahar Ali - Bilty Management System
      </h1>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 md:gap-6">
        <!-- Add New Bilty (Primary) -->
        <a href="add_bilty.php"
           class="card block rounded-xl p-6 bg-primary text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/40"
           aria-label="Add New Bilty">
          <div class="flex items-start gap-4">
            <span class="icon-chip bg-white/20 shadow-inner">
              <i class="fa-solid fa-truck-fast text-2xl"></i>
            </span>
            <div>
              <div class="text-2xl font-semibold">Add New Bilty</div>
              <div class="mt-1 text-sm opacity-90">Create a bilty quickly</div>
            </div>
            <span class="ml-auto self-center opacity-90">
              <i class="fa-solid fa-arrow-right-long text-lg"></i>
            </span>
          </div>
        </a>

        <!-- View Bilties -->
        <a href="view_bilty.php"
           class="card block rounded-xl p-6 bg-white hover:bg-white border border-gray-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/20"
           aria-label="View Bilties">
          <div class="flex items-start gap-4">
            <span class="icon-chip bg-primary/10 text-primary">
              <i class="fa-solid fa-rectangle-list text-2xl"></i>
            </span>
            <div>
              <div class="text-2xl font-semibold text-gray-900">View Bilties</div>
              <div class="mt-1 text-sm text-gray-600">Browse all bilties</div>
            </div>
            <span class="ml-auto self-center text-gray-500">
              <i class="fa-solid fa-arrow-right-long text-lg"></i>
            </span>
          </div>
        </a>

        <!-- Reports -->
        <a href="reports.php"
           class="card block rounded-xl p-6 bg-white hover:bg-white border border-gray-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/20"
           aria-label="Reports">
          <div class="flex items-start gap-4">
            <span class="icon-chip bg-primary/10 text-primary">
              <i class="fa-solid fa-chart-column text-2xl"></i>
            </span>
            <div>
              <div class="text-2xl font-semibold text-gray-900">Reports</div>
              <div class="mt-1 text-sm text-gray-600">Summary by company & totals</div>
            </div>
            <span class="ml-auto self-center text-gray-500">
              <i class="fa-solid fa-arrow-right-long text-lg"></i>
            </span>
          </div>
        </a>
      </div>
    </section>

    <div class="h-16"></div>
  </main>

  <!-- Footer -->
  <footer class="mt-auto py-6">
    <div class="max-w-6xl mx-auto px-4 md:px-6">
      <div class="footer-plate rounded-xl p-4 text-center text-sm text-gray-700">
        <span class="inline-flex items-center gap-2">
          <i class="fa-solid fa-code text-primary"></i>
          <span>Developed by <strong>Ali Abbas</strong></span>
          <span class="text-gray-300">|</span>
          <i class="fa-solid fa-phone text-primary"></i>
          <a class="hover:text-primary font-medium" href="tel:+923483469617" dir="ltr">+92 348 3469617</a>
        </span>
      </div>
    </div>
  </footer>
</body>
</html>