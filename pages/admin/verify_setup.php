<?php
/**
 * VoteUnity Diagnostic & Final Repair Tool
 * Visit this page on Vercel to fix common database/login issues.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

echo "<html><body style='font-family:sans-serif; background:#0f172a; color:#e2e8f0; padding:2rem;'>";
echo "<h1>🛠️ VoteUnity - Final System Sync</h1>";

if (!$pdo) {
    echo "<div style='background:#ef4444; padding:1rem; border-radius:8px;'>";
    echo "<h3>❌ Database Not Connected</h3>";
    echo "<p>$db_error</p>";
    echo "</div>";
    exit;
}

echo "<div style='background:#1e293b; padding:1.5rem; border-radius:12px; border:1px solid #334155; margin-bottom:1rem;'>";
echo "<h3>📊 Database Status: CONNECTED ✅</h3>";

echo "<h4>🔍 Running Comprehensive Schema Audit...</h4>";
try {
    // 1. ADD MISSING COLUMNS TO ADMINS
    $pdo->exec("DO $$ 
    BEGIN 
        BEGIN ALTER TABLE admins ADD COLUMN face_image TEXT; EXCEPTION WHEN duplicate_column THEN NULL; END;
        BEGIN ALTER TABLE admins ADD COLUMN is_super_admin BOOLEAN DEFAULT FALSE; EXCEPTION WHEN duplicate_column THEN NULL; END;
        BEGIN ALTER TABLE admins ADD COLUMN is_approved BOOLEAN DEFAULT FALSE; EXCEPTION WHEN duplicate_column THEN NULL; END;
        BEGIN ALTER TABLE admins ADD COLUMN approved_by INTEGER; EXCEPTION WHEN duplicate_column THEN NULL; END;
        BEGIN ALTER TABLE admins ADD COLUMN can_view_votes BOOLEAN DEFAULT TRUE; EXCEPTION WHEN duplicate_column THEN NULL; END;
        BEGIN ALTER TABLE admins ADD COLUMN can_manage_candidates BOOLEAN DEFAULT FALSE; EXCEPTION WHEN duplicate_column THEN NULL; END;
        BEGIN ALTER TABLE admins ADD COLUMN can_reset_votes BOOLEAN DEFAULT FALSE; EXCEPTION WHEN duplicate_column THEN NULL; END;
        BEGIN ALTER TABLE admins ADD COLUMN can_manage_admins BOOLEAN DEFAULT FALSE; EXCEPTION WHEN duplicate_column THEN NULL; END;
    END $$;");

    // 2. ENSURE TYPES ARE CORRECT
    $pdo->exec("ALTER TABLE admins ALTER COLUMN face_image TYPE TEXT");
    $pdo->exec("ALTER TABLE users ALTER COLUMN face_image TYPE TEXT");

    // 3. FIX USERS & CANDIDATES
    $pdo->exec("DO $$ 
    BEGIN 
        BEGIN ALTER TABLE users ADD COLUMN has_voted BOOLEAN DEFAULT FALSE; EXCEPTION WHEN duplicate_column THEN NULL; END;
        BEGIN ALTER TABLE candidates ADD COLUMN photo VARCHAR(255); EXCEPTION WHEN duplicate_column THEN NULL; END;
    END $$;");

    echo "<p style='color:#10b981;'>✅ Schema synchronized successfully!</p>";

    // 4. ENSURE SUPER ADMIN IS CORRECTly HASHED
    $username = 'lavya';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin) {
        $stmt = $pdo->prepare("UPDATE admins SET password = ?, is_super_admin = TRUE, is_approved = TRUE WHERE username = ?");
        $stmt->execute([$hash, $username]);
        echo "<h4>✅ Default admin 'lavya' updated.</h4>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, is_super_admin, is_approved) VALUES (?, ?, TRUE, TRUE)");
        $stmt->execute([$username, $hash]);
        echo "<h4>✅ Default admin 'lavya' created.</h4>";
    }
} catch (Exception $e) {
    echo "<p style='color:#ef4444;'>❌ Error during repair: " . $e->getMessage() . "</p>";
    echo "<p>If you see 'cached plan' error, just REFRESH this page to fix it.</p>";
}

echo "</div>";
echo "<div style='text-align:center; margin-top:2rem;'>";
echo "<a href='login.php' style='background:#3b82f6; color:white; padding:0.75rem 1.5rem; text-decoration:none; border-radius:8px; font-weight:bold;'>Go to Login Page</a>";
echo "</div>";
echo "</body></html>";
?>