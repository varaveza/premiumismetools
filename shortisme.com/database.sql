-- Database schema for shortlink system
-- Menggunakan database yang sama dengan premiumisme.co
-- CREATE DATABASE IF NOT EXISTS premiumisme_db;
-- USE premiumisme_db;

-- Pastikan menggunakan database premiumisme_db
USE premiumisme_db;

-- Main shortlinks table
CREATE TABLE shortlinks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(10) UNIQUE NOT NULL,
    original_url TEXT NOT NULL,
    clicks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for optimal performance
    INDEX idx_slug (slug),
    INDEX idx_created_at (created_at),
    INDEX idx_clicks (clicks)
);

-- Optional: Analytics table for detailed tracking
CREATE TABLE link_analytics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    shortlink_id BIGINT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer TEXT,
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (shortlink_id) REFERENCES shortlinks(id) ON DELETE CASCADE,
    INDEX idx_shortlink_id (shortlink_id),
    INDEX idx_clicked_at (clicked_at)
);

-- Sample data for testing
INSERT INTO shortlinks (slug, original_url, clicks) VALUES
('abc123', 'https://example.com/very-long-url-here', 0),
('def456', 'https://google.com/search?q=test', 0);
