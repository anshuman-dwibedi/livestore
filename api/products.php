<?php
require_once __DIR__ . '/../../../core/bootstrap.php';

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Support _method override — PHP does NOT populate $_POST/$_FILES for PUT,
// so the admin form always POSTs and signals an update via _method=PUT
if ($method === 'POST' && ($_POST['_method'] ?? '') === 'PUT') {
    $method = 'PUT';
}

if ($method === 'OPTIONS') { http_response_code(204); exit; }

// ── GET /api/products.php?id=X  (single) ─────────────────────
if ($method === 'GET' && isset($_GET['id'])) {
    $product = $db->fetchOne(
        'SELECT p.*, c.name as category_name, c.slug as category_slug
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.id = ? AND p.active = 1',
        [(int)$_GET['id']]
    );
    if (!$product) Api::error('Product not found', 404);
    $product['images'] = $db->fetchAll(
        'SELECT image_url, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC',
        [(int)$_GET['id']]
    );
    Api::success($product);
}

// ── GET /api/products.php  (list with filters) ────────────────
if ($method === 'GET') {
    $where  = ['p.active = 1'];
    $params = [];

    if (!empty($_GET['category'])) {
        $where[]  = 'c.slug = ?';
        $params[] = $_GET['category'];
    }
    if (!empty($_GET['category_id'])) {
        $where[]  = 'p.category_id = ?';
        $params[] = (int)$_GET['category_id'];
    }
    if (!empty($_GET['search'])) {
        $where[]  = '(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)';
        $q        = '%' . $_GET['search'] . '%';
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
    }
    if (isset($_GET['in_stock']) && $_GET['in_stock'] === '1') {
        $where[] = 'p.stock > 0';
    }

    $whereSQL = implode(' AND ', $where);
    $sort     = match ($_GET['sort'] ?? 'newest') {
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name'       => 'p.name ASC',
        default      => 'p.created_at DESC',
    };

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(6, (int)($_GET['per_page'] ?? 24)));
    $offset  = ($page - 1) * $perPage;

    $total = (int)($db->fetchOne(
        "SELECT COUNT(*) as n FROM products p JOIN categories c ON c.id = p.category_id WHERE $whereSQL",
        $params
    )['n'] ?? 0);

    $products = $db->fetchAll(
        "SELECT p.id, p.name, p.slug, p.price, p.compare_price, p.image_url,
                p.stock, p.low_stock_threshold, p.sku, p.category_id,
                c.name as category_name, c.slug as category_slug
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE $whereSQL
         ORDER BY $sort
         LIMIT $perPage OFFSET $offset",
        $params
    );

    Api::paginated($products, $total, $page, $perPage);
}

// ── POST /api/products.php  (create) ─────────────────────────
if ($method === 'POST') {
    Auth::requireRole('admin', '../admin/login.php');

    $data = $_POST;

    $v = Validator::make($data, [
        'name'        => 'required|max:200',
        'category_id' => 'required|numeric',
        'price'       => 'required|numeric',
        'stock'       => 'required|numeric',
        'sku'         => 'required|max:80',
    ]);
    if ($v->fails()) Api::error('Validation failed', 422, $v->errors());

    $imageUrl = '';
    if (!empty($_FILES['image']['name'])) {
        try {
            $imageUrl = Storage::uploadFile($_FILES['image'], 'products');
        } catch (Exception $e) {
            Api::error('Image upload failed: ' . $e->getMessage());
        }
    }

    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($data['name']))) . '-' . time();

    $id = $db->insert('products', [
        'category_id'         => (int)$data['category_id'],
        'name'                => $data['name'],
        'slug'                => $slug,
        'description'         => $data['description'] ?? '',
        'price'               => (float)$data['price'],
        'compare_price'       => ($data['compare_price'] ?? '') !== '' ? (float)$data['compare_price'] : null,
        'image_url'           => $imageUrl,
        'stock'               => (int)$data['stock'],
        'low_stock_threshold' => (int)($data['low_stock_threshold'] ?? 5),
        'sku'                 => $data['sku'],
        'active'              => 1,
    ]);

    $product = $db->fetchOne('SELECT * FROM products WHERE id = ?', [$id]);
    Api::success($product, 'Product created', 201);
}

// ── PUT /api/products.php?id=X  (update) ─────────────────────
if ($method === 'PUT') {
    Auth::requireRole('admin', '../admin/login.php');

    // ID comes from query string (form upload) or JSON body
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$id) Api::error('Product ID required');

    $product = $db->fetchOne('SELECT * FROM products WHERE id = ?', [$id]);
    if (!$product) Api::error('Product not found', 404);

    // For multipart (form with file), data is in $_POST.
    // For JSON (inventory inline edit), data is in php://input.
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isMultipart = str_contains($contentType, 'multipart/form-data');
    $data        = $isMultipart ? $_POST : Api::body();

    $allowed = ['name', 'category_id', 'description', 'price', 'compare_price',
                'image_url', 'stock', 'low_stock_threshold', 'sku', 'active'];
    $update  = array_intersect_key($data, array_flip($allowed));

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        try {
            $update['image_url'] = Storage::uploadFile($_FILES['image'], 'products');
        } catch (Exception $e) {
            Api::error('Image upload failed: ' . $e->getMessage());
        }
    }

    if (empty($update)) Api::error('No valid fields to update');

    if (isset($update['price']))               $update['price']               = (float)$update['price'];
    if (array_key_exists('compare_price', $update))
                                                $update['compare_price']       = ($update['compare_price'] !== '' && $update['compare_price'] !== null)
                                                                                  ? (float)$update['compare_price'] : null;
    if (isset($update['stock']))               $update['stock']               = (int)$update['stock'];
    if (isset($update['low_stock_threshold'])) $update['low_stock_threshold'] = (int)$update['low_stock_threshold'];
    if (isset($update['active']))              $update['active']              = (int)$update['active'];
    if (isset($update['category_id']))         $update['category_id']         = (int)$update['category_id'];

    $db->update('products', $update, 'id = ?', [$id]);
    $product = $db->fetchOne('SELECT * FROM products WHERE id = ?', [$id]);
    Api::success($product, 'Product updated');
}

// ── DELETE /api/products.php?id=X ────────────────────────────
if ($method === 'DELETE') {
    Auth::requireRole('admin', '../admin/login.php');
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) Api::error('Product ID required');
    $db->update('products', ['active' => 0], 'id = ?', [$id]);
    Api::success(null, 'Product deleted');
}

Api::error('Method not allowed', 405);