<?php
/**
 * One-time Web Migration Script to automatically construct missing tables on live production hosts (Render/Railway).
 */
require_once __DIR__ . '/../backend/db.php';

try {
    // 1. Create buddy_analytics table
    $db->exec("CREATE TABLE IF NOT EXISTS buddy_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT NOT NULL,
        category VARCHAR(50) NOT NULL,
        answered_by ENUM('Knowledge Base', 'Gemini') NOT NULL,
        response_time FLOAT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 2. Create gemini_cache table
    $db->exec("CREATE TABLE IF NOT EXISTS gemini_cache (
        query_hash CHAR(32) PRIMARY KEY,
        query_text TEXT NOT NULL,
        response_text TEXT NOT NULL,
        category VARCHAR(50) NOT NULL,
        suggestions TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #10b981; background: #ecfdf5; color: #047857; border-radius: 8px;'>";
    echo "<h3>🎉 Database Migration Successful!</h3>";
    echo "<p>The tables <code>buddy_analytics</code> and <code>gemini_cache</code> were successfully created in your Railway database.</p>";
    echo "<p>You can now close this tab and reload your admin dashboard and chat portals.</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #ef4444; background: #fef2f2; color: #b91c1c; border-radius: 8px;'>";
    echo "<h3>❌ Migration Failed</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
