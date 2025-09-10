<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header('Location: view_bilty.php'); exit; }

// Fetch single bilty
$stmt = $conn->prepare("SELECT c.*, cp.name AS company_name FROM consignments c JOIN companies cp ON cp.id = c.company_id WHERE c.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$bilty = $res ? $res->fetch_assoc() : null;
$stmt->close();
if (!$bilty) { header('Location: view_bilty.php'); exit; }

// extract meta
$owner = '';
$driver_number = '';
if (!empty($bilty['details'])) {
    if (preg_match('/Vehicle:\s*(Own|Rental)/i', $bilty['details'], $m)) $owner = ucfirst(strtolower($m[1]));
    if (preg_match('/DriverNumber:\s*([0-9+\-\s()]+)/i', $bilty['details'], $m2)) $driver_number = trim($m2[1]);
    $notes = preg_replace('/^---META---.*?---ENDMETA---\s*/s', '', $bilty['details']);
} else $notes = '';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'head.php'; ?>
  <title>Bilty #<?php echo htmlspecialchars($bilty['bilty_no']); ?> — Bilty Management</title>
  <style>
    /* print-friendly adjustments for the print preview page when opened */
    @media print {
      body { background: #fff; color: #000; }
      .no-print { display: none !important; }
      .print-box { box-shadow: none !important; border: none !important; }
    }
  </style>
</head>
<body class="bg-page min-h-screen text-gray-800">
  <?php include 'header.php'; ?>

  <main class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-2xl shadow-lg p-6 print-box">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-primary">Bilty #<?php echo htmlspecialchars($bilty['bilty_no']); ?></h1>
          <p class="text-sm text-gray-600"><?php echo htmlspecialchars($bilty['company_name'] ?? ''); ?> — <?php echo htmlspecialchars($bilty['date']); ?></p>
        </div>
        <div class="flex items-center gap-2 no-print">
          <a href="view_bilty.php" class="px-3 py-2 rounded-md border bg-white hover:bg-gray-50">Back</a>
          <a href="add_bilty.php?edit=<?php echo urlencode($bilty['id']); ?>" class="px-3 py-2 rounded-md bg-primary text-white">Edit</a>
          <button id="printBtn" class="px-3 py-2 rounded-md border bg-white">Print</button>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-3">
          <div>
            <div class="text-xs text-gray-500">Route</div>
            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['from_city'] ?? ''); ?> → <?php echo htmlspecialchars($bilty['to_city'] ?? ''); ?></div>
          </div>

          <div>
            <div class="text-xs text-gray-500">Vehicle</div>
            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['vehicle_no'] ?? ''); ?></div>
            <?php if (!empty($bilty['vehicle_type'])): ?><div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($bilty['vehicle_type']); ?></div><?php endif; ?>
            <div class="mt-2">
              <span class="text-xs text-gray-500">Ownership</span>
              <?php if ($owner === 'Rental'): ?>
                <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800">Rental</div>
              <?php elseif ($owner === 'Own'): ?>
                <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">Own</div>
              <?php else: ?>
                <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">—</div>
              <?php endif; ?>
            </div>
          </div>

          <div>
            <div class="text-xs text-gray-500">Driver</div>
            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['driver_name'] ?? ''); ?></div>
            <?php if ($driver_number): ?><div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($driver_number); ?></div><?php endif; ?>
          </div>

          <div>
            <div class="text-xs text-gray-500">Sender</div>
            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['sender_name'] ?? ''); ?></div>
          </div>
        </div>

        <div class="space-y-3">
          <div>
            <div class="text-xs text-gray-500">Quantity</div>
            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['qty'] ?? 0); ?></div>
          </div>

          <div>
            <div class="text-xs text-gray-500">Distance (KM)</div>
            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['km'] ?? 0); ?></div>
          </div>

          <div>
            <div class="text-xs text-gray-500">Rate (per KM)</div>
            <div class="font-medium text-gray-800"><?php echo number_format((float)($bilty['rate'] ?? 0), 2); ?></div>
          </div>

          <div>
            <div class="text-xs text-gray-500">Amount</div>
            <div class="text-lg font-bold text-gray-900"><?php echo number_format((float)($bilty['amount'] ?? 0), 2); ?></div>
            <div class="text-sm text-gray-600 mt-1">Advance: <?php echo number_format((float)($bilty['advance'] ?? 0), 2); ?> • Balance: <span class="text-red-600"><?php echo number_format((float)($bilty['balance'] ?? 0), 2); ?></span></div>
          </div>
        </div>
      </div>

      <?php if (!empty($notes)): ?>
        <div class="mt-6">
          <div class="text-xs text-gray-500">Notes</div>
          <div class="mt-2 whitespace-pre-line text-gray-700"><?php echo htmlspecialchars($notes); ?></div>
        </div>
      <?php endif; ?>

      <div class="mt-6 flex justify-end gap-2 no-print">
        <a href="view_bilty.php" class="px-4 py-2 rounded-md border bg-white">Close</a>
        <a href="add_bilty.php?edit=<?php echo urlencode($bilty['id']); ?>" class="px-4 py-2 rounded-md bg-primary text-white">Edit Bilty</a>
        <button id="printBtn2" class="px-4 py-2 rounded-md border bg-white">Print (Template)</button>
      </div>
    </div>
  </main>

  <script>
    // Open printable template in a new window
    document.getElementById('printBtn').addEventListener('click', function(){
      const url = 'view_bilty_print.php?id=<?php echo urlencode($bilty['id']); ?>';
      const w = window.open(url, '_blank', 'noopener');
      if (w) w.focus();
    });
    document.getElementById('printBtn2').addEventListener('click', function(){
      const url = 'view_bilty_print.php?id=<?php echo urlencode($bilty['id']); ?>&template=1';
      const w = window.open(url, '_blank', 'noopener');
      if (w) w.focus();
    });
  </script>
</body>
</html>