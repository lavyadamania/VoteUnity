-- ============================================
-- VOTEUNITY - FINAL DATABASE SYNC SCRIPT
-- Run this in your Neon SQL Console to fix ALL missing columns
-- ============================================

-- 1. FIX ADMINS TABLE (Add missing columns one by one if they don't exist)
DO $$ 
BEGIN 
    BEGIN
        ALTER TABLE admins ADD COLUMN face_image TEXT;
    EXCEPTION WHEN duplicate_column THEN NULL;
    END;

    BEGIN
        ALTER TABLE admins ADD COLUMN is_super_admin BOOLEAN DEFAULT FALSE;
    EXCEPTION WHEN duplicate_column THEN NULL;
    END;

    BEGIN
        ALTER TABLE admins ADD COLUMN is_approved BOOLEAN DEFAULT FALSE;
    EXCEPTION WHEN duplicate_column THEN NULL;
    END;

    BEGIN
        ALTER TABLE admins ADD COLUMN approved_by INTEGER;
    EXCEPTION WHEN duplicate_column THEN NULL;
    END;

    BEGIN
        ALTER TABLE admins ADD COLUMN can_view_votes BOOLEAN DEFAULT TRUE;
    EXCEPTION WHEN duplicate_column THEN NULL;
    END;

    BEGIN
        ALTER TABLE admins ADD COLUMN can_manage_candidates BOOLEAN DEFAULT FALSE;
    EXCEPTION WHEN duplicate_column THEN NULL;
    END;

    BEGIN
        ALTER TABLE admins ADD COLUMN can_reset_votes BOOLEAN DEFAULT FALSE;
    EXCEPTION WHEN duplicate_column THEN NULL;
    END;

    BEGIN
        ALTER TABLE admins ADD COLUMN can_manage_admins BOOLEAN DEFAULT FALSE;
    EXCEPTION WHEN duplicate_column THEN NULL;
    END;
END $$;

-- 2. ENSURE COLUMN TYPES ARE CORRECT for Vercel (Base64 data needs TEXT)
ALTER TABLE admins ALTER COLUMN face_image TYPE TEXT;
ALTER TABLE users ALTER COLUMN face_image TYPE TEXT;

-- 3. FIX USERS TABLE
DO $$ 
BEGIN 
    BEGIN
        ALTER TABLE users ADD COLUMN has_voted BOOLEAN DEFAULT FALSE;
    EXCEPTION WHEN duplicate_column THEN NULL;
    END;
END $$;

-- 4. FIX CANDIDATES TABLE
DO $$ 
BEGIN 
    BEGIN
        ALTER TABLE candidates ADD COLUMN photo VARCHAR(255);
    EXCEPTION WHEN duplicate_column THEN NULL;
    END;
END $$;

-- 5. ENSURE SUPER ADMIN IS CORRECT
UPDATE admins 
SET is_super_admin = TRUE, is_approved = TRUE, password = '$2y$10$SZoDq/hiEDdeRk9C3CAjZuEM4rPNEECj66GXJWBRVeetI4a6dbASBi'
WHERE username = 'lavya';

-- 6. ENSURE FOREIGN KEYS ARE SET
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'admins_approved_by_fkey') THEN
        ALTER TABLE admins ADD CONSTRAINT admins_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL;
    END IF;
END $$;

-- 7. CLEAR POSTGRES CACHE (By doing a dummy select)
SELECT 1;
