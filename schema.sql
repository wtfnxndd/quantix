CREATE DATABASE IF NOT EXISTS quantix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quantix;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS releases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(40) NOT NULL UNIQUE,
    title VARCHAR(160) NOT NULL,
    notes TEXT NOT NULL,
    status ENUM('PLANNED', 'IN PROGRESS', 'RELEASED') NOT NULL DEFAULT 'PLANNED',
    release_date DATE NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_release_user FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS query_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    query_text TEXT NOT NULL,
    status ENUM('SUCCESS', 'ERROR', 'BLOCKED') NOT NULL,
    row_count INT UNSIGNED NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_query_user FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_query_executed_at (executed_at)
);

INSERT IGNORE INTO releases (version, title, notes, status, release_date, created_by)
SELECT '1.0.0', 'Quantix inventory foundation', 'Dashboard, product catalog, warehouses, movements, customers, vendors, orders, reports, and authentication.', 'RELEASED', CURRENT_DATE, id
FROM users WHERE email = 'admin@quantix.local' LIMIT 1;

INSERT IGNORE INTO users (name, email, password_hash, role) VALUES
('Quantix Administrator', 'admin@quantix.local', '$2y$10$5XWeELblg2Gd.wj87M3Eiu2Pjh4js7dfKEemW.i6ODMpltCyELE2O', 'admin'),
('Store Staff', 'staff@quantix.local', '$2y$10$EWA5pVR8Q6tFLc40txITxe4cwyKCDgISY3ydlspxoEgpyIHZEggji', 'staff');

CREATE TABLE IF NOT EXISTS warehouses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    sku VARCHAR(60) NOT NULL UNIQUE,
    category VARCHAR(100) NOT NULL,
    stock_type VARCHAR(60) NOT NULL DEFAULT 'Finished Goods',
    unit VARCHAR(30) NOT NULL DEFAULT 'units',
    reorder_level DECIMAL(12,2) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS inventory_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    movement_number VARCHAR(40) NOT NULL UNIQUE,
    product_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    movement_type ENUM('IN','OUT','TRANSFER','ADJUSTMENT') NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    stock_after DECIMAL(12,2) NOT NULL,
    reference VARCHAR(80) NOT NULL,
    movement_date DATETIME NOT NULL,
    CONSTRAINT fk_movement_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_movement_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    INDEX idx_movement_date (movement_date),
    INDEX idx_movement_type (movement_type)
);

INSERT IGNORE INTO warehouses (id, name, code) VALUES
(1, 'North Distribution Hub', 'NDH'),
(2, 'Central Fulfillment', 'CFL'),
(3, 'South Reserve Store', 'SRS');

INSERT IGNORE INTO products (id, name, sku, category, stock_type, unit, reorder_level) VALUES
(1, 'Bottled drinking water', 'GRO-001', 'Groceries', 'Finished Goods', 'bottles', 40),
(2, 'Long-grain rice 5kg', 'GRO-002', 'Groceries', 'Finished Goods', 'bags', 25),
(3, 'Cooking oil 1L', 'GRO-003', 'Groceries', 'Finished Goods', 'bottles', 30),
(4, 'White sugar 1kg', 'GRO-004', 'Groceries', 'Finished Goods', 'bags', 30),
(5, 'Bath soap bar', 'HOU-001', 'Personal Care', 'Consumables', 'bars', 35),
(6, 'Toothpaste 100ml', 'HOU-002', 'Personal Care', 'Consumables', 'tubes', 25),
(7, 'Toilet tissue  pack', 'HOU-003', 'Household', 'Consumables', 'packs', 30),
(8, 'Laundry detergent 2kg', 'HOU-004', 'Cleaning', 'Consumables', 'bags', 20),
(9, 'AA alkaline batteries', 'ELE-001', 'Electronics', 'Consumables', 'packs', 18),
(10, 'A4 writing notebook', 'STA-001', 'Stationery', 'Office Supplies', 'books', 20),
(11, 'Blue ballpoint pens', 'STA-002', 'Stationery', 'Office Supplies', 'packs', 25),
(12, 'Facial tissue box', 'HOU-005', 'Household', 'Consumables', 'boxes', 25),
(13, 'Breakfast cereal 500g', 'GRO-005', 'Groceries', 'Finished Goods', 'boxes', 20),
(14, 'Ground coffee 250g', 'GRO-006', 'Groceries', 'Finished Goods', 'packs', 15),
(15, 'Black tea bags 100s', 'GRO-007', 'Groceries', 'Finished Goods', 'boxes', 15);

CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    email VARCHAR(160) NULL,
    phone VARCHAR(40) NULL,
    address VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vendors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    email VARCHAR(160) NULL,
    phone VARCHAR(40) NULL,
    address VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sales_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(40) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NOT NULL,
    status ENUM('DRAFT', 'CONFIRMED', 'SHIPPED', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(40) NOT NULL UNIQUE,
    vendor_id INT UNSIGNED NOT NULL,
    status ENUM('DRAFT', 'CONFIRMED', 'RECEIVED', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

INSERT IGNORE INTO customers (id, name, code, email, phone) VALUES
(1, 'Green Valley Supermarket', 'CUS-001', 'orders@greenvalley.example', '+1 555 0101'),
(2, 'Daily Needs Mini Mart', 'CUS-002', 'buying@dailyneeds.example', '+1 555 0102');

INSERT IGNORE INTO vendors (id, name, code, email, phone) VALUES
(1, 'Fresh Foods Wholesale', 'VEN-001', 'sales@freshfoods.example', '+1 555 0201'),
(2, 'Clean Home Distributors', 'VEN-002', 'orders@cleanhome.example', '+1 555 0202');

INSERT IGNORE INTO sales_orders (order_number, customer_id, status, total) VALUES
('SO-2219', 1, 'CONFIRMED', 1840.00), ('SO-2214', 2, 'SHIPPED', 960.00);

INSERT IGNORE INTO purchase_orders (order_number, vendor_id, status, total) VALUES
('PO-8031', 1, 'RECEIVED', 3250.00), ('PO-8028', 2, 'COMPLETED', 1420.00);

INSERT IGNORE INTO inventory_movements
(movement_number, product_id, warehouse_id, movement_type, quantity, stock_after, reference, movement_date) VALUES
('MOV-1048', 1, 1, 'IN', 48, 126, 'PO-8031', NOW() - INTERVAL 2 HOUR),
('MOV-1047', 2, 2, 'OUT', -18, 64, 'SO-2219', NOW() - INTERVAL 5 HOUR),
('MOV-1046', 3, 1, 'TRANSFER', 30, 92, 'TRF-118', NOW() - INTERVAL 8 HOUR),
('MOV-1045', 4, 3, 'ADJUSTMENT', -6, 11, 'ADJ-042', NOW() - INTERVAL 1 DAY),
('MOV-1044', 5, 2, 'OUT', -12, 38, 'SO-2214', NOW() - INTERVAL 1 DAY),
('MOV-1043', 1, 2, 'IN', 25, 78, 'PO-8028', NOW() - INTERVAL 2 DAY);
