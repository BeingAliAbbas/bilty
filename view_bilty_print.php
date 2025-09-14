<?php
require_once 'config.php';

/*
  A5 Single Bilty Printable (With Control Bar Editing)
  ----------------------------------------------------
  Requirements (from user):
    - Use the same style control bar (Edit / Apply / Print / Save PDF / Reset / Back)
    - NO bill number / no finalize workflow
    - A5 size (portrait)
    - Simpler layout (NOT the same big "Bill" multi-row template)
    - Allow inline editing of key text fields (company name, address, route text, phone, notes, amount-related fields)
    - PDF download & server save (uses save_pdf.php; adapt there if needed)

  Notes:
    - We keep the original bilty_no from the consignments table as a read-only display (the unique reference)
    - Amount usually = KM * Rate. If user edits KM or Rate or Amount manually in edit mode:
        * If KM or Rate changed we auto recompute Amount.
        * If Amount field itself is edited directly (by focusing it), we temporarily pause auto-compute until KM/Rate changes again
    - Advance & Balance editable (Balance NOT auto recomputed; user decides)
    - No tax logic here (bility typically a transport docket; tax handled at aggregated billing stage)
    - Save PDF posts FormData(bilty_no, pdf_data) to save_pdf.php (update save_pdf.php if it only expects bill_no)

  Usage:
    print_bilty_a5.php?id=123
    Optional: ?auto=1 to auto open print dialog after load

  Adjust styling / fields as necessary.
*/

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo "Invalid bilty id."; exit; }

$sql = "SELECT c.*,
               cp.name    AS company_name,
               cp.address AS company_address
        FROM consignments c
        JOIN companies cp ON cp.id = c.company_id
        WHERE c.id = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res  = $stmt->get_result();
$bilty = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$bilty) { echo "Bilty not found."; exit; }

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function n2($v){ return number_format((float)$v,2,'.',','); }
function n0($v){ return is_numeric($v)?(int)$v:0; }

$dateDisplay = !empty($bilty['date']) ? date('d-M-Y', strtotime($bilty['date'])) : '';
$company_name = $bilty['company_name'] ?? '';
$company_address = $bilty['company_address'] ?? '';
$route_str = trim(($bilty['from_city'] ?? '') . (($bilty['to_city'] ?? '') ? ' → '.$bilty['to_city'] : ''));
$vehicle_type = $bilty['vehicle_type'] ?: '';
$vehicle_no = $bilty['vehicle_no'] ?: '';
$driver_name = $bilty['driver_name'] ?: '';
$driver_number = $bilty['driver_number'] ?: '';

// Extract driver number if embedded inside details meta
if ($driver_number === '' && !empty($bilty['details']) &&
    preg_match('/Driver\s*number:\s*([0-9+\-\s()]+)/i', $bilty['details'], $m2)) {
  $driver_number = trim($m2[1]);
}

// Clean notes (remove meta style lines)
function clean_details_text(string $text): string {
  if ($text==='') return '';
  $lines = preg_split('/\R/', $text);
  $keep = [];
  foreach ($lines as $ln){
    $t = trim($ln);
    if ($t==='') continue;
    if (preg_match('/^(Vehicle|Driver\s*number|DriverNumber|Additional\s*info)\s*[:\-]/i',$t)) continue;
    if (preg_match('/^(Own|Rental)$/i',$t)) continue;
    $keep[] = $t;
  }
  return trim(implode("\n",$keep));
}
$notes = clean_details_text($bilty['details'] ?? '');

$qty     = n0($bilty['qty'] ?? 0);
$km      = n0($bilty['km'] ?? 0);
$rate    = (float)($bilty['rate'] ?? 0);
$amount  = (float)($bilty['amount'] ?? 0);
$advance = (float)($bilty['advance'] ?? 0);
$balance = (float)($bilty['balance'] ?? ($amount - $advance));

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Print Bilty #<?php echo esc($bilty['bilty_no']); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="pdf.js"></script>
<style>
@page { size: A5 portrait; margin: 8mm; }

:root {
  --font: "Arial","Helvetica",sans-serif;
  --fs: 11px;
  --hair: 0.6px;
  --accent-bg: #e5e7eb;
}

html,body {
  margin:0; padding:0;
  font-family:var(--font);
  font-size:var(--fs);
  background:#f1f5f9;
  color:#000;
  -webkit-print-color-adjust: exact !important;
  print-color-adjust: exact !important;
}

.no-print { }
@media print {
  body { background:#fff; }
  .no-print { display:none !important; }
  .a5-page { box-shadow:none; margin:0; width:auto; }
  #controlBar { display:none !important; }
}

#controlBar {
  background:#fff;
  border-bottom:1px solid #d1d5db;
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  align-items:center;
  justify-content:flex-end;
  padding:14px 18px;
  box-shadow:0 2px 4px rgba(0,0,0,.08);
}

#controlBar .status-badge {
  font-size:13px;
  padding:8px 16px;
  border-radius:999px;
  font-weight:600;
  background:#dbeafe;
  color:#1e3a8a;
  box-shadow:0 2px 4px rgba(0,0,0,.08);
}

#controlBar button, #controlBar a {
  font-size:14px;
  font-weight:600;
  padding:10px 18px;
  border-radius:8px;
  border:1px solid #d1d5db;
  background:#fff;
  color:#111827;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  gap:6px;
  text-decoration:none;
  box-shadow:0 2px 4px rgba(0,0,0,.08);
  transition:background .18s, box-shadow .18s;
}
#controlBar button:hover, #controlBar a:hover { background:#f1f5f9; }
#controlBar .primary-btn { background:#2563eb; color:#fff; border-color:#1d4ed8; }
#controlBar .primary-btn:hover { background:#1d4ed8; }
#controlBar .print-btn { background:#4f46e5; color:#fff; border-color:#4338ca; }
#controlBar .print-btn:hover{ background:#4338ca; }
#controlBar .pdf-btn { background:#374151; color:#fff; border-color:#1f2937; }
#controlBar .pdf-btn:hover { background:#1f2937; }
#controlBar button[disabled] { opacity:.5; cursor:not-allowed; }

.a5-wrapper {
  width:100%;
  display:flex;
  justify-content:center;
  padding:16px 0 40px;
  box-sizing:border-box;
}

.a5-page {
  width:100%;
  max-width: 430px; /* approximate inner width for A5 with margins */
  background:#fff;
  border:1px solid #d1d5db;
  /* box-shadow:0 2px 8px rgba(0,0,0,.12); */
  padding:14px 16px 18px;
  box-sizing:border-box;
  position:relative;
}

.header-row {
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:10px;
  border-bottom:var(--hair) solid #000;
  padding-bottom:4px;
  margin-bottom:6px;
}

.brand-left .brand-name {
  font-size:20px;
  font-weight:700;
  font-style:italic;
  letter-spacing:.4px;
  margin-bottom:2px;
}
.brand-left .brand-sub { font-size:10px; font-weight:600; }

.inline-rows { margin-top:4px; font-size:10px; line-height:1.2; }
.inline-rows .lbl { font-weight:700; margin-right:4px; }

.section-pair {
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:10px;
  margin-top:8px;
}

.panel {
  border:var(--hair) solid #000;
  display:flex;
  flex-direction:column;
}
.panel-title {
  background:var(--accent-bg);
  font-weight:700;
  font-size:11px;
  padding:3px 6px;
  border-bottom:var(--hair) solid #000;
}
.panel-body { padding:6px 6px 4px; font-size:10.5px; }

.field-line {
  display:flex;
  margin-bottom:4px;
  gap:4px;
  align-items:flex-start;
}
.field-line .flabel { width:60px; font-weight:700; flex-shrink:0; }
.field-line .fval {
  flex:1;
  min-height:16px;
  line-height:1.2;
  word-break:break-word;
}

.route-box {
  margin-top:8px;
  border:var(--hair) solid #000;
  padding:6px 6px 4px;
  font-size:11px;
  background:#fff;
  min-height:32px;
  white-space:pre-wrap;
  word-break:break-word;
}

.items-table-wrap {
  margin-top:8px;
  border:var(--hair) solid #000;
  overflow:hidden;
}
.items-table {
  width:100%;
  border-collapse:collapse;
  table-layout:fixed;
  font-size:10.5px;
}
.items-table th, .items-table td {
  border:var(--hair) solid #000;
  padding:4px 4px;
  vertical-align:middle;
}
.items-table th {
  background:#ececec;
  font-weight:700;
  font-size:10px;
  text-align:center;
}
.items-table td.num { text-align:right; font-variant-numeric:tabular-nums; }
.items-table td.center { text-align:center; }

.amount-summary {
  margin-top:8px;
  width:100%;
  border:var(--hair) solid #000;
  border-collapse:collapse;
  font-size:10.5px;
}
.amount-summary td {
  border:var(--hair) solid #000;
  padding:4px 4px;
}
.amount-summary .label { font-weight:700; width:70%; }
.amount-summary .val { text-align:right; font-variant-numeric:tabular-nums; }

.notes-box {
  margin-top:8px;
  border:var(--hair) solid #000;
  padding:6px 6px 4px;
  font-size:10.5px;
  min-height:40px;
  white-space:pre-wrap;
  word-break:break-word;
}

.signature-block {
  margin-top:30mm; /* or 120px */
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
  font-size:10px;
  gap:10px;
}
.signature-area {
  flex: 1;
  border-top:var(--hair) solid #000;
  padding-top:4px;
  text-align:center;
  font-weight:600;
  min-height:30px;
}

.print-footer-tag {
  font-size:9px;
  opacity:.75;
  text-align:right;
}

.inline-input {
  font-size:10px;
  padding:2px 4px;
  border:0.7px solid #555;
  border-radius:3px;
  width:100%;
  background:#fff;
  box-sizing:border-box;
  font-family:inherit;
}

.editable-block.editing {
  background: #fff;
  min-height:16px;
}

/* Overlay for progress (PDF save) */
.progress-overlay {
  position:fixed; inset:0;
  background:rgba(17,24,39,.55);
  display:none;
  align-items:center; justify-content:center;
  z-index:2000;
  color:#fff;
  flex-direction:column;
  gap:12px;
  font-size:14px;
}
.progress-wrap {
  width:240px;
  background:rgba(255,255,255,.15);
  border:1px solid rgba(255,255,255,.35);
  border-radius:6px;
  overflow:hidden;
  height:10px;
}
.progress-bar {
  height:10px;
  width:0%;
  background:#10b981;
  transition:width .3s;
}
@media (max-width:560px){
  .section-pair { grid-template-columns:1fr; }
  .a5-page { max-width:100%; }
}

</style>
</head>
<body>

<!-- Control Bar -->
<div id="controlBar" class="no-print">
  <span id="statusBadge" class="status-badge">BILTY</span>
  <button id="toggleEdit" type="button">Edit</button>
  <button id="applyBtn" class="primary-btn" type="button">Apply (No Save)</button>
  <button id="printBtn" class="print-btn" type="button">Print</button>
  <button id="savePdfBtn" class="pdf-btn" type="button">Save PDF</button>
  <button id="resetBtn" type="button">Reset</button>
  <a href="view_bilty.php">Back</a>
</div>

<div class="a5-wrapper" id="biltyRoot">
  <div class="a5-page" id="biltyPage">
    <div class="header-row">
      <div class="brand-left">
        <div id="from_name" class="brand-name editable-block"><?php echo esc('Bahar Ali'); ?></div>
        <div id="from_business" class="brand-sub editable-block"><?php echo esc('Mini Goods Transport Hyderabad'); ?></div>
        <div class="inline-rows">
          <div><span class="lbl">Cell:</span><span id="from_phone" class="editable-block" style="font-weight:600;"><?php echo esc('0306-3591311, 0302-3928417'); ?></span></div>
          <div><span class="lbl">Addr:</span><span id="from_address" class="editable-block"><?php echo esc('Hala Naka Road Near Isra University Hyderabad'); ?></span></div>
        </div>
      </div>
      <div style="text-align:right; min-width:140px;">
        <div style="font-size:14px; font-weight:700; letter-spacing:.5px;">BILTY</div>
        <div style="margin-top:6px; font-size:10.5px;">
          <div style="margin-bottom:4px;"><strong>No:</strong> <span><?php echo esc($bilty['bilty_no']); ?></span></div>
          <div style="margin-bottom:4px;"><strong>Date:</strong> <span id="bilty_date" class="editable-block"><?php echo esc($dateDisplay); ?></span></div>
          
        </div>
      </div>
    </div>

    <div class="section-pair">
      <div class="panel">
        <div class="panel-title">Company / To</div>
        <div class="panel-body">
          <div class="field-line">
            <div class="flabel">Name</div>
            <div id="to_name" class="fval editable-block"><?php echo esc($company_name); ?></div>
          </div>
          <div class="field-line">
            <div class="flabel">Address</div>
            <div id="to_address" class="fval editable-block"><?php echo esc($company_address); ?></div>
          </div>
          <div class="field-line">
            <div class="flabel">Phone</div>
            <div id="to_phone" class="fval editable-block"><?php echo esc($driver_number); ?></div>
          </div>
          <div class="field-line">
            <div class="flabel">Sender</div>
            <div id="sender_name" class="fval editable-block"><?php echo esc($bilty['sender_name'] ?: ''); ?></div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-title">Vehicle / Driver</div>
        <div class="panel-body">
          <div class="field-line">
            <div class="flabel">Vehicle</div>
            <div id="vehicle_type" class="fval editable-block"><?php echo esc($vehicle_type); ?></div>
          </div>
          <div class="field-line">
            <div class="flabel">V No</div>
            <div id="vehicle_no" class="fval editable-block"><?php echo esc($vehicle_no); ?></div>
          </div>
            <div class="field-line">
              <div class="flabel">Driver</div>
              <div id="driver_name" class="fval editable-block"><?php echo esc($driver_name); ?></div>
            </div>
          <div class="field-line">
            <div class="flabel">Route</div>
            <div id="route_text" class="fval editable-block"><?php echo esc($route_str); ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- <div id="notes_box" class="notes-box editable-block" style="margin-top:10px;">
      <?php echo nl2br(esc($notes ?: 'Notes...')); ?>
    </div> -->

    <div class="items-table-wrap">
      <table class="items-table">
        <thead>
          <tr>
            <th style="width:12%;">Qty</th>
            <th style="width:16%;">KM</th>
            <th style="width:16%;">Rate</th>
            <th style="width:20%;">Advance</th>
            <th style="width:20%;">Balance</th>
            <th style="width:16%;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="center amount-cell" data-field="qty" data-type="int"><?php echo $qty; ?></td>
            <td class="center amount-cell" data-field="km" data-type="int"><?php echo $km; ?></td>
            <td class="center amount-cell" data-field="rate" data-type="float"><?php echo n2($rate); ?></td>
            <td class="center amount-cell" data-field="advance" data-type="float"><?php echo n2($advance); ?></td>
            <td class="center amount-cell" data-field="balance" data-type="float"><?php echo n2($balance); ?></td>
            <td class="center amount-cell" data-field="amount" data-type="float" data-computed="1"><?php echo n2($amount); ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <table class="amount-summary">
      <tr>
        <td class="label">Total Amount</td>
        <td class="val"><span id="total_amount"><?php echo n2($amount); ?></span></td>
      </tr>
    </table>

    <div class="signature-block">
      <div class="signature-area">
        Transporter Signature
      </div>
      <!-- <div class="print-footer-tag">
        Printed: <?php echo esc(date('d-M-Y H:i')); ?>
      </div> -->
    </div>
  </div>
</div>

<div id="progressOverlay" class="progress-overlay">
  <div style="text-align:center;">
    <div style="font-weight:600;">Generating PDF…</div>
    <div class="progress-wrap"><div id="progressBar" class="progress-bar"></div></div>
    <div id="progressText" style="font-size:11px; opacity:.9; margin-top:6px;">Please wait…</div>
  </div>
</div>

<script>
(function(){
  const editableBlocks = [
    'from_name','from_business','from_phone','from_address',
    'bilty_date','to_name','to_address','to_phone',
    'sender_name','vehicle_type','vehicle_no','driver_name','route_text','notes_box'
  ];

  let editMode = false;
  let manualAmountOverride = false; // if user directly edits the amount cell
  const toggleBtn   = document.getElementById('toggleEdit');
  const applyBtn    = document.getElementById('applyBtn');
  const printBtn    = document.getElementById('printBtn');
  const savePdfBtn  = document.getElementById('savePdfBtn');
  const resetBtn    = document.getElementById('resetBtn');
  const progressOverlay = document.getElementById('progressOverlay');
  const progressBar     = document.getElementById('progressBar');
  const progressText    = document.getElementById('progressText');

  function asNumber(val, type){
    let v = (''+val).replace(/,/g,'').trim();
    if(v==='') return 0;
    let n = type==='int'? parseInt(v,10): parseFloat(v);
    return isNaN(n)?0:n;
  }

  function format2(n){
    return (Math.round(n*100)/100).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  }

  function enterEdit(){
    if(editMode) return;
    editMode = true;
    toggleBtn.textContent = 'Exit Edit';
    // Turn text nodes into inputs
    editableBlocks.forEach(id=>{
      const el = document.getElementById(id);
      if(!el) return;
      if(el.querySelector('input,textarea')) return;
      let text = el.innerText.replace(/\u00A0/g,' ').trim();
      // For multiline notes: use textarea
      if(id==='notes_box'){
        const ta = document.createElement('textarea');
        ta.className='inline-input';
        ta.style.minHeight='70px';
        ta.value=text;
        el.innerHTML='';
        el.appendChild(ta);
      } else {
        const inp = document.createElement('input');
        inp.type='text';
        inp.className='inline-input';
        inp.value=text;
        el.innerHTML='';
        el.appendChild(inp);
      }
    });
    // Amount cells
    document.querySelectorAll('.amount-cell').forEach(cell=>{
      if(cell.querySelector('input')) return;
      const txt = cell.textContent.trim();
      const inp = document.createElement('input');
      inp.type='text';
      inp.value=txt;
      inp.className='inline-input';
      inp.style.textAlign='center';
      cell.innerHTML='';
      cell.appendChild(inp);
      if(cell.dataset.field==='amount'){
        inp.addEventListener('input',()=> { manualAmountOverride = true; recompute(); });
      } else {
        inp.addEventListener('input',()=> {
          if(cell.dataset.field==='km' || cell.dataset.field==='rate'){
            // Changing km or rate re-enables auto compute
            manualAmountOverride = false;
          }
          recompute();
        });
      }
    });
  }

  function exitEdit(){
    if(!editMode) return;
    // Commit values
    editableBlocks.forEach(id=>{
      const el = document.getElementById(id);
      if(!el) return;
      const input = el.querySelector('input,textarea');
      if(input){
        el.textContent = input.value.trim();
      }
    });
    document.querySelectorAll('.amount-cell').forEach(cell=>{
      const inp = cell.querySelector('input');
      if(!inp) return;
      cell.textContent = inp.value.trim();
    });
    editMode = false;
    toggleBtn.textContent = 'Edit';
    recompute();
  }

  function toggleEdit(){
    editMode?exitEdit():enterEdit();
  }

  function applyChanges(){
    if(editMode) exitEdit();
    recompute();
  }

  function recompute(){
    // Get numeric fields
    const getFieldVal = (f)=>{
      const cell = document.querySelector(`.amount-cell[data-field="${f}"]`);
      if(!cell) return 0;
      let v;
      if(editMode){
        const inp = cell.querySelector('input');
        v = inp ? inp.value : cell.textContent;
      } else {
        v = cell.textContent;
      }
      return asNumber(v, cell.dataset.type);
    };

    let km = getFieldVal('km');
    let rate = getFieldVal('rate');
    let qty = getFieldVal('qty');
    let advance = getFieldVal('advance');
    let balance = getFieldVal('balance');
    let amountCell = document.querySelector('.amount-cell[data-field="amount"]');

    if(!manualAmountOverride){
      const amount = km * rate; // or qty*rate depending on your business rule
      if(editMode && amountCell){
        let inp = amountCell.querySelector('input');
        if(inp) inp.value = format2(amount);
      } else if(amountCell){
        amountCell.textContent = format2(amount);
      }
    } else {
      // If manually overridden, ensure proper formatting (when leaving edit mode)
    }

    // Update total
    const amountFinal = asNumber(
      editMode
        ? (amountCell?.querySelector('input')?.value || amountCell?.textContent || '0')
        : (amountCell?.textContent || '0'),
      'float'
    );

    const totalEl = document.getElementById('total_amount');
    if(totalEl){
      totalEl.textContent = format2(amountFinal);
    }
  }

  async function waitForResources(timeoutMs=5000){
    if(document.fonts && document.fonts.ready){
      try { await Promise.race([document.fonts.ready,new Promise(r=>setTimeout(r,timeoutMs))]); } catch(e){}
    }
    const imgs = Array.from(document.images);
    await Promise.all(imgs.map(img=>img.complete?Promise.resolve():new Promise(res=>{
      const t=setTimeout(res,timeoutMs);
      img.addEventListener('load',()=>{clearTimeout(t);res();});
      img.addEventListener('error',()=>{clearTimeout(t);res();});
    })));
  }

  async function savePdf(){
    if(editMode) exitEdit();
    if(typeof html2pdf === 'undefined'){
      alert('PDF library not loaded.'); return;
    }
    const biltyNo = "<?php echo esc($bilty['bilty_no']); ?>".replace(/[^A-Za-z0-9_\-]/g,'_');
    savePdfBtn.disabled=true;
    const prevTxt = savePdfBtn.textContent;
    savePdfBtn.textContent='Generating...';

    progressOverlay.style.display='flex';
    progressBar.style.width='10%';
    progressText.textContent='Preparing...';

    try{
      await waitForResources();
      progressBar.style.width='30%';
      progressText.textContent='Rendering...';

      const element = document.getElementById('biltyRoot');
      const opt = {
        margin:[0,0,0,0],
        filename:'Bilty-'+biltyNo+'.pdf',
        image:{ type:'jpeg', quality:0.95 },
        html2canvas:{ scale:2, useCORS:true, allowTaint:false, backgroundColor:'#FFFFFF' },
        jsPDF:{ unit:'mm', format:'a5', orientation:'portrait' },
        pagebreak:{ mode:['css','legacy'] }
      };
      const worker = html2pdf().set(opt).from(element).toPdf();
      progressBar.style.width='60%';
      progressText.textContent='Composing...';
      const pdf = await worker.get('pdf');
      progressBar.style.width='80%';
      progressText.textContent='Encoding...';
      const blob = pdf.output('blob');

      const base64Data = await new Promise((resolve,reject)=>{
        const fr=new FileReader();
        fr.onload=()=>resolve(fr.result);
        fr.onerror=()=>reject(new Error('Blob read failed'));
        fr.readAsDataURL(blob);
      });

      progressBar.style.width='90%';
      progressText.textContent='Uploading...';

      const fd = new FormData();
      fd.append('bill_no', biltyNo); // reuse field name used by save_pdf.php
      fd.append('pdf_data', base64Data);

      const resp = await fetch('save_pdf.php',{method:'POST',body:fd});
      const data = await resp.json();
      if(!data.ok) throw new Error(data.error||'Upload failed');

      progressBar.style.width='100%';
      progressText.textContent='Saved!';
      alert('PDF saved on server: '+data.file);

      let linkBox = document.getElementById('serverPdfLinkBox');
      if(!linkBox){
        linkBox = document.createElement('div');
        linkBox.id='serverPdfLinkBox';
        linkBox.style.marginLeft='12px';
        linkBox.style.fontSize='13px';
        document.getElementById('controlBar')?.appendChild(linkBox);
      }
      linkBox.innerHTML='<a href="'+data.file+'" target="_blank" style="color:#2563eb;font-weight:600;text-decoration:underline;">Open Stored PDF</a>';

    }catch(e){
      console.error(e);
      alert('PDF save failed: '+(e?.message||e));
    }finally{
      setTimeout(()=>{ progressOverlay.style.display='none'; },600);
      savePdfBtn.disabled=false;
      savePdfBtn.textContent=prevTxt;
    }
  }

  function resetAll(){
    if(editMode) exitEdit();
    location.reload();
  }

  toggleBtn.addEventListener('click', toggleEdit);
  applyBtn.addEventListener('click', applyChanges);
  printBtn.addEventListener('click', () => {
    if(editMode) exitEdit();
    window.print();
  });
  savePdfBtn.addEventListener('click', savePdf);
  resetBtn.addEventListener('click', resetAll);

  // Auto print if ?auto=1
  (function(){
    const url = new URL(window.location.href);
    if(url.searchParams.get('auto') === '1'){
      setTimeout(()=>window.print(), 400);
    }
  })();

  // Initial compute
  recompute();
})();
</script>
</body>
</html>