<?php
require_once __DIR__ . '/../../../core/bootstrap.php';

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') { http_response_code(204); exit; }

// Helper: enrich cart with product data
function buildCartResponse(Database $db): array {
    $cart   = $_SESSION['cart'] ?? [];
    $items  = [];
    $subtotal = 0.0;

    foreach ($cart as $productId => $qty) {
        $product = $db->fetchOne(
            'SELECT id, name, price, compare_price, image_url, stock, sku FROM products WHERE id = ? AND active = 1',
            [(int)$productId]
        );
        if (!$product) continue;

        $lineTotal  = $product['price'] * $qty;
        $subtotal  += $lineTotal;
        $items[]    = [
            'product_id'    => $product['id'],
            'name'          => $product['name'],
            'sku'           => $product['sku'],
            'image_url'     => $product['image_url'],
            'unit_price'    => $product['price'],
            'compare_price' => $product['compare_price'],
            'quantity'      => $qty,
            'line_total'    => $lineTotal,
            'stock'         => $product['stock'],
            'in_stock'      => $product['stock'] >= $qty,
        ];
    }

    // Apply coupon if set
    $coupon   = $_SESSION['coupon'] ?? null;
    $discount = 0.0;
    if ($coupon) {
        if ($coupon['type'] === 'percent') {
            $discount = round($subtotal * ($coupon['value'] / 100), 2);
        } else {
            $discount = min((float)$coupon['value'], $subtotal);
        }
    }

    return [
        'items'     => $items,
        'subtotal'  => round($subtotal, 2),
        'discount'  => round($discount, 2),
        'total'     => round(max(0, $subtotal - $discount), 2),
        'item_count'=> array_sum($cart),
        'coupon'    => $coupon,
    ];
}

// ── GET ───────────────────────────────────────────────────────
if ($method === 'GET') {
    Api::success(buildCartResponse($db));
}

// ── POST — add item ───────────────────────────────────────────
if ($method === 'POST') {
    $body       = Api::body();
    $productId  = (int)($body['product_id'] ?? 0);
    $qty        = max(1, (int)($body['quantity'] ?? 1));

    if (!$productId) Api::error('product_id required');

    $product = $db->fetchOne('SELECT id, stock, active FROM products WHERE id = ? AND active = 1', [$productId]);
    if (!$product) Api::error('Product not found', 404);

    $existing = (int)(($_SESSION['cart'][$productId] ?? 0));
    $newQty   = $existing + $qty;

    if ($product['stock'] < $newQty) {
        Api::error("Only {$product['stock']} units available", 422);
    }

    $_SESSION['cart'][$productId] = $newQty;
    Api::success(buildCartResponse($db), 'Item added to cart');
}

// ── PUT — update quantity ─────────────────────────────────────
if ($method === 'PUT') {
    $body      = Api::body();
    $productId = (int)($body['product_id'] ?? 0);
    $qty       = (int)($body['quantity'] ?? 0);

    if (!$productId) Api::error('product_id required');

    if ($qty <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $product = $db->fetchOne('SELECT stock FROM products WHERE id = ? AND active = 1', [$productId]);
        if (!$product) Api::error('Product not found', 404);
        if ($product['stock'] < $qty) Api::error("Only {$product['stock']} units available", 422);
        $_SESSION['cart'][$productId] = $qty;
    }

    Api::success(buildCartResponse($db), 'Cart updated');
}

// ── DELETE — remove item ──────────────────────────────────────
if ($method === 'DELETE') {
    $productId = (int)($_GET['product_id'] ?? 0);
    if ($productId) unset($_SESSION['cart'][$productId]);
    Api::success(buildCartResponse($db), 'Item removed');
}

Api::error('Method not allowed', 405);
