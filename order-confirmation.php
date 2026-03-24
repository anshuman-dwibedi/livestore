<?php
require_once __DIR__ . '/core/bootstrap.php';
$db    = Database::getInstance();
$token = trim($_GET['order'] ?? '');
if (!$token) { header('Location: index.php'); exit; }

$order = $db->fetchOne('SELECT * FROM orders WHERE token = ?', [$token]);
if (!$order) { header('Location: index.php'); exit; }

$items    = $db->fetchAll('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC', [(int)$order['id']]);
$baseUrl  = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/order-confirmation.php'), '/');
$orderUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST']
            . $baseUrl . '/order-confirmation.php?order=' . urlencode($token);
$qrSrc    = QrCode::url($orderUrl, 220);

$statusMap = [
    'pending'    => ['Pending',    'dc-badge-warning'],
    'processing' => ['Processing', 'dc-badge-accent'],
    'shipped'    => ['Shipped',    'dc-badge-info'],
    'delivered'  => ['Delivered',  'dc-badge-success'],
    'cancelled'  => ['Cancelled',  'dc-badge-danger'],
];
[$statusLabel, $statusClass] = $statusMap[$order['status']] ?? ['Unknown', 'dc-badge-neutral'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed — LiveStore</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../core/ui/devcore.css">
<style>
  .order-layout { display:grid; grid-template-columns:1fr 280px; gap:24px; align-items:start; }
  @media(max-width:720px) { .order-layout { grid-template-columns:1fr; } }

  .check-circle {
    width:80px; height:80px; border-radius:50%;
    background:linear-gradient(135deg,var(--dc-success),#16a34a);
    display:flex; align-items:center; justify-content:center;
    margin:0 auto 16px;
    animation:dc-fade-up .6s var(--dc-ease) both;
    box-shadow:0 8px 32px rgba(34,211,160,.3);
  }

  .items-table th, .items-table td { padding:10px 14px; font-size:.88rem; }
  .summary-row { display:flex; justify-content:space-between; padding:4px 0; font-size:.9rem; }

  .qr-wrap { text-align:center; padding:24px; }
  .token-code { font-family:monospace; font-size:1rem; font-weight:700; letter-spacing:.06em; padding:8px 12px; background:var(--dc-bg-3); border-radius:var(--dc-radius); display:inline-block; margin-top:10px; }

  @media print {
    .dc-nav, .action-btns { display:none !important; }
    .dc-container { padding:0; }
    body { background:#fff; color:#000; }
    .dc-card-solid { border:1px solid #ddd; }
  }
</style>
</head>
<body>

<nav class="dc-nav">
  <div class="dc-nav__brand">
    <a href="<?= $baseUrl ?>/index.php" style="color:inherit;text-decoration:none">Live<span>Store</span></a>
  </div>
</nav>

<div class="dc-container" style="padding:40px 24px 64px;max-width:900px">

  <!-- Success header -->
  <div class="dc-animate-fade-up" style="text-align:center;margin-bottom:36px">
    <div class="check-circle">
      <i class="dc-icon dc-icon-check dc-icon-xl" style="color:#fff"></i>
    </div>
    <h1 class="dc-h2" style="margin:.5rem 0">Order Confirmed!</h1>
    <p class="dc-body">
      Thank you, <strong><?= htmlspecialchars($order['customer_name']) ?></strong>!
      A confirmation will be sent to <strong><?= htmlspecialchars($order['customer_email']) ?></strong>
    </p>
  </div>

  <div class="order-layout">
    <!-- Left: details -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- Order info -->
      <div class="dc-card-solid" style="padding:20px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px">
          <div>
            <div class="dc-caption" style="text-transform:uppercase;letter-spacing:.08em">Order Token</div>
            <div style="font-family:monospace;font-weight:700;font-size:.95rem"><?= htmlspecialchars($token) ?></div>
          </div>
          <span class="dc-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
        </div>
        <div class="dc-grid dc-grid-2" style="gap:12px;font-size:.88rem">
          <div>
            <div class="dc-caption" style="text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">Ship to</div>
            <div style="font-weight:600"><?= htmlspecialchars($order['customer_name']) ?></div>
            <div style="color:var(--dc-text-2)"><?= htmlspecialchars($order['address']) ?></div>
            <div style="color:var(--dc-text-2)"><?= htmlspecialchars($order['city']) ?></div>
          </div>
          <div>
            <div class="dc-caption" style="text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">Order date</div>
            <div style="font-weight:600"><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
            <div style="color:var(--dc-text-2)"><?= date('g:i A', strtotime($order['created_at'])) ?></div>
          </div>
        </div>
      </div>

      <!-- Items table -->
      <div class="dc-table-wrap">
        <table class="dc-table items-table">
          <thead>
            <tr>
              <th>Product</th>
              <th style="text-align:center">Qty</th>
              <th style="text-align:right">Unit Price</th>
              <th style="text-align:right">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['product_name']) ?></td>
              <td style="text-align:center"><?= $item['quantity'] ?></td>
              <td style="text-align:right">$<?= number_format($item['unit_price'], 2) ?></td>
              <td style="text-align:right;font-weight:700">$<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Totals -->
      <div class="dc-card-solid" style="padding:16px 20px">
        <div class="summary-row">
          <span style="color:var(--dc-text-2)">Subtotal</span>
          <span>$<?= number_format($order['total'] + $order['discount'], 2) ?></span>
        </div>
        <?php if ($order['discount'] > 0): ?>
        <div class="summary-row" style="color:var(--dc-success)">
          <span>Discount</span>
          <span>−$<?= number_format($order['discount'], 2) ?></span>
        </div>
        <?php endif; ?>
        <hr class="dc-divider" style="margin:8px 0">
        <div class="summary-row" style="font-size:1.05rem;font-weight:700">
          <span>Total Paid</span>
          <span>$<?= number_format($order['total'], 2) ?></span>
        </div>
      </div>

      <!-- Actions -->
      <div class="action-btns" style="display:flex;gap:10px;flex-wrap:wrap">
        <button class="dc-btn dc-btn-ghost" onclick="window.print()">
          <i class="dc-icon dc-icon-printer dc-icon-sm"></i> Print Receipt
        </button>
        <a href="<?= $baseUrl ?>/index.php" class="dc-btn dc-btn-primary">
          <i class="dc-icon dc-icon-home dc-icon-sm"></i> Continue Shopping
        </a>
      </div>
    </div>

    <!-- Right: QR -->
    <div class="dc-card-solid qr-wrap">
      <div class="dc-caption" style="text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;color:var(--dc-text-3)">
        <i class="dc-icon dc-icon-qr-code dc-icon-sm"></i> Order Receipt QR
      </div>
      <div class="dc-qr-card" style="margin:0 auto">
        <img src="<?= htmlspecialchars($qrSrc) ?>" width="200" height="200"
             alt="QR Code for order <?= htmlspecialchars($token) ?>">
      </div>
      <div class="token-code"><?= htmlspecialchars($token) ?></div>
      <p class="dc-caption" style="margin-top:12px;color:var(--dc-text-3);line-height:1.5">
        Scan to access your order anytime. This QR code links permanently to your order.
      </p>
      <hr class="dc-divider">
      <div class="dc-caption" style="color:var(--dc-text-3);display:flex;flex-direction:column;gap:6px">
        <span><i class="dc-icon dc-icon-package dc-icon-xs"></i> We will notify you when your order ships</span>
        <span><i class="dc-icon dc-icon-mail dc-icon-xs"></i> <a href="mailto:support@livestore.com" style="color:var(--dc-accent-2)">support@livestore.com</a></span>
      </div>
    </div>
  </div>
</div>

<script src="../../core/ui/devcore.js"></script>
<script>
setTimeout(() => Toast.success('Order placed successfully!'), 500);
</script>
</body>
</html>
