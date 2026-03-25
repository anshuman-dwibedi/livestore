# LiveStore - E-Commerce Live Inventory Platform

A full-featured e-commerce store with live inventory tracking, real-time stock counters, shopping cart, coupon system, and QR-based order receipts. Built on the DevCore Shared Library with comprehensive admin dashboard.

Perfect for small to medium-sized online retail operations that need inventory visibility and customer experience in one platform.

## Live Deployment

- Production Website: https://livestore.42web.io

**Part of the DevCore Suite** — a collection of business-ready web applications sharing a common core library.

---

## Features

| Feature | Description |
|---------|-------------|
| Live Stock Counters | Product cards show real-time inventory levels updating every 4 seconds |
| Stock Status Indicators | Green (plenty) → Yellow pulsing (low) → Red disabled (out of stock) |
| Product Catalog | Browse categories, search, sort by price/popularity, pagination |
| Product Detail Pages | Hero image, gallery thumbnails, live stock, customer reviews, add to cart |
| Session Cart | Add/update/remove items with quantity validation against live stock |
| Coupon System | Percent and fixed-amount discount codes with expiry dates, usage limits, minimum orders |
| QR Order Receipts | Every order gets unique token and scannable QR code linking to order summary |
| Analytics Dashboard | Revenue KPIs, order counts, average order value, charts, live order feed |
| Image Storage | Upload product images to local filesystem, AWS S3, or Cloudflare R2 — change one config line |
| Admin Panel | Manage products, orders, inventory, coupons behind session-based auth |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.1+ with DevCore framework |
| Database | MySQL 8 / MariaDB 10.6+ |
| Frontend | Vanilla JavaScript ES2022 + DevCore UI library |
| Charts | Chart.js via DevCore wrapper |
| QR Codes | qrserver.com API (no library dependencies) |
| Image Storage | Local filesystem (swap to S3 or R2 in config) |
| Sessions | PHP native sessions for cart and auth |
| Shared Core | DevCore Shared Library (git submodule at ./core/) |

---

## Project Structure

```
livestore/
├── index.php                   Product grid with live stock updates
├── product.php                 Single product detail + add to cart
├── cart.php                    Shopping cart with coupon field
├── checkout.php                Order form (name, email, address)
├── order-confirmation.php      Post-order summary + QR code receipt
├── config.example.php          Configuration template
├── database.sql                Schema + sample products and coupons
├── .env.example                Environment variables template
│
├── api/
│   ├── products.php            GET list/filter, POST create, PUT update, DELETE (admin)
│   ├── cart.php                GET cart, POST add, PUT quantity, DELETE remove
│   ├── orders.php              POST create, GET list/view (admin), PUT status (admin)
│   ├── coupons.php             POST validate (public), GET/PUT/DELETE (admin)
│   ├── live.php                GET real-time stock + recent orders (public polling)
│   ├── analytics.php           GET dashboard stats (admin only)
│   └── logout.php              Admin session logout redirect
│
├── admin/
│   ├── login.php               Admin authentication
│   ├── dashboard.php           Analytics + live order feed
│   ├── products.php            Product management (add/edit/delete + image)
│   ├── orders.php              Order management + status tracking
│   ├── inventory.php           Live stock manager with inline editing
│   └── coupons.php             Coupon management with usage tracking
│
└── core/                       DevCore shared library (git submodule)
    ├── bootstrap.php           Autoloader + config loader
    ├── backend/                PHP classes (Database, Api, Auth, Storage, etc.)
    └── ui/                     CSS framework + JavaScript utilities
```

---

## Setup Instructions

### 1. Clone DevCore Shared Library

```bash
git clone https://github.com/anshuman-dwibedi/devcore-shared.git core
```

Or if using this as a git submodule, it's automatically initialized:
```bash
git clone --recursive https://github.com/anshuman-dwibedi/livestore.git
```

### 2. Create Database

```bash
mysql -u root -p -e "CREATE DATABASE ecommerce_live_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p ecommerce_live_store < database.sql
```

Database includes sample products and coupons ready to use.

### 3. Configure Application

```bash
cp config.example.php config.php
```

Edit `config.php`:

```php
return [
    'db_host'    => 'localhost',
    'db_name'    => 'ecommerce_live_store',
    'db_user'    => 'root',
    'db_pass'    => 'your_password',
    'app_name'   => 'LiveStore',
    'app_url'    => 'http://localhost/livestore',
    'debug'      => true,  // set false in production
    'api_secret' => 'your-secure-random-string',
];
```

### 4. Configure Storage

Choose where to store product images:

**Local filesystem (default):**
```php
'storage' => [
    'driver' => 'local',
    'local' => [
        'root'     => __DIR__ . '/uploads',
        'base_url' => 'http://localhost/livestore/uploads',
    ],
],
```

**AWS S3:**
```php
'storage' => [
    'driver' => 's3',
    's3' => [
        'key'      => 'YOUR_AWS_KEY',
        'secret'   => 'YOUR_AWS_SECRET',
        'bucket'   => 'livestore-products',
        'region'   => 'us-east-1',
        'acl'      => 'public-read',
    ],
],
```

**Cloudflare R2:**
```php
'storage' => [
    'driver' => 'r2',
    'r2' => [
        'account_id' => 'YOUR_CF_ACCOUNT_ID',
        'key'        => 'YOUR_R2_KEY',
        'secret'     => 'YOUR_R2_SECRET',
        'bucket'     => 'livestore',
        'base_url'   => 'https://pub-xxxx.r2.dev',
    ],
],
```

Create uploads folder:
```bash
mkdir -p uploads
chmod 755 uploads
```

### 5. Start Web Server

Using PHP built-in server:
```bash
php -S localhost:8000
```

Or configure Apache/Nginx to point to project root.

### 6. Access Application

- **Storefront:** http://localhost:8000/livestore/index.php
- **Admin Panel:** http://localhost:8000/livestore/admin/login.php

**Default Admin Credentials:**
```
Email: admin@livestore.com
Password: admin123
```

> Change these credentials immediately in production.

---

## Configuration

### config.example.php

Database settings, app URL, and storage driver. See Setup Instructions above for all options.

Sample coupons in database:
- `SAVE10` — 10% off (no minimum)
- `FLAT20` — $20 off (minimum $100 order)
- `NEWUSER` — 15% off (1 use limit)

---

## How It Works

### Live Stock System

The stock counter system uses **client-side polling** — no WebSockets required.

1. `/api/live.php` returns all product IDs and current stock levels (single fast DB query, <5ms)
2. `LivePoller` JavaScript calls this every **4 seconds** on index.php and product.php
3. Stock badge updates in real-time:
   - Green: plenty in stock
   - Yellow pulsing: low stock threshold
   - Red: out of stock (add-to-cart disabled)
4. Low stock threshold is per-product (`low_stock_threshold` column, default: 5)

**What triggers updates:** Customer places order → `UPDATE products SET stock = stock - qty` → next poll cycle shows new value

### Coupon System

Coupons support two types: **percent** (e.g., 10% off) and **fixed** (e.g., $20 off).

**Validation flow:**
1. Customer enters code on cart page
2. `POST /api/coupons.php` validates:
   - Code exists and is active
   - Not past expiry date
   - Usage count < limit (or unlimited)
   - Cart subtotal >= minimum order
3. On success, coupon stored in `$_SESSION['coupon']`
4. Discount calculated on render
5. When order placed, `uses_count` incremented atomically

**Sample codes:** See configuration section above.

### QR Receipts

1. Order placed → 12-character random hex token generated and stored in `orders.token`
2. `order-confirmation.php` generates QR code via qrserver.com API encoding:
   ```
   https://yourstore.com/order-confirmation.php?order=TOKEN
   ```
3. Customer scans QR anytime to view order summary
4. Order remains accessible forever via token

### Analytics Dashboard

Dashboard tracks:
- Revenue today, this month, total
- Order count, average order value
- 30-day revenue trend chart
- Top products bar chart
- Orders by status doughnut chart
- Live order feed

Data refreshes every 30 seconds via `/api/analytics.php`.

### Shopping Cart Mechanism

Cart uses **PHP sessions** for persistence, JavaScript for rendering:

```javascript
// Customer adds item to cart
DC.post('api/cart.php' { product_id, qty })
  .then(() => {
    // Cart updated in $_SESSION['cart']
    // Re-render cart sidebar from JavaScript
  });
```

Cart validates quantity against live stock. Out-of-stock items cannot be added. Checkout validates again before order creation.

---

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/products.php | No | List products with filters (category, price, search) |
| POST | /api/products.php | Admin | Create product with image upload |
| PUT | /api/products.php?id=X | Admin | Update product details and image |
| DELETE | /api/products.php?id=X | Admin | Delete product and remove images |
| GET | /api/cart.php | No | Get current cart from session |
| POST | /api/cart.php | No | Add item to cart |
| PUT | /api/cart.php?id=X | No | Update item quantity |
| DELETE | /api/cart.php?id=X | No | Remove item from cart |
| POST | /api/orders.php | No | Create new order from cart |
| GET | /api/orders.php | Admin | List all orders with filtering |
| GET | /api/orders.php?id=X | No/Admin | View single order (public via token, admin via session) |
| PUT | /api/orders.php?id=X | Admin | Update order status (processing, shipped, delivered, etc.) |
| POST | /api/coupons.php | No | Validate coupon code |
| GET | /api/coupons.php | Admin | List all coupons |
| PUT | /api/coupons.php?id=X | Admin | Toggle coupon Active/Inactive |
| DELETE | /api/coupons.php?id=X | Admin | Delete coupon |
| GET | /api/live.php | No | Real-time product stock + recent orders (polling endpoint) |
| GET | /api/analytics.php | Admin | Dashboard statistics and charts |
| GET | /api/logout.php | Admin | Admin logout redirect |

---

## Troubleshooting

**Database not found**
- Create database: `mysql -u root -p -e "CREATE DATABASE ecommerce_live_store;"`
- Import schema: `mysql -u root -p ecommerce_live_store < database.sql`
- Verify config.php lists correct database name

**"Cannot include core/bootstrap.php"**
- Clone DevCore: `git clone https://github.com/anshuman-dwibedi/devcore-shared.git core`
- Or update submodule: `git submodule update --init`

**Stock not updating in real-time**
- Check browser console for JavaScript errors
- Verify `/api/live.php` returns valid JSON
- Ensure polling interval is reasonable (default: 4 seconds)

**Coupon code not working**
- Verify code exists in database: `SELECT * FROM coupons WHERE code = 'SAVE10';`
- Check expiry date: `SELECT expires_at FROM coupons WHERE code = 'SAVE10';`
- Check usage count: `SELECT uses_count, uses_limit FROM coupons WHERE code = 'SAVE10';`
- Verify minimum order met: `SELECT min_order FROM coupons;`

**Images not uploading**
- Ensure uploads folder exists and is writable: `chmod 755 uploads/`
- Verify storage backend in config.php is correct
- Check disk space available on server

**Cart empty after page reload**
- Verify PHP sessions are enabled in php.ini
- Check session.save_path is writable: `php -r "echo ini_get('session.save_path');"`

**Admin login returns to login repeatedly**
- Check database has users: `SELECT COUNT(*) FROM users;`
- Verify session integrity: Clear browser cookies/cache
- Reset admin password via database if needed

---

## Environment Variables

Create `.env` or configure in config.php:

| Variable | Purpose |
|----------|---------|
| DB_HOST | MySQL hostname (default: localhost) |
| DB_NAME | Database name (default: ecommerce_live_store) |
| DB_USER | Database username (default: root) |
| DB_PASS | Database password |
| APP_NAME | Application title in UI |
| APP_URL | Base URL for cart, checkout, order links |
| DEBUG | Enable debug mode (true/false) |
| API_SECRET | Secret for API bearer token validation |
| STORAGE_DRIVER | Product image storage: 'local', 's3', or 'r2' |
| UPLOADS_PATH | Path to product images folder |
| CART_SESSION_KEY | Session key for cart data (advanced) |
| USER_SESSION_NAME | Session name for authentication (advanced) |

---

## License

MIT License — see LICENSE file for details.

---

**Questions?** Visit the [DevCore Shared Library](https://github.com/anshuman-dwibedi/devcore-shared) repository.
