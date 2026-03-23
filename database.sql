-- ============================================================
-- E-commerce Live Store — database.sql
-- Complete schema + sample data
-- ============================================================

-- ── 1. CREATE & SELECT DATABASE ──────────────────────────────
CREATE DATABASE IF NOT EXISTS livestore
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE livestore;

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ── Drop tables ──────────────────────────────────────────────
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admins;

-- ── Categories ───────────────────────────────────────────────
CREATE TABLE categories (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    slug       VARCHAR(110) NOT NULL UNIQUE,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Products ─────────────────────────────────────────────────
CREATE TABLE products (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id         INT UNSIGNED NOT NULL,
    name                VARCHAR(200) NOT NULL,
    slug                VARCHAR(220) NOT NULL UNIQUE,
    description         TEXT,
    price               DECIMAL(10,2) NOT NULL,
    compare_price       DECIMAL(10,2) NULL,
    image_url           VARCHAR(500)  NOT NULL DEFAULT '',
    stock               INT          NOT NULL DEFAULT 0,
    low_stock_threshold INT          NOT NULL DEFAULT 5,
    sku                 VARCHAR(80)  NOT NULL UNIQUE,
    active              TINYINT(1)   NOT NULL DEFAULT 1,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Product images ───────────────────────────────────────────
CREATE TABLE product_images (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_url  VARCHAR(500) NOT NULL,
    sort_order TINYINT      NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Coupons ──────────────────────────────────────────────────
CREATE TABLE coupons (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(50)  NOT NULL UNIQUE,
    type       ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    value      DECIMAL(10,2) NOT NULL,
    min_order  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    uses_limit INT           NULL COMMENT 'NULL = unlimited',
    uses_count INT           NOT NULL DEFAULT 0,
    expires_at DATETIME      NULL,
    active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Orders ───────────────────────────────────────────────────
CREATE TABLE orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token           VARCHAR(20)  NOT NULL UNIQUE,
    customer_name   VARCHAR(150) NOT NULL,
    customer_email  VARCHAR(200) NOT NULL,
    customer_phone  VARCHAR(30)  NOT NULL DEFAULT '',
    address         VARCHAR(300) NOT NULL,
    city            VARCHAR(100) NOT NULL,
    total           DECIMAL(10,2) NOT NULL,
    discount        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    coupon_id       INT UNSIGNED NULL,
    status          ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Order items ──────────────────────────────────────────────
CREATE TABLE order_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity     SMALLINT     NOT NULL DEFAULT 1,
    unit_price   DECIMAL(10,2) NOT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Admins ───────────────────────────────────────────────────
CREATE TABLE admins (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    email      VARCHAR(200) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       VARCHAR(50)  NOT NULL DEFAULT 'admin',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- ── Categories ───────────────────────────────────────────────
INSERT INTO categories (name, slug) VALUES
    ('Electronics',    'electronics'),
    ('Clothing',       'clothing'),
    ('Home & Garden',  'home-garden'),
    ('Sports',         'sports'),
    ('Books',          'books');

-- ── Products (24 products across 5 categories) ───────────────
INSERT INTO products (category_id, name, slug, description, price, compare_price, image_url, stock, low_stock_threshold, sku) VALUES
-- Electronics (cat 1)
(1, 'Wireless Noise-Cancelling Headphones', 'wireless-nc-headphones',
 'Premium over-ear headphones with active noise cancellation, 30-hour battery life, and superior sound quality. Compatible with all Bluetooth devices.',
 149.99, 199.99, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&q=80', 42, 5, 'ELEC-001'),

(1, 'Ultra 4K Smart TV 55"', 'ultra-4k-smart-tv-55',
 'Experience cinematic brilliance with our 55-inch 4K Smart TV. Dolby Vision HDR, built-in streaming apps, and voice control.',
 599.99, 799.99, 'https://images.unsplash.com/photo-1593359677879-a4bb92f829e1?w=600&q=80', 15, 3, 'ELEC-002'),

(1, 'Mechanical Gaming Keyboard RGB', 'mechanical-gaming-keyboard-rgb',
 'Tactile Cherry MX switches, full RGB per-key backlighting, aluminium frame. Built for competitive gaming and long coding sessions.',
 89.99, NULL, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=600&q=80', 78, 10, 'ELEC-003'),

(1, 'Portable Bluetooth Speaker', 'portable-bluetooth-speaker',
 '360° surround sound with deep bass. Waterproof IPX7 rating, 20-hour playtime, USB-C charging. Perfect for outdoors.',
 49.99, 69.99, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&q=80', 3, 5, 'ELEC-004'),

(1, 'Smartwatch Pro Series 5', 'smartwatch-pro-series-5',
 'Advanced health tracking: heart rate, SpO2, sleep analysis. GPS, 7-day battery, 50m water resistance.',
 249.99, NULL, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80', 0, 5, 'ELEC-005'),

(1, 'USB-C 100W Charging Hub', 'usb-c-100w-charging-hub',
 '7-in-1 multiport hub: 4K HDMI, 100W PD passthrough, 3x USB-A 3.0, SD/MicroSD reader. Compact aluminium design.',
 39.99, 59.99, 'https://images.unsplash.com/photo-1625895197185-efcec01cffe0?w=600&q=80', 120, 15, 'ELEC-006'),

-- Clothing (cat 2)
(2, 'Premium Cotton Crew-Neck Tee', 'premium-cotton-crew-neck-tee',
 '100% organic ring-spun cotton. Pre-shrunk, ultra-soft, perfect everyday fit. Available in 12 colours.',
 29.99, NULL, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&q=80', 150, 20, 'CLTH-001'),

(2, 'Slim-Fit Chino Trousers', 'slim-fit-chino-trousers',
 'Classic slim-fit chinos in stretch-cotton blend. Wrinkle-resistant, machine washable. Great for work or casual wear.',
 59.99, 79.99, 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=600&q=80', 4, 5, 'CLTH-002'),

(2, 'Hooded Fleece Pullover', 'hooded-fleece-pullover',
 'Cosy 300gsm polar fleece. Kangaroo pocket, drawstring hood, contrast ribbed cuffs. Unisex sizing.',
 44.99, NULL, 'https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=600&q=80', 67, 10, 'CLTH-003'),

(2, 'Waterproof Hiking Jacket', 'waterproof-hiking-jacket',
 '3-layer Gore-Tex shell. Fully taped seams, pit-zip venting, helmet-compatible hood. Ready for any weather.',
 189.99, 249.99, 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=600&q=80', 22, 5, 'CLTH-004'),

(2, 'Running Performance Shorts', 'running-performance-shorts',
 'Lightweight 4-way stretch fabric with moisture-wicking technology. Built-in liner, hidden zip pocket.',
 34.99, NULL, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&q=80', 89, 15, 'CLTH-005'),

-- Home & Garden (cat 3)
(3, 'Stainless Steel Cookware Set 10pc', 'stainless-steel-cookware-set-10pc',
 'Professional-grade 18/10 stainless steel. Tri-ply base for even heat distribution. Oven safe to 500°F. Dishwasher safe.',
 299.99, 399.99, 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600&q=80', 18, 5, 'HOME-001'),

(3, 'Bamboo Cutting Board Set', 'bamboo-cutting-board-set',
 'Set of 3 eco-friendly bamboo boards with juice grooves. Antimicrobial surface, non-slip feet. Ideal for every kitchen.',
 34.99, NULL, 'https://images.unsplash.com/photo-1591488320449-011701bb6704?w=600&q=80', 55, 10, 'HOME-002'),

(3, 'Aromatherapy Diffuser & Humidifier', 'aromatherapy-diffuser-humidifier',
 '500ml ultrasonic diffuser with 7 LED mood light colours. Whisper-quiet, auto shut-off, timer settings.',
 49.99, 64.99, 'https://images.unsplash.com/photo-1602928321679-560bb453f190?w=600&q=80', 2, 5, 'HOME-003'),

(3, 'Solar LED Garden String Lights', 'solar-led-garden-string-lights',
 '12m / 100 solar-powered warm-white LEDs. Auto on/off sensor, IP65 waterproof, 8 lighting modes.',
 24.99, NULL, 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=600&q=80', 74, 10, 'HOME-004'),

(3, 'Cast Iron Dutch Oven 6Qt', 'cast-iron-dutch-oven-6qt',
 'Enamelled cast iron for superior heat retention. Pre-seasoned interior, compatible with all hob types including induction.',
 119.99, 159.99, 'https://images.unsplash.com/photo-1585515320310-259814833e62?w=600&q=80', 31, 5, 'HOME-005'),

-- Sports (cat 4)
(4, 'Adjustable Dumbbell Set 5–52lb', 'adjustable-dumbbell-set-5-52lb',
 'Replace 15 pairs of dumbbells. Dial-select weight system from 5 to 52.5lb. Compact storage, smooth adjustment.',
 349.99, 449.99, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=80', 9, 3, 'SPRT-001'),

(4, 'Yoga Mat Premium 6mm', 'yoga-mat-premium-6mm',
 'Extra-wide 72" x 26" non-slip yoga mat. Double-sided texture, carrying strap included. Eco-friendly TPE material.',
 39.99, NULL, 'https://images.unsplash.com/photo-1601925228025-7847c4912fce?w=600&q=80', 63, 10, 'SPRT-002'),

(4, 'Resistance Band Set (5 levels)', 'resistance-band-set-5-levels',
 'Five progressive resistance bands from 10–50lb. Latex-free, with door anchor, handles, and ankle straps. Full body workout.',
 24.99, 34.99, 'https://images.unsplash.com/photo-1598289431512-b97b0917affc?w=600&q=80', 110, 15, 'SPRT-003'),

(4, 'Pro Jump Rope Speed Cable', 'pro-jump-rope-speed-cable',
 'Thin steel speed cable with 360° bearing swivel handles. Adjustable length, ideal for double-unders and cardio workouts.',
 19.99, NULL, 'https://images.unsplash.com/photo-1540497077202-7c8a3999166f?w=600&q=80', 0, 5, 'SPRT-004'),

(4, 'Foam Roller Deep Tissue 18"', 'foam-roller-deep-tissue-18',
 'High-density EVA foam with multi-surface texture for myofascial release. Lightweight, hollow core for portability.',
 29.99, 39.99, 'https://images.unsplash.com/photo-1544216717-3bbf52512659?w=600&q=80', 47, 10, 'SPRT-005'),

-- Books (cat 5)
(5, 'Atomic Habits – James Clear', 'atomic-habits-james-clear',
 'The life-changing million-copy #1 bestseller on building good habits and breaking bad ones. Proven framework for remarkable results.',
 14.99, 19.99, 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=600&q=80', 200, 20, 'BOOK-001'),

(5, 'Deep Work – Cal Newport', 'deep-work-cal-newport',
 'Rules for focused success in a distracted world. How to develop the ability to focus without distraction on cognitively demanding tasks.',
 13.99, NULL, 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?w=600&q=80', 88, 15, 'BOOK-002'),

(5, 'The Art of Clean Code', 'the-art-of-clean-code',
 'Best practices to eliminate complexity and write code that is clean, concise, and easy to maintain. Essential for every developer.',
 34.99, 44.99, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&q=80', 5, 5, 'BOOK-003'),

(5, 'Sapiens: A Brief History of Humankind', 'sapiens-brief-history-humankind',
 'A bold, wide-ranging exploration of how Homo sapiens came to rule the world. Over 25 million copies sold globally.',
 16.99, NULL, 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=600&q=80', 145, 20, 'BOOK-004');

-- ── Product gallery images ────────────────────────────────────
INSERT INTO product_images (product_id, image_url, sort_order) VALUES
(1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&q=80', 0),
(1, 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=600&q=80', 1),
(2, 'https://images.unsplash.com/photo-1593359677879-a4bb92f829e1?w=600&q=80', 0),
(3, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=600&q=80', 0),
(4, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&q=80', 0),
(5, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80', 0),
(6, 'https://images.unsplash.com/photo-1625895197185-efcec01cffe0?w=600&q=80', 0),
(7, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&q=80', 0),
(8, 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=600&q=80', 0),
(9, 'https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=600&q=80', 0),
(10,'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=600&q=80', 0),
(11,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&q=80', 0),
(12,'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600&q=80', 0),
(13,'https://images.unsplash.com/photo-1591488320449-011701bb6704?w=600&q=80', 0),
(14,'https://images.unsplash.com/photo-1602928321679-560bb453f190?w=600&q=80', 0),
(15,'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=600&q=80', 0),
(16,'https://images.unsplash.com/photo-1585515320310-259814833e62?w=600&q=80', 0),
(17,'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=80', 0),
(18,'https://images.unsplash.com/photo-1601925228025-7847c4912fce?w=600&q=80', 0),
(19,'https://images.unsplash.com/photo-1598289431512-b97b0917affc?w=600&q=80', 0),
(20,'https://images.unsplash.com/photo-1540497077202-7c8a3999166f?w=600&q=80', 0),
(21,'https://images.unsplash.com/photo-1544216717-3bbf52512659?w=600&q=80', 0),
(22,'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=600&q=80', 0),
(23,'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?w=600&q=80', 0),
(24,'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&q=80', 0);

-- ── Coupons ──────────────────────────────────────────────────
INSERT INTO coupons (code, type, value, min_order, uses_limit, uses_count, expires_at, active) VALUES
('SAVE10',   'percent', 10.00,  0.00, NULL, 12, DATE_ADD(NOW(), INTERVAL 30 DAY),  1),
('FLAT20',   'fixed',   20.00, 100.00,NULL,  5, DATE_ADD(NOW(), INTERVAL 14 DAY),  1),
('NEWUSER',  'percent', 15.00,  0.00,    1,  0, DATE_ADD(NOW(), INTERVAL 60 DAY),  1);

-- ── Admin ─────────────────────────────────────────────────────
-- password: admin123 (bcrypt, cost 12)
INSERT INTO admins (name, email, password, role) VALUES
('Store Admin', 'admin@store.com',
 '$2a$12$ZNzRQvODic4uFOoRa2EA.eTsw9FhqMtVukxqSOEablxjkrMP4QSLW',
 'admin');

-- ── Sample orders (40 orders over last 30 days) ───────────────
INSERT INTO orders (token, customer_name, customer_email, customer_phone, address, city, total, discount, coupon_id, status, created_at) VALUES
('TKN001ABCDEF12', 'Alice Johnson',   'alice@example.com',   '555-0101', '12 Maple St',       'New York',    289.98, 0.00,  NULL, 'delivered',   NOW() - INTERVAL 29 DAY),
('TKN002GHIJKL34', 'Bob Smith',       'bob@example.com',     '555-0102', '45 Oak Ave',        'Chicago',     149.99, 0.00,  NULL, 'delivered',   NOW() - INTERVAL 28 DAY),
('TKN003MNOPQR56', 'Carol White',     'carol@example.com',   '555-0103', '78 Pine Rd',        'Houston',     219.97, 20.00, 2,   'delivered',   NOW() - INTERVAL 27 DAY),
('TKN004STUVWX78', 'David Brown',     'david@example.com',   '555-0104', '23 Elm St',         'Phoenix',     599.99, 0.00,  NULL, 'delivered',   NOW() - INTERVAL 26 DAY),
('TKN005YZABCD90', 'Emma Davis',      'emma@example.com',    '555-0105', '56 Cedar Blvd',     'San Antonio', 74.98,  7.50,  1,   'delivered',   NOW() - INTERVAL 25 DAY),
('TKN006EFGHIJ12', 'Frank Garcia',    'frank@example.com',   '555-0106', '89 Birch Lane',     'San Diego',   349.99, 0.00,  NULL, 'delivered',   NOW() - INTERVAL 24 DAY),
('TKN007KLMNOP34', 'Grace Lee',       'grace@example.com',   '555-0107', '34 Spruce Ave',     'Dallas',      129.97, 0.00,  NULL, 'delivered',   NOW() - INTERVAL 23 DAY),
('TKN008QRSTUV56', 'Henry Martin',    'henry@example.com',   '555-0108', '67 Walnut St',      'San Jose',    449.98, 0.00,  NULL, 'shipped',     NOW() - INTERVAL 22 DAY),
('TKN009WXYZAB78', 'Iris Chen',       'iris@example.com',    '555-0109', '12 Cypress Dr',     'Austin',      89.99,  13.50, 1,   'shipped',     NOW() - INTERVAL 21 DAY),
('TKN010CDEFGH90', 'Jack Wilson',     'jack@example.com',    '555-0110', '45 Magnolia Blvd',  'Jacksonville',189.99, 0.00,  NULL, 'shipped',     NOW() - INTERVAL 20 DAY),
('TKN011IJKLMN12', 'Karen Taylor',    'karen@example.com',   '555-0111', '78 Dogwood Ct',     'Columbus',    64.98,  0.00,  NULL, 'shipped',     NOW() - INTERVAL 19 DAY),
('TKN012OPQRST34', 'Leo Anderson',    'leo@example.com',     '555-0112', '23 Redwood Way',    'Charlotte',   299.99, 30.00, 2,   'shipped',     NOW() - INTERVAL 18 DAY),
('TKN013UVWXYZ56', 'Mia Thomas',      'mia@example.com',     '555-0113', '56 Hazel St',       'Fort Worth',  49.99,  0.00,  NULL, 'processing',  NOW() - INTERVAL 17 DAY),
('TKN014ABCDEF78', 'Noah Jackson',    'noah@example.com',    '555-0114', '89 Juniper Dr',     'Indianapolis',119.98, 0.00,  NULL, 'processing',  NOW() - INTERVAL 16 DAY),
('TKN015GHIJKL90', 'Olivia Harris',   'olivia@example.com',  '555-0115', '34 Chestnut Ln',   'San Francisco',239.97,35.99, 1,   'processing',  NOW() - INTERVAL 15 DAY),
('TKN016MNOPQR12', 'Paul Clark',      'paul@example.com',    '555-0116', '67 Poplar Ave',     'Seattle',     599.99, 0.00,  NULL, 'processing',  NOW() - INTERVAL 14 DAY),
('TKN017STUVWX34', 'Quinn Lewis',     'quinn@example.com',   '555-0117', '12 Sycamore St',    'Denver',      84.98,  0.00,  NULL, 'processing',  NOW() - INTERVAL 13 DAY),
('TKN018YZABCD56', 'Rachel Robinson', 'rachel@example.com',  '555-0118', '45 Beech Blvd',     'Nashville',   174.98, 26.25, 1,   'processing',  NOW() - INTERVAL 12 DAY),
('TKN019EFGHIJ78', 'Sam Walker',      'sam@example.com',     '555-0119', '78 Aspen Way',      'Louisville',  349.99, 0.00,  NULL, 'pending',     NOW() - INTERVAL 11 DAY),
('TKN020KLMNOP90', 'Tina Young',      'tina@example.com',    '555-0120', '23 Larch Ct',       'Portland',    94.97,  0.00,  NULL, 'pending',     NOW() - INTERVAL 10 DAY),
('TKN021QRSTUV12', 'Umar Hall',       'umar@example.com',    '555-0121', '56 Willow Dr',      'Las Vegas',   249.99, 37.50, 1,   'pending',     NOW() - INTERVAL 9 DAY),
('TKN022WXYZAB34', 'Vera Allen',      'vera@example.com',    '555-0122', '89 Elm Ridge',      'Memphis',     39.98,  0.00,  NULL, 'pending',     NOW() - INTERVAL 8 DAY),
('TKN023CDEFGH56', 'Will Scott',      'will@example.com',    '555-0123', '34 Oak Park',       'Baltimore',   189.99, 20.00, 2,   'pending',     NOW() - INTERVAL 7 DAY),
('TKN024IJKLMN78', 'Xena Green',      'xena@example.com',    '555-0124', '67 Maple Ridge',    'Milwaukee',   129.97, 0.00,  NULL, 'pending',     NOW() - INTERVAL 6 DAY),
('TKN025OPQRST90', 'Yusuf Adams',     'yusuf@example.com',   '555-0125', '12 Cedar Ct',       'Albuquerque', 74.98,  0.00,  NULL, 'pending',     NOW() - INTERVAL 5 DAY),
('TKN026UVWXYZ12', 'Zoe Baker',       'zoe@example.com',     '555-0126', '45 Birch St',       'Tucson',      449.98, 67.50, 1,   'pending',     NOW() - INTERVAL 5 DAY),
('TKN027ABCDEF34', 'Aaron Nelson',    'aaron@example.com',   '555-0127', '78 Pine Blvd',      'Fresno',      59.98,  0.00,  NULL, 'pending',     NOW() - INTERVAL 4 DAY),
('TKN028GHIJKL56', 'Beth Carter',     'beth@example.com',    '555-0128', '23 Spruce Rd',      'Sacramento',  299.99, 0.00,  NULL, 'cancelled',   NOW() - INTERVAL 4 DAY),
('TKN029MNOPQR78', 'Chris Mitchell',  'chris@example.com',   '555-0129', '56 Walnut Pkwy',    'Kansas City', 89.97,  0.00,  NULL, 'cancelled',   NOW() - INTERVAL 3 DAY),
('TKN030STUVWX90', 'Diana Perez',     'diana@example.com',   '555-0130', '89 Dogwood Ave',    'Mesa',        149.99, 22.50, 1,   'pending',     NOW() - INTERVAL 3 DAY),
('TKN031YZABCD12', 'Ethan Roberts',   'ethan@example.com',   '555-0131', '34 Redwood Ln',     'Omaha',       219.98, 0.00,  NULL, 'pending',     NOW() - INTERVAL 2 DAY),
('TKN032EFGHIJ34', 'Fiona Turner',    'fiona@example.com',   '555-0132', '67 Hazel Blvd',     'Raleigh',     44.99,  0.00,  NULL, 'pending',     NOW() - INTERVAL 2 DAY),
('TKN033KLMNOP56', 'George Phillips', 'george@example.com',  '555-0133', '12 Juniper St',     'Colorado Springs', 599.99, 0.00, NULL,'pending',  NOW() - INTERVAL 2 DAY),
('TKN034QRSTUV78', 'Hannah Campbell', 'hannah@example.com',  '555-0134', '45 Chestnut Ave',   'Atlanta',     164.97, 24.75, 1,   'pending',     NOW() - INTERVAL 1 DAY),
('TKN035WXYZAB90', 'Ivan Parker',     'ivan@example.com',    '555-0135', '78 Poplar Ct',      'Virginia Beach',349.99,0.00, NULL, 'pending',    NOW() - INTERVAL 1 DAY),
('TKN036CDEFGH12', 'Julia Evans',     'julia@example.com',   '555-0136', '23 Sycamore Dr',    'Minneapolis',  79.97, 0.00,  NULL, 'pending',    NOW() - INTERVAL 1 DAY),
('TKN037IJKLMN34', 'Kyle Edwards',    'kyle@example.com',    '555-0137', '56 Beech St',       'Tulsa',       239.98, 0.00,  NULL, 'pending',    NOW() - INTERVAL 12 HOUR),
('TKN038OPQRST56', 'Laura Collins',   'laura@example.com',   '555-0138', '89 Aspen Blvd',     'Tampa',       129.99, 19.50, 1,   'pending',    NOW() - INTERVAL 8 HOUR),
('TKN039UVWXYZ78', 'Marcus Stewart',  'marcus@example.com',  '555-0139', '34 Larch Ave',      'Arlington',   49.98,  0.00,  NULL, 'pending',    NOW() - INTERVAL 4 HOUR),
('TKN040ABCDEF90', 'Nina Morris',     'nina@example.com',    '555-0140', '67 Willow Way',     'New Orleans', 399.98, 0.00,  NULL, 'pending',    NOW() - INTERVAL 1 HOUR);

-- ── Order items (2–4 per order, referencing real products) ────
INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, created_at) VALUES
(1,  1,  'Wireless Noise-Cancelling Headphones', 1, 149.99, NOW() - INTERVAL 29 DAY),
(1,  7,  'Premium Cotton Crew-Neck Tee',          2, 29.99,  NOW() - INTERVAL 29 DAY),
(1,  22, 'Atomic Habits – James Clear',            2, 14.99,  NOW() - INTERVAL 29 DAY),
(2,  1,  'Wireless Noise-Cancelling Headphones', 1, 149.99, NOW() - INTERVAL 28 DAY),
(3,  3,  'Mechanical Gaming Keyboard RGB',        1, 89.99,  NOW() - INTERVAL 27 DAY),
(3,  6,  'USB-C 100W Charging Hub',               1, 39.99,  NOW() - INTERVAL 27 DAY),
(3,  22, 'Atomic Habits – James Clear',            3, 14.99,  NOW() - INTERVAL 27 DAY),
(4,  2,  'Ultra 4K Smart TV 55"',                 1, 599.99, NOW() - INTERVAL 26 DAY),
(5,  11, 'Running Performance Shorts',             1, 34.99,  NOW() - INTERVAL 25 DAY),
(5,  22, 'Atomic Habits – James Clear',            2, 14.99,  NOW() - INTERVAL 25 DAY),
(6,  17, 'Adjustable Dumbbell Set 5–52lb',         1, 349.99, NOW() - INTERVAL 24 DAY),
(7,  7,  'Premium Cotton Crew-Neck Tee',           2, 29.99,  NOW() - INTERVAL 23 DAY),
(7,  9,  'Hooded Fleece Pullover',                 1, 44.99,  NOW() - INTERVAL 23 DAY),
(7,  22, 'Atomic Habits – James Clear',            1, 14.99,  NOW() - INTERVAL 23 DAY),
(8,  2,  'Ultra 4K Smart TV 55"',                  1, 599.99, NOW() - INTERVAL 22 DAY),
(8,  22, 'Atomic Habits – James Clear',             1, 14.99,  NOW() - INTERVAL 22 DAY),
(8,  23, 'Deep Work – Cal Newport',                 1, 13.99,  NOW() - INTERVAL 22 DAY),
(9,  3,  'Mechanical Gaming Keyboard RGB',          1, 89.99,  NOW() - INTERVAL 21 DAY),
(10, 10, 'Waterproof Hiking Jacket',                1, 189.99, NOW() - INTERVAL 20 DAY),
(11, 18, 'Yoga Mat Premium 6mm',                    1, 39.99,  NOW() - INTERVAL 19 DAY),
(11, 19, 'Resistance Band Set (5 levels)',           1, 24.99,  NOW() - INTERVAL 19 DAY),
(12, 12, 'Stainless Steel Cookware Set 10pc',        1, 299.99, NOW() - INTERVAL 18 DAY),
(13, 4,  'Portable Bluetooth Speaker',               1, 49.99,  NOW() - INTERVAL 17 DAY),
(14, 14, 'Aromatherapy Diffuser & Humidifier',       1, 49.99,  NOW() - INTERVAL 16 DAY),
(14, 15, 'Solar LED Garden String Lights',           2, 24.99,  NOW() - INTERVAL 16 DAY),
(15, 3,  'Mechanical Gaming Keyboard RGB',           1, 89.99,  NOW() - INTERVAL 15 DAY),
(15, 6,  'USB-C 100W Charging Hub',                  2, 39.99,  NOW() - INTERVAL 15 DAY),
(15, 22, 'Atomic Habits – James Clear',              1, 14.99,  NOW() - INTERVAL 15 DAY),
(16, 2,  'Ultra 4K Smart TV 55"',                    1, 599.99, NOW() - INTERVAL 14 DAY),
(17, 7,  'Premium Cotton Crew-Neck Tee',             2, 29.99,  NOW() - INTERVAL 13 DAY),
(17, 13, 'Bamboo Cutting Board Set',                 1, 34.99,  NOW() - INTERVAL 13 DAY),
(18, 1,  'Wireless Noise-Cancelling Headphones',     1, 149.99, NOW() - INTERVAL 12 DAY),
(18, 7,  'Premium Cotton Crew-Neck Tee',             1, 29.99,  NOW() - INTERVAL 12 DAY),
(19, 17, 'Adjustable Dumbbell Set 5–52lb',            1, 349.99, NOW() - INTERVAL 11 DAY),
(20, 8,  'Slim-Fit Chino Trousers',                   1, 59.99,  NOW() - INTERVAL 10 DAY),
(20, 22, 'Atomic Habits – James Clear',               1, 14.99,  NOW() - INTERVAL 10 DAY),
(20, 23, 'Deep Work – Cal Newport',                   1, 13.99,  NOW() - INTERVAL 10 DAY),
(21, 5,  'Smartwatch Pro Series 5',                   1, 249.99, NOW() - INTERVAL 9 DAY),
(22, 18, 'Yoga Mat Premium 6mm',                      1, 39.99,  NOW() - INTERVAL 8 DAY),
(23, 10, 'Waterproof Hiking Jacket',                  1, 189.99, NOW() - INTERVAL 7 DAY),
(24, 1,  'Wireless Noise-Cancelling Headphones',      1, 149.99, NOW() - INTERVAL 6 DAY),
(24, 22, 'Atomic Habits – James Clear',               1, 14.99,  NOW() - INTERVAL 6 DAY),
(24, 23, 'Deep Work – Cal Newport',                   1, 13.99,  NOW() - INTERVAL 6 DAY),
(25, 4,  'Portable Bluetooth Speaker',                1, 49.99,  NOW() - INTERVAL 5 DAY),
(25, 19, 'Resistance Band Set (5 levels)',             1, 24.99,  NOW() - INTERVAL 5 DAY),
(26, 2,  'Ultra 4K Smart TV 55"',                     1, 599.99, NOW() - INTERVAL 5 DAY),
(26, 6,  'USB-C 100W Charging Hub',                   1, 39.99,  NOW() - INTERVAL 5 DAY),
(27, 7,  'Premium Cotton Crew-Neck Tee',               2, 29.99,  NOW() - INTERVAL 4 DAY),
(28, 12, 'Stainless Steel Cookware Set 10pc',          1, 299.99, NOW() - INTERVAL 4 DAY),
(29, 21, 'Foam Roller Deep Tissue 18"',                1, 29.99,  NOW() - INTERVAL 3 DAY),
(29, 22, 'Atomic Habits – James Clear',                2, 14.99,  NOW() - INTERVAL 3 DAY),
(29, 23, 'Deep Work – Cal Newport',                    1, 13.99,  NOW() - INTERVAL 3 DAY),
(30, 1,  'Wireless Noise-Cancelling Headphones',       1, 149.99, NOW() - INTERVAL 3 DAY),
(31, 3,  'Mechanical Gaming Keyboard RGB',             1, 89.99,  NOW() - INTERVAL 2 DAY),
(31, 6,  'USB-C 100W Charging Hub',                   2, 39.99,  NOW() - INTERVAL 2 DAY),
(32, 11, 'Running Performance Shorts',                 1, 44.99,  NOW() - INTERVAL 2 DAY),
(33, 2,  'Ultra 4K Smart TV 55"',                      1, 599.99, NOW() - INTERVAL 2 DAY),
(34, 1,  'Wireless Noise-Cancelling Headphones',       1, 149.99, NOW() - INTERVAL 1 DAY),
(34, 7,  'Premium Cotton Crew-Neck Tee',               1, 29.99,  NOW() - INTERVAL 1 DAY),
(35, 17, 'Adjustable Dumbbell Set 5–52lb',              1, 349.99, NOW() - INTERVAL 1 DAY),
(36, 7,  'Premium Cotton Crew-Neck Tee',               1, 29.99,  NOW() - INTERVAL 1 DAY),
(36, 9,  'Hooded Fleece Pullover',                     1, 44.99,  NOW() - INTERVAL 1 DAY),
(37, 3,  'Mechanical Gaming Keyboard RGB',              1, 89.99,  NOW() - INTERVAL 12 HOUR),
(37, 16, 'Cast Iron Dutch Oven 6Qt',                    1, 119.99, NOW() - INTERVAL 12 HOUR),
(38, 1,  'Wireless Noise-Cancelling Headphones',        1, 149.99, NOW() - INTERVAL 8 HOUR),
(39, 18, 'Yoga Mat Premium 6mm',                         1, 39.99,  NOW() - INTERVAL 4 HOUR),
(39, 19, 'Resistance Band Set (5 levels)',               1, 24.99,  NOW() - INTERVAL 4 HOUR),
(40, 17, 'Adjustable Dumbbell Set 5–52lb',               1, 349.99, NOW() - INTERVAL 1 HOUR),
(40, 22, 'Atomic Habits – James Clear',                  1, 14.99,  NOW() - INTERVAL 1 HOUR),
(40, 23, 'Deep Work – Cal Newport',                      1, 13.99,  NOW() - INTERVAL 1 HOUR),
(40, 24, 'The Art of Clean Code',                        1, 34.99,  NOW() - INTERVAL 1 HOUR);

SET foreign_key_checks = 1;
