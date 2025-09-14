<!doctype html>
<html lang="en">
<head>
  <?php include 'app/views/layout/head.php'; ?>
  <title><?php echo isset($title) ? htmlspecialchars($title) . ' — Bilty Management' : 'Bilty Management'; ?></title>
  <style>
    /* simple helper styles for selection UI */
    .select-col { width: 40px; text-align:center; }
    .bulk-actions { display:flex; gap:8px; align-items:center; }
    @media (max-width:768px) {
      .select-col { width: 34px; }
    }
  </style>
</head>
<body class="bg-page min-h-screen text-gray-800">
  <?php include 'app/views/layout/header.php'; ?>

  <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-6">
      <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-primary">Bilty Records</h1>
          <p class="text-sm text-gray-600">Select one or more bilties then print them together using the PDF template.</p>
        </div>

        <div class="flex items-center gap-3">
          <a href="/consignments/create" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-primary text-white">New Bilty</a>
          <a href="/consignments/export<?php echo !empty($filters['search']) ? '?q=' . urlencode($filters['search']) : ''; ?><?php echo !empty($filters['company_id']) ? (empty($filters['search']) ? '?' : '&') . 'company=' . intval($filters['company_id']) : ''; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border bg-white">Export CSV</a>
        </div>
      </header>

      <form id="filterForm" method="get" class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3 w-full md:max-w-md">
          <select id="company" name="company" class="block w-1/2 rounded-md border border-gray-200 px-3 py-2">
            <option value="0">— All companies —</option>
            <?php foreach ($companies as $c): ?>
              <option value="<?php echo intval($c['id']); ?>" <?php if ($filters['company_id'] == intval($c['id'])) echo 'selected'; ?>><?php echo htmlspecialchars($c['name']); ?></option>
            <?php endforeach; ?>
          </select>

          <input id="q" name="q" type="search" placeholder="Search bilty, company, driver, route..." value="<?php echo htmlspecialchars($filters['search']); ?>" class="block w-1/2 rounded-md border border-gray-200 px-3 py-2">
        </div>

        <div class="flex items-center gap-3">
          <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-primary text-white">Apply</button>
          <a href="/consignments" class="inline-flex items-center px-4 py-2 rounded-md border bg-white">Clear</a>
        </div>
      </form>

      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-500">Showing <strong><?php echo count($consignments); ?></strong> records</div>

        <div class="bulk-actions">
          <label class="text-sm text-gray-600 mr-2">Bulk actions:</label>
          <button id="printSelectedBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-primary text-white" type="button">Print Selected</button>
          <button id="downloadSelectedPdfBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border bg-white" type="button">Download PDF</button>
        </div>
      </div>

      <!-- Desktop Table -->
      <div class="hidden md:block bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="select-col px-4 py-3"><input id="selectAll" type="checkbox" aria-label="Select all"></th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">#</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Bilty No</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Company</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Route</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Vehicle No</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Owner</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Driver</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">KM</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Rate</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Amount</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Advance</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Balance</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
            </tr>
          </thead>

          <tbody id="biltiesTableBody" class="bg-white divide-y divide-gray-100">
            <?php foreach ($consignments as $r):
                $owner = '';
                $driver_number = '';
                if (!empty($r['details'])) {
                    if (preg_match('/Vehicle:\s*(Own|Rental)/i', $r['details'], $m)) $owner = ucfirst(strtolower($m[1]));
                    if (preg_match('/Driver number:\s*([0-9+\-\s()]+)/i', $r['details'], $m2)) $driver_number = trim($m2[1]);
                }
                $balance = (float)($r['balance'] ?? 0);
            ?>
              <tr class="<?php echo $balance > 0 ? 'bg-gray-50' : ''; ?>">
                <td class="select-col px-4 py-3 text-sm text-gray-700">
                  <input class="row-checkbox" type="checkbox" value="<?php echo intval($r['id']); ?>" aria-label="Select bilty <?php echo htmlspecialchars($r['bilty_no']); ?>">
                </td>
                <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($r['id']); ?></td>
                <td class="px-4 py-3 text-sm font-medium text-primary"><?php echo htmlspecialchars($r['bilty_no']); ?></td>
                <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($r['date']); ?></td>
                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($r['company_name'] ?? ''); ?></td>
                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($r['from_city'] ?? '') . ' → ' . htmlspecialchars($r['to_city'] ?? ''); ?></td>
                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($r['vehicle_no'] ?? ''); ?></td>
                <td class="px-4 py-3 text-sm">
                  <?php if ($owner === 'Rental'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800">Rental</span>
                  <?php elseif ($owner === 'Own'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">Own</span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">—</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-sm">
                  <div><?php echo htmlspecialchars($r['driver_name'] ?? ''); ?></div>
                  <?php if ($driver_number): ?><div class="text-xs text-gray-500"><?php echo htmlspecialchars($driver_number); ?></div><?php endif; ?>
                </td>

                <!-- KM -->
                <td class="px-4 py-3 text-sm text-right text-gray-700"><?php echo number_format((float)($r['km'] ?? 0)); ?></td>
                <!-- Rate -->
                <td class="px-4 py-3 text-sm text-right text-gray-700"><?php echo number_format((float)($r['rate'] ?? 0), 2); ?></td>

                <!-- Amount, Advance, Balance -->
                <td class="px-4 py-3 text-sm text-right font-medium"><?php echo number_format((float)($r['amount'] ?? 0), 2); ?></td>
                <td class="px-4 py-3 text-sm text-right"><?php echo number_format((float)($r['advance'] ?? 0), 2); ?></td>
                <td class="px-4 py-3 text-sm text-right <?php echo $balance > 0 ? 'text-red-600 font-bold' : ''; ?>"><?php echo number_format($balance, 2); ?></td>

                <td class="px-4 py-3 text-sm text-right">
                  <a href="/consignments/<?php echo urlencode($r['id']); ?>" class="inline-flex items-center px-3 py-1.5 rounded-md bg-primary text-white text-sm">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile cards (selection + actions) -->
      <div class="md:hidden space-y-4">
        <?php foreach ($consignments as $r):
           $owner = '';
           $driver_number = '';
           if (!empty($r['details'])) {
               if (preg_match('/Vehicle:\s*(Own|Rental)/i', $r['details'], $m)) $owner = ucfirst(strtolower($m[1]));
               if (preg_match('/Driver number:\s*([0-9+\-\s()]+)/i', $r['details'], $m2)) $driver_number = trim($m2[1]);
           }
           $balance = (float)($r['balance'] ?? 0);
        ?>
          <article class="bg-white shadow rounded-lg p-4 <?php echo $balance > 0 ? 'border border-gray-200' : ''; ?>">
            <div class="flex items-start justify-between">
              <div>
                <div class="flex items-center gap-2">
                  <input class="row-checkbox" type="checkbox" value="<?php echo intval($r['id']); ?>">
                  <div class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($r['bilty_no']); ?></div>
                </div>
                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($r['date']); ?> • <?php echo htmlspecialchars($r['company_name'] ?? ''); ?></div>
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-500">Amount</div>
                <div class="text-lg font-medium"><?php echo number_format((float)($r['amount'] ?? 0), 2); ?></div>
                <div class="mt-2 flex gap-2 justify-end">
                  <a href="/consignments/<?php echo urlencode($r['id']); ?>" class="inline-flex items-center px-3 py-1.5 rounded-md bg-primary text-white text-sm">View</a>
                </div>
              </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-sm text-gray-700">
              <div><span class="font-medium">Route:</span> <?php echo htmlspecialchars($r['from_city'] ?? '') . ' → ' . htmlspecialchars($r['to_city'] ?? ''); ?></div>
              <div><span class="font-medium">Vehicle:</span> <?php echo htmlspecialchars($r['vehicle_no'] ?? ''); ?></div>
              <div><span class="font-medium">Driver:</span> <?php echo htmlspecialchars($r['driver_name'] ?? ''); ?></div>
              <div><span class="font-medium">Owner:</span> <?php echo $owner ?: '—'; ?></div>
              <div><span class="font-medium">KM:</span> <?php echo number_format((float)($r['km'] ?? 0)); ?></div>
              <div><span class="font-medium">Rate:</span> <?php echo number_format((float)($r['rate'] ?? 0), 2); ?></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </main>

  <script>
    // Selection UI and bulk print/download
    (function(){
      const selectAll = document.getElementById('selectAll');
      const checkboxes = () => Array.from(document.querySelectorAll('.row-checkbox'));
      const getSelectedIds = () => checkboxes().filter(cb => cb.checked).map(cb => cb.value);

      if (selectAll) {
        selectAll.addEventListener('change', function(){
          checkboxes().forEach(cb => cb.checked = this.checked);
        });
      }

      // Keep selectAll in sync if any row checkbox toggled
      document.addEventListener('change', function(e){
        if (!e.target.matches('.row-checkbox')) return;
        const all = checkboxes();
        if (all.length === 0) return;
        const allChecked = all.every(cb => cb.checked);
        if (selectAll) selectAll.checked = allChecked;
      });

      const openInNewWindow = (url) => {
        const w = window.open(url, '_blank', 'noopener');
        if (w) w.focus();
      };

      // Print Selected: open printable HTML in new window
      const printBtn = document.getElementById('printSelectedBtn');
      if (printBtn) {
        printBtn.addEventListener('click', function(){
          const ids = getSelectedIds();
          if (ids.length === 0) { alert('Please select at least one bilty to print.'); return; }
          const url = '/consignments/bulk?ids=' + encodeURIComponent(ids.join(','));
          openInNewWindow(url + '&auto=1');
        });
      }

      // Download PDF: hits a server endpoint
      const downloadBtn = document.getElementById('downloadSelectedPdfBtn');
      if (downloadBtn) {
        downloadBtn.addEventListener('click', function(){
          const ids = getSelectedIds();
          if (ids.length === 0) { alert('Please select at least one bilty to download.'); return; }
          const url = '/consignments/bulk?ids=' + encodeURIComponent(ids.join(','));
          openInNewWindow(url + '&auto=1');
        });
      }
    })();
  </script>
</body>
</html>