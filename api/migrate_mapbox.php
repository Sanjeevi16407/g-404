<?php
/**
 * One-time Web Migration Script to automatically add mapbox_token column to buddy_settings table on live production hosts (Render/Railway).
 */
require_once __DIR__ . '/../backend/db.php';

try {
    // Execute Alter Table command
    $db->exec("ALTER TABLE buddy_settings ADD COLUMN mapbox_token VARCHAR(255) DEFAULT NULL");
    
    echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #10b981; background: #ecfdf5; color: #047857; border-radius: 8px;'>";
    echo "<h3>🎉 Database Migration Successful!</h3>";
    echo "<p>The column <code>mapbox_token</code> was successfully added to the <code>buddy_settings</code> table in your Railway database.</p>";
    echo "<p>You can now close this tab and reload your campus navigator page.</p>";
    echo "</div>";
} catch (Exception $e) {
    // If it already exists, let them know it's fine!
    if (strpos($e->getMessage(), 'Duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #3b82f6; background: #eff6ff; color: #1d4ed8; border-radius: 8px;'>";
        echo "<h3>ℹ️ Column Already Exists</h3>";
        echo "<p>The column <code>mapbox_token</code> already exists in your database table.</p>";
        echo "<p>You can close this tab and reload your campus navigator page.</p>";
        echo "</div>";
    } else {
        echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #ef4444; background: #fef2f2; color: #b91c1c; border-radius: 8px;'>";
        echo "<h3>❌ Migration Failed</h3>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}
?>
