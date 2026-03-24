<?php
require_once __DIR__ . '/core/bootstrap.php';
$apiBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/cart.php'), '/') . '/api';
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/cart.php'), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart — LiveStore</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../core/ui/devcore.css">
<style>
  .cart-layout { display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start; }
  @media(max-width:900px) { .cart-layout { grid-template-columns:1fr; } }

  .cart-img { width:60px; height:60px; object-fit:cover; border-radius:var(--dc-radius); flex-shrink:0; background:var(--dc-bg-3); }
  .qty-control { display:inline-flex; align-items:center; border:1px solid var(--dc-border); border-radius:var(--dc-radius); overflow:hidden; }
  .qty-btn { width:32px; height:32px; border:none; background:var(--dc-bg-3); color:var(--dc-text); cursor:pointer; font-weight:700; font-size:.95rem; transition:background var(--dc-t-fast); display:flex; align-items:center; justify-content:center; }
  .qty-btn:hover { background:var(--dc-bg-glass); }
  .qty-num { width:36px; text-align:center; font-weight:600; font-size:.88rem; border:none; border-left:1px solid var(--dc-border); border-right:1px solid var(--dc-border); height:32px; line-height:32px; background:var(--dc-bg-3); color:var(--dc-text); display:inline-block; }

  .summary-row { display:flex; justify-content:space-between; align-items:center; padding:6px 0; font-size:.9rem; }
  .coupon-chip { display:inline-flex; align-items:center; gap:6px; background:rgba(34,211,160,0.12); color:var(--dc-success); padding:3px 10px; border-radius:var(--dc-radius-full); font-size:.82rem; font-weight:600; }
  .coupon-remove { background:none; border:none; cursor:pointer; color:inherit; display:flex; align-items:center; padding:0; }

  .summary-sticky { position:sticky; top:84px; }
</style>
</head>
<body>

<nav class="dc-nav">
  <div class="dc-nav__brand">
    <a href="<?= $baseUrl ?>/index.php" style="color:inherit;text-decoration:none">Live<span>Store</span></a>
  </div>
  <div class="dc-nav__links">
    <a href="<?= $baseUrl ?>/index.php" class="dc-nav__link">
      <i class="dc-icon dc-icon-arrow-right dc-icon-sm" style="transform:rotate(180deg)"></i> Continue Shopping
    </a>
  </div>
</nav>

<div class="dc-container" style="padding:32px 24px 64px">
  <h1 class="dc-h2" style="margin-bottom:24px">
    <i class="dc-icon dc-icon-shopping-cart dc-icon-lg" style="color:var(--dc-accent-2)"></i>
    Your Cart
  </h1>

  <div id="emptyState" style="display:none">
    <div class="dc-empty">
      <i class="dc-icon dc-icon-shopping-cart dc-icon-2xl dc-empty__icon"></i>
      <div class="dc-empty__title">Your cart is empty</div>
      <p class="dc-empty__text">Add some products to get started</p>
      <a href="<?= $baseUrl ?>/index.php" class="dc-btn dc-btn-primary" style="margin-top:16px">
        <i class="dc-icon dc-icon-home dc-icon-sm"></i> Start Shopping
      </a>
    </div>
  </div>

  <div id="cartContent" style="display:none">
    <div class="cart-layout">

      <!-- Items -->
      <div>
        <div class="dc-table-wrap">
          <table class="dc-table">
            <thead>
              <tr>
                <th style="width:68px">Image</th>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th style="width:36px"></th>
              </tr>
            </thead>
            <tbody id="cartBody"></tbody>
          </table>
        </div>
        <div id="stockWarnings" style="margin-top:12px"></div>
      </div>

      <!-- Summary -->
      <div class="dc-card-solid summary-sticky" style="padding:24px">
        <h3 class="dc-h4" style="margin-bottom:16px">
          <i class="dc-icon dc-icon-receipt dc-icon-sm" style="color:var(--dc-accent-2)"></i>
          Order Summary
        </h3>

        <div class="summary-row">
          <span style="color:var(--dc-text-2)">Subtotal</span>
          <span id="sumSubtotal">—</span>
        </div>
        <div class="summary-row" id="discountRow" style="display:none">
          <span style="color:var(--dc-text-2)">Discount</span>
          <span id="sumDiscount" style="color:var(--dc-success)">—</span>
        </div>

        <hr class="dc-divider">

        <div class="summary-row" style="font-size:1.05rem;font-weight:700">
          <span>Total</span>
          <span id="sumTotal">—</span>
        </div>

        <!-- Coupon -->
        <div style="margin:16px 0">
          <div id="couponApplied" style="display:none;margin-bottom:10px">
            <span class="coupon-chip" id="couponChip">
              <i class="dc-icon dc-icon-tag dc-icon-sm"></i>
              <span id="couponCode"></span>
              <button class="coupon-remove" onclick="removeCoupon()" title="Remove coupon">
                <i class="dc-icon dc-icon-x dc-icon-xs"></i>
              </button>
            </span>
          </div>
          <div id="couponInputRow" style="display:flex;gap:8px">
            <input type="text" id="couponInput" class="dc-input" placeholder="Coupon code"
                   style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
            <button class="dc-btn dc-btn-ghost dc-btn-sm" onclick="applyCoupon()" style="flex-shrink:0">
              Apply
            </button>
          </div>
        </div>

        <a href="<?= $baseUrl ?>/checkout.php" class="dc-btn dc-btn-primary dc-btn-full dc-btn-lg" id="checkoutBtn">
          <i class="dc-icon dc-icon-lock dc-icon-sm"></i> Checkout
        </a>
        <p class="dc-caption" style="text-align:center;margin-top:10px;color:var(--dc-text-3)">
          <i class="dc-icon dc-icon-check dc-icon-xs"></i> Secure · Free returns
        </p>
      </div>
    </div>
  </div>
</div>

<script src="../../core/ui/devcore.js"></script>
<script>
const API_BASE    = '<?= $apiBase ?>';
const BASE_URL    = '<?= $baseUrl ?>';
const API_CART    = `${API_BASE}/cart.php`;
const API_COUPONS = `${API_BASE}/coupons.php`;

async function loadCart() {
  const res = await DC.get(API_CART);
  renderCart(res.data);
}

function renderCart(data) {
  const items = data.items || [];
  if (!items.length) {
    document.getElementById('emptyState').style.display   = '';
    document.getElementById('cartContent').style.display  = 'none';
    return;
  }
  document.getElementById('emptyState').style.display  = 'none';
  document.getElementById('cartContent').style.display = '';

  const tbody = document.getElementById('cartBody');
  tbody.innerHTML = '';
  document.getElementById('stockWarnings').innerHTML = '';
  let hasWarning = false;

  items.forEach(item => {
    if (!item.in_stock) {
      hasWarning = true;
      document.getElementById('stockWarnings').insertAdjacentHTML('beforeend', `
        <div style="background:rgba(245,166,35,0.08);border:1px solid rgba(245,166,35,0.2);border-radius:var(--dc-radius);padding:12px 16px;display:flex;align-items:center;gap:10px;font-size:.88rem;color:var(--dc-warning)">
          <i class="dc-icon dc-icon-alert-triangle dc-icon-sm"></i>
          <strong>${item.name}</strong> — only ${item.stock} left but you have ${item.quantity} in cart
        </div>
      `);
    }
    const img = item.image_url
      ? `<img src="${item.image_url}" class="cart-img" onerror="this.style.display='none'" alt="${item.name}">`
      : `<div class="cart-img" style="display:flex;align-items:center;justify-content:center;color:var(--dc-text-3)"><i class="dc-icon dc-icon-package dc-icon-md"></i></div>`;

    tbody.insertAdjacentHTML('beforeend', `
      <tr>
        <td><a href="${BASE_URL}/product.php?id=${item.product_id}">${img}</a></td>
        <td>
          <a href="${BASE_URL}/product.php?id=${item.product_id}" style="font-weight:600;font-size:.92rem;text-decoration:none;color:var(--dc-text)">${item.name}</a>
          <div class="dc-caption" style="margin-top:2px">${item.sku || ''}</div>
        </td>
        <td>
          $${fmt(item.unit_price)}
          ${item.compare_price ? `<div style="text-decoration:line-through;font-size:.78rem;color:var(--dc-text-3)">$${fmt(item.compare_price)}</div>` : ''}
        </td>
        <td>
          <div class="qty-control">
            <button class="qty-btn" onclick="updateQty(${item.product_id},${item.quantity-1})">−</button>
            <span class="qty-num">${item.quantity}</span>
            <button class="qty-btn" onclick="updateQty(${item.product_id},${item.quantity+1})" ${item.quantity>=item.stock?'disabled title="Max stock"':''}>+</button>
          </div>
        </td>
        <td style="font-weight:700">$${fmt(item.line_total)}</td>
        <td>
          <button class="dc-btn dc-btn-icon dc-btn-sm" onclick="removeItem(${item.product_id})"
                  style="color:var(--dc-text-3);background:none;border:1px solid var(--dc-border)"
                  title="Remove">
            <i class="dc-icon dc-icon-trash dc-icon-sm"></i>
          </button>
        </td>
      </tr>
    `);
  });

  document.getElementById('sumSubtotal').textContent = '$' + fmt(data.subtotal);
  document.getElementById('sumTotal').textContent    = '$' + fmt(data.total);

  if (data.discount > 0) {
    document.getElementById('discountRow').style.display   = '';
    document.getElementById('sumDiscount').textContent     = '−$' + fmt(data.discount);
  } else {
    document.getElementById('discountRow').style.display = 'none';
  }

  if (data.coupon) {
    document.getElementById('couponApplied').style.display  = '';
    document.getElementById('couponInputRow').style.display = 'none';
    document.getElementById('couponCode').textContent =
      data.coupon.code + (data.coupon.type === 'percent' ? ` (${data.coupon.value}% off)` : ` ($${fmt(data.coupon.value)} off)`);
  } else {
    document.getElementById('couponApplied').style.display  = 'none';
    document.getElementById('couponInputRow').style.display = 'flex';
  }

  const btn = document.getElementById('checkoutBtn');
  btn.style.opacity       = hasWarning ? '.5' : '1';
  btn.style.pointerEvents = hasWarning ? 'none' : '';
}

async function updateQty(id, qty) {
  if (qty < 1) { removeItem(id); return; }
  try {
    const res = await DC.put(API_CART, { product_id: id, quantity: qty });
    renderCart(res.data);
  } catch(err) { Toast.error(err.message || 'Could not update quantity'); }
}

async function removeItem(id) {
  try {
    const res = await DC.delete(`${API_CART}?product_id=${id}`);
    renderCart(res.data);
    Toast.success('Item removed');
  } catch(err) { Toast.error(err.message); }
}

async function applyCoupon() {
  const code = document.getElementById('couponInput').value.trim().toUpperCase();
  if (!code) { Toast.error('Enter a coupon code'); return; }
  const cartRes = await DC.get(API_CART);
  try {
    const res = await DC.post(API_COUPONS, { code, order_total: cartRes.data?.subtotal || 0 });
    Toast.success('Coupon applied — saving $' + fmt(res.data.discount));
    loadCart();
  } catch(err) { Toast.error(err.message || 'Invalid coupon'); }
}

async function removeCoupon() {
  try {
    await DC.delete(`${API_COUPONS}?action=remove`);
    Toast.success('Coupon removed');
    loadCart();
  } catch(err) { loadCart(); }
}

function fmt(n) { return Number(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

loadCart();
</script>
</body>
</html>
