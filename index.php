<?php
require_once __DIR__ . '/../../core/bootstrap.php';

$db         = Database::getInstance();
$categories = $db->fetchAll('SELECT id, name, slug FROM categories ORDER BY name ASC');
$totalCount = (int)($db->fetchOne('SELECT COUNT(*) as c FROM products WHERE active = 1')['c'] ?? 0);
$cartCount  = array_sum($_SESSION['cart'] ?? []);

$apiBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/') . '/api';
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LiveStore — Shop the Best Products Online</title>
<meta name="description" content="Browse <?= $totalCount ?> products with live stock counters.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../core/ui/devcore.css">
<style>
  .filter-bar {
    position: sticky; top: 64px; z-index: 90;
    background: rgba(10,10,15,0.92);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--dc-border);
    padding: 10px 0;
  }
  .filter-inner { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

  .prod-card {
    background: var(--dc-bg-glass);
    border: 1px solid var(--dc-border);
    border-radius: var(--dc-radius-lg);
    overflow: hidden; backdrop-filter: blur(12px);
    transition: border-color var(--dc-t-med), box-shadow var(--dc-t-med), transform var(--dc-t-med);
    display: flex; flex-direction: column;
  }
  .prod-card:hover { border-color: var(--dc-border-2); box-shadow: var(--dc-shadow); transform: translateY(-3px); }
  .prod-img { position:relative; aspect-ratio:1; overflow:hidden; background:var(--dc-bg-3); flex-shrink:0; }
  .prod-img img { width:100%; height:100%; object-fit:cover; transition: transform 0.5s var(--dc-ease); display:block; }
  .prod-card:hover .prod-img img { transform: scale(1.05); }
  .prod-body { padding:16px; display:flex; flex-direction:column; flex:1; }
  .prod-price { font-family:var(--dc-font-display); font-size:1.3rem; font-weight:800; color:var(--dc-accent-2); letter-spacing:-0.02em; }
  .prod-compare { font-size:.82rem; text-decoration:line-through; color:var(--dc-text-3); margin-left:6px; }
  .prod-name { font-weight:600; font-size:.93rem; line-height:1.35; margin-bottom:4px; margin-top:4px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; color:var(--dc-text); }
  .prod-footer { display:flex; align-items:center; justify-content:space-between; margin-top:auto; padding-top:12px; border-top:1px solid var(--dc-border); gap:8px; flex-wrap:wrap; }

  .hero { padding:60px 0 32px; text-align:center; }
  .hero-badge { display:inline-flex; align-items:center; gap:8px; background:var(--dc-accent-glow); border:1px solid rgba(108,99,255,0.3); border-radius:var(--dc-radius-full); padding:6px 16px; font-size:.78rem; font-weight:600; color:var(--dc-accent-2); margin-bottom:18px; }

  .cat-tabs { display:flex; gap:6px; overflow-x:auto; padding:2px 0; scrollbar-width:none; }
  .cat-tabs::-webkit-scrollbar { display:none; }
  .cat-tab { padding:6px 14px; border-radius:var(--dc-radius-full); border:1px solid var(--dc-border); background:transparent; color:var(--dc-text-2); font-size:.82rem; font-weight:500; white-space:nowrap; cursor:pointer; transition:all var(--dc-t-fast); font-family:var(--dc-font-body); }
  .cat-tab:hover { border-color:var(--dc-border-2); color:var(--dc-text); background:var(--dc-bg-glass); }
  .cat-tab.active { background:rgba(108,99,255,0.15); border-color:rgba(108,99,255,0.4); color:var(--dc-accent-2); }

  .results-bar { display:flex; align-items:center; justify-content:space-between; padding:16px 0 8px; flex-wrap:wrap; gap:8px; }
  .pagination { display:flex; align-items:center; justify-content:center; gap:8px; padding:32px 0; }

  .cart-count-badge { position:absolute; top:-6px; right:-6px; min-width:18px; height:18px; padding:0 4px; font-size:.68rem; border-radius:var(--dc-radius-full); background:var(--dc-danger); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; }

  .stock-pulse { animation: dc-pulse 1.2s ease infinite; }
  .prod-img-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--dc-text-3); }
</style>
</head>
<body>

<nav class="dc-nav">
  <div class="dc-nav__brand">
    <a href="<?= $baseUrl ?>/index.php" style="color:inherit;text-decoration:none">
      Live<span>Store</span>
    </a>
  </div>
  <div class="dc-nav__links">
    <a href="<?= $baseUrl ?>/index.php" class="dc-nav__link active">
      <i class="dc-icon dc-icon-home dc-icon-sm"></i> Shop
    </a>
    <a href="<?= $baseUrl ?>/cart.php" class="dc-nav__link" id="cartNavBtn" style="position:relative">
      <i class="dc-icon dc-icon-shopping-cart dc-icon-sm"></i> Cart
      <span class="cart-count-badge" id="cartCount" <?= $cartCount ? '' : 'style="display:none"' ?>><?= $cartCount ?></span>
    </a>
    <a href="<?= $baseUrl ?>/admin/login.php" class="dc-nav__link">
      Admin <i class="dc-icon dc-icon-arrow-right dc-icon-sm"></i>
    </a>
    <div class="dc-live" style="margin-left:4px">
      <div class="dc-live__dot"></div>
      <span>Live</span>
    </div>
  </div>
</nav>

<div class="hero">
  <div class="dc-container">
    <div class="hero-badge">
      <i class="dc-icon dc-icon-package dc-icon-sm"></i>
      <?= $totalCount ?> Products Available
    </div>
    <h1 class="dc-h1">Shop with <span style="color:var(--dc-accent)">Confidence</span></h1>
    <p class="dc-body" style="max-width:480px;margin:12px auto 0">
      Live stock counters update in real time. When you see it in stock — it really is.
    </p>
  </div>
</div>

<div class="filter-bar">
  <div class="dc-container">
    <div class="filter-inner">
      <div class="cat-tabs" id="catTabs">
        <button class="cat-tab active" data-cat="" onclick="selectCat(this,'')">All</button>
        <?php foreach ($categories as $c): ?>
        <button class="cat-tab" data-cat="<?= $c['slug'] ?>" onclick="selectCat(this,'<?= $c['slug'] ?>')">
          <?= htmlspecialchars($c['name']) ?>
        </button>
        <?php endforeach; ?>
      </div>
      <input type="text" id="fSearch" class="dc-input" style="width:200px;flex-shrink:0" placeholder="Search products…" oninput="debounce()">
      <select id="fSort" class="dc-select" style="width:170px;flex-shrink:0" onchange="loadProducts(1)">
        <option value="newest">Newest First</option>
        <option value="price_asc">Price: Low to High</option>
        <option value="price_desc">Price: High to Low</option>
        <option value="name">Name A–Z</option>
      </select>
      <button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="clearFilters()">
        <i class="dc-icon dc-icon-x dc-icon-sm"></i> Clear
      </button>
    </div>
  </div>
</div>

<div class="dc-container" style="padding-top:20px;padding-bottom:48px">
  <div class="results-bar">
    <div>
      <span class="dc-h4" id="resultsCount">Loading…</span>
      <span class="dc-body" id="resultsLabel"> products</span>
    </div>
    <span id="loadingSpinner" class="dc-caption" style="color:var(--dc-accent-2);display:none">
      <i class="dc-icon dc-icon-refresh dc-icon-sm"></i> Loading…
    </span>
  </div>

  <div class="dc-grid dc-grid-4" id="prodGrid" style="margin-top:8px">
    <?php for ($i = 0; $i < 8; $i++): ?>
    <div class="dc-card" style="padding:0;overflow:hidden">
      <div class="dc-skeleton" style="aspect-ratio:1;border-radius:0"></div>
      <div style="padding:16px">
        <div class="dc-skeleton" style="height:18px;margin-bottom:8px;border-radius:4px"></div>
        <div class="dc-skeleton" style="height:14px;width:60%;border-radius:4px"></div>
      </div>
    </div>
    <?php endfor; ?>
  </div>

  <div class="pagination" id="paginationBar"></div>

  <div id="emptyState" style="display:none">
    <div class="dc-empty">
      <i class="dc-icon dc-icon-search dc-icon-2xl dc-empty__icon"></i>
      <div class="dc-empty__title">No products found</div>
      <p class="dc-empty__text">Try adjusting your search or category filter</p>
      <button class="dc-btn dc-btn-primary dc-btn-sm" style="margin-top:16px" onclick="clearFilters()">
        <i class="dc-icon dc-icon-x dc-icon-sm"></i> Clear Filters
      </button>
    </div>
  </div>
</div>

<footer style="border-top:1px solid var(--dc-border);padding:24px 0;text-align:center">
  <div class="dc-caption" style="color:var(--dc-text-3)">
    LiveStore &middot; Part of the <strong>DevCore Portfolio Suite</strong>
  </div>
</footer>

<script src="../../core/ui/devcore.js"></script>
<script src="../../core/utils/helpers.js"></script>
<script>
const API_BASE = '<?= $apiBase ?>';
const BASE_URL = '<?= $baseUrl ?>';
let currentPage = 1, totalPages = 1, currentCat = '', debTimer;

function debounce() { clearTimeout(debTimer); debTimer = setTimeout(() => loadProducts(1), 380); }

function selectCat(btn, slug) {
  document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  currentCat = slug;
  loadProducts(1);
}

function clearFilters() {
  document.getElementById('fSearch').value = '';
  document.getElementById('fSort').value = 'newest';
  document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
  document.querySelector('.cat-tab[data-cat=""]').classList.add('active');
  currentCat = '';
  loadProducts(1);
}

async function loadProducts(page = 1) {
  currentPage = page;
  document.getElementById('loadingSpinner').style.display = '';
  const params = new URLSearchParams({ page, per_page: 24, sort: document.getElementById('fSort').value });
  const search = document.getElementById('fSearch').value;
  if (search)     params.set('search', search);
  if (currentCat) params.set('category', currentCat);

  try {
    const res  = await DC.get(`${API_BASE}/products.php?${params}`);
    const data = res.data, meta = res.meta;
    totalPages = meta.total_pages;
    document.getElementById('resultsCount').textContent = meta.total;
    document.getElementById('resultsLabel').textContent = ' product' + (meta.total !== 1 ? 's' : '');
    const grid = document.getElementById('prodGrid');
    if (!data.length) {
      grid.innerHTML = '';
      document.getElementById('emptyState').style.display = 'block';
    } else {
      document.getElementById('emptyState').style.display = 'none';
      grid.innerHTML = data.map(renderCard).join('');
    }
    renderPagination(meta.page, meta.total_pages);
  } catch(err) { Toast.error('Failed to load products'); }
  document.getElementById('loadingSpinner').style.display = 'none';
}

function renderCard(p) {
  const isOut = p.stock === 0;
  const isLow = !isOut && p.stock <= (p.low_stock_threshold || 5);
  const bCls  = isOut ? 'dc-badge-danger' : isLow ? 'dc-badge-warning' : 'dc-badge-success';
  const bTxt  = isOut ? 'Out of Stock' : isLow ? `Only ${p.stock} left` : `${p.stock} in stock`;
  const cmp   = p.compare_price ? `<span class="prod-compare">$${fmtNum(p.compare_price)}</span>` : '';
  const img   = p.image_url
    ? `<img src="${esc(p.image_url)}" alt="${esc(p.name)}" loading="lazy" onerror="this.parentNode.innerHTML='<div class=prod-img-placeholder><i class=\'dc-icon dc-icon-package dc-icon-xl\'></i></div>'">`
    : `<div class="prod-img-placeholder"><i class="dc-icon dc-icon-package dc-icon-xl"></i></div>`;

  return `<div class="prod-card" id="pc-${p.id}">
    <a href="${BASE_URL}/product.php?id=${p.id}"><div class="prod-img">${img}</div></a>
    <div class="prod-body">
      <span class="dc-badge dc-badge-accent" style="font-size:.68rem;width:fit-content">${esc(p.category_name)}</span>
      <a href="${BASE_URL}/product.php?id=${p.id}" style="text-decoration:none">
        <div class="prod-name">${esc(p.name)}</div>
      </a>
      <div style="margin:.2rem 0 .6rem">
        <span class="prod-price">$${fmtNum(p.price)}</span>${cmp}
      </div>
      <div class="prod-footer">
        <span class="dc-badge ${bCls} stock-badge${isLow&&!isOut?' stock-pulse':''}" data-id="${p.id}">${bTxt}</span>
        <button class="dc-btn dc-btn-primary dc-btn-sm" onclick="addToCart(${p.id},this)" ${isOut?'disabled':''}>
          <i class="dc-icon dc-icon-shopping-cart dc-icon-sm"></i> ${isOut ? 'Sold Out' : 'Add'}
        </button>
      </div>
    </div>
  </div>`;
}

function renderPagination(page, total) {
  const bar = document.getElementById('paginationBar');
  if (total <= 1) { bar.innerHTML = ''; return; }
  let h = '';
  if (page > 1) h += `<button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="loadProducts(${page-1})"><i class="dc-icon dc-icon-arrow-right dc-icon-sm" style="transform:rotate(180deg)"></i></button>`;
  for (let p = Math.max(1, page-2); p <= Math.min(total, page+2); p++)
    h += `<button class="dc-btn dc-btn-sm ${p===page?'dc-btn-primary':'dc-btn-ghost'}" onclick="loadProducts(${p})">${p}</button>`;
  if (page < total) h += `<button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="loadProducts(${page+1})"><i class="dc-icon dc-icon-arrow-right dc-icon-sm"></i></button>`;
  bar.innerHTML = h;
}

async function addToCart(productId, btn) {
  DCForm.setLoading(btn, true);
  try {
    const res = await DC.post(`${API_BASE}/cart.php`, { product_id: productId, quantity: 1 });
    Toast.success('Added to cart');
    const badge = document.getElementById('cartCount');
    badge.textContent   = res.data.item_count;
    badge.style.display = res.data.item_count > 0 ? '' : 'none';
    DCForm.setLoading(btn, false);
    btn.innerHTML = '<i class="dc-icon dc-icon-check dc-icon-sm"></i> Added';
    setTimeout(() => { btn.innerHTML = '<i class="dc-icon dc-icon-shopping-cart dc-icon-sm"></i> Add'; }, 1800);
  } catch(err) {
    Toast.error(err.message || 'Could not add to cart');
    DCForm.setLoading(btn, false);
  }
}

function esc(s) {
  if (window.DCHelpers && typeof window.DCHelpers.escHtml === 'function') {
    return window.DCHelpers.escHtml(s);
  }
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtNum(n)  { return Number(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

// Live stock polling every 4 seconds
const livePoller = new LivePoller(`${API_BASE}/live.php`, res => {
  (res.data?.stocks || []).forEach(item => {
    const badge = document.querySelector(`.stock-badge[data-id="${item.product_id}"]`);
    if (!badge) return;
    const isOut = item.stock === 0;
    const isLow = !isOut && item.stock <= 5;
    badge.className = `dc-badge ${isOut?'dc-badge-danger':isLow?'dc-badge-warning':'dc-badge-success'} stock-badge${isLow&&!isOut?' stock-pulse':''}`;
    badge.textContent = isOut ? 'Out of Stock' : isLow ? `Only ${item.stock} left` : `${item.stock} in stock`;
    const card = document.getElementById(`pc-${item.product_id}`);
    if (card) {
      const btn = card.querySelector('.dc-btn-primary');
      if (btn && isOut) { btn.disabled = true; btn.innerHTML = '<i class="dc-icon dc-icon-x dc-icon-sm"></i> Sold Out'; }
      else if (btn && !isOut && btn.disabled) { btn.disabled = false; btn.innerHTML = '<i class="dc-icon dc-icon-shopping-cart dc-icon-sm"></i> Add'; }
    }
  });
}, 4000);

loadProducts();
livePoller.start();
</script>
</body>
</html>