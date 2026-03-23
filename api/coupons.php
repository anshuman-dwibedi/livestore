<?php
require_once __DIR__ . '/../../../core/bootstrap.php';

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') { http_response_code(204); exit; }

// ── POST — validate coupon ────────────────────────────────────
if ($method === 'POST' && !isset($_GET['action'])) {
    $body = Api::body();
    $code = strtoupper(trim($body['code'] ?? ''));
    $orderTotal = (float)($body['order_total'] ?? 0);

    if (!$code) Api::error('Coupon code required', 422);

    $coupon = $db->fetchOne('SELECT * FROM coupons WHERE code = ? AND active = 1', [$code]);

    if (!$coupon)                                         Api::error('Invalid coupon code', 404);
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time())
                                                          Api::error('This coupon has expired', 422);
    if ($coupon['uses_limit'] !== null && $coupon['uses_count'] >= $coupon['uses_limit'])
                                                          Api::error('Coupon usage limit reached', 422);
    if ($orderTotal > 0 && $orderTotal < $coupon['min_order'])
                                                          Api::error("Minimum order of \${$coupon['min_order']} required", 422);

    // Calculate discount
    $discount = 0.0;
    if ($coupon['type'] === 'percent') {
        $discount = round($orderTotal * ($coupon['value'] / 100), 2);
    } else {
        $discount = min((float)$coupon['value'], $orderTotal);
    }

    // Store in session
    $_SESSION['coupon'] = [
        'id'       => $coupon['id'],
        'code'     => $coupon['code'],
        'type'     => $coupon['type'],
        'value'    => $coupon['value'],
        'discount' => $discount,
    ];

    Api::success([
        'coupon'   => $coupon,
        'discount' => $discount,
    ], 'Coupon applied successfully');
}

// ── DELETE — remove coupon from session ──────────────────────
if ($method === 'DELETE' && isset($_GET['action']) && $_GET['action'] === 'remove') {
    $_SESSION['coupon'] = null;
    Api::success(null, 'Coupon removed');
}

// ── GET — list coupons (admin) ────────────────────────────────
if ($method === 'GET') {
    Auth::requireRole('admin', '../admin/login.php');
    $coupons = $db->fetchAll('SELECT * FROM coupons ORDER BY created_at DESC');
    Api::success($coupons);
}

// ── POST with action=create — create coupon (admin) ───────────
if ($method === 'POST' && ($_GET['action'] ?? '') === 'create') {
    Auth::requireRole('admin', '../admin/login.php');
    $body = Api::body();

    $v = Validator::make($body, [
        'code'  => 'required|max:50',
        'type'  => 'required|in:percent,fixed',
        'value' => 'required|numeric',
    ]);
    if ($v->fails()) Api::error('Validation failed', 422, $v->errors());

    $exists = $db->fetchOne('SELECT id FROM coupons WHERE code = ?', [strtoupper($body['code'])]);
    if ($exists) Api::error('Coupon code already exists', 409);

    $id = $db->insert('coupons', [
        'code'       => strtoupper(trim($body['code'])),
        'type'       => $body['type'],
        'value'      => (float)$body['value'],
        'min_order'  => (float)($body['min_order'] ?? 0),
        'uses_limit' => !empty($body['uses_limit']) ? (int)$body['uses_limit'] : null,
        'expires_at' => !empty($body['expires_at']) ? $body['expires_at'] : null,
        'active'     => 1,
    ]);

    $coupon = $db->fetchOne('SELECT * FROM coupons WHERE id = ?', [$id]);
    Api::success($coupon, 'Coupon created', 201);
}

// ── PUT — toggle active ────────────────────────────────────────
if ($method === 'PUT') {
    Auth::requireRole('admin', '../admin/login.php');
    $id   = (int)($_GET['id'] ?? 0);
    $body = Api::body();
    if (!$id) Api::error('Coupon ID required');
    $db->update('coupons', ['active' => (int)$body['active']], 'id = ?', [$id]);
    Api::success(null, 'Coupon updated');
}

// ── DELETE — delete coupon (admin) ────────────────────────────
if ($method === 'DELETE' && !isset($_GET['action'])) {
    Auth::requireRole('admin', '../admin/login.php');
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) Api::error('Coupon ID required');
    $db->delete('coupons', 'id = ?', [$id]);
    Api::success(null, 'Coupon deleted');
}

Api::error('Method not allowed', 405);
