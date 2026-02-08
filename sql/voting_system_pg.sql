-- ============================================
-- VOTEUNITY - POSTGRESQL SCHEMA (for Supabase)
-- Run this in your Supabase SQL Editor
-- ============================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    aadhaar_number VARCHAR(12) UNIQUE NOT NULL,
    face_image VARCHAR(255),
    has_voted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Candidates table
CREATE TABLE IF NOT EXISTS candidates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    party VARCHAR(100) NOT NULL,
    symbol VARCHAR(50),
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Votes table (with blockchain-style hash chain)
CREATE TABLE IF NOT EXISTS votes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    candidate_id INTEGER NOT NULL,
    vote_hash VARCHAR(64) NOT NULL,
    previous_hash VARCHAR(64) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id)
);

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    face_image VARCHAR(255),
    is_super_admin BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin login locations
CREATE TABLE IF NOT EXISTS admin_locations (
    id SERIAL PRIMARY KEY,
    admin_id INTEGER NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- Insert default super admin (password: admin123)
INSERT INTO admins (username, password, is_super_admin, is_approved) 
SELECT 'lavya', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'lavya');

-- Insert sample candidates
INSERT INTO candidates (name, party, symbol) VALUES 
('Rahul Gandhi', 'Indian National Congress', '✋'),
('Narendra Modi', 'Bharatiya Janata Party', '🪷'),
('Arvind Kejriwal', 'Aam Aadmi Party', '🧹'),
('Mamata Banerjee', 'All India Trinamool Congress', '🌸')
ON CONFLICT DO NOTHING;
