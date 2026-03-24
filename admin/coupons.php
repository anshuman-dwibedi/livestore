<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';
Auth::requireRole('admin', 'login.php');
$apiBase   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/coupons.php'), '/');
$storeBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/coupons.php')), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Coupons — LiveStore Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<style>
  .main-content { padding:32px; }
  .page-topbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:10px; }
  .uses-bar { height:5px;background:var(--dc-bg-3);border-radius:3px;margin-top:5px;min-width:70px; }
  .uses-fill { height:100%;background:var(--dc-accent);border-radius:3px;transition:width .4s; }
</style>
</head>
<body class="dc-with-sidebar">

<aside class="dc-sidebar">
  <div class="dc-sidebar__logo">Live<span>Store</span></div>
  <a href="dashboard.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-activity dc-icon-sm"></i> Dashboard</a>
  <a href="products.php"  class="dc-sidebar__link"><i class="dc-icon dc-icon-package dc-icon-sm"></i> Products</a>
  <a href="orders.php"    class="dc-sidebar__link"><i class="dc-icon dc-icon-receipt dc-icon-sm"></i> Orders</a>
  <a href="inventory.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-clipboard dc-icon-sm"></i> Inventory</a>
  <a href="coupons.php"   class="dc-sidebar__link active"><i class="dc-icon dc-icon-tag dc-icon-sm"></i> Coupons</a>
  <div class="dc-sidebar__section">Store</div>
  <a href="<?= $storeBase ?>/index.php" class="dc-sidebar__link" target="_blank"><i class="dc-icon dc-icon-home dc-icon-sm"></i> View Store</a>
  <a href="../api/logout.php" class="dc-sidebar__link" style="margin-top:auto;color:var(--dc-danger)"><i class="dc-icon dc-icon-log-out dc-icon-sm"></i> Logout</a>
</aside>

<main class="main-content">
  <div class="page-topbar">
    <h1 class="dc-h2">Coupons</h1>
    <button class="dc-btn dc-btn-primary" onclick="Modal.open('couponModal')">
      <i class="dc-icon dc-icon-plus dc-icon-sm"></i> New Coupon
    </button>
  </div>

  <div class="dc-card-solid" style="padding:0;overflow:hidden">
    <div class="dc-table-wrap" style="border:none">
      <table class="dc-table">
        <thead>
          <tr><th>Code</th><th>Type / Value</th><th>Min Order</th><th>Usage</th><th>Expires</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody id="couponsBody">
          <tr><td colspan="7" class="dc-text-center dc-text-dim" style="padding:40px">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal -->
<div class="dc-modal-overlay" id="couponModal">
  <div class="dc-modal" style="max-width:480px">
    <div class="dc-modal__header">
      <div class="dc-h3">New Coupon</div>
      <button class="dc-modal__close" data-modal-close="couponModal">
        <i class="dc-icon dc-icon-x dc-icon-md"></i>
      </button>
    </div>
    <div class="dc-flex dc-flex-col" style="gap:14px">
      <div class="dc-grid dc-grid-2" style="gap:14px">
        <div class="dc-form-group" style="grid-column:1/-1">
          <label class="dc-label-field">Coupon Code *</label>
          <input type="text" id="fCode" class="dc-input" placeholder="SAVE10" style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Type *</label>
          <select id="fType" class="dc-select">
            <option value="percent">Percent (%)</option>
            <option value="fixed">Fixed ($)</option>
          </select>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Value *</label>
          <input type="number" id="fValue" class="dc-input" step="0.01" min="0.01" placeholder="10">
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Min Order ($)</label>
          <input type="number" id="fMinOrder" class="dc-input" step="0.01" min="0" value="0">
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Uses Limit</label>
          <input type="number" id="fUsesLimit" class="dc-input" min="1" placeholder="Unlimited">
        </div>
        <div class="dc-form-group" style="grid-column:1/-1">
          <label class="dc-label-field">Expiry Date</label>
          <input type="datetime-local" id="fExpiry" class="dc-input">
        </div>
      </div>
      <div class="dc-flex" style="justify-content:flex-end;gap:10px">
        <button class="dc-btn dc-btn-ghost" data-modal-close="couponModal">Cancel</button>
        <button class="dc-btn dc-btn-primary" onclick="saveCoupon()">
          <i class="dc-icon dc-icon-plus dc-icon-sm"></i> Create
        </button>
      </div>
    </div>
  </div>
</div>

<script src="../../../core/ui/devcore.js"></script>
<script>
const API_COUPONS = '<?= $storeBase ?>/api/coupons.php';

function expiryLabel(exp) {
  if (!exp) return '<span class="dc-caption dc-text-dim">Never</span>';
  const d    = new Date(exp), now = new Date();
  const days = (d - now) / 86400000;
  if (days < 0)  return '<span class="dc-caption" style="color:var(--dc-danger)">Expired</span>';
  if (days < 3)  return `<span class="dc-caption" style="color:var(--dc-warning)"><i class="dc-icon dc-icon-alert-triangle dc-icon-xs"></i> ${Math.ceil(days)}d left</span>`;
  return `<span class="dc-caption">${d.toLocaleDateString()}</span>`;
}

async function loadCoupons() {
  const res  = await DC.get(API_COUPONS);
  const body = document.getElementById('couponsBody');
  body.innerHTML = '';
  if (!res.data?.length) {
    body.innerHTML = `<tr><td colspan="7" class="dc-text-center dc-text-dim" style="padding:40px">No coupons yet</td></tr>`;
    return;
  }
  res.data.forEach(c => {
    const valLabel = c.type === 'percent' ? `<strong>${c.value}% off</strong>` : `<strong>$${fmt(c.value)} off</strong>`;
    const limit    = c.uses_limit, count = c.uses_count;
    const pct      = limit ? Math.min(100, Math.round((count/limit)*100)) : 0;
    const usage    = limit
      ? `${count}/${limit} <div class="uses-bar"><div class="uses-fill" style="width:${pct}%"></div></div>`
      : `${count} <span class="dc-caption">(unlimited)</span>`;

    body.insertAdjacentHTML('beforeend', `
      <tr>
        <td><span style="font-family:monospace;font-weight:700;font-size:.9rem;color:var(--dc-accent-2)">${c.code}</span></td>
        <td>${valLabel}<div class="dc-caption">${c.type}</div></td>
        <td>${c.min_order > 0 ? '$' + fmt(c.min_order) : '<span class="dc-caption dc-text-dim">—</span>'}</td>
        <td>${usage}</td>
        <td>${expiryLabel(c.expires_at)}</td>
        <td>${c.active ? '<span class="dc-badge dc-badge-success">Active</span>' : '<span class="dc-badge dc-badge-neutral">Inactive</span>'}</td>
        <td class="dc-flex" style="gap:6px">
          <button class="dc-btn dc-btn-sm ${c.active?'dc-btn-ghost':'dc-btn-success'}" onclick="toggleActive(${c.id},${c.active?0:1})">
            <i class="dc-icon dc-icon-${c.active?'x':'check'} dc-icon-sm"></i>
          </button>
          <button class="dc-btn dc-btn-sm dc-btn-danger" onclick="deleteCoupon(${c.id},'${c.code}')">
            <i class="dc-icon dc-icon-trash dc-icon-sm"></i>
          </button>
        </td>
      </tr>
    `);
  });
}

async function saveCoupon() {
  const body = {
    code:       document.getElementById('fCode').value.trim().toUpperCase(),
    type:       document.getElementById('fType').value,
    value:      document.getElementById('fValue').value,
    min_order:  document.getElementById('fMinOrder').value || '0',
    uses_limit: document.getElementById('fUsesLimit').value || null,
    expires_at: document.getElementById('fExpiry').value || null,
  };
  if (!body.code || !body.value) { Toast.error('Code and value required'); return; }
  try {
    await DC.post(API_COUPONS + '?action=create', body);
    Toast.success('Coupon created');
    Modal.close('couponModal');
    loadCoupons();
    ['fCode','fValue','fMinOrder','fUsesLimit','fExpiry'].forEach(id => document.getElementById(id).value = '');
  } catch(err) { Toast.error(err.message || 'Failed to create'); }
}

async function toggleActive(id, active) {
  try {
    await DC.put(API_COUPONS + '?id=' + id, { active });
    Toast.success(active ? 'Coupon enabled' : 'Coupon disabled');
    loadCoupons();
  } catch(err) { Toast.error(err.message); }
}

async function deleteCoupon(id, code) {
  if (!confirm(`Delete coupon "${code}"?`)) return;
  try {
    await DC.delete(API_COUPONS + '?id=' + id);
    Toast.success('Coupon deleted');
    loadCoupons();
  } catch(err) { Toast.error(err.message); }
}

function fmt(n) { return Number(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
loadCoupons();
</script>
</body>
</html>
