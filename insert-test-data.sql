-- Insert test sellers
INSERT INTO users (id, name, phone, password, role, inn, bank_account, mfo, status) 
VALUES 
('u_seller1', 'Seller 1 Shop', '998901234567', '123456', 'seller', '123456789', '12345678901234567890', '00000', 'active'),
('u_seller2', 'Seller 2 Shop', '998902234567', '123456', 'seller', '987654321', '12345678901234567890', '00000', 'active');

-- Insert test buyer
INSERT INTO users (id, name, phone, password, role, status) 
VALUES 
('u_buyer1', 'Test Buyer', '998903234567', '123456', 'buyer', 'active');

-- Insert test products
INSERT INTO products (id, seller_id, name, sku, category, price, unit, region, model, status, image, created_at) 
VALUES 
('p_test1', 'u_seller1', 'Kompyuter Monitori', 'MON-001', 'electronics', 500000, 'dona', 'Toshkent shahri', 'realization', 'active', 'uploads/products/monitor.jpg', NOW()),
('p_test2', 'u_seller1', 'Ofis Stoli', 'DSK-001', 'furniture', 1000000, 'dona', 'Toshkent viloyati', 'prepayment', 'active', 'uploads/products/desk.jpg', NOW()),
('p_test3', 'u_seller2', 'Qurilish Mixi', 'BLD-001', 'building', 250000, 'dona', 'Samarqand', 'realization', 'active', 'uploads/products/mixer.jpg', NOW());
