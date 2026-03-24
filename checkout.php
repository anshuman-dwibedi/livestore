<?php
require_once __DIR__ . '/core/bootstrap.php';
if (empty($_SESSION['cart'])) { header('Location: cart.php'); exit; }
$apiBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/checkout.php'), '/') . '/api';
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/checkout.php'), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — LiveStore</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../core/ui/devcore.css">
<style>
  .checkout-layout { display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start; }
  @media(max-width:900px) { .checkout-layout { grid-template-columns:1fr; } }

  .step-num { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:50%; background:var(--dc-accent); color:#fff; font-size:.78rem; font-weight:700; flex-shrink:0; }
  .section-head { display:flex; align-items:center; gap:10px; margin-bottom:20px; }

  .order-item-row { display:flex; gap:12px; align-items:center; padding:10px 0; border-bottom:1px solid var(--dc-border); }
  .order-item-row:last-child { border-bottom:none; }
  .order-item-img { width:48px; height:48px; object-fit:cover; border-radius:var(--dc-radius); flex-shrink:0; background:var(--dc-bg-3); }

  .summary-row { display:flex; justify-content:space-between; padding:5px 0; font-size:.9rem; }
  .coupon-chip { display:inline-flex; align-items:center; gap:6px; background:rgba(34,211,160,0.12); color:var(--dc-success); padding:3px 10px; border-radius:var(--dc-radius-full); font-size:.78rem; font-weight:600; }
  .coupon-remove { background:none; border:none; cursor:pointer; color:inherit; display:flex; align-items:center; }

  .summary-sticky { position:sticky; top:84px; }
</style>
</head>
<body>

<nav class="dc-nav">
  <div class="dc-nav__brand">
    <a href="<?= $baseUrl ?>/index.php" style="color:inherit;text-decoration:none">Live<span>Store</span></a>
  </div>
  <div class="dc-nav__links">
    <span class="dc-caption" style="color:var(--dc-text-3);display:flex;align-items:center;gap:6px">
      <i class="dc-icon dc-icon-lock dc-icon-sm"></i> Secure Checkout
    </span>
  </div>
</nav>

<div class="dc-container" style="padding:32px 24px 64px">
  <h1 class="dc-h2" style="margin-bottom:28px">Checkout</h1>

  <div class="checkout-layout">
    <!-- Form -->
    <div style="display:flex;flex-direction:column;gap:20px">

      <!-- Contact -->
      <div class="dc-card-solid" style="padding:24px">
        <div class="section-head">
          <span class="step-num">1</span>
          <h3 class="dc-h4" style="margin:0">Contact Information</h3>
        </div>
        <div class="dc-grid dc-grid-2" style="gap:16px">
          <div class="dc-form-group" style="grid-column:1/-1">
            <label class="dc-label-field">Full Name *</label>
            <input type="text" id="fName" class="dc-input" name="name" placeholder="Jane Smith" required>
            <span class="dc-error-msg" id="err-name"></span>
          </div>
          <div class="dc-form-group">
            <label class="dc-label-field">Email Address *</label>
            <input type="email" id="fEmail" class="dc-input" name="email" placeholder="jane@example.com" required>
            <span class="dc-error-msg" id="err-email"></span>
          </div>
          <div class="dc-form-group">
            <label class="dc-label-field">Phone Number *</label>
            <input type="tel" id="fPhone" class="dc-input" name="phone" placeholder="555-0100" required>
            <span class="dc-error-msg" id="err-phone"></span>
          </div>
        </div>
      </div>

      <!-- Address -->
      <div class="dc-card-solid" style="padding:24px">
        <div class="section-head">
          <span class="step-num">2</span>
          <h3 class="dc-h4" style="margin:0">Shipping Address</h3>
        </div>
        <div class="dc-grid dc-grid-2" style="gap:16px">
          <div class="dc-form-group" style="grid-column:1/-1">
            <label class="dc-label-field">Street Address *</label>
            <input type="text" id="fAddress" class="dc-input" name="address" placeholder="123 Main Street, Apt 4B" required>
            <span class="dc-error-msg" id="err-address"></span>
          </div>
          <div class="dc-form-group">
            <label class="dc-label-field">City *</label>
            <input type="text" id="fCity" class="dc-input" name="city" placeholder="New York" required>
            <span class="dc-error-msg" id="err-city"></span>
          </div>
          <div class="dc-form-group">
            <label class="dc-label-field">ZIP / Postal Code</label>
            <input type="text" id="fZip" class="dc-input" placeholder="10001">
          </div>
        </div>
      </div>

      <!-- Payment placeholder -->
      <div class="dc-card-solid" style="padding:24px">
        <div class="section-head">
          <span class="step-num">3</span>
          <h3 class="dc-h4" style="margin:0">Payment</h3>
        </div>
        <div style="background:var(--dc-bg-3);border:1px solid var(--dc-border);border-radius:var(--dc-radius);padding:20px;text-align:center;color:var(--dc-text-2);font-size:.9rem">
          <i class="dc-icon dc-icon-dollar dc-icon-lg" style="color:var(--dc-text-3);display:block;margin:0 auto 10px"></i>
          In production, a payment gateway (Stripe, PayPal) would appear here.<br>
          Clicking <strong>Place Order</strong> completes the demo order directly.
        </div>
      </div>
    </div>

    <!-- Summary -->
    <div class="dc-card-solid summary-sticky" style="padding:24px">
      <h3 class="dc-h4" style="margin-bottom:16px">
        <i class="dc-icon dc-icon-receipt dc-icon-sm" style="color:var(--dc-accent-2)"></i>
        Order Summary
      </h3>

      <div id="orderItems">
        <div class="dc-caption" style="text-align:center;padding:16px;color:var(--dc-text-3)">Loading…</div>
      </div>

      <hr class="dc-divider">

      <div class="summary-row">
        <span style="color:var(--dc-text-2)">Subtotal</span>
        <span id="sumSubtotal">—</span>
      </div>
      <div class="summary-row" id="discountRow" style="display:none">
        <span style="color:var(--dc-text-2)">
          Discount
          <span id="couponChipWrap" style="margin-left:6px"></span>
        </span>
        <span id="sumDiscount" style="color:var(--dc-success)">—</span>
      </div>
      <div class="summary-row">
        <span style="color:var(--dc-text-2)">Shipping</span>
        <span style="color:var(--dc-success)">Free</span>
      </div>

      <hr class="dc-divider" style="margin-top:8px">

      <div class="summary-row" style="font-size:1.05rem;font-weight:700">
        <span>Total</span>
        <span id="sumTotal">—</span>
      </div>

      <button class="dc-btn dc-btn-primary dc-btn-full dc-btn-lg" id="placeBtn" onclick="placeOrder()" style="margin-top:20px">
        <i class="dc-icon dc-icon-check dc-icon-md"></i> Place Order
      </button>

      <div class="dc-caption" style="text-align:center;margin-top:12px;color:var(--dc-text-3);display:flex;align-items:center;justify-content:center;gap:12px">
        <span><i class="dc-icon dc-icon-lock dc-icon-xs"></i> Secure</span>
        <span><i class="dc-icon dc-icon-check dc-icon-xs"></i> Free returns</span>
        <span><i class="dc-icon dc-icon-package dc-icon-xs"></i> Fast shipping</span>
      </div>
    </div>
  </div>
</div>

<script src="../../core/ui/devcore.js"></script>
<script>
const API_BASE = '<?= $apiBase ?>';
const BASE_URL = '<?= $baseUrl ?>';
const API_CART = `${API_BASE}/cart.php`;

async function loadSummary() {
  const res   = await DC.get(API_CART);
  const data  = res.data;
  const items = data.items || [];
  if (!items.length) { window.location.href = `${BASE_URL}/cart.php`; return; }

  document.getElementById('orderItems').innerHTML = items.map(item => `
    <div class="order-item-row">
      ${item.image_url
        ? `<img src="${item.image_url}" class="order-item-img" onerror="this.style.display='none'" alt="${item.name}">`
        : `<div class="order-item-img" style="display:flex;align-items:center;justify-content:center;color:var(--dc-text-3)"><i class="dc-icon dc-icon-package dc-icon-sm"></i></div>`}
      <div style="flex:1;min-width:0">
        <div style="font-size:.86rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${item.name}</div>
        <div class="dc-caption">Qty: ${item.quantity}</div>
      </div>
      <span style="font-weight:700;white-space:nowrap">$${fmt(item.line_total)}</span>
    </div>
  `).join('');

  document.getElementById('sumSubtotal').textContent = '$' + fmt(data.subtotal);
  document.getElementById('sumTotal').textContent    = '$' + fmt(data.total);

  if (data.discount > 0 && data.coupon) {
    document.getElementById('discountRow').style.display = '';
    document.getElementById('sumDiscount').textContent   = '−$' + fmt(data.discount);
    document.getElementById('couponChipWrap').innerHTML  =
      `<span class="coupon-chip"><i class="dc-icon dc-icon-tag dc-icon-xs"></i>${data.coupon.code}
       <button class="coupon-remove" onclick="removeCoupon()"><i class="dc-icon dc-icon-x dc-icon-xs"></i></button></span>`;
  }
}

async function removeCoupon() {
  await DC.delete(`${API_BASE}/coupons.php?action=remove`);
  loadSummary();
}

function validate() {
  const fields = [
    { id:'fName',    err:'err-name',    label:'Full name' },
    { id:'fEmail',   err:'err-email',   label:'Email',   email:true },
    { id:'fPhone',   err:'err-phone',   label:'Phone' },
    { id:'fAddress', err:'err-address', label:'Address' },
    { id:'fCity',    err:'err-city',    label:'City' },
  ];
  let valid = true;
  fields.forEach(f => {
    const el  = document.getElementById(f.id);
    const err = document.getElementById(f.err);
    const val = el.value.trim();
    if (!val) {
      err.textContent = f.label + ' is required';
      el.classList.add('dc-input-error'); valid = false;
    } else if (f.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
      err.textContent = 'Enter a valid email';
      el.classList.add('dc-input-error'); valid = false;
    } else {
      err.textContent = ''; el.classList.remove('dc-input-error');
    }
  });
  return valid;
}

async function placeOrder() {
  if (!validate()) {
    Toast.error('Please fill in all required fields');
    document.querySelector('.dc-input-error')?.scrollIntoView({behavior:'smooth',block:'center'});
    return;
  }
  const btn = document.getElementById('placeBtn');
  DCForm.setLoading(btn, true);

  const zip  = document.getElementById('fZip').value.trim();
  const body = {
    customer_name:  document.getElementById('fName').value.trim(),
    customer_email: document.getElementById('fEmail').value.trim(),
    customer_phone: document.getElementById('fPhone').value.trim(),
    address: document.getElementById('fAddress').value.trim() + (zip ? ', ' + zip : ''),
    city:    document.getElementById('fCity').value.trim(),
  };

  try {
    const res = await DC.post(`${API_BASE}/orders.php`, body);
    Toast.success('Order placed!');
    window.location.href = `${BASE_URL}/order-confirmation.php?order=${res.data.token}`;
  } catch(err) {
    Toast.error(err.message || 'Failed to place order — please try again');
    DCForm.setLoading(btn, false);
  }
}

function fmt(n) { return Number(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

loadSummary();

document.querySelectorAll('.dc-input').forEach(el => el.addEventListener('input', () => {
  el.classList.remove('dc-input-error');
  const id = el.id.replace('f','').toLowerCase();
  const err = document.getElementById('err-' + id);
  if (err) err.textContent = '';
}));
</script>
</body>
</html>
