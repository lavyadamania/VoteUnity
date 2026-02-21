-- ============================================
-- VOTEUNITY - DATABASE CLEANUP SCRIPT
-- This will wipe voter data and candidates to fix duplication.
-- Run this in your Neon SQL Console.
-- ============================================

-- 1. Clear Voting Data
TRUNCATE TABLE votes RESTART IDENTITY;

-- 2. Clear Voter Data
TRUNCATE TABLE users RESTART IDENTITY CASCADE;

-- 3. Clear existing candidates (to fix duplicates)
TRUNCATE TABLE candidates RESTART IDENTITY CASCADE;

-- 4. Clear activity history
TRUNCATE TABLE admin_locations RESTART IDENTITY;

-- 5. Clear Non-Primary Admins
DELETE FROM admins WHERE username != 'admin';

SELECT 'Database Resetted. You can now re-run voting_system_pg.sql one time.' as result;
