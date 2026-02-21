-- ============================================
-- VOTEUNITY - DATABASE SCHEMA
-- Run this on your cloud MySQL to initialize the database
-- ============================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    aadhaar_number VARCHAR(12) UNIQUE NOT NULL,
    face_image VARCHAR(255),
    has_voted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Candidates table
CREATE TABLE IF NOT EXISTS candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    party VARCHAR(100) NOT NULL,
    symbol VARCHAR(50),
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Votes table (with blockchain-style hash chain)
CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    candidate_id INT NOT NULL,
    vote_hash VARCHAR(64) NOT NULL,
    previous_hash VARCHAR(64) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id)
);

-- Admins table (with permissions and approval workflow)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    face_image VARCHAR(255),
    is_super_admin TINYINT(1) DEFAULT 0,
    is_approved TINYINT(1) DEFAULT 0,
    approved_by INT NULL,
    can_view_votes TINYINT(1) DEFAULT 1,
    can_manage_candidates TINYINT(1) DEFAULT 0,
    can_reset_votes TINYINT(1) DEFAULT 0,
    can_manage_admins TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- Admin login locations (with full tracking fields)
CREATE TABLE IF NOT EXISTS admin_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    accuracy DECIMAL(10, 2) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    tracked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- Insert default super admin (password: admin123)
INSERT INTO admins (username, password, is_approved, is_super_admin) 
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'admin');

-- Insert sample candidates (Idempotent: only inserts if the name/party doesn't exist)
INSERT INTO candidates (name, party, symbol) 
SELECT 'Rahul Gandhi', 'Indian National Congress', '✋' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Rahul Gandhi' AND party = 'Indian National Congress');

INSERT INTO candidates (name, party, symbol) 
SELECT 'Narendra Modi', 'Bharatiya Janata Party', '🪷' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Narendra Modi' AND party = 'Bharatiya Janata Party');

INSERT INTO candidates (name, party, symbol) 
SELECT 'Arvind Kejriwal', 'Aam Aadmi Party', '🧹' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Arvind Kejriwal' AND party = 'Aam Aadmi Party');

INSERT INTO candidates (name, party, symbol) 
SELECT 'Mamata Banerjee', 'All India Trinamool Congress', '🌸' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Mamata Banerjee' AND party = 'All India Trinamool Congress');