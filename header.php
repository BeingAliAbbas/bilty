<?php
// header.php - shared top navigation
// Include this inside <body>. Assumes head.php defines your CSS variables (like --primary).
// Add this line in pages BEFORE including header if not already started sessions (optional for future auth):
// if(session_status()===PHP_SESSION_NONE) session_start();

// Detect current script for active state
$current = basename($_SERVER['PHP_SELF']);

// Central navigation configuration
// Add or remove items here; 'match' can contain multiple filenames that should highlight the same nav item.
$navItems = [
    [
        'label' => 'Home',
        'href'  => 'index.php',
        'match' => ['index.php'],
        'icon'  => '🏠'
    ],
    [
        'label' => 'New Bilty',
        'href'  => 'add_bilty.php',
        'match' => ['add_bilty.php'],
        'icon'  => '➕'
    ],
    [
        'label' => 'All Bilties',
        'href'  => 'view_bilty.php',
        'match' => ['view_bilty.php', 'print_bulk.php'],
        'icon'  => '📄'
    ],
    [
        'label' => 'Manage Bills',
        'href'  => 'manage_bills.php',
        'match' => ['manage_bills.php'],
        'icon'  => '🧾'
    ],
    [
        'label' => 'Maintenance',
        'href'  => 'vehicle_maintenance.php',
        'match' => ['vehicle_maintenance.php'],
        'icon'  => '🛠️'
    ],
    [
        'label' => 'Reports',
        'href'  => 'reports.php',
        'match' => ['reports.php'],
        'icon'  => '📊'
    ],
    // Example of an optional extended reporting link if you split reports later:
    // [
    //     'label' => 'Detailed',
    //     'href'  => 'detailed_reports.php',
    //     'match' => ['detailed_reports.php'],
    //     'icon'  => '🔎'
    // ],
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
      <a href="index.php" class="flex items-center gap-3 group select-none">
        <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-xl font-semibold group-hover:bg-white/15 transition-colors duration-150">
          🚚
        </div>
        <div class="leading-tight">
          <div class="text-lg font-semibold">Bilty Management</div>
          <div class="text-[11px] opacity-90 tracking-wide font-medium">Clear. Fast. Reliable.</div>
        </div>
      </a>

      <!-- Desktop Nav -->
      <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
        <?php foreach ($navItems as $item):
            $active = isActiveNav($item, $current);
            $icon   = $item['icon'] ?? '';
        ?>
          <a
            href="<?php echo htmlspecialchars($item['href']); ?>"
            class="flex items-center gap-2 px-4 py-2 rounded transition-all duration-150 <?php echo $active
              ? 'bg-white/15 ring-2 ring-white/25 font-semibold shadow-inner'
              : 'hover:bg-white/10'; ?>"
            <?php if ($active): ?>aria-current="page"<?php endif; ?>
          >
            <?php if($icon): ?><span aria-hidden="true"><?php echo $icon; ?></span><?php endif; ?>
            <span><?php echo htmlspecialchars($item['label']); ?></span>
          </a>
        <?php endforeach; ?>
      </nav>

      <!-- (Optional) Right side user / actions placeholder -->
      <!--
      <div class="hidden md:flex items-center gap-3 text-sm">
        <span class="px-3 py-1 rounded bg-white/10">User</span>
        <a href="logout.php" class="px-3 py-1 rounded hover:bg-white/10">Logout</a>
      </div>
      -->

      <!-- Mobile Toggle -->
      <div class="md:hidden">
        <button id="navToggle"
                class="p-2 rounded hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40 active:scale-95 transition"
                aria-label="Toggle menu"
                aria-expanded="false"
                aria-controls="mobileMenu">
          ☰
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobileMenu"
       class="md:hidden hidden bg-primary/95 backdrop-blur-sm border-t border-white/10"
       style="background:rgba(151,17,58,0.95);">
    <div class="px-4 pb-4 pt-2 space-y-1">
      <?php foreach ($navItems as $item):
        $active = isActiveNav($item, $current);
        $icon   = $item['icon'] ?? '';
      ?>
        <a
          href="<?php echo htmlspecialchars($item['href']); ?>"
          class="flex items-center gap-2 px-3 py-2 rounded text-white/95 transition-colors duration-150 <?php echo $active
            ? 'bg-white/20 font-semibold shadow-inner'
            : 'hover:bg-white/10'; ?>"
          <?php if ($active): ?>aria-current="page"<?php endif; ?>
        >
          <?php if($icon): ?><span aria-hidden="true"><?php echo $icon; ?></span><?php endif; ?>
          <span><?php echo htmlspecialchars($item['label']); ?></span>
        </a>
      <?php endforeach; ?>

      <!-- (Optional) Add user / logout items for mobile here -->
      <!--
      <div class="mt-3 border-t border-white/10 pt-3">
        <a href="logout.php" class="block px-3 py-2 rounded hover:bg-white/10">Logout</a>
      </div>
      -->
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

      // Close menu when a link is clicked (mobile UX improvement)
      menu.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
          menu.classList.add('hidden');
          toggleBtn.setAttribute('aria-expanded', 'false');
        });
      });

      // Close on Escape
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !menu.classList.contains('hidden')) {
          menu.classList.add('hidden');
          toggleBtn.setAttribute('aria-expanded', 'false');
        }
      });
    });
  </script>
</header>