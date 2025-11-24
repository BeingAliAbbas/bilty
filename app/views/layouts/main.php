<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  
  <!-- Theme variables -->
  <style>
    :root {
      --primary: #97113a;
      --page: #e4d7e8;
      --text: #111827;
    }

    /* minimal fallback utilities */
    .bg-primary { background-color: var(--primary) !important; }
    .text-primary { color: var(--primary) !important; }
    .bg-page { background-color: var(--page) !important; }
    .focus-ring-primary:focus { box-shadow: 0 0 0 3px rgba(151,17,58,0.14); outline: none; }
    .btn-primary { background-color: var(--primary); color: #fff; padding: .45rem .9rem; border-radius: .375rem; display:inline-block; text-decoration:none; }
    html,body { color: var(--text); -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; }
  </style>
  
  <link href="/assets/css/output.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/fonts/fontawesome/css/all.min.css">

  <!-- Tailwind runtime config -->
  <script>
    window.tailwind = window.tailwind || {};
    window.tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#97113a',
            page: '#e4d7e8'
          }
        }
      }
    };
  </script>

  <style>
    a:focus { outline: 3px solid rgba(151,17,58,0.14); outline-offset: 2px; }
  </style>
  
  <title><?php echo isset($title) ? htmlspecialchars($title) . ' — Bilty Management' : 'Bilty Management System'; ?></title>
  
  <?php if (isset($extraHead)): ?>
    <?php echo $extraHead; ?>
  <?php endif; ?>
</head>
<body class="bg-page min-h-screen text-gray-800">
  
  <?php include __DIR__ . '/header.php'; ?>
  
  <main>
    <?php echo $content; ?>
  </main>
  
  <footer class="mt-auto py-6">
    <div class="max-w-6xl mx-auto px-4 md:px-6">
      <div class="footer-plate rounded-xl p-4 text-center text-sm text-gray-700" style="background: rgba(255,255,255,.88); border: 1px solid rgba(2,6,23,.08); box-shadow: 0 2px 4px rgba(16,24,40,.04), 0 8px 24px rgba(16,24,40,.10);">
        <span class="inline-flex items-center gap-2">
          <i class="fa-solid fa-code" style="color:#97113a"></i>
          <span>Developed by <strong>Ali Abbas</strong></span>
          <span class="text-gray-300">|</span>
          <i class="fa-solid fa-phone" style="color:#97113a"></i>
          <a class="hover:text-primary font-medium" href="tel:+923483469617" dir="ltr">+92 348 3469617</a>
        </span>
      </div>
    </div>
  </footer>
  
  <?php if (isset($extraScripts)): ?>
    <?php echo $extraScripts; ?>
  <?php endif; ?>
</body>
</html>
