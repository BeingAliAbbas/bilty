<?php
require_once 'config.php'; // database connection

// Helper: safe dynamic bind for mysqli_stmt using automatic type detection
function mysqli_stmt_bind_dynamic(mysqli_stmt $stmt, array $params) {
    if (empty($params)) return true;
    $types = '';
    foreach ($params as $index => &$v) {
        if ($v === null) { $v = ''; $types .= 's'; continue; }
        if (is_int($v)) { $types .= 'i'; continue; }
        if (is_float($v)) { $types .= 'd'; continue; }

        $sv = (string)$v;
        if (preg_match('/^-?\d+$/', $sv)) { $v = (int)$sv; $types .= 'i'; continue; }
        if (is_numeric($sv) && preg_match('/[.eE]/', $sv)) { $v = (float)$sv; $types .= 'd'; continue; }

        $v = $sv;
        $types .= 's';
    }
    $refs = [];
    $refs[] = & $types;
    foreach ($params as $k => &$val) { $refs[] = & $val; }
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

// Get next bilty number (based on MAX(id) + 1)
function get_next_bilty_no($conn) {
    $next = 1;
    $res = $conn->query("SELECT MAX(id) AS maxid FROM consignments");
    if ($res) {
        $row = $res->fetch_assoc();
        $res->free();
        $next = (int)($row['maxid'] ?? 0) + 1;
    }
    return (string)$next;
}

$errors = [];
$success = '';
$auto_bilty_no = get_next_bilty_no($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize input
    $bilty_no      = trim($_POST['bilty_no'] ?? $auto_bilty_no);
    $date          = $_POST['date'] ?? date('Y-m-d');
    $company_id    = intval($_POST['company'] ?? 0);
    $vehicle_no    = trim($_POST['vehicle_no'] ?? '');
    $vehicle_owner = (($_POST['vehicle_owner'] ?? 'own') === 'rental') ? 'rental' : 'own';
    $driver_name   = trim($_POST['driver_name'] ?? '');
    $driver_number = trim($_POST['driver_number'] ?? '');
    $vehicle_type  = trim($_POST['vehicle_type'] ?? '');
    $sender_name   = trim($_POST['sender_name'] ?? '');
    $from_city     = trim($_POST['from_city'] ?? '');
    $to_city       = trim($_POST['to_city'] ?? '');
    $qty           = intval($_POST['qty'] ?? 0);
    $details       = trim($_POST['details'] ?? '');
    $km            = intval($_POST['km'] ?? 0);
    $rate          = floatval($_POST['rate'] ?? 0);

    // calculate server-side: Amount = km * rate
    $amount  = round($km * $rate, 2);
    $advance = floatval($_POST['advance'] ?? 0);
    $balance = round($amount - $advance, 2);

    // validations
    if ($bilty_no === '') { $errors[] = "Bilty number is required."; }
    elseif (strlen($bilty_no) > 50) { $errors[] = "Bilty number must be 50 characters or less."; }

    if ($company_id <= 0) { $errors[] = "Please select a company."; }
    if ($qty < 0) { $errors[] = "Quantity cannot be negative."; }
    if ($km < 0) { $errors[] = "Distance (KM) cannot be negative."; }
    if ($rate < 0) { $errors[] = "Rate cannot be negative."; }
    if ($advance < 0) { $errors[] = "Advance cannot be negative."; }

    // extra info
    $extra = [];
    $extra[] = "Vehicle: " . ($vehicle_owner === 'rental' ? "Rental" : "Own");
    if ($driver_number !== '') {
        $extra[] = "Driver number: " . $driver_number;
    }
    if (!empty($extra)) {
        $details = trim($details);
        if ($details !== '') $details .= "\n\n";
        $details .= "Additional info:\n" . implode("\n", $extra);
    }

    // save if no errors
    if (empty($errors)) {
        $sql = "INSERT INTO consignments
            (company_id, bilty_no, date, vehicle_no, driver_name, vehicle_type, sender_name, from_city, to_city, qty, details, km, rate, amount, advance, balance)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $attempts = 0;
        $maxAttempts = 3;
        $inserted = false;

        while ($attempts < $maxAttempts && !$inserted) {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $errors[] = "Database prepare error: " . $conn->error;
                break;
            }

            $params = [
                $company_id,
                $bilty_no,
                $date,
                $vehicle_no,
                $driver_name,
                $vehicle_type,
                $sender_name,
                $from_city,
                $to_city,
                $qty,
                $details,
                $km,
                $rate,
                $amount,
                $advance,
                $balance
            ];

            if (!mysqli_stmt_bind_dynamic($stmt, $params)) {
                $errors[] = "Failed to bind parameters.";
                $stmt->close();
                break;
            }

            if ($stmt->execute()) {
                $inserted = true;
                $success = "Bilty has been saved successfully.";
                $_POST = [];
                $auto_bilty_no = get_next_bilty_no($conn);
            } else {
                if ($conn->errno === 1062) { // duplicate bilty_no
                    $attempts++;
                    $bilty_no = get_next_bilty_no($conn);
                    $stmt->close();
                    continue;
                } else {
                    $errors[] = "Database error: " . $conn->error;
                    $stmt->close();
                    break;
                }
            }
            $stmt->close();
        }

        if (!$inserted && empty($errors)) {
            $errors[] = "Failed to save bilty after multiple attempts. Please try again.";
        }
    }
}

// fetch companies (with address if column exists)
$hasAddress = false;
$colCheck = $conn->query("SHOW COLUMNS FROM companies LIKE 'address'");
if ($colCheck && $colCheck->num_rows > 0) $hasAddress = true;
if ($colCheck) $colCheck->close();

$companies = [];
if ($hasAddress) {
    $res = $conn->query("SELECT id, name, address FROM companies ORDER BY name ASC");
} else {
    $res = $conn->query("SELECT id, name FROM companies ORDER BY name ASC");
}
if ($res) {
    while ($row = $res->fetch_assoc()) { $companies[] = $row; }
    $res->free();
}

// ensure displayed bilty_no is fresh when not posting
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $auto_bilty_no = get_next_bilty_no($conn); }
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'head.php'; ?>
  <title>Add Bilty — Bilty Management</title>
  <style>
    /* Simple modal styling for Add Company */
    .modal-backdrop {
      position:fixed; inset:0;
      background:rgba(0,0,0,.55);
      display:none; align-items:center; justify-content:center;
      z-index:5000;
    }
    .modal-panel {
      background:#fff;
      width:500px; max-width:92%;
      border-radius:18px;
      padding:26px 28px 24px;
      position:relative;
      box-shadow:0 18px 60px -8px rgba(0,0,0,.35);
      animation: popIn .3s cubic-bezier(.18,.89,.32,1.28);
    }
    @keyframes popIn {
      0% { transform:scale(.9) translateY(10px); opacity:0; }
      100% { transform:scale(1) translateY(0); opacity:1; }
    }
    .modal-panel h3 { margin:0 0 8px; font-size:20px; font-weight:700; }
    .modal-close {
      position:absolute; top:10px; right:10px;
      background:#f1f5f9; border:1px solid #e2e8f0;
      width:36px; height:36px; border-radius:10px;
      display:flex; align-items:center; justify-content:center;
      font-weight:600; cursor:pointer;
    }
    .modal-close:hover { background:#e2e8f0; }
    .field label {
      font-size:12px; font-weight:600; letter-spacing:.5px;
      text-transform:uppercase; display:block; margin-bottom:4px; color:#475569;
    }
    .field input, .field textarea {
      width:100%; border:1px solid #cbd5e1; border-radius:10px;
      padding:10px 12px; font-size:14px; outline:none;
      transition:border .15s, box-shadow .15s;
      background:#fff;
    }
    .field input:focus, .field textarea:focus {
      border-color: var(--primary,#97113a);
      box-shadow:0 0 0 2px rgba(151,17,58,.25);
    }
    .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:10px; }
    .btn-small {
      background:#97113a; color:#fff; border:1px solid #97113a;
      padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600;
      cursor:pointer; display:inline-flex; align-items:center; gap:6px;
    }
    .btn-small.secondary {
      background:#fff; color:#1e293b; border:1px solid #d0d7e2;
    }
    .btn-small:hover { filter:brightness(1.05); }
    .hint { font-size:11px; color:#64748b; margin-top:4px; }
    .company-address-badge {
      display:inline-block; margin-left:6px; font-size:11px; background:#f1f5f9;
      padding:2px 6px; border-radius:6px; color:#475569; border:1px solid #e2e8f0;
    }
  </style>
</head>
<body class="bg-page min-h-screen text-gray-800">
  <?php include 'header.php'; ?>

  <main class="max-w-5xl mx-auto p-6">
    <section class="bg-white rounded-2xl shadow-xl p-8">
      <div class="flex items-start justify-between gap-6 mb-6">
        <div>
          <h1 class="text-3xl font-extrabold text-primary mb-1">Add New Bilty</h1>
          <p class="text-sm text-gray-600">Enter bilty details below. Required fields are marked with *</p>
        </div>
        <div class="flex items-center gap-3">
          <a href="view_bilty.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border bg-white hover:bg-gray-50">View All Bilties</a>
          <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-white bg-primary hover:opacity-95">Home</a>
        </div>
      </div>

      <?php if ($success): ?>
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-green-800">
          <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-red-800">
          <ul class="list-disc list-inside space-y-1">
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" id="biltyForm" class="space-y-6" novalidate>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="bilty_no" class="block text-sm font-medium text-gray-700">Bilty number <span class="text-red-600">*</span></label>
            <input id="bilty_no" name="bilty_no" type="text" maxlength="50" required
              value="<?php echo htmlspecialchars($_POST['bilty_no'] ?? $auto_bilty_no); ?>"
              readonly class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2 bg-gray-50 shadow-sm" placeholder="Auto generated bilty number">
            <p class="text-xs text-gray-500 mt-1">Auto-generated from the system.</p>
          </div>

          <div>
            <label for="date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-600">*</span></label>
            <input id="date" name="date" type="date" required
              value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d')); ?>"
              class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2">
          </div>

          <div class="md:col-span-2">
            <label for="company" class="block text-sm font-medium text-gray-700">Company <span class="text-red-600">*</span></label>
            <div class="flex gap-3 items-start mt-1">
              <select id="company" name="company" required class="flex-1 rounded-md border border-gray-200 px-3 py-2">
                <option value="">— Select company —</option>
                <?php foreach ($companies as $c): ?>
                  <option
                    value="<?php echo $c['id']; ?>"
                    data-address="<?php echo htmlspecialchars($c['address'] ?? ''); ?>"
                    <?php if (isset($_POST['company']) && (int)$_POST['company'] === (int)$c['id']) echo 'selected'; ?>
                  >
                    <?php
  echo htmlspecialchars($c['name']);
?>

                  </option>
                <?php endforeach; ?>
              </select>
              <button type="button" id="btnAddCompany" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-primary text-white text-sm hover:opacity-95">
                + New
              </button>
            </div>
            <!-- <div id="companyAddressPreview" class="text-xs text-gray-500 mt-2"></div> -->
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Vehicle ownership</label>
            <div class="mt-1 flex items-center gap-4">
              <label class="inline-flex items-center gap-2">
                <input type="radio" name="vehicle_owner" value="own" <?php if (($_POST['vehicle_owner'] ?? 'own') !== 'rental') echo 'checked'; ?>>
                <span class="text-sm">Own</span>
              </label>
              <label class="inline-flex items-center gap-2">
                <input type="radio" name="vehicle_owner" value="rental" <?php if (($_POST['vehicle_owner'] ?? '') === 'rental') echo 'checked'; ?>>
                <span class="text-sm">Rental</span>
              </label>
            </div>
          </div>

          <div>
            <label for="vehicle_no" class="block text-sm font-medium text-gray-700">Vehicle number</label>
            <input id="vehicle_no" name="vehicle_no" type="text" value="<?php echo htmlspecialchars($_POST['vehicle_no'] ?? ''); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2" placeholder="e.g. ABC-1234">
          </div>

          <div>
            <label for="driver_name" class="block text-sm font-medium text-gray-700">Driver</label>
            <input id="driver_name" name="driver_name" type="text" value="<?php echo htmlspecialchars($_POST['driver_name'] ?? ''); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2">
          </div>

          <div>
            <label for="driver_number" class="block text-sm font-medium text-gray-700">Driver number</label>
            <input id="driver_number" name="driver_number" type="text" value="<?php echo htmlspecialchars($_POST['driver_number'] ?? ''); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2">
          </div>

          <div>
            <label for="vehicle_type" class="block text-sm font-medium text-gray-700">Vehicle type</label>
            <input id="vehicle_type" name="vehicle_type" type="text" value="<?php echo htmlspecialchars($_POST['vehicle_type'] ?? ''); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2" placeholder="Truck, Trailer, Van...">
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
          <div>
            <label for="sender_name" class="block text-sm font-medium text-gray-700">Sender</label>
            <input id="sender_name" name="sender_name" type="text" value="<?php echo htmlspecialchars($_POST['sender_name'] ?? ''); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2" placeholder="Sender or company">
          </div>

          <div>
            <label for="from_city" class="block text-sm font-medium text-gray-700">Origin</label>
            <input id="from_city" name="from_city" type="text" value="<?php echo htmlspecialchars($_POST['from_city'] ?? ''); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2">
          </div>

          <div>
            <label for="to_city" class="block text-sm font-medium text-gray-700">Destination</label>
            <input id="to_city" name="to_city" type="text" value="<?php echo htmlspecialchars($_POST['to_city'] ?? ''); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2">
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
          <div>
            <label for="qty" class="block text-sm font-medium text-gray-700">Quantity</label>
            <input id="qty" name="qty" type="number" min="0" step="1" value="<?php echo htmlspecialchars($_POST['qty'] ?? '0'); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2">
          </div>

          <div>
            <label for="km" class="block text-sm font-medium text-gray-700">Distance (KM)</label>
            <input id="km" name="km" type="number" min="0" step="1" value="<?php echo htmlspecialchars($_POST['km'] ?? '0'); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2">
          </div>

          <div>
            <label for="rate" class="block text-sm font-medium text-gray-700">Rate (per KM)</label>
            <input id="rate" name="rate" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($_POST['rate'] ?? '0.00'); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2">
          </div>

          <div>
            <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
            <input id="amount" name="amount" type="text" readonly value="<?php echo htmlspecialchars($_POST['amount'] ?? number_format(0,2)); ?>" class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
            <p class="text-xs text-gray-400 mt-1">Amount = Distance (KM) × Rate</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label for="advance" class="block text-sm font-medium text-gray-700">Advance paid</label>
            <input id="advance" name="advance" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($_POST['advance'] ?? '0.00'); ?>" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2">
          </div>

          <div>
            <label for="balance" class="block text-sm font-medium text-gray-700">Balance</label>
            <input id="balance" name="balance" type="text" readonly value="<?php echo htmlspecialchars($_POST['balance'] ?? number_format(0,2)); ?>" class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
            <p class="text-xs text-gray-400 mt-1">Amount remaining after advance</p>
          </div>
        </div>

        <div class="mt-4">
          <label for="details" class="block text-sm font-medium text-gray-700">Notes</label>
          <textarea id="details" name="details" rows="4" class="mt-1 block w-full rounded-md border border-gray-200 px-3 py-2"><?php echo htmlspecialchars($_POST['details'] ?? ''); ?></textarea>
        </div>

        <div class="flex items-center justify-between gap-4 mt-4">
          <div class="text-sm text-gray-600">All amounts are shown in your local currency.</div>
          <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-md">Save Bilty</button>
            <button type="button" id="resetBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border bg-white" onclick="document.getElementById('biltyForm').reset(); calc();">
              Reset
            </button>
          </div>
        </div>
      </form>
    </section>
  </main>

  <!-- Add Company Modal -->
  <div id="companyModal" class="modal-backdrop">
    <div class="modal-panel">
      <button type="button" class="modal-close" id="companyModalClose">✕</button>
      <h3>Add Company</h3>
      <p class="text-sm text-gray-600 mb-4">Create a new company and it will be auto-selected.</p>
      <form id="companyForm" autocomplete="off">
        <div class="field">
          <label>Company Name *</label>
          <input type="text" name="name" id="new_company_name" required maxlength="150" placeholder="Company name">
        </div>
        <div class="field">
          <label>Address</label>
          <textarea name="address" id="new_company_address" rows="3" maxlength="255" placeholder="Street / City / Optional details"></textarea>
          <p class="hint">Max 255 characters.</p>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-small secondary" id="cancelCompanyBtn">Cancel</button>
          <button type="submit" class="btn-small">Save Company</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function toFixedSafe(n, dec = 2) { return Number.isFinite(n) ? n.toFixed(dec) : (0).toFixed(dec); }

    function calc() {
      const km = parseFloat(document.getElementById('km').value) || 0;
      const rate = parseFloat(document.getElementById('rate').value) || 0;
      const advance = parseFloat(document.getElementById('advance').value) || 0;
      const amount = +(km * rate);
      const balance = +(amount - advance);
      document.getElementById('amount').value = toFixedSafe(amount);
      document.getElementById('balance').value = toFixedSafe(balance);
    }
    ['km','rate','advance'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('input', calc);
    });

    // Company address preview
    function updateCompanyAddressPreview() {
      const sel = document.getElementById('company');
      const preview = document.getElementById('companyAddressPreview');
      if (!sel || !preview) return;
      const opt = sel.options[sel.selectedIndex];
      const addr = opt ? opt.getAttribute('data-address') : '';
      preview.textContent = addr ? ('Address: ' + addr) : '';
    }
    document.getElementById('company')?.addEventListener('change', updateCompanyAddressPreview);
    document.addEventListener('DOMContentLoaded', updateCompanyAddressPreview);

    // Add Company Modal logic
    const companyModal = document.getElementById('companyModal');
    const openCompanyBtn = document.getElementById('btnAddCompany');
    const closeCompanyBtn = document.getElementById('companyModalClose');
    const cancelCompanyBtn = document.getElementById('cancelCompanyBtn');
    const companyForm = document.getElementById('companyForm');

    function openCompanyModal(){
      companyModal.style.display = 'flex';
      document.getElementById('new_company_name').focus();
    }
    function closeCompanyModal(){
      companyModal.style.display = 'none';
      companyForm.reset();
    }
    openCompanyBtn?.addEventListener('click', openCompanyModal);
    closeCompanyBtn?.addEventListener('click', closeCompanyModal);
    cancelCompanyBtn?.addEventListener('click', closeCompanyModal);
    companyModal?.addEventListener('click', e => { if (e.target === companyModal) closeCompanyModal(); });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && companyModal.style.display === 'flex') closeCompanyModal();
    });

    companyForm?.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const name = document.getElementById('new_company_name').value.trim();
      const addr = document.getElementById('new_company_address').value.trim();
      if(!name){
        alert('Company name required');
        return;
      }
      const fd = new FormData();
      fd.append('name', name);
      fd.append('address', addr);

      try {
        const resp = await fetch('company_save.php', { method:'POST', body: fd });
        const data = await resp.json();
        if(!data.ok) throw new Error(data.error || 'Failed to save company');

        // Append new option
        const sel = document.getElementById('company');
        if(sel){
            const opt = document.createElement('option');
            opt.value = data.company.id;
            opt.textContent = data.company.name + (data.company.address ? ' — ' + data.company.address.substring(0,40) : '');
            opt.setAttribute('data-address', data.company.address || '');
            opt.selected = true;
            sel.appendChild(opt);
            sel.dispatchEvent(new Event('change'));
        }

        closeCompanyModal();
      } catch(err) {
        alert(err.message);
      }
    });

    // Bilty form basic validation
    document.addEventListener('DOMContentLoaded', function() {
      calc();
      document.getElementById('biltyForm').addEventListener('submit', function(e) {
        const bilty_no = document.getElementById('bilty_no').value.trim();
        const company = document.getElementById('company').value;
        if (!bilty_no || !company) {
          e.preventDefault();
          alert('Please fill required fields: Bilty number and Company.');
        }
      });
    });
  </script>
</body>
</html>