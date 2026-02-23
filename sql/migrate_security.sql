-- ============================================
-- VOTEUNITY - SECURITY MIGRATION (MySQL)
-- Run this on existing databases to add new columns
-- ============================================

-- Add security columns to votes table
ALTER TABLE votes ADD COLUMN IF NOT EXISTS encrypted_vote TEXT NULL;
ALTER TABLE votes ADD COLUMN IF NOT EXISTS block_index INT NULL;
ALTER TABLE votes ADD COLUMN IF NOT EXISTS nonce VARCHAR(32) NULL;
ALTER TABLE votes ADD COLUMN IF NOT EXISTS merkle_root VARCHAR(64) NULL;
ALTER TABLE votes ADD COLUMN IF NOT EXISTS vote_receipt VARCHAR(64) NULL;

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(30) NOT NULL,
    actor_type VARCHAR(10) NOT NULL DEFAULT 'system',
    actor_id INT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_actor (actor_type, actor_id),
    INDEX idx_created_at (created_at)
);


-- ============================================
-- POSTGRESQL VERSION (for Neon/Supabase)
-- Run this section on your PostgreSQL database
-- ============================================

-- NOTE: PostgreSQL doesn't support IF NOT EXISTS for ALTER TABLE ADD COLUMN
-- Use these DO blocks instead:

-- DO $$ BEGIN ALTER TABLE votes ADD COLUMN encrypted_vote TEXT; EXCEPTION WHEN duplicate_column THEN NULL; END $$;
-- DO $$ BEGIN ALTER TABLE votes ADD COLUMN block_index INTEGER; EXCEPTION WHEN duplicate_column THEN NULL; END $$;
-- DO $$ BEGIN ALTER TABLE votes ADD COLUMN nonce VARCHAR(32); EXCEPTION WHEN duplicate_column THEN NULL; END $$;
-- DO $$ BEGIN ALTER TABLE votes ADD COLUMN merkle_root VARCHAR(64); EXCEPTION WHEN duplicate_column THEN NULL; END $$;
-- DO $$ BEGIN ALTER TABLE votes ADD COLUMN vote_receipt VARCHAR(64); EXCEPTION WHEN duplicate_column THEN NULL; END $$;

-- CREATE TABLE IF NOT EXISTS audit_logs (
--     id SERIAL PRIMARY KEY,
--     event_type VARCHAR(30) NOT NULL,
--     actor_type VARCHAR(10) NOT NULL DEFAULT 'system',
--     actor_id INTEGER NULL,
--     details TEXT,
--     ip_address VARCHAR(45),
--     user_agent TEXT,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );
-- CREATE INDEX IF NOT EXISTS idx_audit_event_type ON audit_logs(event_type);
-- CREATE INDEX IF NOT EXISTS idx_audit_actor ON audit_logs(actor_type, actor_id);
-- CREATE INDEX IF NOT EXISTS idx_audit_created_at ON audit_logs(created_at);
