<?php
require_once __DIR__ . '/../../../core/bootstrap.php';

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') { http_response_code(204); exit; }

// ── POST — place order ────────────────────────────────────────
if ($method === 'POST') {
    $body = Api::body();

    $v = Validator::make($body, [
        'customer_name'  => 'required|max:150',
        'customer_email' => 'required|email|max:200',
        'customer_phone' => 'required|max:30',
        'address'        => 'required|max:300',
        'city'           => 'required|max:100',
    ]);
    if ($v->fails()) Api::error('Validation failed', 422, $v->errors());

    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) Api::error('Your cart is empty', 422);

    // Validate stock for every item
    $orderItems = [];
    $subtotal   = 0.0;
    foreach ($cart as $productId => $qty) {
        $product = $db->fetchOne(
            'SELECT id, name, price, stock FROM products WHERE id = ? AND active = 1',
            [(int)$productId]
        );
        if (!$product) Api::error("Product #$productId not found", 422);
        if ($product['stock'] < $qty) {
            Api::error("Insufficient stock for \"{$product['name']}\" (only {$product['stock']} left)", 422);
        }
        $orderItems[] = [
            'product_id'   => $product['id'],
            'product_name' => $product['name'],
            'quantity'     => $qty,
            'unit_price'   => $product['price'],
        ];
        $subtotal += $product['price'] * $qty;
    }

    // Coupon discount
    $coupon    = $_SESSION['coupon'] ?? null;
    $discount  = 0.0;
    $couponId  = null;
    if ($coupon) {
        $couponRow = $db->fetchOne('SELECT * FROM coupons WHERE id = ? AND active = 1', [(int)$coupon['id']]);
        if ($couponRow && ($couponRow['uses_limit'] === null || $couponRow['uses_count'] < $couponRow['uses_limit'])) {
            if ($couponRow['type'] === 'percent') {
                $discount = round($subtotal * ($couponRow['value'] / 100), 2);
            } else {
                $discount = min((float)$couponRow['value'], $subtotal);
            }
            $couponId = $couponRow['id'];
        }
    }

    $total = max(0, $subtotal - $discount);
    $token = strtoupper(substr(bin2hex(random_bytes(9)), 0, 12));

    // Begin transaction
    $db->beginTransaction();
    try {
        $orderId = $db->insert('orders', [
            'token'          => $token,
            'customer_name'  => $body['customer_name'],
            'customer_email' => $body['customer_email'],
            'customer_phone' => $body['customer_phone'],
            'address'        => $body['address'],
            'city'           => $body['city'],
            'total'          => $total,
            'discount'       => $discount,
            'coupon_id'      => $couponId,
            'status'         => 'pending',
        ]);

        foreach ($orderItems as $item) {
            $db->insert('order_items', array_merge($item, ['order_id' => $orderId]));
            // Decrement stock
            $db->query(
                'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?',
                [$item['quantity'], $item['product_id'], $item['quantity']]
            );
        }

        // Increment coupon usage
        if ($couponId) {
            $db->query('UPDATE coupons SET uses_count = uses_count + 1 WHERE id = ?', [$couponId]);
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        Api::error('Failed to place order: ' . $e->getMessage(), 500);
    }

    // Clear cart + coupon
    $_SESSION['cart']   = [];
    $_SESSION['coupon'] = null;

    Api::success(['order_id' => $orderId, 'token' => $token], 'Order placed successfully', 201);
}

// ── GET — list orders (admin) or single by token ──────────────
if ($method === 'GET') {
    // Public: get by token
    if (!empty($_GET['token'])) {
        $order = $db->fetchOne('SELECT * FROM orders WHERE token = ?', [$_GET['token']]);
        if (!$order) Api::error('Order not found', 404);
        $order['items'] = $db->fetchAll(
            'SELECT * FROM order_items WHERE order_id = ?', [(int)$order['id']]
        );
        Api::success($order);
    }

    // Admin: list with filters
    Auth::requireRole('admin', '../admin/login.php');

    $where  = ['1=1'];
    $params = [];

    if (!empty($_GET['status'])) {
        $where[]  = 'o.status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['search'])) {
        $where[]  = '(o.customer_name LIKE ? OR o.customer_email LIKE ? OR o.token LIKE ?)';
        $q        = '%' . $_GET['search'] . '%';
        $params   = array_merge($params, [$q, $q, $q]);
    }

    $whereSQL = implode(' AND ', $where);
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $perPage  = 20;
    $offset   = ($page - 1) * $perPage;

    $total = (int)($db->fetchOne(
        "SELECT COUNT(*) as n FROM orders o WHERE $whereSQL", $params
    )['n'] ?? 0);

    $orders = $db->fetchAll(
        "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
         FROM orders o WHERE $whereSQL ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset",
        $params
    );

    Api::paginated($orders, $total, $page, $perPage);
}

// ── PUT — update status (admin) ───────────────────────────────
if ($method === 'PUT') {
    Auth::requireRole('admin', '../admin/login.php');

    $id   = (int)($_GET['id'] ?? 0);
    $body = Api::body();
    $v    = Validator::make($body, [
        'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
    ]);
    if ($v->fails()) Api::error('Validation failed', 422, $v->errors());
    if (!$id) Api::error('Order ID required');

    $db->update('orders', ['status' => $body['status']], 'id = ?', [$id]);
    Api::success(null, 'Order status updated');
}

Api::error('Method not allowed', 405);
