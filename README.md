# 🛒 E-commerce Live Store (Livestore)

> A full-featured e-commerce store with live inventory, real-time stock counters, and QR order receipts — built on the [DevCore Shared Library](https://github.com/devcore/shared-library).

---

## ✨ Features

- 🔴 **Live Stock Counters** — Product cards show real-time stock levels, updating every 4 seconds via polling. Stock badges pulse yellow when running low and turn red when sold out. Add-to-cart buttons disable live when stock hits zero.
- 📦 **Full Product Catalog** — Category browsing, search, sort, pagination. Hero images with gallery thumbnails on product pages.
- 🛒 **Session Cart** — Add, update, remove items. Quantity validation against live stock. Persistent across page navigations.
- 🏷️ **Coupon System** — Apply discount codes at cart or checkout. Supports percent (`SAVE10`) and fixed-amount (`FLAT20`) coupons with expiry dates, usage limits, and minimum order requirements.
- 📱 **QR Order Receipts** — Every order gets a unique token and a QR code that permanently links back to the order summary. Screenshot it, print it, scan it forever.
- 📊 **Analytics Dashboard** — Revenue today, order counts, avg order value, 30-day revenue line chart, top products bar chart, orders-by-status doughnut, and a live order feed.
- 🖼️ **Pluggable Image Storage** — Upload product images to local filesystem, AWS S3, or Cloudflare R2 — change one line in `config.php`.
- 🔐 **Admin Panel** — Manage products, orders, inventory, and coupons behind session-based auth.

---

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.1+ |
| Database | MySQL 8 / MariaDB 10.6+ |
| Frontend | Vanilla JS + DevCore UI (`devcore.css` / `devcore.js`) |
| Charts | DCChart (Chart.js wrapper via devcore.js) |
| QR Codes | QrCode::url() via qrserver.com API (no library needed) |
| Storage | Local filesystem (swap to S3 or R2 in config) |
| Session | PHP native sessions (cart + auth) |
| Shared Core | [DevCore Shared Library](../../core/) |

---

## 📁 Folder Structure

```
livestore/
├── index.php                   Storefront — product grid with live stock
├── product.php                 Single product — gallery, details, live stock, add to cart
├── cart.php                    Cart — items, quantities, coupon, totals
├── checkout.php                Checkout form — name, email, address, place order
├── order-confirmation.php      Post-order — summary + QR code receipt
├── config.php                  Local config (DB, storage, app URL)
├── database.sql                Full schema + sample data
│
├── api/
│   ├── products.php            GET list/single, POST create, PUT update, DELETE
│   ├── cart.php                GET cart, POST add, PUT qty, DELETE remove
│   ├── orders.php              POST place, GET list/single, PUT update status
│   ├── coupons.php             POST validate/create, GET list, PUT toggle, DELETE
│   ├── analytics.php           GET dashboard stats (admin only)
│   ├── live.php                GET live stock + recent orders (polling endpoint)
│   └── logout.php              Session logout redirect
│
└── admin/
    ├── login.php               Admin login form
    ├── dashboard.php           Analytics dashboard + live order feed
    ├── products.php            Manage products (add/edit/delete + image upload)
    ├── orders.php              View/manage all orders, update status, QR per order
    ├── inventory.php           Live inventory manager — inline stock editing
    └── coupons.php             Create/manage coupons with usage progress bar
```

---

## 🚀 Setup Instructions

### 1. DevCore Library

This project requires the DevCore Shared Library at `../../core/` relative to the project root. Your directory structure should look like:

```
your-projects/
├── core/                     ← DevCore shared library
│   ├── bootstrap.php
│   ├── backend/
│   └── ui/
└── llivestore/     ← This project
    ├── index.php
    └── ...
```

Clone the shared library:
```bash
git clone https://github.com/devcore/core
```

### 2. Database

Create a MySQL database and import the schema:
```bash
mysql -u root -p -e "CREATE DATABASE ecommerce_live_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p ecommerce_live_store < database.sql
```

### 3. Config

Copy and edit the config file:
```bash
cp config.php config.php   # It's already in place — just edit it
```

Fill in your values:
```php
return [
    'db_host'  => 'localhost',
    'db_name'  => 'ecommerce_live_store',
    'db_user'  => 'root',
    'db_pass'  => 'your_password',
    'app_url'  => 'http://localhost/llivestore',
    'storage'  => [
        'driver' => 'local',
        'local'  => [
            'root'     => __DIR__ . '/uploads',
            'base_url' => 'http://localhost/llivestore/uploads',
        ],
    ],
];
```

### 4. Uploads folder

```bash
mkdir -p uploads
chmod 755 uploads
```

### 5. Web server

Point your web server document root to your projects folder, or use PHP's built-in server:
```bash
cd your-projects
php -S localhost:8000
```
Then visit: `http://localhost:8000/llivestore/`

### Admin credentials

```
URL:      http://localhost:8000/llivestore/admin/login.php
Email:    admin@store.com
Password: admin123
```

---

## 🔴 How Live Stock Works

The live stock system uses **client-side polling** — no WebSockets required.

1. **`/api/live.php`** returns a flat JSON array of all active product IDs + current stock levels on every request. It's a simple, cacheable DB query that runs in < 5ms.

2. **`LivePoller`** (from `devcore.js`) calls this endpoint on a configurable interval:
   - `index.php` polls every **4 seconds** — updates all product card stock badges
   - `product.php` polls every **3 seconds** — updates the single product indicator
   - `admin/inventory.php` polls every **5 seconds** — syncs inline stock inputs

3. When stock changes are detected, the JS updates the DOM in-place:
   - Stock badge text: `"42 in stock"` → `"Only 3 left!"` → `"Out of Stock"`
   - Badge colour: green → pulsing yellow → red
   - Add-to-cart button: disabled with "Out of Stock" text when `stock === 0`

4. **What triggers updates:** When a customer places an order (`/api/orders.php` POST), the backend runs `UPDATE products SET stock = stock - qty` inside a transaction. The next poll cycle catches the new value.

5. The "Only 3 left!" threshold is **per-product** (`low_stock_threshold` column, default 5).

---

## 🏷️ How the Coupon System Works

Coupons support two types: **percent** (e.g. 10% off) and **fixed** (e.g. $20 off).

**Validation flow:**
1. Customer enters code on the cart page and clicks Apply
2. `POST /api/coupons.php` checks:
   - Code exists and `active = 1`
   - Not past `expires_at`
   - `uses_count < uses_limit` (or limit is NULL = unlimited)
   - Current cart subtotal ≥ `min_order`
3. On success, the coupon is stored in `$_SESSION['coupon']`
4. Every cart/checkout render reads the session coupon and computes the discount
5. When the order is placed, `uses_count` is incremented atomically

**Sample codes in the demo:**
| Code | Type | Value | Condition |
|---|---|---|---|
| `SAVE10` | Percent | 10% off | No minimum |
| `FLAT20` | Fixed | $20 off | $100 minimum order |
| `NEWUSER` | Percent | 15% off | 1 use limit |

---

## 📱 How QR Receipts Work

1. **Token generation** — When an order is placed, a 12-character random hex token is generated: `bin2hex(random_bytes(9))`. This is stored in `orders.token` and is unique per order.

2. **QR code** — `order-confirmation.php` calls `QrCode::url()` from the DevCore library, generating a QR image URL that encodes:
   ```
   https://yourstore.com/order-confirmation.php?order=TKN001ABCDEF12
   ```
   The QR image is served by the free `qrserver.com` API — no library installation required.

3. **Permanent lookup** — The confirmation page reads `?order=TOKEN` from the URL, fetches the order from the DB, and renders the full summary. The token never expires. Anyone with the token (or QR scan) can view the order.

4. **Admin QR** — `admin/orders.php` renders a small 32×32px QR per order row. Admins can scan it on mobile to pull up the customer-facing confirmation page instantly.

5. **Print receipt** — A "Print Receipt" button triggers `window.print()`. CSS `@media print` hides navigation and action buttons, leaving only the order summary and QR code.

---

## 🗄️ Storage: Local, S3, or R2

All product image uploads go through `Storage::uploadFile()` from the DevCore library. To swap providers, change **one line** in `config.php`:

```php
'storage' => [
    'driver' => 'local',   // ← change to 's3' or 'r2'
```

### Local filesystem (default)
Files are saved to `./uploads/` and served directly. Zero configuration.

### AWS S3
```php
'driver' => 's3',
's3' => [
    'key'    => 'AKIAIOSFODNN7EXAMPLE',
    'secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'bucket' => 'my-ecommerce-bucket',
    'region' => 'us-east-1',
    'acl'    => 'public-read',
],
```

### Cloudflare R2
```php
'driver' => 'r2',
'r2' => [
    'account_id' => 'abc123def456',
    'key'        => 'r2-access-key',
    'secret'     => 'r2-secret-key',
    'bucket'     => 'my-r2-bucket',
    'base_url'   => 'https://pub-abc.r2.dev',
],
```

---

## 📊 Sample Data

The `database.sql` includes:
- **5 categories** — Electronics, Clothing, Home & Garden, Sports, Books
- **24 products** — Realistic names, prices $9.99–$599.99, varied stock (including out-of-stock and low-stock items for live demo)
- **3 coupons** — SAVE10, FLAT20, NEWUSER
- **40 orders** — Spread over the last 30 days with mixed statuses for chart data
- **1 admin** — admin@store.com / admin123

---

## 🔗 Part of the DevCore Portfolio Suite

> **4 industry-specific projects, 1 shared core**

This project is one of four full-stack PHP applications built on the DevCore Shared Library:

| # | Project | Key Feature |
|---|---|---|
| 1 | 🛒 **E-commerce Live Store** | Live stock + QR receipts |
| 2 | 🍽️ Restaurant POS | Live table status + kitchen display |
| 3 | 🏠 Property Listings | Map integration + enquiry pipeline |
| 4 | 📅 Booking & Scheduling | Calendar availability + confirmations |

All four projects share the same `core/` library for Database, Auth, Analytics, Storage, QrCode, and Validator — zero code duplication across projects.

---

## 📄 License

MIT — free for personal and commercial use.
