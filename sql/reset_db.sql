-- ============================================
-- VOTEUNITY - FULL DATABASE RESET SCRIPT
-- Clears all voter, vote, and admin data.
-- Keeps only the 'lavya' superadmin with all permissions.
-- Run this in your Neon/PostgreSQL SQL Console.
-- ============================================

-- 1. Clear audit logs
TRUNCATE TABLE audit_logs RESTART IDENTITY;

-- 2. Clear admin login locations
TRUNCATE TABLE admin_locations RESTART IDENTITY;

-- 3. Clear all votes
TRUNCATE TABLE votes RESTART IDENTITY;

-- 4. Clear all voters (users)
TRUNCATE TABLE users RESTART IDENTITY CASCADE;

-- 5. Clear all admins
TRUNCATE TABLE admins RESTART IDENTITY CASCADE;

-- 6. Insert superadmin: username=lavya, password=admin123 (all permissions)
INSERT INTO admins (
    username,
    password,
    is_super_admin,
    is_approved,
    can_view_votes,
    can_manage_candidates,
    can_reset_votes,
    can_manage_admins
) VALUES (
    'lavya',
    '$2y$10$6Dm5GLznGsyAdlJBA0l2kOA4J1wkQ/2e/sDcpBajK8ryqWryPZ4zi',
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE
);

SELECT 'Reset complete. Superadmin lavya (password: admin123) is the only admin.' AS result;
