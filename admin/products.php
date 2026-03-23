<?php
require_once __DIR__ . '/../../../core/bootstrap.php';
Auth::requireRole('admin', 'login.php');

$db         = Database::getInstance();
$categories = $db->fetchAll('SELECT id, name FROM categories ORDER BY name ASC');
$apiBase    = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/products.php'), '/');
$storeBase  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/products.php')), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products — LiveStore Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<style>
  .main-content { padding:32px; }
  .page-topbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:10px; }
  .prod-thumb { width:44px;height:44px;object-fit:cover;border-radius:var(--dc-radius);background:var(--dc-bg-3); }
</style>
</head>
<body class="dc-with-sidebar">

<aside class="dc-sidebar">
  <div class="dc-sidebar__logo">Live<span>Store</span></div>
  <a href="dashboard.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-activity dc-icon-sm"></i> Dashboard</a>
  <a href="products.php"  class="dc-sidebar__link active"><i class="dc-icon dc-icon-package dc-icon-sm"></i> Products</a>
  <a href="orders.php"    class="dc-sidebar__link"><i class="dc-icon dc-icon-receipt dc-icon-sm"></i> Orders</a>
  <a href="inventory.php" class="dc-sidebar__link"><i class="dc-icon dc-icon-clipboard dc-icon-sm"></i> Inventory</a>
  <a href="coupons.php"   class="dc-sidebar__link"><i class="dc-icon dc-icon-tag dc-icon-sm"></i> Coupons</a>
  <div class="dc-sidebar__section">Store</div>
  <a href="<?= $storeBase ?>/index.php" class="dc-sidebar__link" target="_blank"><i class="dc-icon dc-icon-home dc-icon-sm"></i> View Store</a>
  <a href="../api/logout.php" class="dc-sidebar__link" style="margin-top:auto;color:var(--dc-danger)"><i class="dc-icon dc-icon-log-out dc-icon-sm"></i> Logout</a>
</aside>

<main class="main-content">
  <div class="page-topbar">
    <h1 class="dc-h2">Products</h1>
    <button class="dc-btn dc-btn-primary" onclick="openAdd()">
      <i class="dc-icon dc-icon-plus dc-icon-sm"></i> Add Product
    </button>
  </div>

  <!-- Filters -->
  <div class="dc-card-solid" style="padding:16px;margin-bottom:20px">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <div class="dc-form-group" style="flex:1;min-width:180px">
        <label class="dc-label-field">Search</label>
        <input type="text" id="fSearch" class="dc-input" placeholder="Name or SKU…" oninput="debounce()">
      </div>
      <div class="dc-form-group" style="width:160px">
        <label class="dc-label-field">Category</label>
        <select id="fCat" class="dc-select" onchange="loadProducts()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="dc-form-group" style="width:160px">
        <label class="dc-label-field">Sort</label>
        <select id="fSort" class="dc-select" onchange="loadProducts()">
          <option value="newest">Newest</option>
          <option value="price_asc">Price Low–High</option>
          <option value="price_desc">Price High–Low</option>
          <option value="name">Name A–Z</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="dc-card-solid" style="padding:0;overflow:hidden">
    <div class="dc-table-wrap" style="border:none">
      <table class="dc-table">
        <thead>
          <tr>
            <th style="width:52px">Img</th><th>Name</th><th>SKU</th>
            <th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="prodBody">
          <tr><td colspan="8" class="dc-text-center dc-text-dim" style="padding:40px">Loading…</td></tr>
        </tbody>
      </table>
    </div>
    <div id="pagination" class="dc-flex-center dc-gap-sm" style="padding:16px"></div>
  </div>
</main>

<!-- Modal -->
<div class="dc-modal-overlay" id="prodModal">
  <div class="dc-modal" style="max-width:640px">
    <div class="dc-modal__header">
      <div class="dc-h3" id="modalTitle">Add Product</div>
      <button class="dc-modal__close" data-modal-close="prodModal">
        <i class="dc-icon dc-icon-x dc-icon-md"></i>
      </button>
    </div>
    <div style="display:flex;flex-direction:column;gap:16px">
      <input type="hidden" id="editId">
      <div class="dc-grid dc-grid-2" style="gap:14px">
        <div class="dc-form-group" style="grid-column:1/-1">
          <label class="dc-label-field">Product Name *</label>
          <input type="text" id="fName" class="dc-input" required>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Category *</label>
          <select id="fCatMod" class="dc-select" required>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">SKU *</label>
          <input type="text" id="fSku" class="dc-input" required>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Price ($) *</label>
          <input type="number" id="fPrice" class="dc-input" step="0.01" min="0" required>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Compare Price ($)</label>
          <input type="number" id="fCompare" class="dc-input" step="0.01" min="0" placeholder="Optional">
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Stock *</label>
          <input type="number" id="fStock" class="dc-input" min="0" required>
        </div>
        <div class="dc-form-group">
          <label class="dc-label-field">Low Stock Threshold</label>
          <input type="number" id="fThreshold" class="dc-input" min="1" value="5">
        </div>
        <div class="dc-form-group" style="grid-column:1/-1">
          <label class="dc-label-field">Description</label>
          <textarea id="fDesc" class="dc-textarea" rows="3"></textarea>
        </div>
        <div class="dc-form-group" style="grid-column:1/-1">
          <label class="dc-label-field">Image URL</label>
          <input type="url" id="fImageUrl" class="dc-input" placeholder="https://images.unsplash.com/photo-…" oninput="previewImageUrl(this.value)">
          <div class="dc-caption dc-text-dim" style="margin-top:5px">Paste a URL — or upload a file below (upload takes priority)</div>
        </div>
        <div class="dc-form-group" style="grid-column:1/-1">
          <label class="dc-label-field">Upload Image File</label>
          <input type="file" id="fImage" class="dc-input" accept="image/*" onchange="previewUpload(this)">
          <div id="curImg" style="margin-top:8px;display:flex;align-items:center;gap:12px"></div>
        </div>
      </div>
      <div class="dc-flex" style="justify-content:flex-end;gap:10px;padding-top:4px">
        <button class="dc-btn dc-btn-ghost" data-modal-close="prodModal">Cancel</button>
        <button class="dc-btn dc-btn-primary" id="saveBtn" onclick="saveProduct()">
          <i class="dc-icon dc-icon-check dc-icon-sm"></i> Save Product
        </button>
      </div>
    </div>
  </div>
</div>

<script src="../../../core/ui/devcore.js"></script>
<script>
const API   = '<?= $storeBase ?>/api/products.php';
let curPage = 1, debTimer;
function debounce() { clearTimeout(debTimer); debTimer = setTimeout(() => loadProducts(1), 380); }

async function loadProducts(page = 1) {
  curPage = page;
  const params = new URLSearchParams({ page, per_page:15, sort:document.getElementById('fSort').value });
  const s = document.getElementById('fSearch').value, c = document.getElementById('fCat').value;
  if (s) params.set('search', s);
  if (c) params.set('category_id', c);

  const res = await DC.get(API + '?' + params);
  const tb  = document.getElementById('prodBody');
  tb.innerHTML = '';

  if (!res.data?.length) {
    tb.innerHTML = `<tr><td colspan="8" class="dc-text-center dc-text-dim" style="padding:40px">No products found</td></tr>`;
    document.getElementById('pagination').innerHTML = '';
    return;
  }

  res.data.forEach(p => {
    const sCls = p.stock === 0 ? 'dc-badge-danger' : p.stock <= p.low_stock_threshold ? 'dc-badge-warning' : 'dc-badge-success';
    const img  = p.image_url
      ? `<img src="${p.image_url}" class="prod-thumb" onerror="this.style.display='none'" alt="">`
      : `<div class="prod-thumb dc-flex-center dc-text-dim"><i class="dc-icon dc-icon-image dc-icon-sm"></i></div>`;
    tb.insertAdjacentHTML('beforeend', `
      <tr>
        <td>${img}</td>
        <td style="font-weight:600">${p.name}</td>
        <td><span style="font-family:monospace;font-size:.82rem">${p.sku}</span></td>
        <td>${p.category_name}</td>
        <td>$${fmt(p.price)}${p.compare_price?`<div class="dc-text-dim" style="text-decoration:line-through;font-size:.75rem">$${fmt(p.compare_price)}</div>`:''}</td>
        <td><span class="dc-badge ${sCls}">${p.stock}</span></td>
        <td><span class="dc-badge dc-badge-success">Active</span></td>
        <td>
          <button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="editProduct(${p.id})">
            <i class="dc-icon dc-icon-edit dc-icon-sm"></i>
          </button>
          <button class="dc-btn dc-btn-danger dc-btn-sm" onclick="delProduct(${p.id},'${p.name.replace(/'/g,"\\'")}')">
            <i class="dc-icon dc-icon-trash dc-icon-sm"></i>
          </button>
        </td>
      </tr>
    `);
  });

  const meta = res.meta, pages = meta.total_pages;
  const pg   = document.getElementById('pagination');
  pg.innerHTML = '';
  for (let i = 1; i <= pages; i++)
    pg.insertAdjacentHTML('beforeend', `<button class="dc-btn dc-btn-sm ${i===curPage?'dc-btn-primary':'dc-btn-ghost'}" onclick="loadProducts(${i})">${i}</button>`);
}

function openAdd() {
  document.getElementById('editId').value  = '';
  document.getElementById('modalTitle').textContent = 'Add Product';
  ['fName','fSku','fPrice','fCompare','fDesc','fStock','fImageUrl'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('fThreshold').value = '5';
  document.getElementById('curImg').innerHTML = '';
  Modal.open('prodModal');
}

async function editProduct(id) {
  const res = await DC.get(API + '?id=' + id);
  const p   = res.data;
  document.getElementById('editId').value         = p.id;
  document.getElementById('modalTitle').textContent = 'Edit Product';
  document.getElementById('fName').value          = p.name;
  document.getElementById('fCatMod').value        = p.category_id;
  document.getElementById('fSku').value           = p.sku;
  document.getElementById('fPrice').value         = p.price;
  document.getElementById('fCompare').value       = p.compare_price || '';
  document.getElementById('fStock').value         = p.stock;
  document.getElementById('fThreshold').value     = p.low_stock_threshold;
  document.getElementById('fDesc').value          = p.description || '';
  document.getElementById('fImageUrl').value      = p.image_url || '';
  document.getElementById('curImg').innerHTML     = p.image_url
    ? `<img src="${p.image_url}" style="height:56px;border-radius:var(--dc-radius);object-fit:cover"> <span class="dc-caption">Current image</span>`
    : '';
  Modal.open('prodModal');
}

async function saveProduct() {
  const id  = document.getElementById('editId').value;
  const btn = document.getElementById('saveBtn');
  DCForm.setLoading(btn, true);

  const fd = new FormData();
  fd.append('name',                document.getElementById('fName').value);
  fd.append('category_id',         document.getElementById('fCatMod').value);
  fd.append('sku',                  document.getElementById('fSku').value);
  fd.append('price',               document.getElementById('fPrice').value);
  fd.append('compare_price',       document.getElementById('fCompare').value);
  fd.append('stock',               document.getElementById('fStock').value);
  fd.append('low_stock_threshold', document.getElementById('fThreshold').value);
  fd.append('description',         document.getElementById('fDesc').value);
  const img    = document.getElementById('fImage').files[0];
  const imgUrl = document.getElementById('fImageUrl').value.trim();
  if (img) {
    fd.append('image', img);          // file upload takes priority
  } else if (imgUrl) {
    fd.append('image_url', imgUrl);   // fall back to pasted URL
  }

  // Always POST — PHP does not populate $_FILES/$_POST for PUT requests.
  // Signal an update by appending _method=PUT + id into the FormData.
  if (id) {
    fd.append('_method', 'PUT');
    fd.append('id', id);
  }

  try {
    const r   = await fetch(id ? API+'?id='+id : API, {method:'POST', body:fd});
    const res = await r.json();
    if (res.status === 'success') {
      Toast.success(id ? 'Product updated' : 'Product created');
      Modal.close('prodModal');
      loadProducts(curPage);
    } else { Toast.error(res.message || 'Save failed'); }
  } catch(e) { Toast.error('Network error'); }
  DCForm.setLoading(btn, false);
}

async function delProduct(id, name) {
  if (!confirm(`Delete "${name}"?`)) return;
  try {
    await DC.delete(API + '?id=' + id);
    Toast.success('Product deleted');
    loadProducts(curPage);
  } catch(err) { Toast.error(err.message); }
}

function fmt(n) { return Number(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

function previewImageUrl(url) {
  const wrap = document.getElementById('curImg');
  if (!url) { wrap.innerHTML = ''; return; }
  wrap.innerHTML = `<img src="${url}" style="height:64px;border-radius:var(--dc-radius);object-fit:cover;border:1px solid var(--dc-border)" onerror="this.style.display='none'"> <span class="dc-caption dc-text-dim">URL preview</span>`;
}

function previewUpload(input) {
  const wrap = document.getElementById('curImg');
  if (!input.files[0]) { wrap.innerHTML = ''; return; }
  const url = URL.createObjectURL(input.files[0]);
  wrap.innerHTML = `<img src="${url}" style="height:64px;border-radius:var(--dc-radius);object-fit:cover;border:1px solid var(--dc-border)"> <span class="dc-caption dc-text-dim">New upload</span>`;
}

loadProducts();
</script>
</body>
</html>