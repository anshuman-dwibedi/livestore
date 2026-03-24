<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';

Auth::requireRole('admin', '../admin/login.php');

$db        = Database::getInstance();
$analytics = new Analytics();
$method    = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') Api::error('Method not allowed', 405);

// ── KPI stats ─────────────────────────────────────────────────
$ordersKpi = $analytics->kpi('orders');

$revenueToday = (float)($db->fetchOne(
    "SELECT COALESCE(SUM(total),0) as r FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'"
)['r'] ?? 0);

$revenueTotal = (float)($db->fetchOne(
    "SELECT COALESCE(SUM(total),0) as r FROM orders WHERE status != 'cancelled'"
)['r'] ?? 0);

$avgOrderValue = (float)($db->fetchOne(
    "SELECT COALESCE(AVG(total),0) as a FROM orders WHERE status != 'cancelled'"
)['a'] ?? 0);

$totalProducts = (int)($db->fetchOne(
    "SELECT COUNT(*) as n FROM products WHERE active = 1"
)['n'] ?? 0);

// ── Revenue + Orders by day (last 30 days) ───────────────────
$revenueByDay = $analytics->sumByDay('orders', 'total', 'created_at', 30);
$ordersByDay  = $analytics->countByDay('orders', 'created_at', 30);

// ── Top 10 products by units sold ────────────────────────────
$topProducts = $db->fetchAll(
    "SELECT p.name, SUM(oi.quantity) as units_sold, SUM(oi.quantity * oi.unit_price) as revenue
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     JOIN orders o ON o.id = oi.order_id
     WHERE o.status != 'cancelled'
     GROUP BY oi.product_id, p.name
     ORDER BY units_sold DESC
     LIMIT 10"
);

// ── Orders by status ─────────────────────────────────────────
$ordersByStatus = $db->fetchAll(
    "SELECT status, COUNT(*) as count FROM orders GROUP BY status"
);

// ── Recent orders (last 10, for live feed) ───────────────────
$recentOrders = $db->fetchAll(
    "SELECT o.id, o.token, o.customer_name, o.customer_email, o.total,
            o.status, o.created_at,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
     FROM orders o ORDER BY o.created_at DESC LIMIT 10"
);

Api::success([
    'kpi' => [
        'revenue_today'   => round($revenueToday, 2),
        'revenue_total'   => round($revenueTotal, 2),
        'orders_today'    => (int)$ordersKpi['today'],
        'orders_total'    => (int)$ordersKpi['total'],
        'avg_order_value' => round($avgOrderValue, 2),
        'total_products'  => $totalProducts,
    ],
    'revenue_by_day'   => $revenueByDay,
    'orders_by_day'    => $ordersByDay,
    'top_products'     => $topProducts,
    'orders_by_status' => $ordersByStatus,
    'recent_orders'    => $recentOrders,
]);
