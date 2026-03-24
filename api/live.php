<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Api::error('Method not allowed', 405);

// All active product stock levels
$stocks = $db->fetchAll(
    'SELECT id as product_id, stock, low_stock_threshold FROM products WHERE active = 1'
);

// Last 10 orders for dashboard feed
$recentOrders = $db->fetchAll(
    "SELECT o.id, o.token, o.customer_name, o.total, o.status, o.created_at,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
     FROM orders o ORDER BY o.created_at DESC LIMIT 10"
);

Api::success([
    'stocks'        => $stocks,
    'recent_orders' => $recentOrders,
    'timestamp'     => date('c'),
]);
