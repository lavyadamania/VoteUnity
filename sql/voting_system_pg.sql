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
    photo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Votes table (immutable ledger with Merkle tree)
CREATE TABLE IF NOT EXISTS votes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    candidate_id INTEGER NOT NULL,
    vote_hash VARCHAR(64) NOT NULL,
    previous_hash VARCHAR(64) NOT NULL,
    encrypted_vote TEXT NULL,
    block_index INTEGER NULL,
    nonce VARCHAR(32) NULL,
    merkle_root VARCHAR(64) NULL,
    vote_receipt VARCHAR(64) NULL UNIQUE,
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

-- Audit logs table (security event tracking)
CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    event_type VARCHAR(30) NOT NULL,
    actor_type VARCHAR(10) NOT NULL DEFAULT 'system',
    actor_id INTEGER NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_audit_event_type ON audit_logs(event_type);
CREATE INDEX IF NOT EXISTS idx_audit_actor ON audit_logs(actor_type, actor_id);
CREATE INDEX IF NOT EXISTS idx_audit_created_at ON audit_logs(created_at);

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

-- Insert default super admin (username: lavya, password: admin123, all permissions)
-- Hash: $2y$10$6Dm5GLznGsyAdlJBA0l2kOA4J1wkQ/2e/sDcpBajK8ryqWryPZ4zi (admin123)
INSERT INTO admins (username, password, is_approved, is_super_admin, can_view_votes, can_manage_candidates, can_reset_votes, can_manage_admins) 
SELECT 'lavya', '$2y$10$6Dm5GLznGsyAdlJBA0l2kOA4J1wkQ/2e/sDcpBajK8ryqWryPZ4zi', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE
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
