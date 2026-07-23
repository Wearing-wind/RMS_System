-- QR Code Restaurant Ordering System - Database Schema
-- All prices in Nepali Rupees (Rs.)
-- Run this SQL to create all required tables

-- Create database
CREATE DATABASE IF NOT EXISTS qr_restaurant;
USE qr_restaurant;

-- Tables table
CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Menu items table (prices in Nepali Rupees)
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    category_id INT NOT NULL,
    status ENUM('active', 'inactive', 'sold_out') DEFAULT 'active',
    is_popular TINYINT(1) DEFAULT 0,
    preparation_time INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) NOT NULL,
    customer_name VARCHAR(100),
    notes TEXT,
    status ENUM('new', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'new',
    total_amount DECIMAL(10, 2) DEFAULT 0,
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Waiter calls table (for Call Waiter feature)
CREATE TABLE IF NOT EXISTS waiter_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) NOT NULL,
    status ENUM('pending', 'served') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payment Settings table (for QR code)
CREATE TABLE IF NOT EXISTS payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_name VARCHAR(200) DEFAULT 'QR Restaurant',
    payment_note VARCHAR(500),
    qr_code_image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admin_users (username, password, full_name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

-- Insert default payment settings
INSERT INTO payment_settings (restaurant_name, payment_note) VALUES 
('QR Restaurant', 'Scan QR to pay via Esewa/Khalti');

-- Insert sample tables
INSERT INTO tables (table_number) VALUES 
('1'), ('2'), ('3'), ('4'), ('5'), ('6'), ('7'), ('8'), ('9'), ('10');

-- Insert sample categories
INSERT INTO categories (name, description) VALUES 
('Burgers', 'Delicious gourmet burgers'),
('Pizza', 'Freshly baked pizzas'),
('Drinks', 'Refreshing beverages'),
('Desserts', 'Sweet treats'),
('Sides', 'Perfect accompaniments');

-- Insert sample menu items (prices in Nepali Rupees - Rs.)
INSERT INTO menu_items (name, description, price, image, category_id, status, is_popular, preparation_time) VALUES
-- Burgers (Rs.)
('Classic Burger', 'Juicy beef patty with lettuce, tomato, onion, and special sauce', 350, 'burger1.jpg', 1, 'active', 1, 15),
('Cheese Burger', 'Classic burger topped with melted cheddar cheese', 399, 'burger2.jpg', 1, 'active', 1, 15),
('Bacon Burger', 'Loaded with crispy bacon and BBQ sauce', 499, 'burger3.jpg', 1, 'active', 0, 18),
('Veggie Burger', 'Plant-based patty with fresh vegetables', 449, 'burger4.jpg', 1, 'active', 0, 15),
('Double Burger', 'Double patty for double satisfaction', 599, 'burger5.jpg', 1, 'active', 1, 20),

-- Pizza (Rs.)
('Margherita Pizza', 'Fresh tomatoes, mozzarella, basil', 549, 'pizza1.jpg', 2, 'active', 1, 25),
('Pepperoni Pizza', 'Classic pepperoni with extra cheese', 649, 'pizza2.jpg', 2, 'active', 1, 25),
('BBQ Chicken Pizza', 'Grilled chicken, BBQ sauce, red onions', 699, 'pizza3.jpg', 2, 'active', 0, 28),
('Veggie Supreme', 'Bell peppers, mushrooms, olives, onions', 599, 'pizza4.jpg', 2, 'active', 0, 25),
('Hawaiian Pizza', 'Ham and pineapple classic', 649, 'pizza5.jpg', 2, 'active', 0, 25),

-- Drinks (Rs.)
('Cola', 'Refreshing cola drink', 50, 'drink1.jpg', 3, 'active', 1, 2),
('Lemonade', 'Fresh squeezed lemonade', 80, 'drink2.jpg', 3, 'active', 1, 3),
('Iced Tea', 'Cold brewed iced tea', 70, 'drink3.jpg', 3, 'active', 0, 2),
('Coffee', 'Freshly brewed coffee', 100, 'drink4.jpg', 3, 'active', 0, 5),
('Milkshake', 'Creamy vanilla milkshake', 199, 'drink5.jpg', 3, 'active', 1, 5),

-- Desserts (Rs.)
('Chocolate Cake', 'Rich chocolate layered cake', 250, 'dessert1.jpg', 4, 'active', 1, 5),
('Ice Cream', 'Three scoops of premium ice cream', 199, 'dessert2.jpg', 4, 'active', 1, 3),
('Cheesecake', 'New York style cheesecake', 299, 'dessert3.jpg', 4, 'active', 0, 5),
('Apple Pie', 'Warm apple pie with cinnamon', 199, 'dessert4.jpg', 4, 'active', 0, 8),
('Brownie', 'Fudge brownie with whipped cream', 150, 'dessert5.jpg', 4, 'active', 0, 5),

-- Sides (Rs.)
('French Fries', 'Crispy golden fries', 120, 'side1.jpg', 5, 'active', 1, 8),
('Onion Rings', 'Crispy battered onion rings', 150, 'side2.jpg', 5, 'active', 0, 10),
('Chicken Wings', 'Spicy chicken wings (6 pcs)', 350, 'side3.jpg', 5, 'active', 1, 15),
('Caesar Salad', 'Fresh romaine lettuce with caesar dressing', 199, 'side4.jpg', 5, 'active', 0, 5),
('Garlic Bread', 'Toasted garlic bread', 100, 'side5.jpg', 5, 'active', 0, 5);
