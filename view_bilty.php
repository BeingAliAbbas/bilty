<?php
require_once 'config.php';

// Read filter/search params (GET)
$q = trim($_GET['q'] ?? '');
$company_filter = isset($_GET['company']) ? intval($_GET['company']) : 0;

// Build WHERE clause
$where = [];
if ($company_filter > 0) {
    $where[] = "c.company_id = " . intval($company_filter);
}
if ($q !== '') {
    $esc = $conn->real_escape_string($q);
    $where[] = "(
        c.bilty_no LIKE '%{$esc}%'
        OR cp.name LIKE '%{$esc}%'
        OR c.driver_name LIKE '%{$esc}%'
        OR c.from_city LIKE '%{$esc}%'
        OR c.to_city LIKE '%{$esc}%'
        OR c.vehicle_no LIKE '%{$esc}%'
    )";
}
$where_sql = '';
if (!empty($where)) $where_sql = 'WHERE ' . implode(' AND ', $where);

// Fetch companies for company filter dropdown
$companies = [];
$cres = $conn->query("SELECT id, name FROM companies ORDER BY name ASC");
if ($cres) {
    while ($crow = $cres->fetch_assoc()) $companies[] = $crow;
    $cres->free();
}

// Handle CSV export (keeps meta/notes stripped) with applied filters
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $qsql = "SELECT c.*, cp.name AS company_name FROM consignments c JOIN companies cp ON cp.id = c.company_id {$where_sql} ORDER BY c.date DESC, c.id DESC";
    $res = $conn->query($qsql);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=bilties.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Bilty No','Date','Company','From','To','Vehicle No','Vehicle Type','Vehicle Owner','Driver','Driver Number','Qty','KM','Rate','Amount','Advance','Balance','Notes']);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            // extract meta if present
            $owner = '';
            $driver_number = '';
            $notes = '';
            if (!empty($r['details'])) {
                if (preg_match('/Vehicle:\s*(Own|Rental)/i', $r['details'], $m)) $owner = ucfirst(strtolower($m[1]));
                if (preg_match('/DriverNumber:\s*([0-9+\-\s()]+)/i', $r['details'], $m2)) $driver_number = trim($m2[1]);
                // strip meta block for notes
                $notes = preg_replace('/^---META---.*?---ENDMETA---\s*/is', '', $r['details']);
                $notes = str_replace(["\r","\n"], [' ',' '], trim($notes));
            }
            fputcsv($out, [
                $r['id'] ?? '',
                $r['bilty_no'] ?? '',
                $r['date'] ?? '',
                $r['company_name'] ?? '',
                $r['from_city'] ?? '',
                $r['to_city'] ?? '',
                $r['vehicle_no'] ?? '',
                $r['vehicle_type'] ?? '',
                $owner,
                $driver_number,
                $r['qty'] ?? '',
                $r['km'] ?? '',
                $r['rate'] ?? '',
                $r['amount'] ?? '',
                $r['advance'] ?? '',
                $r['balance'] ?? '',
                $notes
            ]);
        }
        $res->free();
    }
    fclose($out);
    exit;
}

// Fetch rows with applied filters
$sql = "SELECT c.*, cp.name AS company_name FROM consignments c JOIN companies cp ON cp.id = c.company_id {$where_sql} ORDER BY c.date DESC, c.id DESC";
$res = $conn->query($sql);
$rows = [];
if ($res) {
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $res->free();
}
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'head.php'; ?>
  <title>Bilties — Bilty Management</title>
  <style>
    /* small helper styles for selection UI */
    .select-col { width: 40px; text-align:center; }
    .bulk-actions { display:flex; gap:8px; align-items:center; }
    @media (max-width:768px) {
      .select-col { width: 34px; }
    }
  </style>
</head>
<body class="bg-page min-h-screen text-gray-800">
  <?php include 'header.php'; ?>

  <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-6">
      <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-primary">Bilty Records</h1>
          <p class="text-sm text-gray-600">Select one or more bilties then print them together using the PDF template.</p>
        </div>

        <div class="flex items-center gap-3">
          <a href="add_bilty.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-primary text-white">New Bilty</a>
          <a href="?export=csv<?php echo $q !== '' ? '&q=' . urlencode($q) : ''; ?><?php echo $company_filter ? '&company=' . intval($company_filter) : ''; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border bg-white">Export CSV</a>
        </div>
      </header>

      <form id="filterForm" method="get" class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3 w-full md:max-w-md">
          <select id="company" name="company" class="block w-1/2 rounded-md border border-gray-200 px-3 py-2">
            <option value="0">— All companies —</option>
            <?php foreach ($companies as $c): ?>
              <option value="<?php echo intval($c['id']); ?>" <?php if ($company_filter == intval($c['id'])) echo 'selected'; ?>><?php echo htmlspecialchars($c['name']); ?></option>
            <?php endforeach; ?>
          </select>

          <input id="q" name="q" type="search" placeholder="Search bilty, company, driver, route..." value="<?php echo htmlspecialchars($q); ?>" class="block w-1/2 rounded-md border border-gray-200 px-3 py-2">
        </div>

        <div class="flex items-center gap-3">
          <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-primary text-white">Apply</button>
          <a href="view_bilty.php" class="inline-flex items-center px-4 py-2 rounded-md border bg-white">Clear</a>
        </div>
      </form>

      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-500">Showing <strong><?php echo count($rows); ?></strong> records</div>

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
            <?php foreach ($rows as $r):
                $owner = '';
                $driver_number = '';
                if (!empty($r['details'])) {
                    if (preg_match('/Vehicle:\s*(Own|Rental)/i', $r['details'], $m)) $owner = ucfirst(strtolower($m[1]));
                    if (preg_match('/DriverNumber:\s*([0-9+\-\s()]+)/i', $r['details'], $m2)) $driver_number = trim($m2[1]);
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
                  <a href="view_bilty_details.php?id=<?php echo urlencode($r['id']); ?>" class="inline-flex items-center px-3 py-1.5 rounded-md bg-primary text-white text-sm">View</a>
                  <!-- <a href="view_bilty_print_custom.php?id=<?php echo urlencode($r['id']); ?>&auto=1" class="inline-flex items-center px-3 py-1.5 rounded-md border bg-white text-sm ml-2" target="_blank">Print</a> -->
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile cards (selection + actions) -->
      <div class="md:hidden space-y-4">
        <?php foreach ($rows as $r):
           $owner = '';
           $driver_number = '';
           if (!empty($r['details'])) {
               if (preg_match('/Vehicle:\s*(Own|Rental)/i', $r['details'], $m)) $owner = ucfirst(strtolower($m[1]));
               if (preg_match('/DriverNumber:\s*([0-9+\-\s()]+)/i', $r['details'], $m2)) $driver_number = trim($m2[1]);
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
                  <a href="view_bilty_details.php?id=<?php echo urlencode($r['id']); ?>" class="inline-flex items-center px-3 py-1.5 rounded-md bg-primary text-white text-sm">View</a>
                  <a href="view_bilty_print_custom.php?id=<?php echo urlencode($r['id']); ?>&auto=1" class="inline-flex items-center px-3 py-1.5 rounded-md border bg-white text-sm" target="_blank">Print</a>
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

      // Print Selected: open printable HTML (print_bulk.php) in new window (rendered for printing)
      const printBtn = document.getElementById('printSelectedBtn');
      if (printBtn) {
        printBtn.addEventListener('click', function(){
          const ids = getSelectedIds();
          if (ids.length === 0) { alert('Please select at least one bilty to print.'); return; }
          // open printable page (auto open print dialog)
          const url = 'print_bulk.php?ids=' + encodeURIComponent(ids.join(','));
          openInNewWindow(url + '&auto=1');
        });
      }

      // Download PDF: hits a server endpoint that could generate a PDF (if implemented),
      // fallback: open printable page and user can print to PDF manually.
      const downloadBtn = document.getElementById('downloadSelectedPdfBtn');
      if (downloadBtn) {
        downloadBtn.addEventListener('click', function(){
          const ids = getSelectedIds();
          if (ids.length === 0) { alert('Please select at least one bilty to download.'); return; }
          // If you have server-side PDF generation route (e.g. generate_bulk_pdf.php), change URL below.
          const url = 'print_bulk.php?ids=' + encodeURIComponent(ids.join(','));
          openInNewWindow(url + '&auto=1');
        });
      }
    })();
  </script>
</body>
</html>
