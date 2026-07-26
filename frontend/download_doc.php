<?php
/**
 * Student Portal - Binary Document Download Handler
 * Stream files safely with attachment headers.
 */
require_once __DIR__ . '/../backend/db.php';

// Ensure student is logged in
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$doc_id = (int)($_GET['id'] ?? 0);

if ($doc_id <= 0) {
    die("Invalid document request.");
}

$stmt = $db->prepare("SELECT * FROM documents WHERE id = ? LIMIT 1");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) {
    die("Document not found.");
}

$relative_path = ltrim($doc['file_path'], '/');
$full_path = dirname(__DIR__) . '/' . $relative_path;

if (!file_exists($full_path)) {
    // Check fallback path without uploads prefix
    $fallback = dirname(__DIR__) . '/uploads/' . $relative_path;
    if (file_exists($fallback)) {
        $full_path = $fallback;
    } else {
        die("File does not exist on server.");
    }
}

$filename = sanitize_input($doc['title']);
$ext = pathinfo($full_path, PATHINFO_EXTENSION);
if (empty($ext)) $ext = 'pdf';

$clean_filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename) . '.' . $ext;

// Set binary download headers
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $clean_filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($full_path));

ob_clean();
flush();
readfile($full_path);
exit;
