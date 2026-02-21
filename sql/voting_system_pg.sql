-- ============================================
-- VOTEUNITY - POSTGRESQL SCHEMA (for Neon/Supabase)
-- Run this in your Neon SQL Console
-- ============================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    aadhaar_number VARCHAR(12) UNIQUE NOT NULL,
    face_image TEXT,
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

-- Admins table (with permissions and approval workflow)
CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    face_image TEXT,
    is_super_admin BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by INTEGER NULL,
    can_view_votes BOOLEAN DEFAULT TRUE,
    can_manage_candidates BOOLEAN DEFAULT FALSE,
    can_reset_votes BOOLEAN DEFAULT FALSE,
    can_manage_admins BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- Admin login locations (with full tracking fields)
CREATE TABLE IF NOT EXISTS admin_locations (
    id SERIAL PRIMARY KEY,
    admin_id INTEGER NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    accuracy DECIMAL(10, 2) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    tracked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- Insert default super admin (username: lavya, password: admin123)
-- Hash: $2y$10$O0kR.S3T0C9P6S1P7P6P7O0P0P0P0P0P0P0P0P0P0P0P0P0P0P0P. (Updated to 'admin123')
INSERT INTO admins (username, password, is_super_admin, is_approved) 
SELECT 'lavya', '$2y$10$SZoDq/hiEDdeRk9C3CAjZuEM4rPNEECj66GXJWBRVeetI4a6dbASBi', TRUE, TRUE
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'lavya');

-- Insert sample candidates (Idempotent: only inserts if the name/party doesn't exist)
INSERT INTO candidates (name, party, symbol) 
SELECT 'Rahul Gandhi', 'Indian National Congress', '✋'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Rahul Gandhi' AND party = 'Indian National Congress');

INSERT INTO candidates (name, party, symbol) 
SELECT 'Narendra Modi', 'Bharatiya Janata Party', '🪷'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Narendra Modi' AND party = 'Bharatiya Janata Party');

INSERT INTO candidates (name, party, symbol) 
SELECT 'Arvind Kejriwal', 'Aam Aadmi Party', '🧹'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Arvind Kejriwal' AND party = 'Aam Aadmi Party');

INSERT INTO candidates (name, party, symbol) 
SELECT 'Mamata Banerjee', 'All India Trinamool Congress', '🌸'
WHERE NOT EXISTS (SELECT 1 FROM candidates WHERE name = 'Mamata Banerjee' AND party = 'All India Trinamool Congress');
