<style>
  :root {
    --main-color: #97113a;
    --main-color-hover: #b31547;
    --main-color-light: #fff0f5;
  }
  body.bg-page {
    background:
      radial-gradient(1200px 420px at 50% 0%, rgba(153, 27, 65, 0.10), transparent 60%),
      #efe6f3;
  }
  .theme-surface {
    position: relative;
    overflow: hidden;
    background:
      linear-gradient(0deg, rgba(255,255,255,.97), rgba(255,255,255,.93)),
      radial-gradient(900px 280px at -10% -10%, rgba(151, 17, 58, .09), transparent 60%),
      radial-gradient(900px 280px at 110% -10%, rgba(151, 17, 58, .07), transparent 60%);
    border: none;
    box-shadow:
      0 10px 30px rgba(151,17,58,.11),
      0 30px 60px rgba(151,17,58,.18);
    border-radius: 28px;
  }
  .bilty-search-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-top: 2.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }
  .bilty-search-input {
    flex: 1 1 200px;
    min-width: 0;
    font-size: 1.1rem;
    border-radius: 12px;
    border: 1px solid #cbd5e1;
    padding: 0.9rem 1.2rem;
    outline: none;
    transition: border-color .14s;
    background: #fff;
  }
  .bilty-search-input:focus {
    border-color: var(--main-color);
  }
  .bilty-search-btn {
    background: var(--main-color);
    color: #fff;
    font-weight: 700;
    border-radius: 12px;
    border: none;
    padding: 0.9rem 2rem;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: .8rem;
    transition: background .14s, transform .12s;
    box-shadow: 0 2px 8px rgba(151,17,58,.07);
  }
  .bilty-search-btn:hover,
  .bilty-search-btn:focus {
    background: var(--main-color-hover);
    transform: translateY(-1px) scale(1.03);
  }
  .bilty-table-wrap {
    width: 100%;
    overflow-x: auto;
    margin-top: 1.2rem;
    margin-bottom: 1.2rem;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 2px 10px rgba(151,17,58,.10);
    position: relative;
  }
  .bilty-table {
    min-width: 950px;
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 18px;
    overflow: hidden;
    background: #fff;
  }
  .bilty-table th, .bilty-table td {
    padding: 1.1rem 1.2rem;
    border-bottom: 1px solid #edf2f7;
    font-size: 1.08rem;
    text-align: left;
    white-space: nowrap;
  }
  .bilty-table th {
    background: var(--main-color);
    color: #fff;
    font-weight: 700;
    border-top: none;
  }
  .bilty-table tr:last-child td {
    border-bottom: none;
  }
  .bilty-table tr {
    background: #fff;
  }
  .print-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.6em;
    background: var(--main-color);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1.08rem;
    padding: 0.8rem 1.5rem;
    margin: 1rem 0 0 0;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(151,17,58,.11);
    transition: background .13s, transform .12s;
  }
  .print-btn:hover,
  .print-btn:focus {
    background: var(--main-color-hover);
    transform: translateY(-1px) scale(1.03);
  }
  .main-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.3rem;
    margin-top: 1.6rem;
  }
  .action-btn {
    display: flex;
    align-items: center;
    gap: 1.1rem;
    background: var(--main-color);
    color: #fff;
    border-radius: 18px;
    padding: 1.15rem 1.3rem;
    font-size: 1.18rem;
    font-weight: 700;
    border: none;
    box-shadow: 0 2px 10px rgba(151,17,58,.10);
    transition: transform .13s, box-shadow .22s, background .18s;
    text-decoration: none;
  }
  .action-btn:hover,
  .action-btn:focus {
    background: var(--main-color-hover);
    transform: translateY(-2px) scale(1.02);
    color: #fff;
    box-shadow: 0 8px 24px rgba(151,17,58,.14);
  }
  .icon-chip {
    width: 2.7rem;
    height: 2.7rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: .7rem;
    background: #97113a;
    font-size: 1.6rem;
  }
  @media (max-width: 640px) {
    .main-actions {
      grid-template-columns: 1fr;
      gap: 1rem;
    }
    .action-btn {
      font-size: 1rem;
      padding: .95rem 1rem;
    }
  }
</style>

<script>
  function printBiltyExternal(biltyId) {
    if (!biltyId) return;
    window.open('/bilty/print/' + biltyId, '_blank');
  }
</script>

<main class="max-w-6xl mx-auto py-10 px-4 md:px-6">
  <section class="theme-surface rounded-2xl p-6 md:p-10">
    <h1 class="text-3xl md:text-5xl font-extrabold" style="color:var(--main-color);font-family:'Montserrat',sans-serif;">
      Bahar Ali - Bilty Management System
    </h1>
    
    <!-- Bilty number search field -->
    <form class="bilty-search-row" action="/" method="post" autocomplete="off">
      <input type="text" name="bilty_no" class="bilty-search-input" placeholder="Enter Bilty Number..." required value="" />
      <button type="submit" class="bilty-search-btn">
        <i class="fa-solid fa-magnifying-glass"></i>
        Open Bilty
      </button>
    </form>
    
    <?php if (isset($bilty_row) && $bilty_row): ?>
      <div class="bilty-table-wrap" id="bilty-table-print">
        <table class="bilty-table">
          <thead>
            <tr>
              <th>Bilty No</th>
              <th>Date</th>
              <th>Company</th>
              <th>Vehicle No</th>
              <th>Vehicle Type</th>
              <th>Driver</th>
              <th>Sender Name</th>
              <th>From</th>
              <th>To</th>
              <th>Qty</th>
              <th>KM</th>
              <th>Rate</th>
              <th>Amount</th>
              <th>Advance</th>
              <th>Balance</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><?php echo htmlspecialchars($bilty_row['bilty_no']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['date']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['company_name']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['vehicle_no']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['vehicle_type']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['driver_name']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['sender_name']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['from_city']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['to_city']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['qty']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['km']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['rate']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['amount']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['advance']); ?></td>
              <td><?php echo htmlspecialchars($bilty_row['balance']); ?></td>
              <td><?php echo nl2br(htmlspecialchars($bilty_row['details'])); ?></td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <button type="button" class="print-btn" id="view_bilty_print" onclick="printBiltyExternal(<?php echo (int)$found_bilty_id; ?>)">
        <i class="fa-solid fa-print"></i> Print
      </button>
    <?php elseif (isset($bilty_no) && $bilty_no !== ''): ?>
      <div style="color:#97113a; font-weight:500; margin-top:1rem;">
        No bilty found for number <b><?php echo htmlspecialchars($bilty_no); ?></b>.
      </div>
    <?php endif; ?>

    <div class="main-actions">
      <!-- Add New Bilty -->
      <a href="/bilty/add" class="action-btn" aria-label="Add New Bilty">
        <span class="icon-chip">
          <i class="fa-solid fa-truck-fast"></i>
        </span>
        <span>
          Add New Bilty
          <div style="font-weight:400;font-size:.95rem;opacity:.85;">Create a bilty quickly</div>
        </span>
        <span class="ml-auto">
          <i class="fa-solid fa-arrow-right-long"></i>
        </span>
      </a>
      
      <!-- View Bilties -->
      <a href="/bilty" class="action-btn" aria-label="View Bilties">
        <span class="icon-chip">
          <i class="fa-solid fa-rectangle-list"></i>
        </span>
        <span>
          View Bilties
          <div style="font-weight:400;font-size:.95rem;opacity:.85;">Browse all bilties</div>
        </span>
        <span class="ml-auto">
          <i class="fa-solid fa-arrow-right-long"></i>
        </span>
      </a>
    </div>
  </section>
  <div class="h-16"></div>
</main>
