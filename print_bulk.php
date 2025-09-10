<?php
require_once 'config.php';

// Combine selected bilties into a single "Bill" (one page containing all selected bilties in a consolidated table).
// Usage: print_bulk_combined.php?ids=1,2,3&auto=1

$ids_raw = trim($_GET['ids'] ?? '');
if ($ids_raw === '') {
    echo "No bilties selected.";
    exit;
}

// sanitize ids: allow only digits and commas, then build array of ints
$ids_clean = preg_replace('/[^0-9,]/', '', $ids_raw);
$ids_arr = array_values(array_filter(array_map('intval', array_map('trim', explode(',', $ids_clean))), function($v){ return $v > 0; }));
if (empty($ids_arr)) {
    echo "No valid bilty ids provided.";
    exit;
}

// Fetch selected bilties
$in_list = implode(',', $ids_arr);
$sql = "SELECT c.*, cp.name AS company_name
        FROM consignments c
        JOIN companies cp ON cp.id = c.company_id
        WHERE c.id IN ($in_list)
        ORDER BY c.date ASC, c.id ASC";
$res = $conn->query($sql);
$bilties = [];
if ($res) {
    while ($row = $res->fetch_assoc()) $bilties[] = $row;
    $res->free();
}
if (empty($bilties)) {
    echo "No bilties found for selected ids.";
    exit;
}

// Generate simple sequential bill number (YYYY + sequential number)
$year = date('Y');
$bill_number = $year . "-" . count($bilties);

// Configurable "Bill From" data (adjust as needed)
$bill_from_name = "Bahar Ali";
$bill_from_business = "Mini Goods Transport Hyderabad";
$bill_from_phone = "0306-3591311, 0302-3928417";
$bill_from_address = "Hala Naka Road Near Isra University Hyderabad";
$account_name = "Bahar Ali";
$bank_account_no = "8511008167530";
$iban = "PK32ALFH0851001008167530";

// helpers
function esc($s) { return htmlspecialchars((string)$s); }
function fmtAmount($v) { return number_format((float)$v, 2); }
function fmtNumber($v) { return is_numeric($v) ? (int)$v : $v; }

// compute totals
$gross = 0.0;
foreach ($bilties as $b) $gross += (float)($b['amount'] ?? 0);
$tax_default_percent = 4.0; // default tax percent (editable)
$tax = $gross * ($tax_default_percent/100.0);
$net = $gross - $tax;

$date_now = date('d-M-Y');

// Prepare combined trip detail text safely
$tripCitiesArray = array_values(array_filter(array_map(function($b){
    return trim((string)($b['to_city'] ?? ''));
}, $bilties), function($v){ return $v !== ''; }));
$tripCitiesUnique = array_unique($tripCitiesArray);
$trip_detail_text = !empty($tripCitiesUnique) ? implode(', ', $tripCitiesUnique) : '-';

// Calculate if we need multiple pages (max 20 rows per page)
$rows_per_page = 20;
$total_pages = ceil(count($bilties) / $rows_per_page);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Bill <?php echo $bill_number; ?> — Selected Bilties</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    /* Layout & print sizing */
    @page { size: A4; margin: 12mm; }
    html,body { height:100%; margin: 0; padding: 0; }
    body {
      font-family: Arial, sans-serif;
      color: #000;
      background: #fff;
      font-size: 11px;
      line-height: 1.2;
    }

    .page { 
      max-width: 800px; 
      margin: 14px auto; 
      padding: 10px; 
      border: 1px solid #000; 
      box-sizing: border-box; 
      background: #fff;
      page-break-after: always;
    }
    
    .page:last-child {
      page-break-after: auto;
    }

    /* Header design matching image */
    .header {
      display: flex;
      justify-content: space-between;
      border-bottom: 1px solid #000;
      padding-bottom: 3px;
      margin-bottom: 0;
    }

    .name-section {
      font-family: "Times New Roman", serif;
      font-style: italic;
      font-weight: 700;
      font-size: 26px;
      padding-top: 2px;
      padding-left: 5px;
    }

    .business-section {
      font-family: cursive, "Brush Script MT", serif;
      font-size: 20px;
      text-align: right;
      padding-top: 2px;
      padding-right: 5px;
    }

    .info-bar {
      display: flex;
      border-bottom: 1px solid #000;
      padding: 3px;
      font-size: 11px;
    }

    .info-bar .pro {
      font-weight: 700;
      margin-right: 5px;
      text-transform: uppercase;
    }

    .info-bar .label {
      font-weight: 700;
      margin-right: 3px;
      margin-left: 8px;
    }

    .info-bar .cell {
      font-family: monospace;
      font-weight: 600;
    }

    /* Bill title bar */
    .bill-title {
      text-align: center;
      background: #ccc;
      padding: 3px;
      font-weight: 700;
      margin-top: 1px;
      font-size: 14px;
    }

    /* Bill From/To sections */
    .bill-sections {
      display: flex;
      margin-top: 1px;
    }

    .bill-section {
      border: 1px solid #000;
      width: 50%;
    }

    .bill-section-title {
      font-weight: 700;
      padding: 3px;
      font-size: 12px;
      text-align: center;
    }

    .bill-section-content {
      padding: 5px;
    }

    .bill-section-row {
      display: flex;
      margin-bottom: 3px;
    }

    .bill-section-label {
      width: 80px;
      font-weight: 700;
    }

    /* Table styling */
    table.items {
      width: 100%;
      border-collapse: collapse;
      margin-top: 5px;
    }

    table.items th, table.items td {
      border: 1px solid #000;
      padding: 3px;
      font-size: 10px;
      vertical-align: middle;
    }

    table.items th {
      background: #eee;
      font-weight: 700;
      text-align: center;
    }

    table.items tr {
      height: 20px;
    }

    table.items tr.empty-row {
      border-bottom: 1px dotted #ccc;
    }

    table.items td.right {
      text-align: right;
    }

    /* Totals section */
    .totals {
      margin-top: 8px;
      width: 100%;
    }

    .totals table {
      width: 100%;
      border-collapse: collapse;
    }

    .totals tr td {
      padding: 3px;
    }

    .totals tr td:first-child {
      text-align: left;
      font-weight: 700;
    }

    .totals tr td:last-child {
      text-align: right;
      width: 120px;
    }

    .totals .currency {
      display: inline-block;
      width: 30px;
      text-align: left;
      font-weight: 700;
    }

    /* Account info */
    .account {
      margin-top: 15px;
    }

    .account .row {
      display: flex;
      margin-bottom: 3px;
    }

    .account .label {
      font-weight: 700;
      width: 120px;
    }
    
    .account .value {
      font-weight: 600;
    }

    /* Signature area */
    .signature {
      margin-top: 30px;
    }

    .signature .line {
      width: 150px;
      border-bottom: 1px solid #000;
    }

    .signature .label {
      margin-top: 4px;
      font-weight: 700;
    }

    /* Controls for editing */
    .print-controls { 
      margin-bottom: 12px; 
      text-align: right; 
    }
    
    .edit-panel { 
      display: flex; 
      gap: 10px; 
      align-items: center; 
      justify-content: flex-end; 
      flex-wrap: wrap; 
      margin-bottom: 10px; 
    }
    
    .btn { 
      padding: 7px 10px; 
      font-size: 12px; 
      border-radius: 5px; 
      cursor: pointer; 
    }
    
    .btn-primary { 
      background: #0b63d6; 
      color: #fff; 
      border: 0; 
    }
    
    .btn-secondary { 
      background: #fff; 
      border: 1px solid #ccc; 
      color: #000; 
    }

    .inline-input { 
      padding: 2px 3px; 
      font-size: 10px; 
    }

    .page-number {
      text-align: center;
      font-size: 10px;
      margin-top: 5px;
      font-weight: 600;
    }

    @media print {
      .print-controls, .edit-panel { display: none !important; }
      .page { box-shadow: none; border: none; margin: 0; padding: 5mm; }
    }
  </style>
</head>
<body>
  <div class="print-controls">
    <div class="edit-panel">
      <button id="toggleEdit" class="btn btn-secondary" type="button">Edit Details</button>
      <button id="applyBtn" class="btn btn-primary" type="button">Apply</button>
      <button id="resetBtn" class="btn btn-secondary" type="button">Reset</button>
      <button id="applyPrintBtn" class="btn btn-primary" type="button">Apply & Print</button>
      <button id="downloadPdfBtn" class="btn btn-secondary" type="button">Save PDF (Bill-<?php echo $bill_number; ?>)</button>
    </div>
    <button onclick="window.print()" class="btn btn-secondary">Quick Print</button>
    <a href="view_bilty.php" style="margin-left: 9px;" class="btn btn-secondary">Back</a>
  </div>

  <?php for ($page = 0; $page < $total_pages; $page++): 
    $start_idx = $page * $rows_per_page;
    $end_idx = min(($page + 1) * $rows_per_page, count($bilties));
    $page_bilties = array_slice($bilties, $start_idx, $end_idx - $start_idx);
    
    // Calculate page totals
    $page_gross = 0;
    foreach ($page_bilties as $b) $page_gross += (float)($b['amount'] ?? 0);
    $page_tax = $page_gross * ($tax_default_percent/100.0);
    $page_net = $page_gross - $page_tax;
  ?>
  <div class="page" role="document" aria-label="Combined bill for selected bilties - Page <?php echo $page + 1; ?>">
    <!-- Header matching the image design -->
    <div class="header">
      <div class="name-section" id="display_bill_from_name"><?php echo esc($bill_from_name); ?></div>
      <div class="business-section" id="display_bill_from_business"><?php echo esc($bill_from_business); ?></div>
    </div>

    <div class="info-bar">
      <div>
        <span class="pro">Pro:</span>
        <span id="display_pro" style="font-weight: 700;"><?php echo esc($bill_from_name); ?></span>
      </div>
      <div style="flex: 3;">
        <span class="label">Cell:</span>
        <span id="display_phone" class="cell"><?php echo esc($bill_from_phone); ?></span>
        <span id="display_address" style="margin-left: 5px;"><?php echo esc($bill_from_address); ?></span>
      </div>
    </div>

    <div class="bill-title">Bill</div>

    <div class="bill-sections">
      <div class="bill-section">
        <div class="bill-section-title">Bill From</div>
        <div class="bill-section-content">
          <div class="bill-section-row">
            <div class="bill-section-label">Name</div>
            <div id="bf_name"><?php echo esc($bill_from_name . ' Mini Goods Transport'); ?></div>
          </div>
          <div class="bill-section-row">
            <div class="bill-section-label">Bill No.</div>
            <div id="bf_bilty_no"><?php echo esc($bill_number); ?></div>
          </div>
          <div class="bill-section-row">
            <div class="bill-section-label">Bill Date</div>
            <div id="bf_bill_date"><?php echo esc($date_now); ?></div>
          </div>
        </div>
      </div>
      
      <div class="bill-section">
        <div class="bill-section-title">Bill To</div>
        <div class="bill-section-content">
          <?php $first = $bilties[0]; ?>
          <div class="bill-section-row">
            <div class="bill-section-label">Billing Name</div>
            <div id="bt_name"><?php echo esc($first['company_name'] ?? ''); ?></div>
          </div>
          <div class="bill-section-row">
            <div class="bill-section-label">Billing Add</div>
            <div id="bt_addr"><?php echo esc($first['to_city'] ?? ''); ?></div>
          </div>
          <div class="bill-section-row">
            <div class="bill-section-label">Phone #</div>
            <div id="bt_phone"><?php 
              $phone = '';
              if (!empty($first['details']) && preg_match('/DriverNumber:\s*([0-9+\-\s()]+)/i', $first['details'], $m2)) {
                $phone = trim($m2[1]);
              }
              echo esc($phone);
            ?></div>
          </div>
        </div>
      </div>
    </div>

    <table class="items">
      <thead>
        <tr>
          <th style="width: 30px;">S.No</th>
          <th style="width: 60px;">Bilty No</th>
          <th style="width: 70px;">Date</th>
          <th>Route/Cities</th>
          <th style="width: 60px;">Vehicle</th>
          <th style="width: 60px;">V-No</th>
          <th style="width: 40px;">KM</th>
          <th style="width: 40px;">Rate</th>
          <th style="width: 60px;">Amount</th>
        </tr>
      </thead>
      <tbody id="items_tbody_<?php echo $page; ?>">
        <?php foreach ($page_bilties as $i => $b):
            $row_num = $start_idx + $i + 1;
            $dateDisplay = !empty($b['date']) ? date('d-M-y', strtotime($b['date'])) : '';
            $amount_val = (float)($b['amount'] ?? 0);
            $vehicle_type = $b['vehicle_type'] ?? 'Suzuki';
        ?>
          <tr data-id="<?php echo intval($b['id']); ?>">
            <td style="text-align: center;"><?php echo $row_num; ?></td>
            <td><?php echo esc($b['bilty_no']); ?></td>
            <td><?php echo esc($dateDisplay); ?></td>
            <td><?php echo esc(trim(($b['from_city'] ?? '') . (($b['to_city'] ?? '') ? ' → ' . $b['to_city'] : ''))); ?></td>
            <td><?php echo esc($vehicle_type); ?></td>
            <td><?php echo esc($b['vehicle_no'] ?? ''); ?></td>
            <td class="right"><?php echo fmtNumber($b['km'] ?? 0); ?></td>
            <td class="right"><?php echo fmtAmount($b['rate'] ?? 0); ?></td>
            <td class="right amount-cell" data-raw="<?php echo $amount_val; ?>" data-page="<?php echo $page; ?>" data-idx="<?php echo $i; ?>"><?php echo fmtAmount($amount_val); ?></td>
          </tr>
        <?php endforeach; ?>

        <?php
          // add empty rows with dotted lines to match the template image
          $current = count($page_bilties);
          for ($r = $current; $r < $rows_per_page; $r++): ?>
            <tr class="empty-row">
              <td style="text-align: center;"><?php echo $start_idx + $r + 1; ?></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
        <?php endfor; ?>
      </tbody>
    </table>

    <!-- Show totals on each page -->
    <div class="totals">
      <table>
        <tr>
          <td>Gross Amount</td>
          <td><span class="currency">PKR</span> <span id="display_gross_<?php echo $page; ?>" class="display-gross"><?php echo fmtAmount($page_gross); ?></span></td>
        </tr>
        <tr>
          <td>Tax <span id="tax_percent_label_<?php echo $page; ?>" class="tax-percent-label">(<?php echo fmtAmount($tax_default_percent); ?>%)</span></td>
          <td><span class="currency">PKR</span> <span id="display_tax_<?php echo $page; ?>" class="display-tax"><?php echo fmtAmount($page_tax); ?></span></td>
        </tr>
        <tr>
          <td>Net Amount</td>
          <td><span class="currency">PKR</span> <span id="display_net_<?php echo $page; ?>" class="display-net"><?php echo fmtAmount($page_net); ?></span></td>
        </tr>
      </table>
    </div>

    <div class="account">
      <div class="row">
        <div class="label">Account Name</div>
        <div id="acc_name" class="value"><?php echo esc($account_name); ?></div>
      </div>
      <div class="row">
        <div class="label">Bank Account No</div>
        <div id="acc_bank" class="value"><?php echo esc($bank_account_no); ?></div>
      </div>
      <div class="row">
        <div class="label">IBAN</div>
        <div id="acc_iban" class="value"><?php echo esc($iban); ?></div>
      </div>
    </div>

    <div class="signature">
      <div class="line"></div>
      <div class="label">Sign And Stamp</div>
    </div>
    
    <?php if ($total_pages > 1): ?>
      <div class="page-number">Page <?php echo ($page + 1); ?> of <?php echo $total_pages; ?></div>
    <?php endif; ?>
  </div>
  <?php endfor; ?>

  <script>
    (function(){
      // Utilities
      function parseNumber(v) {
        if (v === null || v === undefined) return 0;
        v = String(v).replace(/,/g, '').trim();
        v = v.replace(/[^\d.\-]/g, '');
        return v === '' ? 0 : parseFloat(v);
      }
      
      function formatNumber(v) {
        return Number(v).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
      }

      // extract percent number from a string like "(4.00%)" or "(4%)"
      function parsePercentFromLabel(text) {
        const m = String(text).match(/([0-9]+(?:\.[0-9]+)?)/);
        return m ? parseFloat(m[1]) : 0;
      }

      // Element IDs we will toggle between text and input
      const editableFields = [
        'display_bill_from_name','display_bill_from_business','display_pro','display_phone','display_address',
        'bf_name','bf_bilty_no','bf_bill_date',
        'bt_name','bt_addr','bt_phone',
        'acc_name','acc_bank','acc_iban'
      ];

      // Helpers to swap nodes safely
      function toInput(id, inputId, width) {
        const el = document.getElementById(id);
        if (!el) return;
        // if already an input, skip
        if (el.tagName.toLowerCase() === 'input') return;
        
        const val = el.textContent.trim();
        const input = document.createElement('input');
        input.type = 'text';
        input.id = inputId;
        input.value = val;
        input.className = 'inline-input';
        input.style.width = (width || 100) + 'px';
        
        // Store original content and styling for restoration
        el.dataset.originalHtml = el.innerHTML;
        el.innerHTML = '';
        el.appendChild(input);
      }
      
      function toTextFromInput(inputId, idToSet) {
        const inp = document.getElementById(inputId);
        if (!inp) return;
        
        // Find parent element
        const parent = inp.parentNode;
        const newValue = inp.value;
        
        // If there was original HTML stored, use that structure but update text
        if (parent.dataset.originalHtml) {
          parent.innerHTML = parent.dataset.originalHtml;
          parent.textContent = newValue;
        } else {
          // Simple text replacement
          parent.textContent = newValue;
        }
      }

      // amount inputs
      function amountCellToInput(td, idx) {
        const val = parseNumber(td.dataset.raw || td.textContent);
        const page = td.dataset.page || 0;
        td.innerHTML = '<input class="inline-input line-amount" data-page="' + page + '" data-idx="' + idx + '" id="amount_input_' + page + '_' + idx + '" value="' + formatNumber(val) + '" style="width:60px;">';
      }
      
      function amountInputToCell(page, idx) {
        const inp = document.getElementById('amount_input_' + page + '_' + idx);
        if (!inp) return;
        const val = parseNumber(inp.value);
        const td = document.querySelector('.amount-cell[data-page="' + page + '"][data-idx="' + idx + '"]');
        if (td) {
          td.dataset.raw = val;
          td.textContent = formatNumber(val);
        }
      }

      // state
      let editMode = false;
      const toggleEditBtn = document.getElementById('toggleEdit');
      const applyBtn = document.getElementById('applyBtn');
      const resetBtn = document.getElementById('resetBtn');
      const applyPrintBtn = document.getElementById('applyPrintBtn');
      const downloadPdfBtn = document.getElementById('downloadPdfBtn');

      // snapshot originals for reset
      const original = {
        html: document.body.innerHTML,
        tax_percent: <?php echo json_encode($tax_default_percent); ?>,
      };

      // current tax percent used for calculations and label
      let currentTaxPercent = parsePercentFromLabel(document.querySelector('.tax-percent-label').textContent || '') || original.tax_percent;

      function enterEditMode() {
        if (editMode) return;
        editMode = true;
        toggleEditBtn.textContent = 'Exit Edit';
        
        // transform each editable field to an input
        editableFields.forEach(id => {
          const el = document.getElementById(id);
          if (el) {
            const width = Math.max(el.clientWidth, 80);
            toInput(id, 'edit_' + id, width);
          }
        });
        
        // transform amount cells
        Array.from(document.querySelectorAll('.amount-cell')).forEach(td => {
          const idx = td.dataset.idx;
          const page = td.dataset.page;
          amountCellToInput(td, idx);
        });
        
        // show tax input inline initialized with currentTaxPercent
        document.querySelectorAll('.tax-percent-label').forEach((taxLabel, idx) => {
          if (taxLabel) {
            taxLabel.innerHTML = '(<input id="edit_tax_percent_' + idx + '" type="number" step="0.01" min="0" style="width:40px; padding:1px;" value="' + 
              (isFinite(currentTaxPercent) ? currentTaxPercent : original.tax_percent) + '">%)';
          }
        });
      }

      function exitEditMode() {
        if (!editMode) return;
        editMode = false;
        toggleEditBtn.textContent = 'Edit Details';
        
        // replace inputs back to text content (preserve edited values)
        editableFields.forEach(id => {
          toTextFromInput('edit_' + id, id);
        });
        
        // amount inputs back to cells
        Array.from(document.querySelectorAll('.line-amount')).forEach(inp => {
          const idx = inp.dataset.idx;
          const page = inp.dataset.page;
          amountInputToCell(page, idx);
        });
        
        // restore tax label display using currentTaxPercent
        document.querySelectorAll('.tax-percent-label').forEach(taxLabel => {
          if (taxLabel) taxLabel.textContent = '(' + formatNumber(currentTaxPercent) + '%)';
        });
      }

      function applyChanges() {
        // if in edit mode, read inputs and update display and calculations
        if (editMode) {
          // amounts -> update dataset and display
          Array.from(document.querySelectorAll('.line-amount')).forEach(inp => {
            const idx = inp.dataset.idx;
            const page = inp.dataset.page;
            const val = parseNumber(inp.value);
            const td = document.querySelector('.amount-cell[data-page="' + page + '"][data-idx="' + idx + '"]');
            if (td) {
              td.dataset.raw = val;
              td.textContent = formatNumber(val);
            }
          });

          // read tax percent input if present and update currentTaxPercent
          const taxInput = document.getElementById('edit_tax_percent_0');
          if (taxInput) {
            currentTaxPercent = parseNumber(taxInput.value);
          }

          // compute totals with new percent
          recomputeTotals(currentTaxPercent);

          // exit edit mode (this will update the visible tax label)
          exitEditMode();
        } else {
          // not edit mode: just recompute using existing currentTaxPercent
          recomputeTotals(currentTaxPercent);
        }
      }

      function recomputeTotals(taxPercent) {
        // Calculate totals for each page
        const pageCount = <?php echo $total_pages; ?>;
        
        for (let page = 0; page < pageCount; page++) {
          const amounts = Array.from(document.querySelectorAll('.amount-cell[data-page="' + page + '"]'))
            .map(td => parseNumber(td.dataset.raw || td.textContent));
            
          const gross = amounts.reduce((s, n) => s + (isFinite(n) ? n : 0), 0);
          const tax = gross * ((isFinite(taxPercent) ? taxPercent : 0) / 100.0);
          const net = gross - tax;
          
          const grossEl = document.getElementById('display_gross_' + page);
          const taxEl = document.getElementById('display_tax_' + page);
          const netEl = document.getElementById('display_net_' + page);
          
          if (grossEl) grossEl.textContent = formatNumber(gross);
          if (taxEl) taxEl.textContent = formatNumber(tax);
          if (netEl) netEl.textContent = formatNumber(net);
          
          // update percent label as well
          const taxLabel = document.getElementById('tax_percent_label_' + page);
          if (taxLabel && !document.getElementById('edit_tax_percent_' + page)) {
            taxLabel.textContent = '(' + formatNumber(taxPercent) + '%)';
          }
        }
      }

      function resetAll() {
        // reload page to get original state quickly (cleanest approach)
        location.reload();
      }
      
      function savePDF() {
        const billNumber = document.getElementById('bf_bilty_no').textContent.trim();
        const fileName = 'Bill-' + billNumber + '.pdf';
        
        // Apply any pending changes first
        applyChanges();
        
        // Alert user about the PDF download
        alert('Preparing to save "' + fileName + '". The browser print dialog will open. Please select "Save as PDF" as the destination.');
        
        // Use window.print() which will trigger the browser's print dialog
        // The user can then select "Save as PDF" option
        setTimeout(function() {
          window.print();
        }, 500);
      }

      // wire buttons
      toggleEditBtn.addEventListener('click', function(){
        if (!editMode) enterEditMode(); else exitEditMode();
      });
      applyBtn.addEventListener('click', function(){ applyChanges(); });
      resetBtn.addEventListener('click', function(){ resetAll(); });
      applyPrintBtn.addEventListener('click', function(){ applyChanges(); setTimeout(()=>window.print(), 250); });
      downloadPdfBtn.addEventListener('click', function(){ savePDF(); });

      // initial totals using currentTaxPercent
      recomputeTotals(currentTaxPercent);

      // auto print
      const url = new URL(window.location.href);
      if (url.searchParams.get('auto') === '1') {
        setTimeout(function(){ window.print(); }, 400);
      }
    })();
  </script>
</body>
</html>