<?php
// header.php - shared top navigation
// Include this inside <body>. Assumes head.php defines your CSS variables (like --primary).

// Detect current script for active state
$current = basename($_SERVER['PHP_SELF']);

// Define navigation items once (desktop + mobile reuse)
$navItems = [
    [
        'label' => 'Home',
        'href'  => 'index.php',
        'match' => ['index.php'],
    ],
    [
        'label' => 'New Bilty',
        'href'  => 'add_bilty.php',
        'match' => ['add_bilty.php'],
    ],
    [
        'label' => 'All Bilties',
        'href'  => 'view_bilty.php',
        'match' => ['view_bilty.php'],
    ],
    // New Bill Management page
    [
        'label' => 'Manage Bills',
        'href'  => 'manage_bills.php',
        'match' => ['manage_bills.php'],
    ],
    // Optional: direct entry to a “Generate Bill” workflow (comment out if not needed)
    // [
    //     'label' => 'Create Bill',
    //     'href'  => 'view_bilty.php?bill=1',
    //     'match' => ['print_bulk.php', 'view_bilty.php'],
    // ],
    [
        'label' => 'Reports',
        'href'  => 'reports.php',
        'match' => ['reports.php'],
    ],
];

// Helper: returns true if current file matches any alias in 'match'
function isActiveNav(array $item, string $current): bool {
    if (empty($item['match'])) return false;
    return in_array($current, $item['match'], true);
}
?>
<head>
  <?php include 'head.php'; ?>
</head>

<header class="bg-primary text-white" style="background:var(--primary);">
  <div class="max-w-6xl mx-auto px-4 md:px-6">
    <div class="flex items-center justify-between h-16">

      <!-- Logo / Brand -->
      <a href="index.php" class="flex items-center gap-3 group">
        <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-xl font-semibold group-hover:bg-white/15 transition-colors">
          🚚
        </div>
        <div>
          <div class="text-lg font-semibold leading-tight">Bilty Management</div>
          <div class="text-[11px] opacity-90 tracking-wide">Clear. Fast. Reliable.</div>
        </div>
      </a>

      <!-- Desktop Nav -->
      <nav class="hidden md:flex items-center gap-1 text-sm">
        <?php foreach ($navItems as $item): 
          $active = isActiveNav($item, $current);
        ?>
          <a
            href="<?php echo htmlspecialchars($item['href']); ?>"
            class="px-4 py-2 rounded transition-colors <?php echo $active
              ? 'bg-white/15 ring-2 ring-white/25 font-semibold'
              : 'hover:bg-white/10'; ?>"
          >
            <?php echo htmlspecialchars($item['label']); ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <!-- Mobile Toggle -->
      <div class="md:hidden">
        <button id="navToggle" class="p-2 rounded hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40" aria-label="Toggle menu" aria-expanded="false">
          ☰
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobileMenu" class="md:hidden hidden bg-primary/95 backdrop-blur-sm" style="background:rgba(151,17,58,0.95);">
    <div class="px-4 pb-4 pt-2 space-y-1">
      <?php foreach ($navItems as $item):
        $active = isActiveNav($item, $current);
      ?>
        <a
          href="<?php echo htmlspecialchars($item['href']); ?>"
          class="block px-3 py-2 rounded text-white/95 transition-colors <?php echo $active
            ? 'bg-white/20 font-semibold'
            : 'hover:bg-white/10'; ?>"
        >
          <?php echo htmlspecialchars($item['label']); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const toggleBtn = document.getElementById('navToggle');
      const menu = document.getElementById('mobileMenu');
      if (!toggleBtn || !menu) return;

      toggleBtn.addEventListener('click', () => {
        const hidden = menu.classList.toggle('hidden');
        toggleBtn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
      });

      // Optional: close menu when clicking a link (improves UX on mobile)
      menu.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
          menu.classList.add('hidden');
          toggleBtn.setAttribute('aria-expanded', 'false');
        });
      });
    });
  </script>
</header>