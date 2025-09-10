<?php
require_once 'config.php';

// Printable bilty template — custom layout matching provided mockup.
// Usage: view_bilty_print_custom.php?id=123

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "Invalid bilty id";
    exit;
}

// Fetch bilty
$stmt = $conn->prepare("
  SELECT c.*, cp.name AS company_name
  FROM consignments c
  JOIN companies cp ON cp.id = c.company_id
  WHERE c.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$bilty = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$bilty) {
    echo "Bilty not found";
    exit;
}

// Parse meta block (if present) and prepare notes/details cleanly
$owner = '';
$driver_number = '';
$raw_details = $bilty['details'] ?? '';

if (!empty($raw_details)) {
    // extract owner and driver number if present in meta block
    if (preg_match('/Vehicle:\s*(Own|Rental)/i', $raw_details, $m)) $owner = ucfirst(strtolower($m[1]));
    if (preg_match('/DriverNumber:\s*([0-9+\-\s()]+)/i', $raw_details, $m2)) $driver_number = trim($m2[1]);
    // we'll produce a cleaned details string below that removes meta and ignores "own/rental/additional info" lines
} else {
    $raw_details = '';
}

// Helper: clean details column — remove meta block and ignore lines like Vehicle:, DriverNumber:, own, rental, Additional info
function clean_details_text(string $details): string {
    if ($details === '') return '';

    // remove meta block entirely (case-insensitive, dot matches newline)
    $no_meta = preg_replace('/^---META---.*?---ENDMETA---\s*/is', '', $details);

    // Normalize line endings and split
    $lines = preg_split('/\r\n|\r|\n/', $no_meta);

    $keep = [];
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '') continue;

        // Ignore lines that are meta or irrelevant labels or just say 'own'/'rental' or 'additional info'
        $ignore_patterns = [
            '/^\s*vehicle\s*[:\-]/i',         // Vehicle: ...
            '/^\s*drivernumber\s*[:\-]/i',    // DriverNumber: ...
            '/^\s*driver\s*number\s*[:\-]/i', // Driver number: ...
            '/^\s*additional\s*info\s*[:\-]/i', // Additional info:
            '/^\s*(own|rental)\s*$/i',        // lines that are just "own" or "rental"
            '/^\s*vehicle\s*:\s*(own|rental)\s*$/i' // Vehicle: Own
        ];

        $skip = false;
        foreach ($ignore_patterns as $pat) {
            if (preg_match($pat, $trim)) { $skip = true; break; }
        }
        if ($skip) continue;

        // Also ignore lines that are just the words 'own', 'rental' mixed with punctuation
        if (preg_match('/^[\W_]*(own|rental)[\W_]*$/i', $trim)) continue;

        $keep[] = $trim;
    }

    // Join remaining lines preserving line breaks (we'll nl2br when rendering)
    $cleaned = trim(implode("\n", $keep));
    return $cleaned;
}

$notes = clean_details_text($raw_details);

// If driver_number wasn't found in meta, try to use dedicated column if present
if (empty($driver_number) && !empty($bilty['driver_number'] ?? '')) {
    $driver_number = trim($bilty['driver_number']);
}

// Try to locate a signature image for this bilty.
// Search common locations; fallback to nothing.
$signaturePaths = [
    __DIR__ . "/uploads/signatures/{$bilty['id']}.png",
    __DIR__ . "/uploads/signatures/{$bilty['id']}.jpg",
    __DIR__ . "/signatures/{$bilty['id']}.png",
    __DIR__ . "/assets/signature.png",
    __DIR__ . "/signature.png"
];
$signatureUrl = '';
foreach ($signaturePaths as $p) {
    if (file_exists($p)) {
        // create a web-accessible URL from file path if possible
        // best-effort: try to make it relative to DOCUMENT_ROOT
        $docRoot = rtrim(realpath($_SERVER['DOCUMENT_ROOT'] ?? ''), DIRECTORY_SEPARATOR);
        $realP = realpath($p);
        if ($docRoot && $realP && strpos($realP, $docRoot) === 0) {
            $rel = str_replace('\\', '/', substr($realP, strlen($docRoot)));
            $rel = preg_replace('#^/+?#', '', $rel);
            $signatureUrl = '/' . $rel;
        } else {
            // fallback to filename only (may work if the file is in same directory and served)
            $signatureUrl = basename($p);
        }
        break;
    }
}

// Formatters
function esc($s) { return htmlspecialchars((string)$s); }
function fmtAmount($v) { return number_format((float)$v, 2); }
function fmtNumber($v) { return is_numeric($v) ? (int)$v : $v; }
$dateDisplay = !empty($bilty['date']) ? date('d-M-Y', strtotime($bilty['date'])) : '';

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Print Bilty #<?php echo esc($bilty['bilty_no']); ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    /* A4 friendly */
    @page { size: A4; margin: 18mm; }
    body {
      font-family: "Helvetica Neue", Arial, sans-serif;
      color: #000; /* all primary text in pure black as requested */
      background: #fff;
      margin: 0;
      padding: 0;
    }

    .page {
      width: 100%;
      max-width: 800px;
      margin: 0 auto;
      padding: 18px;
      border: 1px solid #000; /* border in black for consistency */
      box-sizing: border-box;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
    }

    .brand {
      font-family: "Georgia", serif;
    }

    .brand .name {
      font-size: 30px;
      font-style: italic;
      font-weight: 700;
      color: #000; /* black text */
    }
    .brand .subtitle {
      font-size: 13px;
      color: #000; /* black text */
      margin-top: 2px;
    }
    .pro-info {
      font-size: 13px;
      color: #000; /* black text */
      margin-top: 8px;
      line-height: 1.1;
    }
    .pro-info .label { font-weight: 700; margin-right: 6px; color: #000; }
    .pro-info .cell {
      font-family: "Helvetica Neue", Arial, sans-serif; /* ensure consistent phone font */
      font-variant-numeric: tabular-nums; /* improves numeric alignment */
      font-weight: 700;
      letter-spacing: 0.2px;
      color: #000;
    }

    hr.sep {
      margin: 12px 0;
      border: none;
      border-top: 1px solid #000; /* black separator */
    }

    .box {
      border: 1px solid #000; /* black border */
      padding: 8px;
      box-sizing: border-box;
      background: #fff; /* keep white background for print clarity */
    }

    .bilty-top {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .bilty-field {
      display: flex;
      gap: 8px;
      align-items: center;
      font-size: 13px;
      color: #000;
    }
    .bilty-field .label { width: 90px; font-weight: 700; color:#000; }
    .bilty-field .value { background: #fff; padding: 4px 8px; border: 1px solid #000; min-width: 120px; text-align: left; color:#000; }

    .section-title {
      background: #fff; /* keep background white to obey "pure black" text requirement */
      padding: 6px 10px;
      border: 1px solid #000;
      font-weight: 700;
      font-size: 13px;
      color: #000;
    }

    .trip-detail {
      min-height: 36px;
      border: 1px solid #000;
      padding: 10px;
      font-size: 14px;
      background: #fff;
      color:#000;
    }

    table.items {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
    }
    table.items th, table.items td {
      border: 1px solid #000;
      padding: 8px;
      font-size: 13px;
      text-align: left;
      color: #000;
    }
    table.items th { background: #fff; font-weight: 700; font-size: 12px; color: #000; }
    table.items td.right { text-align: right; }

    .signature-area {
      margin-top: 24px;
      min-height: 90px;
      border-top: 1px dashed #000;
      padding-top: 8px;
      display:flex;
      align-items:flex-end;
      gap:18px;
    }
    .signature-line {
      width: 40%;
      border-bottom: 1px solid #000;
      margin-top: 8px;
    }
    .signature-image {
      max-width: 180px;
      max-height: 70px;
      object-fit: contain;
      filter: none; /* ensure image prints as-is */
    }

    .sign-label {
      font-weight:700;
      margin-top:6px;
      font-size:13px;
      color:#000;
    }

    .print-controls { display: none; } /* hidden on print; we'll show via JS when viewing */
    @media screen {
      .no-screen { display: none; }
      .print-controls { display:block; margin-bottom:10px; text-align:right; }
      .page { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    }

    /* Small tweaks for mobile */
    @media (max-width:600px) {
      .brand .name { font-size: 22px; }
      .page { padding: 12px; }
      .bilty-field .label { width: 70px; }
    }
  </style>
</head>
<body>
  <div class="print-controls no-print">
    <button onclick="window.print()">Print</button>
    <a href="view_bilty_details.php?id=<?php echo urlencode($bilty['id']); ?>">Back</a>
  </div>

  <div class="page" role="document" aria-label="Bilty document">
    <div class="header">
      <div class="brand">
        <div class="name">Bahar Ali</div>
        <div class="subtitle">Mini Goods Transport Hyderabad</div>
        <div class="pro-info">
          <div><span class="label">Pro:</span> <span class="cell">Bahar Ali</span></div>
          <div><span class="label">Cell:</span> <span class="cell"><?php echo esc($driver_number ?: ($bilty['driver_number'] ?? '0306-3591311')); ?></span></div>
          <div style="margin-top:6px; font-size:12px; color:#000;">
            Address: Hala Naka Road Near Isra University Hyderabad
          </div>
        </div>
      </div>

      <div style="text-align:right; min-width:220px;">
        <div style="font-size:14px; font-weight:700; color:#000;">Bilty</div>
        <div style="margin-top:6px;">
          <div class="bilty-field"><div class="label">Bilty NO</div><div class="value"><?php echo esc($bilty['bilty_no']); ?></div></div>
          <div class="bilty-field"><div class="label">Bilty Date</div><div class="value"><?php echo esc($dateDisplay ?: ''); ?></div></div>
        </div>
      </div>
    </div>

    <hr class="sep" />

    <div class="bilty-top" style="margin-top:8px;">
      <div class="box">
        <div class="section-title">Bilty To</div>
        <div style="padding:8px;">
          <div style="display:flex; gap:8px; margin-bottom:6px;"><strong style="width:90px;">Name</strong><div><?php echo esc($bilty['company_name'] ?? ''); ?></div></div>
          <div style="display:flex; gap:8px; margin-bottom:6px;"><strong style="width:90px;">Email</strong><div><?php echo esc($bilty['email'] ?? ''); ?></div></div>
          <div style="display:flex; gap:8px; margin-bottom:6px;"><strong style="width:90px;">Phone</strong><div><?php echo esc($bilty['driver_number'] ?? $driver_number); ?></div></div>
          <div style="display:flex; gap:8px;"><strong style="width:90px;">Address</strong><div><?php echo esc($bilty['from_city'] ?? ''); ?></div></div>
        </div>
      </div>

      <div class="box">
        <div class="section-title">Bilty Detail</div>
        <div style="padding:8px;">
          <div style="display:flex; gap:8px; margin-bottom:6px;"><strong style="width:100px;">Bilty No</strong><div><?php echo esc($bilty['bilty_no']); ?></div></div>
          <div style="display:flex; gap:8px; margin-bottom:6px;"><strong style="width:100px;">Trip Date</strong><div><?php echo esc($dateDisplay); ?></div></div>
          <div style="display:flex; gap:8px; margin-bottom:6px;"><strong style="width:100px;">Vehicle NO</strong><div><?php echo esc($bilty['vehicle_no']); ?></div></div>
          <div style="display:flex; gap:8px;"><strong style="width:100px;">Driver Name</strong><div><?php echo esc($bilty['driver_name'] ?: ''); ?></div></div>
        </div>
      </div>
    </div>

    <div style="margin-top:12px;">
      <div class="section-title">Trip Detail</div>
      <div class="trip-detail"><?php echo nl2br(esc($bilty['to_city'] ?? '')); ?></div>
    </div>

    <div style="margin-top:10px;">
      <table class="items" aria-describedby="items">
        <thead>
          <tr>
            <th style="width:55px;">Qty</th>
            <th style="width:100px;">Qty Details</th>
            <th>Details</th>
            <th style="width:70px; text-align:right;">KM</th>
            <th style="width:70px; text-align:right;">Rate</th>
            <th style="width:90px; text-align:right;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php
            // Render single item row using main consignments fields.
            $qty = fmtNumber($bilty['qty'] ?? 0);
            $qtyDetails = isset($bilty['qty_details']) ? esc($bilty['qty_details']) : 'CTN/Bags';

            // Prepare the "Details" column: strip meta and ignore known meta/labels (Vehicle, DriverNumber, Additional info, own/rental)
            $prodDetailsText = clean_details_text($bilty['details'] ?? '');

            // As fallback use a simple title if no detail text present
            if ($prodDetailsText === '') {
                $prodDetailsText = isset($bilty['details_title']) ? esc($bilty['details_title']) : '';
            } else {
                // already cleaned; escape when rendering but preserve newlines
                $prodDetailsText = $prodDetailsText;
            }

            $km = fmtNumber($bilty['km'] ?? 0);
            $rate = fmtAmount($bilty['rate'] ?? 0);
            $amount = fmtAmount($bilty['amount'] ?? 0);
          ?>
          <tr>
            <td><?php echo $qty; ?></td>
            <td><?php echo $qtyDetails; ?></td>
            <td><?php echo nl2br(esc($prodDetailsText)); ?></td>
            <td class="right"><?php echo $km; ?></td>
            <td class="right"><?php echo $rate; ?></td>
            <td class="right"><?php echo $amount; ?></td>
          </tr>

          <tr>
            <td colspan="6" style="height:18px; border:none;"></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="signature-area">
      <?php if ($signatureUrl): ?>
        <div>
          <img src="<?php echo esc($signatureUrl); ?>" alt="signature" class="signature-image" />
          <div class="sign-label">Sign of Transporter</div>
        </div>
      <?php else: ?>
        <div style="flex:1;">
          <div class="signature-line"></div>
          <div class="sign-label">Sign of Transporter</div>
        </div>
      <?php endif; ?>

      <div style="flex:1;"></div>
    </div>
  </div>

  <script>
    // when opened in a new window, auto-trigger print if query param auto=1 is present
    (function(){
      const url = new URL(window.location.href);
      if (url.searchParams.get('auto') === '1') {
        setTimeout(function(){ window.print(); }, 350);
      }
    })();
  </script>
</body>
</html>