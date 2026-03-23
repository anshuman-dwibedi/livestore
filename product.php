<?php
require_once __DIR__ . '/../../core/bootstrap.php';

$db        = Database::getInstance();
$productId = (int)($_GET['id'] ?? 0);
if (!$productId) { header('Location: index.php'); exit; }

$product = $db->fetchOne(
    'SELECT p.*, c.name as category_name, c.slug as category_slug
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.id = ? AND p.active = 1', [$productId]
);
if (!$product) { header('Location: index.php'); exit; }

$images = $db->fetchAll(
    'SELECT image_url, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC', [$productId]
);
if (empty($images)) $images = [['image_url' => $product['image_url'], 'sort_order' => 0]];

$related   = $db->fetchAll(
    'SELECT id, name, price, compare_price, image_url, stock, low_stock_threshold
     FROM products WHERE category_id = ? AND id != ? AND active = 1 LIMIT 4',
    [$product['category_id'], $productId]
);
$cartCount = array_sum($_SESSION['cart'] ?? []);

$apiBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/product.php'), '/') . '/api';
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/product.php'), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['name']) ?> — LiveStore</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../core/ui/devcore.css">
<style>
  .product-layout { display:grid; grid-template-columns:60% 1fr; gap:2.5rem; align-items:start; }
  @media(max-width:800px) { .product-layout { grid-template-columns:1fr; } }

  .gallery-main { aspect-ratio:1; border-radius:var(--dc-radius-lg); overflow:hidden; background:var(--dc-bg-3); border:1px solid var(--dc-border); }
  .gallery-main img { width:100%; height:100%; object-fit:cover; transition:transform .4s var(--dc-ease); display:block; }
  .gallery-main:hover img { transform:scale(1.04); }
  .gallery-thumbs { display:flex; gap:8px; margin-top:10px; overflow-x:auto; }
  .gallery-thumb { width:68px; height:68px; flex-shrink:0; border-radius:var(--dc-radius); overflow:hidden; cursor:pointer; border:2px solid transparent; transition:border-color var(--dc-t-fast); }
  .gallery-thumb.active { border-color:var(--dc-accent); }
  .gallery-thumb img { width:100%; height:100%; object-fit:cover; }

  .price-row { display:flex; align-items:baseline; gap:10px; margin:10px 0 16px; }
  .price-main { font-family:var(--dc-font-display); font-size:2rem; font-weight:800; color:var(--dc-accent-2); letter-spacing:-0.03em; }
  .price-compare { font-size:1.1rem; text-decoration:line-through; color:var(--dc-text-3); }
  .price-save { font-size:.78rem; font-weight:700; padding:2px 8px; border-radius:var(--dc-radius-full); background:rgba(34,211,160,0.12); color:var(--dc-success); }

  .stock-bar { display:flex; align-items:center; gap:10px; padding:12px 16px; background:var(--dc-bg-3); border-radius:var(--dc-radius); border:1px solid var(--dc-border); margin-bottom:16px; }
  .stock-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
  .stock-dot.ok   { background:var(--dc-success); animation:dc-pulse 2s ease infinite; }
  .stock-dot.low  { background:var(--dc-warning); animation:dc-pulse 1s ease infinite; }
  .stock-dot.out  { background:var(--dc-danger); }

  .qty-control { display:flex; align-items:center; border:1px solid var(--dc-border); border-radius:var(--dc-radius); overflow:hidden; width:fit-content; }
  .qty-btn { width:40px; height:40px; border:none; background:var(--dc-bg-3); color:var(--dc-text); cursor:pointer; font-size:1.1rem; font-weight:700; transition:background var(--dc-t-fast); }
  .qty-btn:hover { background:var(--dc-bg-glass); }
  .qty-display { width:48px; text-align:center; font-weight:700; border:none; border-left:1px solid var(--dc-border); border-right:1px solid var(--dc-border); padding:0; height:40px; line-height:40px; background:var(--dc-bg-3); color:var(--dc-text); font-family:var(--dc-font-body); font-size:.95rem; }

  .meta-table { width:100%; border-collapse:collapse; font-size:.88rem; }
  .meta-table td { padding:8px 0; border-bottom:1px solid var(--dc-border); }
  .meta-table td:first-child { color:var(--dc-text-3); width:40%; }
  .meta-table tr:last-child td { border-bottom:none; }

  .related-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-top:16px; }
  @media(max-width:800px) { .related-grid { grid-template-columns:repeat(2,1fr); } }
  .related-card { text-decoration:none; color:inherit; display:block; }
  .related-img { aspect-ratio:1; overflow:hidden; border-radius:var(--dc-radius-lg); background:var(--dc-bg-3); }
  .related-img img { width:100%; height:100%; object-fit:cover; transition:transform .3s var(--dc-ease); }
  .related-card:hover .related-img img { transform:scale(1.05); }

  .cart-count-badge { position:absolute; top:-6px; right:-6px; min-width:18px; height:18px; padding:0 4px; font-size:.68rem; border-radius:var(--dc-radius-full); background:var(--dc-danger); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; }

  @media print {
    .dc-nav, .action-row, .related-section { display:none !important; }
    body { background:#fff; color:#000; }
  }
</style>
</head>
<body>

<nav class="dc-nav">
  <div class="dc-nav__brand">
    <a href="<?= $baseUrl ?>/index.php" style="color:inherit;text-decoration:none">Live<span>Store</span></a>
  </div>
  <div class="dc-nav__links">
    <a href="<?= $baseUrl ?>/index.php" class="dc-nav__link">
      <i class="dc-icon dc-icon-arrow-right dc-icon-sm" style="transform:rotate(180deg)"></i> Back to Shop
    </a>
    <a href="<?= $baseUrl ?>/cart.php" class="dc-nav__link" style="position:relative">
      <i class="dc-icon dc-icon-shopping-cart dc-icon-sm"></i> Cart
      <span class="cart-count-badge" id="cartCount" <?= $cartCount ? '' : 'style="display:none"' ?>><?= $cartCount ?></span>
    </a>
  </div>
</nav>

<div class="dc-container" style="padding:32px 24px 64px">
  <!-- Breadcrumb -->
  <div style="display:flex;align-items:center;gap:6px;font-size:.82rem;color:var(--dc-text-3);margin-bottom:24px">
    <a href="<?= $baseUrl ?>/index.php" style="color:var(--dc-text-3);text-decoration:none">Home</a>
    <i class="dc-icon dc-icon-arrow-right dc-icon-xs"></i>
    <a href="<?= $baseUrl ?>/index.php?category=<?= $product['category_slug'] ?>" style="color:var(--dc-text-3);text-decoration:none">
      <?= htmlspecialchars($product['category_name']) ?>
    </a>
    <i class="dc-icon dc-icon-arrow-right dc-icon-xs"></i>
    <span class="dc-truncate" style="max-width:200px"><?= htmlspecialchars($product['name']) ?></span>
  </div>

  <div class="product-layout">
    <!-- Gallery -->
    <div>
      <div class="gallery-main">
        <img id="mainImage" src="<?= htmlspecialchars($images[0]['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
      </div>
      <?php if (count($images) > 1): ?>
      <div class="gallery-thumbs">
        <?php foreach ($images as $i => $img): ?>
        <div class="gallery-thumb <?= $i===0?'active':'' ?>" onclick="setImg(this,'<?= htmlspecialchars($img['image_url']) ?>')">
          <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="">
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Details -->
    <div style="display:flex;flex-direction:column;gap:12px">
      <span class="dc-badge dc-badge-accent" style="width:fit-content">
        <?= htmlspecialchars($product['category_name']) ?>
      </span>
      <?php if ($product['compare_price']): ?>
        <?php $savePct = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100); ?>
        <span class="price-save" style="width:fit-content">Save <?= $savePct ?>%</span>
      <?php endif; ?>

      <h1 class="dc-h2" style="margin:0"><?= htmlspecialchars($product['name']) ?></h1>

      <div class="price-row">
        <span class="price-main">$<?= number_format($product['price'], 2) ?></span>
        <?php if ($product['compare_price']): ?>
          <span class="price-compare">$<?= number_format($product['compare_price'], 2) ?></span>
        <?php endif; ?>
      </div>

      <!-- Live stock -->
      <?php
        $isOut = $product['stock'] === 0;
        $isLow = !$isOut && $product['stock'] <= $product['low_stock_threshold'];
        $dotCls = $isOut ? 'out' : ($isLow ? 'low' : 'ok');
      ?>
      <div class="stock-bar" id="stockBar">
        <div class="stock-dot <?= $dotCls ?>" id="stockDot"></div>
        <span id="stockText" style="font-weight:600">
          <?php if ($isOut): ?>Out of Stock
          <?php elseif ($isLow): ?>Only <?= $product['stock'] ?> left — order soon
          <?php else: ?><?= $product['stock'] ?> in stock
          <?php endif; ?>
        </span>
        <div class="dc-live" style="margin-left:auto">
          <div class="dc-live__dot"></div>
          <span>Live</span>
        </div>
      </div>

      <p style="color:var(--dc-text-2);line-height:1.7;font-size:.94rem"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>

      <!-- Quantity -->
      <div>
        <div class="dc-label-field" style="margin-bottom:8px">Quantity</div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px" id="qtyRow" <?= $isOut ? 'style="opacity:.4;pointer-events:none"' : '' ?>>
          <div class="qty-control">
            <button class="qty-btn" onclick="adjQty(-1)">−</button>
            <div class="qty-display" id="qtyDisplay">1</div>
            <button class="qty-btn" onclick="adjQty(1)">+</button>
          </div>
          <span class="dc-caption" id="maxNote"><?= !$isOut ? 'Max: '.$product['stock'] : '' ?></span>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button class="dc-btn dc-btn-primary dc-btn-lg" id="addCartBtn" onclick="addToCart()" <?= $isOut ? 'disabled' : '' ?> style="flex:1;justify-content:center">
            <i class="dc-icon dc-icon-shopping-cart dc-icon-md"></i>
            <?= $isOut ? 'Out of Stock' : 'Add to Cart' ?>
          </button>
          <a href="<?= $baseUrl ?>/cart.php" class="dc-btn dc-btn-ghost dc-btn-lg" id="viewCartBtn" style="display:none">
            View Cart <i class="dc-icon dc-icon-arrow-right dc-icon-sm"></i>
          </a>
        </div>
      </div>

      <!-- Meta -->
      <div class="dc-card-solid" style="padding:16px">
        <table class="meta-table">
          <tr><td>SKU</td><td><span style="font-family:monospace;font-size:.85rem"><?= htmlspecialchars($product['sku']) ?></span></td></tr>
          <tr><td>Category</td><td><?= htmlspecialchars($product['category_name']) ?></td></tr>
          <tr>
            <td>Status</td>
            <td id="availCell">
              <?php if ($isOut): ?>
                <span class="dc-badge dc-badge-danger">Out of Stock</span>
              <?php else: ?>
                <span class="dc-badge dc-badge-success">In Stock</span>
              <?php endif; ?>
            </td>
          </tr>
          <tr><td>Free shipping</td><td>On orders over $75</td></tr>
          <tr><td>Returns</td><td>30-day free returns</td></tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Related -->
  <?php if (!empty($related)): ?>
  <div class="related-section" style="margin-top:56px;padding-top:40px;border-top:1px solid var(--dc-border)">
    <div class="dc-page-header__eyebrow">More from <?= htmlspecialchars($product['category_name']) ?></div>
    <div class="related-grid">
      <?php foreach ($related as $r):
        $rOut = $r['stock'] === 0;
        $rLow = !$rOut && $r['stock'] <= ($r['low_stock_threshold'] ?? 5);
        $rBadge = $rOut ? 'dc-badge-danger' : ($rLow ? 'dc-badge-warning' : 'dc-badge-success');
        $rTxt   = $rOut ? 'Out of Stock' : ($rLow ? "Only {$r['stock']} left" : "{$r['stock']} in stock");
      ?>
      <a href="<?= $baseUrl ?>/product.php?id=<?= $r['id'] ?>" class="related-card">
        <div class="related-img">
          <img src="<?= htmlspecialchars($r['image_url']) ?>" alt="<?= htmlspecialchars($r['name']) ?>" loading="lazy">
        </div>
        <div style="padding:10px 0">
          <div style="font-weight:600;font-size:.88rem;margin-bottom:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= htmlspecialchars($r['name']) ?></div>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
            <span style="font-family:var(--dc-font-display);font-weight:800;color:var(--dc-accent-2)">$<?= number_format($r['price'],2) ?></span>
            <span class="dc-badge <?= $rBadge ?>" style="font-size:.68rem"><?= $rTxt ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script src="../../core/ui/devcore.js"></script>
<script>
const API_BASE   = '<?= $apiBase ?>';
const BASE_URL   = '<?= $baseUrl ?>';
const PRODUCT_ID = <?= $productId ?>;
const THRESHOLD  = <?= (int)$product['low_stock_threshold'] ?>;
let qty = 1, currentStock = <?= (int)$product['stock'] ?>;

function setImg(thumb, url) {
  document.getElementById('mainImage').src = url;
  document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
}

function adjQty(d) {
  qty = Math.max(1, Math.min(currentStock, qty + d));
  document.getElementById('qtyDisplay').textContent = qty;
}

async function addToCart() {
  const btn = document.getElementById('addCartBtn');
  DCForm.setLoading(btn, true);
  try {
    const res = await DC.post(`${API_BASE}/cart.php`, { product_id: PRODUCT_ID, quantity: qty });
    Toast.success(`${qty > 1 ? qty + '× ' : ''}Added to cart`);
    const badge = document.getElementById('cartCount');
    badge.textContent   = res.data.item_count;
    badge.style.display = res.data.item_count > 0 ? '' : 'none';
    document.getElementById('viewCartBtn').style.display = '';
    DCForm.setLoading(btn, false);
    btn.innerHTML = '<i class="dc-icon dc-icon-check dc-icon-md"></i> Added to Cart';
    setTimeout(() => { btn.innerHTML = '<i class="dc-icon dc-icon-shopping-cart dc-icon-md"></i> Add to Cart'; }, 2000);
  } catch(err) {
    Toast.error(err.message || 'Could not add to cart');
    DCForm.setLoading(btn, false);
  }
}

// Live stock polling every 3 seconds
const livePoller = new LivePoller(`${API_BASE}/live.php`, res => {
  const item = (res.data?.stocks || []).find(s => s.product_id == PRODUCT_ID);
  if (!item || item.stock === currentStock) return;
  currentStock = item.stock;
  const isOut = currentStock === 0;
  const isLow = !isOut && currentStock <= THRESHOLD;

  document.getElementById('stockDot').className = `stock-dot ${isOut?'out':isLow?'low':'ok'}`;
  document.getElementById('stockText').textContent = isOut
    ? 'Out of Stock' : isLow ? `Only ${currentStock} left — order soon` : `${currentStock} in stock`;
  document.getElementById('maxNote').textContent = isOut ? '' : 'Max: ' + currentStock;

  const btn  = document.getElementById('addCartBtn');
  const qRow = document.getElementById('qtyRow');
  if (isOut) {
    btn.disabled = true;
    btn.innerHTML = 'Out of Stock';
    qRow.style.opacity = '.4'; qRow.style.pointerEvents = 'none';
    document.getElementById('availCell').innerHTML = '<span class="dc-badge dc-badge-danger">Out of Stock</span>';
  } else {
    btn.disabled = false;
    btn.innerHTML = '<i class="dc-icon dc-icon-shopping-cart dc-icon-md"></i> Add to Cart';
    qRow.style.opacity = '1'; qRow.style.pointerEvents = '';
    document.getElementById('availCell').innerHTML = '<span class="dc-badge dc-badge-success">In Stock</span>';
    if (qty > currentStock) { qty = currentStock; document.getElementById('qtyDisplay').textContent = qty; }
  }
}, 3000);

livePoller.start();
</script>
</body>
</html>
