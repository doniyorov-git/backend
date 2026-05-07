CREATE DATABASE IF NOT EXISTS mydilleruz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mydilleruz;

DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS ticket_replies;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS contract_signatures;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    inn VARCHAR(20),
    phone VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'seller', 'buyer') NOT NULL,
    status ENUM('active', 'blocked') DEFAULT 'active',
    bank_account VARCHAR(50),
    mfo VARCHAR(10),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(50) PRIMARY KEY,
    seller_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(50),
    category VARCHAR(100),
    price DECIMAL(15,2) NOT NULL,
    unit VARCHAR(20),
    image VARCHAR(255),
    region VARCHAR(100),
    model VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected', 'active', 'inactive') DEFAULT 'pending',
    moderation_note TEXT,
    moderated_by VARCHAR(50),
    moderated_at DATETIME,
    view_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(100),
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_id, is_read, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id VARCHAR(50) PRIMARY KEY,
    buyer_id VARCHAR(50) NOT NULL,
    seller_id VARCHAR(50) NOT NULL,
    total DECIMAL(15,2) NOT NULL,
    status ENUM('pending_seller_accept', 'seller_accepted', 'dispatched', 'delivered', 'buyer_accepted', 'buyer_paid', 'trade_closed', 'seller_paid_comm', 'paid') DEFAULT 'pending_seller_accept',
    comm DECIMAL(15,2) DEFAULT 0,
    comm_status ENUM('pending', 'pending_admin', 'paid') DEFAULT 'pending',
    dispatch_report VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contract_signatures (
    id VARCHAR(50) PRIMARY KEY,
    contract_type ENUM('platform_terms', 'seller_listing', 'buyer_order') NOT NULL,
    title VARCHAR(255) NOT NULL,
    signer_id VARCHAR(50) NOT NULL,
    counterparty_id VARCHAR(50),
    product_id VARCHAR(50),
    order_id VARCHAR(50),
    source VARCHAR(50),
    content MEDIUMTEXT NOT NULL,
    signer_snapshot TEXT,
    counterparty_snapshot TEXT,
    ip_address VARCHAR(64),
    user_agent VARCHAR(255),
    signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contracts_signer (signer_id, signed_at),
    INDEX idx_contracts_counterparty (counterparty_id, signed_at),
    INDEX idx_contracts_order (order_id),
    INDEX idx_contracts_product (product_id),
    FOREIGN KEY (signer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (counterparty_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS reports (
    id VARCHAR(50) PRIMARY KEY,
    seller_id VARCHAR(50),
    order_id VARCHAR(50),
    prod_id VARCHAR(50),
    status ENUM('pending', 'done') DEFAULT 'pending',
    due_date DATETIME,
    note TEXT,
    image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tickets (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ticket_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(50) NOT NULL,
    author_name VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);

INSERT INTO users (id, name, phone, password, role) 
VALUES ('u_admin', 'Tizim Administratori', '998901234567', 'admin', 'admin');
