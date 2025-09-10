<?php
// header.php - shared top navigation
// Keep this include inside <body> (after head.php
?>
<head>
  <?php include 'head.php'; ?>

</head>
<header class="bg-primary text-white" style="background:var(--primary);">
  <div class="max-w-6xl mx-auto px-4 md:px-6">
    <div class="flex items-center justify-between h-16">
      <a href="index.php" class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-xl font-semibold">
          🚚
        </div>
        <div>
          <div class="text-lg font-semibold">Bilty Management</div>
          <div class="text-xs opacity-90">Clear. Fast. Reliable.</div>
        </div>
      </a>

      <nav class="hidden md:flex items-center gap-4 text-sm">
        <a href="index.php" class="px-4 py-2 rounded hover:bg-white/10 <?php if(basename($_SERVER['PHP_SELF'])=='index.php') echo 'ring-2 ring-white/25'; ?>">Home</a>
        <a href="add_bilty.php" class="px-4 py-2 rounded hover:bg-white/10">New Bilty</a>
        <a href="view_bilty.php" class="px-4 py-2 rounded hover:bg-white/10">All Bilties</a>
        <a href="reports.php" class="px-4 py-2 rounded hover:bg-white/10">Reports</a>
      </nav>

      <div class="md:hidden">
        <button id="navToggle" class="p-2 rounded hover:bg-white/10" aria-label="Toggle menu">☰</button>
      </div>
    </div>
  </div>

  <!-- mobile menu -->
  <div id="mobileMenu" class="md:hidden hidden bg-primary/95" style="background:rgba(151,17,58,0.95);">
    <div class="px-4 pb-4">
      <a href="index.php" class="block px-3 py-2 rounded text-white/95 hover:bg-white/10">Home</a>
      <a href="add_bilty.php" class="block px-3 py-2 rounded text-white/95 hover:bg-white/10">New Bilty</a>
      <a href="view_bilty.php" class="block px-3 py-2 rounded text-white/95 hover:bg-white/10">All Bilties</a>
      <a href="reports.php" class="block px-3 py-2 rounded text-white/95 hover:bg-white/10">Reports</a>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function(){
      var t = document.getElementById('navToggle'), m = document.getElementById('mobileMenu');
      if (t) t.addEventListener('click', function(){ m.classList.toggle('hidden'); });
    });
  </script>
</header>