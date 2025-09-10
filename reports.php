<?php
require_once 'config.php';

// overall totals
$totals = [
    'count' => 0,
    'total_amount' => 0.00,
    'total_advance' => 0.00,
    'total_balance' => 0.00
];
$res = $conn->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total_amount, COALESCE(SUM(advance),0) AS total_advance, COALESCE(SUM(balance),0) AS total_balance FROM consignments");
if ($res) {
    $data = $res->fetch_assoc();
    if ($data) {
        $totals['count'] = $data['cnt'];
        $totals['total_amount'] = $data['total_amount'];
        $totals['total_advance'] = $data['total_advance'];
        $totals['total_balance'] = $data['total_balance'];
    }
    $res->free();
}

// totals by company
$by_company = [];
$res2 = $conn->query("SELECT cp.name AS company_name, COUNT(*) AS cnt, COALESCE(SUM(c.amount),0) AS total_amount, COALESCE(SUM(c.advance),0) AS total_advance, COALESCE(SUM(c.balance),0) AS total_balance
    FROM consignments c
    JOIN companies cp ON cp.id = c.company_id
    GROUP BY cp.id
    ORDER BY cp.name ASC");
if ($res2) {
    while ($r = $res2->fetch_assoc()) {
        $by_company[] = $r;
    }
    $res2->free();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Bilty Reports — Bilty Management</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />

  <script>
    tailwind = tailwind || {};
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#97113a',
            page: '#e4d7e8'
          }
        }
      }
    }
  </script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-page min-h-screen text-gray-800">
  <?php include 'header.php'; ?>

  <main class="max-w-5xl mx-auto py-10 px-4 md:px-6">
    <section class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-primary">Bilty Summary</h2>
        <a href="index.php" class="inline-block px-4 py-2 rounded bg-white border border-gray-200">Home</a>
      </div>

      <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <div class="p-4 bg-white border rounded shadow-sm">
          <div class="text-sm text-gray-500">Total Bilties</div>
          <div class="mt-2 text-2xl font-bold"><?php echo htmlspecialchars($totals['count']); ?></div>
        </div>

        <div class="p-4 bg-white border rounded shadow-sm">
          <div class="text-sm text-gray-500">Total Amount</div>
          <div class="mt-2 text-2xl font-bold"><?php echo number_format($totals['total_amount'], 2); ?></div>
        </div>

        <div class="p-4 bg-white border rounded shadow-sm">
          <div class="text-sm text-gray-500">Total Outstanding</div>
          <div class="mt-2 text-2xl font-bold"><?php echo number_format($totals['total_balance'], 2); ?></div>
        </div>
      </div>

      <h3 class="text-lg font-medium mb-3">By Company</h3>

      <?php if (empty($by_company)): ?>
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded text-yellow-800">No data available yet.</div>
      <?php else: ?>
        <div class="overflow-x-auto rounded">
          <table class="min-w-full table-auto divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Company</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Bilties</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Amount</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Advance</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Outstanding</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              <?php foreach ($by_company as $b): ?>
                <tr>
                  <td class="px-3 py-3 text-sm"><?php echo htmlspecialchars($b['company_name']); ?></td>
                  <td class="px-3 py-3 text-sm text-right"><?php echo htmlspecialchars($b['cnt']); ?></td>
                  <td class="px-3 py-3 text-sm text-right"><?php echo number_format($b['total_amount'], 2); ?></td>
                  <td class="px-3 py-3 text-sm text-right"><?php echo number_format($b['total_advance'], 2); ?></td>
                  <td class="px-3 py-3 text-sm text-right"><?php echo number_format($b['total_balance'], 2); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>