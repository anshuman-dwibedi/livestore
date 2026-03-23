<?php
require_once __DIR__ . '/../../../core/bootstrap.php';
Auth::requireRole('admin', 'login.php');
$apiBase   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/inventory.php'), '/');
$storeBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/inventory.php')), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory — LiveStore Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<style>
  .main-content { padding:32px; }
  .page-topbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:10px; }
  .stock-input { width:80px; text-align:center; padding:6px 10px; }
  .low-row  { background:rgba(245,166,35,0.06); }
  .out-row  { background:rgba(255,92,106,0.06); }
</style>
</head>
<body class="dc-with-sidebar">

<aside class="dc-sidebar">
  <div class="dc-sidebar__logo">Live<span>Store</span></div>
  <a href="dashboard.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-activity dc-icon-sm"></i> Dashboard</a>
  <a href="products.php"  class="dc-sidebar__link"><i class="dc-icon dc-icon-package dc-icon-sm"></i> Products</a>
  <a href="orders.php"    class="dc-sidebar__link"><i class="dc-icon dc-icon-receipt dc-icon-sm"></i> Orders</a>
  <a href="inventory.php" class="dc-sidebar__link active"><i class="dc-icon dc-icon-clipboard dc-icon-sm"></i> Inventory</a>
  <a href="coupons.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-tag dc-icon-sm"></i> Coupons</a>
  <div class="dc-sidebar__section">Store</div>
  <a href="<?= $storeBase ?>/index.php" class="dc-sidebar__link" target="_blank"><i class="dc-icon dc-icon-home dc-icon-sm"></i> View Store</a>
  <a href="../api/logout.php" class="dc-sidebar__link" style="margin-top:auto;color:var(--dc-danger)"><i class="dc-icon dc-icon-log-out dc-icon-sm"></i> Logout</a>
</aside>

<main class="main-content">
  <div class="page-topbar">
    <h1 class="dc-h2">Inventory</h1>
    <div class="dc-flex dc-items-center" style="gap:12px">
      <span class="dc-badge dc-badge-warning" id="lowBadge"></span>
      <span class="dc-badge dc-badge-danger"  id="outBadge"></span>
      <div class="dc-live"><div class="dc-live__dot"></div><span>Live · every 5s</span></div>
    </div>
  </div>

  <!-- Stats -->
  <div class="dc-grid dc-grid-3" style="margin-bottom:24px">
    <div class="dc-stat">
      <div class="dc-stat__icon"><i class="dc-icon dc-icon-package dc-icon-md"></i></div>
      <div class="dc-stat__value" id="statTotal">—</div>
      <div class="dc-stat__label">Total SKUs</div>
    </div>
    <div class="dc-stat" style="border-left:3px solid var(--dc-warning)">
      <div class="dc-stat__icon" style="background:rgba(245,166,35,.15)"><i class="dc-icon dc-icon-alert-triangle dc-icon-md" style="color:var(--dc-warning)"></i></div>
      <div class="dc-stat__value" id="statLow" style="color:var(--dc-warning)">—</div>
      <div class="dc-stat__label">Low Stock</div>
    </div>
    <div class="dc-stat" style="border-left:3px solid var(--dc-danger)">
      <div class="dc-stat__icon" style="background:rgba(255,92,106,.15)"><i class="dc-icon dc-icon-x dc-icon-md" style="color:var(--dc-danger)"></i></div>
      <div class="dc-stat__value" id="statOut" style="color:var(--dc-danger)">—</div>
      <div class="dc-stat__label">Out of Stock</div>
    </div>
  </div>

  <!-- Filter -->
  <div class="dc-card-solid dc-flex dc-items-center" style="padding:14px 16px;margin-bottom:16px;gap:12px;flex-wrap:wrap">
    <input type="text" id="fSearch" class="dc-input" style="max-width:260px" placeholder="Search products…" oninput="filterTable()">
    <select id="fLevel" class="dc-select" style="width:160px" onchange="filterTable()">
      <option value="">All Stock Levels</option>
      <option value="out">Out of Stock</option>
      <option value="low">Low Stock</option>
      <option value="ok">In Stock</option>
    </select>
    <span class="dc-caption dc-text-dim" style="margin-left:auto">
      <i class="dc-icon dc-icon-refresh dc-icon-xs"></i> Updates every 5s
    </span>
  </div>

  <!-- Table -->
  <div class="dc-card-solid" style="padding:0;overflow:hidden">
    <div class="dc-table-wrap" style="border:none">
      <table class="dc-table" id="invTable">
        <thead>
          <tr>
            <th>Product</th><th>SKU</th><th>Category</th>
            <th>Stock</th><th>Low Threshold</th><th>Status</th><th style="width:70px">Save</th>
          </tr>
        </thead>
        <tbody id="invBody">
          <tr><td colspan="7" class="dc-text-center dc-text-dim" style="padding:40px">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script src="../../../core/ui/devcore.js"></script>
<script>
const API_PRODS = '<?= $storeBase ?>/api/products.php';
const API_LIVE  = '<?= $storeBase ?>/api/live.php';
let allProducts = [];

async function loadInventory() {
  const res = await DC.get(API_PRODS + '?per_page=100&sort=name');
  allProducts = res.data || [];
  renderTable(allProducts);
  updateStats(allProducts);
}

function updateStats(products) {
  const low = products.filter(p => p.stock > 0 && p.stock <= p.low_stock_threshold).length;
  const out = products.filter(p => p.stock === 0).length;
  document.getElementById('statTotal').textContent = products.length;
  document.getElementById('statLow').textContent   = low;
  document.getElementById('statOut').textContent   = out;
  document.getElementById('lowBadge').textContent  = low + ' low stock';
  document.getElementById('outBadge').textContent  = out + ' out of stock';
}

function renderTable(products) {
  const body = document.getElementById('invBody');
  body.innerHTML = '';
  if (!products.length) {
    body.innerHTML = `<tr><td colspan="7" class="dc-text-center dc-text-dim" style="padding:40px">No products</td></tr>`;
    return;
  }
  products.forEach(p => {
    const isOut = p.stock === 0;
    const isLow = !isOut && p.stock <= p.low_stock_threshold;
    const rowCls = isOut ? 'out-row' : isLow ? 'low-row' : '';
    const badge  = isOut
      ? '<span class="dc-badge dc-badge-danger">Out of Stock</span>'
      : isLow
      ? '<span class="dc-badge dc-badge-warning">Low Stock</span>'
      : '<span class="dc-badge dc-badge-success">In Stock</span>';

    body.insertAdjacentHTML('beforeend', `
      <tr class="${rowCls}" data-id="${p.id}" data-status="${isOut?'out':isLow?'low':'ok'}">
        <td>
          <div class="dc-flex dc-items-center" style="gap:10px">
            ${p.image_url
              ? `<img src="${p.image_url}" style="width:36px;height:36px;object-fit:cover;border-radius:var(--dc-radius);flex-shrink:0" onerror="this.style.display='none'">`
              : `<div style="width:36px;height:36px;background:var(--dc-bg-3);border-radius:var(--dc-radius);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="dc-icon dc-icon-image dc-icon-sm dc-text-dim"></i></div>`}
            <span style="font-weight:600;font-size:.88rem">${p.name}</span>
          </div>
        </td>
        <td><span style="font-family:monospace;font-size:.8rem">${p.sku}</span></td>
        <td class="dc-caption">${p.category_name}</td>
        <td>
          <input type="number" class="dc-input stock-input" id="stk-${p.id}"
                 value="${p.stock}" min="0" data-orig="${p.stock}" onchange="markDirty(${p.id})">
        </td>
        <td>
          <input type="number" class="dc-input stock-input" id="thr-${p.id}"
                 value="${p.low_stock_threshold}" min="1" onchange="markDirty(${p.id})">
        </td>
        <td id="st-${p.id}">${badge}</td>
        <td>
          <button class="dc-btn dc-btn-sm dc-btn-primary" id="sv-${p.id}" onclick="saveStock(${p.id})" disabled>
            <i class="dc-icon dc-icon-check dc-icon-sm"></i>
          </button>
        </td>
      </tr>
    `);
  });
}

function markDirty(id) { document.getElementById('sv-' + id).disabled = false; }

async function saveStock(id) {
  const stock     = parseInt(document.getElementById('stk-' + id).value);
  const threshold = parseInt(document.getElementById('thr-' + id).value);
  const btn       = document.getElementById('sv-' + id);
  DCForm.setLoading(btn, true);
  try {
    await DC.put(API_PRODS + '?id=' + id, { stock, low_stock_threshold: threshold });
    Toast.success('Stock updated');
    const idx = allProducts.findIndex(p => p.id == id);
    if (idx > -1) { allProducts[idx].stock = stock; allProducts[idx].low_stock_threshold = threshold; }
    updateStats(allProducts);
    DCForm.setLoading(btn, false);
    btn.disabled = true;
    // Update row style
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const isOut = stock === 0, isLow = !isOut && stock <= threshold;
    if (row) {
      row.className   = isOut ? 'out-row' : isLow ? 'low-row' : '';
      row.dataset.status = isOut ? 'out' : isLow ? 'low' : 'ok';
    }
    document.getElementById('st-' + id).innerHTML = isOut
      ? '<span class="dc-badge dc-badge-danger">Out of Stock</span>'
      : isLow ? '<span class="dc-badge dc-badge-warning">Low Stock</span>'
      : '<span class="dc-badge dc-badge-success">In Stock</span>';
  } catch(err) {
    Toast.error(err.message || 'Failed to save');
    DCForm.setLoading(btn, false);
  }
}

function filterTable() {
  const q   = document.getElementById('fSearch').value.toLowerCase();
  const lvl = document.getElementById('fLevel').value;
  document.querySelectorAll('#invBody tr[data-id]').forEach(row => {
    const match = (!q || row.textContent.toLowerCase().includes(q)) && (!lvl || row.dataset.status === lvl);
    row.style.display = match ? '' : 'none';
  });
}

// Live poll every 5 seconds
const livePoller = new LivePoller(API_LIVE, res => {
  (res.data?.stocks || []).forEach(item => {
    const stkEl = document.getElementById('stk-' + item.product_id);
    const svBtn = document.getElementById('sv-'  + item.product_id);
    if (!stkEl || !svBtn?.disabled) return; // don't overwrite unsaved edits
    stkEl.value = item.stock;
    const threshold = parseInt(document.getElementById('thr-' + item.product_id)?.value || 5);
    const isOut = item.stock === 0, isLow = !isOut && item.stock <= threshold;
    const row   = document.querySelector(`tr[data-id="${item.product_id}"]`);
    if (row) {
      row.className      = isOut ? 'out-row' : isLow ? 'low-row' : '';
      row.dataset.status = isOut ? 'out' : isLow ? 'low' : 'ok';
    }
    const stCell = document.getElementById('st-' + item.product_id);
    if (stCell) stCell.innerHTML = isOut
      ? '<span class="dc-badge dc-badge-danger">Out of Stock</span>'
      : isLow ? '<span class="dc-badge dc-badge-warning">Low Stock</span>'
      : '<span class="dc-badge dc-badge-success">In Stock</span>';
    const idx = allProducts.findIndex(p => p.id == item.product_id);
    if (idx > -1) allProducts[idx].stock = item.stock;
  });
  updateStats(allProducts);
}, 5000);

loadInventory();
livePoller.start();
</script>
</body>
</html>
