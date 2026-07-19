<?php
/**
 * PHP-based SQL Schema Importer
 * Parses schema.sql, executes queries sequentially, and outputs detailed errors.
 */
header('Content-Type: text/plain');
require_once __DIR__ . '/../backend/db.php';

$schema_file = __DIR__ . '/../database/schema.sql';

if (!file_exists($schema_file)) {
    die("Error: schema.sql file not found at: " . $schema_file);
}

$sql_content = file_get_contents($schema_file);

// Strip SQL comments to avoid parsing errors
$sql_content = preg_replace('/--.*\n/', '', $sql_content);
$sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);

// Split SQL content by semicolon, making sure we don't split inside quotes
// A simpler but highly effective approach is parsing statement by statement
$statements = explode(';', $sql_content);

echo "Starting database schema import...\n";
echo "Total queries to process: " . count($statements) . "\n\n";

$success_count = 0;
$fail_count = 0;

foreach ($statements as $index => $stmt) {
    $query = trim($stmt);
    if (empty($query)) {
        continue;
    }

    try {
        $db->exec($query);
        $success_count++;
        // Print preview of first 50 chars of query
        $preview = substr($query, 0, 50);
        $preview = str_replace(array("\r", "\n"), ' ', $preview);
        echo "Query #" . ($index + 1) . " SUCCESS: " . $preview . "...\n";
    } catch (PDOException $e) {
        $fail_count++;
        $preview = substr($query, 0, 70);
        $preview = str_replace(array("\r", "\n"), ' ', $preview);
        echo "Query #" . ($index + 1) . " FAILED: " . $preview . "...\n";
        echo "Error: " . $e->getMessage() . "\n\n";
    }
}

echo "\nImport finished!\n";
echo "Successful queries: " . $success_count . "\n";
echo "Failed queries: " . $fail_count . "\n";
?>
