CREATE DATABASE IF NOT EXISTS newsportal_db;
USE newsportal_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor') DEFAULT 'editor',
    profile_image VARCHAR(255) DEFAULT 'default_avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(100) DEFAULT 'folder',
    color VARCHAR(20) DEFAULT '#6366f1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Posts Table
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    video_url VARCHAR(255),
    external_link TEXT,
    external_type ENUM('none', 'url', 'whatsapp', 'call') DEFAULT 'none',
    external_label ENUM('none', 'Ad', 'Promoted', 'Sponsored') DEFAULT 'none',
    status ENUM('draft', 'published') DEFAULT 'draft',
    views INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    meta_description VARCHAR(160),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Pivot Table for Multi-Categories
CREATE TABLE IF NOT EXISTS post_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    category_id INT NOT NULL,
    KEY (post_id),
    KEY (category_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Ads Table
CREATE TABLE IF NOT EXISTS ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(50) NOT NULL,
    type ENUM('image', 'code') NOT NULL,
    image_path VARCHAR(255),
    link_url TEXT,
    link_type ENUM('url', 'whatsapp', 'call') DEFAULT 'url',
    ad_code TEXT,
    start_date DATE,
    end_date DATE,
    status BOOLEAN DEFAULT TRUE,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Epapers Table
CREATE TABLE IF NOT EXISTS epapers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    paper_date DATE NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Admin (Password: admin123)
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi is 'password'
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert Initial Categories
INSERT INTO categories (name, slug, color) VALUES 
('Technology', 'technology', '#3b82f6'),
('Business', 'business', '#10b981'),
('Health', 'health', '#ef4444'),
('Entertainment', 'entertainment', '#f59e0b'),
('Sports', 'sports', '#6366f1');
