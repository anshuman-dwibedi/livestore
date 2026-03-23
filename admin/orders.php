<?php
require_once __DIR__ . '/../../../core/bootstrap.php';
Auth::requireRole('admin', 'login.php');
$apiBase   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/orders.php'), '/');
$storeBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/orders.php')), '/');
$storeHost = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders — LiveStore Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<style>
  .main-content { padding:32px; }
  .page-topbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:10px; }
</style>
</head>
<body class="dc-with-sidebar">

<aside class="dc-sidebar">
  <div class="dc-sidebar__logo">Live<span>Store</span></div>
  <a href="dashboard.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-activity dc-icon-sm"></i> Dashboard</a>
  <a href="products.php"  class="dc-sidebar__link"><i class="dc-icon dc-icon-package dc-icon-sm"></i> Products</a>
  <a href="orders.php"    class="dc-sidebar__link active"><i class="dc-icon dc-icon-receipt dc-icon-sm"></i> Orders</a>
  <a href="inventory.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-clipboard dc-icon-sm"></i> Inventory</a>
  <a href="coupons.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-tag dc-icon-sm"></i> Coupons</a>
  <div class="dc-sidebar__section">Store</div>
  <a href="<?= $storeBase ?>/index.php" class="dc-sidebar__link" target="_blank"><i class="dc-icon dc-icon-home dc-icon-sm"></i> View Store</a>
  <a href="../api/logout.php" class="dc-sidebar__link" style="margin-top:auto;color:var(--dc-danger)"><i class="dc-icon dc-icon-log-out dc-icon-sm"></i> Logout</a>
</aside>

<main class="main-content">
  <div class="page-topbar">
    <h1 class="dc-h2">Orders</h1>
    <span class="dc-caption dc-text-dim" id="orderCount"></span>
  </div>

  <!-- Filters -->
  <div class="dc-card-solid" style="padding:16px;margin-bottom:20px">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <div class="dc-form-group" style="flex:1;min-width:200px">
        <label class="dc-label-field">Search</label>
        <input type="text" id="fSearch" class="dc-input" placeholder="Name, email, token…" oninput="debounce()">
      </div>
      <div class="dc-form-group" style="width:160px">
        <label class="dc-label-field">Status</label>
        <select id="fStatus" class="dc-select" onchange="loadOrders()">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="shipped">Shipped</option>
          <option value="delivered">Delivered</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="loadOrders()">
        <i class="dc-icon dc-icon-refresh dc-icon-sm"></i> Refresh
      </button>
    </div>
  </div>

  <!-- Table -->
  <div class="dc-card-solid" style="padding:0;overflow:hidden">
    <div class="dc-table-wrap" style="border:none">
      <table class="dc-table">
        <thead>
          <tr>
            <th>Token</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="ordersBody">
          <tr><td colspan="7" class="dc-text-center dc-text-dim" style="padding:40px">Loading…</td></tr>
        </tbody>
      </table>
    </div>
    <div id="pagination" class="dc-flex-center dc-gap-sm" style="padding:16px"></div>
  </div>
</main>

<!-- Order detail modal -->
<div class="dc-modal-overlay" id="orderModal">
  <div class="dc-modal" style="max-width:700px">
    <div class="dc-modal__header">
      <div class="dc-h3">Order Detail</div>
      <button class="dc-modal__close" data-modal-close="orderModal">
        <i class="dc-icon dc-icon-x dc-icon-md"></i>
      </button>
    </div>
    <div id="orderModalBody" class="dc-text-muted">Loading…</div>
    <div class="dc-flex dc-items-center dc-border-top" style="gap:10px;margin-top:20px;padding-top:16px;flex-wrap:wrap">
      <span class="dc-label-field" style="white-space:nowrap">Update status:</span>
      <select id="statusSelect" class="dc-select" style="flex:1;min-width:140px"></select>
      <button class="dc-btn dc-btn-primary dc-btn-sm" onclick="updateStatus()">
        <i class="dc-icon dc-icon-check dc-icon-sm"></i> Save
      </button>
      <button class="dc-btn dc-btn-ghost dc-btn-sm" data-modal-close="orderModal">Close</button>
    </div>
  </div>
</div>

<script src="../../../core/ui/devcore.js"></script>
<script>
const API_ORDERS = '<?= $storeBase ?>/api/orders.php';
const STORE_BASE = '<?= $storeBase ?>';
const STORE_HOST = '<?= $storeHost ?>';
const STATUS_CLS = { pending:'dc-badge-warning', processing:'dc-badge-accent', shipped:'dc-badge-info', delivered:'dc-badge-success', cancelled:'dc-badge-danger' };
const STATUSES   = ['pending','processing','shipped','delivered','cancelled'];
let curPage = 1, curOrderId = null, debTimer;

function debounce() { clearTimeout(debTimer); debTimer = setTimeout(loadOrders, 380); }

async function loadOrders(page = 1) {
  curPage = page;
  const params = new URLSearchParams({ page });
  const s = document.getElementById('fSearch').value, st = document.getElementById('fStatus').value;
  if (s)  params.set('search', s);
  if (st) params.set('status', st);

  const res  = await DC.get(API_ORDERS + '?' + params);
  const tb   = document.getElementById('ordersBody');
  tb.innerHTML = '';
  document.getElementById('orderCount').textContent = res.meta ? res.meta.total + ' orders' : '';

  if (!res.data?.length) {
    tb.innerHTML = `<tr><td colspan="7" class="dc-text-center dc-text-dim" style="padding:40px">No orders found</td></tr>`;
    document.getElementById('pagination').innerHTML = '';
    return;
  }

  res.data.forEach(o => {
    const cls    = STATUS_CLS[o.status] || 'dc-badge-neutral';
    const qrUrl  = `${STORE_HOST}${STORE_BASE}/order-confirmation.php?order=${o.token}`;
    const qrSrc  = `https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=${encodeURIComponent(qrUrl)}`;
    tb.insertAdjacentHTML('beforeend', `
      <tr>
        <td style="display:flex;align-items:center;gap:8px">
          <span style="font-family:monospace;font-size:.8rem;color:var(--dc-accent-2)">${o.token}</span>
          <img src="${qrSrc}" width="28" height="28" style="border-radius:3px;flex-shrink:0" title="QR: ${o.token}">
        </td>
        <td>
          <div style="font-weight:600">${o.customer_name}</div>
          <div class="dc-caption">${o.customer_email}</div>
        </td>
        <td>${o.items_count}</td>
        <td style="font-weight:700">$${fmt(o.total)}${o.discount>0?`<div class="dc-caption" style="color:var(--dc-success)">−$${fmt(o.discount)}</div>`:''}</td>
        <td><span class="dc-badge ${cls}">${o.status}</span></td>
        <td class="dc-caption">${new Date(o.created_at).toLocaleDateString()}</td>
        <td>
          <button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="viewOrder(${o.id},'${o.token}')">
            <i class="dc-icon dc-icon-eye dc-icon-sm"></i>
          </button>
        </td>
      </tr>
    `);
  });

  const pages = res.meta?.total_pages || 1;
  const pg    = document.getElementById('pagination');
  pg.innerHTML = '';
  for (let i = 1; i <= pages; i++)
    pg.insertAdjacentHTML('beforeend', `<button class="dc-btn dc-btn-sm ${i===curPage?'dc-btn-primary':'dc-btn-ghost'}" onclick="loadOrders(${i})">${i}</button>`);
}

async function viewOrder(id, token) {
  curOrderId = id;
  document.getElementById('orderModalBody').innerHTML = '<div class="dc-caption dc-text-center dc-text-dim" style="padding:20px">Loading…</div>';
  Modal.open('orderModal');

  const res = await DC.get(API_ORDERS + '?token=' + token);
  const o   = res.data;
  const cls = STATUS_CLS[o.status] || 'dc-badge-neutral';
  const qrUrl = `${STORE_HOST}${STORE_BASE}/order-confirmation.php?order=${o.token}`;

  document.getElementById('orderModalBody').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 160px;gap:20px;margin-bottom:16px">
      <div style="font-size:.88rem;display:flex;flex-direction:column;gap:6px">
        <div><strong>Token:</strong> <span style="font-family:monospace">${o.token}</span></div>
        <div><strong>Customer:</strong> ${o.customer_name}</div>
        <div><strong>Email:</strong> ${o.customer_email}</div>
        <div><strong>Phone:</strong> ${o.customer_phone}</div>
        <div><strong>Address:</strong> ${o.address}, ${o.city}</div>
        <div><strong>Date:</strong> ${new Date(o.created_at).toLocaleString()}</div>
        <div><strong>Status:</strong> <span class="dc-badge ${cls}">${o.status}</span></div>
      </div>
      <div style="text-align:center">
        <div class="dc-qr-card" style="margin:0 auto;padding:12px">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=${encodeURIComponent(qrUrl)}" width="130" height="130">
        </div>
        <div class="dc-caption dc-text-dim" style="margin-top:6px">Scan to view</div>
      </div>
    </div>
    <div class="dc-table-wrap">
      <table class="dc-table">
        <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
        <tbody>
          ${o.items.map(i => `<tr>
            <td>${i.product_name}</td>
            <td>${i.quantity}</td>
            <td>$${fmt(i.unit_price)}</td>
            <td style="font-weight:700">$${fmt(i.unit_price*i.quantity)}</td>
          </tr>`).join('')}
        </tbody>
        <tfoot>
          ${o.discount>0?`<tr><td colspan="3" style="text-align:right;color:var(--dc-text-2)">Discount</td><td style="color:var(--dc-success)">−$${fmt(o.discount)}</td></tr>`:''}
          <tr><td colspan="3" style="text-align:right;font-weight:700">Total</td><td style="font-weight:700">$${fmt(o.total)}</td></tr>
        </tfoot>
      </table>
    </div>
  `;

  const sel = document.getElementById('statusSelect');
  sel.innerHTML = STATUSES.map(s => `<option value="${s}" ${s===o.status?'selected':''}>${s.charAt(0).toUpperCase()+s.slice(1)}</option>`).join('');
}

async function updateStatus() {
  if (!curOrderId) return;
  const status = document.getElementById('statusSelect').value;
  try {
    await DC.put(API_ORDERS + '?id=' + curOrderId, { status });
    Toast.success('Status updated to ' + status);
    Modal.close('orderModal');
    loadOrders(curPage);
  } catch(err) { Toast.error(err.message); }
}

function fmt(n) { return Number(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

const sp = new URLSearchParams(window.location.search);
if (sp.get('search')) document.getElementById('fSearch').value = sp.get('search');
loadOrders();
</script>
</body>
</html>
