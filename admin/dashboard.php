<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';
Auth::requireRole('admin', 'login.php');

$apiBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/dashboard.php'), '/');
$storeBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/dashboard.php')), '/');
// e.g. /ecommerce-live-store/admin  → storeBase = /ecommerce-live-store
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — LiveStore Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
  .main-content { padding:32px; }
  .page-topbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:10px; }
</style>
</head>
<body class="dc-with-sidebar">

<aside class="dc-sidebar">
  <div class="dc-sidebar__logo">
    Live<span>Store</span>
  </div>
  <a href="dashboard.php" class="dc-sidebar__link active">
    <i class="dc-icon dc-icon-activity dc-icon-sm"></i> Dashboard
  </a>
  <a href="products.php" class="dc-sidebar__link">
    <i class="dc-icon dc-icon-package dc-icon-sm"></i> Products
  </a>
  <a href="orders.php" class="dc-sidebar__link">
    <i class="dc-icon dc-icon-receipt dc-icon-sm"></i> Orders
  </a>
  <a href="inventory.php" class="dc-sidebar__link">
    <i class="dc-icon dc-icon-clipboard dc-icon-sm"></i> Inventory
  </a>
  <a href="coupons.php" class="dc-sidebar__link">
    <i class="dc-icon dc-icon-tag dc-icon-sm"></i> Coupons
  </a>
  <div class="dc-sidebar__section">Store</div>
  <a href="<?= $storeBase ?>/index.php" class="dc-sidebar__link" target="_blank">
    <i class="dc-icon dc-icon-home dc-icon-sm"></i> View Store
  </a>
  <a href="../api/logout.php" class="dc-sidebar__link" style="margin-top:auto;color:var(--dc-danger)">
    <i class="dc-icon dc-icon-log-out dc-icon-sm"></i> Logout
  </a>
</aside>

<main class="main-content">
  <div class="page-topbar">
    <h1 class="dc-h2">Dashboard</h1>
    <div class="dc-flex dc-items-center" style="gap:12px">
      <div class="dc-live">
        <div class="dc-live__dot"></div>
        <span>Live</span>
      </div>
      <span class="dc-caption dc-text-dim" id="lastUpdated"></span>
    </div>
  </div>

  <!-- KPI stats -->
  <div class="dc-grid dc-grid-4" style="margin-bottom:28px" id="kpiGrid">
    <div class="dc-stat">
      <div class="dc-stat__icon"><i class="dc-icon dc-icon-dollar dc-icon-md"></i></div>
      <div class="dc-stat__value" id="kpiRevToday">—</div>
      <div class="dc-stat__label">Revenue Today</div>
    </div>
    <div class="dc-stat">
      <div class="dc-stat__icon"><i class="dc-icon dc-icon-receipt dc-icon-md"></i></div>
      <div class="dc-stat__value" id="kpiOrdsToday">—</div>
      <div class="dc-stat__label">Orders Today</div>
    </div>
    <div class="dc-stat">
      <div class="dc-stat__icon"><i class="dc-icon dc-icon-activity dc-icon-md"></i></div>
      <div class="dc-stat__value" id="kpiAvg">—</div>
      <div class="dc-stat__label">Avg Order Value</div>
    </div>
    <div class="dc-stat">
      <div class="dc-stat__icon"><i class="dc-icon dc-icon-package dc-icon-md"></i></div>
      <div class="dc-stat__value" id="kpiProds">—</div>
      <div class="dc-stat__label">Total Products</div>
    </div>
  </div>

  <!-- Charts row -->
  <div class="dc-grid dc-grid-2" style="margin-bottom:24px">
    <div class="dc-card" style="padding:24px">
      <div class="dc-h4" style="margin-bottom:16px">Revenue &amp; Orders — Last 30 Days</div>
      <div style="height:280px"><canvas id="revenueChart"></canvas></div>
    </div>
    <div class="dc-card" style="padding:24px">
      <div class="dc-h4" style="margin-bottom:16px">Orders by Status</div>
      <div style="height:280px"><canvas id="statusChart"></canvas></div>
    </div>
  </div>

  <!-- Top products -->
  <div class="dc-card" style="padding:24px;margin-bottom:24px">
    <div class="dc-h4" style="margin-bottom:16px">Top 10 Products by Units Sold</div>
    <div style="height:280px"><canvas id="topChart"></canvas></div>
  </div>

  <!-- Live feed -->
  <div class="dc-card" style="padding:24px">
    <div class="dc-flex-between dc-mb">
      <div class="dc-h4">Live Order Feed</div>
      <span class="dc-badge dc-badge-accent" id="feedCount"></span>
    </div>
    <div class="dc-table-wrap">
      <table class="dc-table">
        <thead>
          <tr>
            <th>Token</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Time</th>
          </tr>
        </thead>
        <tbody id="feedBody">
          <tr><td colspan="6" class="dc-text-center dc-text-dim">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script src="../../../core/ui/devcore.js"></script>
<script src="../../../core/utils/helpers.js"></script>
<script>
const API_BASE   = '<?= $apiBase ?>';
const STORE_BASE = '<?= $storeBase ?>';
const STATUS_CLS = { pending:'dc-badge-warning', processing:'dc-badge-accent', shipped:'dc-badge-info', delivered:'dc-badge-success', cancelled:'dc-badge-danger' };
let revChart, statusChart, topChart;

async function loadAnalytics() {
  const res  = await DC.get(`${STORE_BASE}/api/analytics.php`);
  const data = res.data;

  document.getElementById('kpiRevToday').textContent = '$' + fmt(data.kpi.revenue_today);
  document.getElementById('kpiOrdsToday').textContent = data.kpi.orders_today;
  document.getElementById('kpiAvg').textContent       = '$' + fmt(data.kpi.avg_order_value);
  document.getElementById('kpiProds').textContent     = data.kpi.total_products;

  // Build 30-day label array
  const today  = new Date();
  const labels = [];
  for (let i = 29; i >= 0; i--) {
    const d = new Date(today); d.setDate(d.getDate() - i);
    labels.push(d.toISOString().slice(5,10));
  }
  const revMap = {}, ordMap = {};
  data.revenue_by_day.forEach(r => revMap[r.date.slice(5,10)] = parseFloat(r.total));
  data.orders_by_day.forEach(r  => ordMap[r.date.slice(5,10)] = parseInt(r.count));
  const revData = labels.map(l => revMap[l] || 0);
  const ordData = labels.map(l => ordMap[l] || 0);

  if (revChart) revChart.destroy();
  revChart = DCChart.line('revenueChart', labels, [
    { label:'Revenue ($)', data:revData, yAxisID:'y',  borderColor:'#6c63ff', backgroundColor:'rgba(108,99,255,0.08)', fill:true, tension:.4 },
    { label:'Orders',      data:ordData, yAxisID:'y1', borderColor:'#22d3a0', backgroundColor:'rgba(34,211,160,0.08)', fill:false, tension:.4 },
  ], {
    scales: {
      y:  { type:'linear', position:'left',  ticks:{ callback: v => '$'+fmt(v) } },
      y1: { type:'linear', position:'right', grid:{ drawOnChartArea:false }, ticks:{ precision:0 } },
    },
  });

  // Status doughnut
  const sts    = ['pending','processing','shipped','delivered','cancelled'];
  const stMap  = {};
  data.orders_by_status.forEach(r => stMap[r.status] = parseInt(r.count));
  if (statusChart) statusChart.destroy();
  statusChart = DCChart.doughnut('statusChart',
    sts.map(s => s.charAt(0).toUpperCase()+s.slice(1)),
    sts.map(s => stMap[s] || 0)
  );

  // Top products bar
  const tpLabels = data.top_products.map(p => p.name.length > 18 ? p.name.slice(0,16)+'…' : p.name);
  const tpData   = data.top_products.map(p => parseInt(p.units_sold));
  if (topChart) topChart.destroy();
  topChart = DCChart.bar('topChart', tpLabels, [{ label:'Units Sold', data:tpData }]);

  // Feed
  const tbody = document.getElementById('feedBody');
  tbody.innerHTML = '';
  document.getElementById('feedCount').textContent = data.recent_orders.length + ' recent';
  data.recent_orders.forEach(o => {
    const cls = STATUS_CLS[o.status] || 'dc-badge-neutral';
    tbody.insertAdjacentHTML('beforeend', `
      <tr>
        <td><a href="orders.php?search=${o.token}" style="font-family:monospace;font-size:.82rem;color:var(--dc-accent-2)">${o.token}</a></td>
        <td>${o.customer_name}</td>
        <td>${o.items_count}</td>
        <td style="font-weight:700">$${fmt(o.total)}</td>
        <td><span class="dc-badge ${cls}">${o.status}</span></td>
        <td class="dc-caption">${timeAgo(o.created_at)}</td>
      </tr>
    `);
  });

  document.getElementById('lastUpdated').textContent = 'Updated ' + new Date().toLocaleTimeString();
}

function fmt(n)     { return Number(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function timeAgo(d) {
  if (window.DCHelpers && typeof window.DCHelpers.timeAgo === 'function') {
    return window.DCHelpers.timeAgo(d);
  }
  const s = Math.floor((Date.now() - new Date(d)) / 1000);
  if (s < 60)   return s + 's ago';
  if (s < 3600) return Math.floor(s/60) + 'm ago';
  if (s < 86400)return Math.floor(s/3600) + 'h ago';
  return Math.floor(s/86400) + 'd ago';
}

loadAnalytics();
const livePoller = new LivePoller(`${STORE_BASE}/api/live.php`, () => loadAnalytics(), 5000);
livePoller.start();
</script>
</body>
</html>
