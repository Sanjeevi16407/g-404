<?php
/**
 * Database connection helper using PDO
 * Prepares DB instance and common backend utility functions.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

try {
    $pdo_options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ];

    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $pdo_options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $db = new PDO(
        "mysql:host=" . DB_HOST . 
        ";port=" . DB_PORT . 
        ";dbname=" . DB_NAME . 
        ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        $pdo_options
    );
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

/**
 * Sanitize User Input
 */
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if Administrator is Logged In
 */
function check_admin_session() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: index.php");
        exit;
    }
}

/**
 * Check if Student is Logged In
 */
function check_student_session() {
    if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Handle File Uploads securely
 * 
 * @param array $file $_FILES['input_name']
 * @param string $target_subfolder folder under uploads/
 * @param array $allowed_types array of MIME types
 * @return string|false Path to saved file relative to project root, or false on failure
 */
function handle_file_upload($file, $target_subfolder, $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $file_mime = mime_content_type($file['tmp_name']);
    if (!in_array($file_mime, $allowed_types)) {
        return false;
    }

    // Define root upload path
    $base_dir = dirname(__DIR__) . '/uploads/' . trim($target_subfolder, '/') . '/';
    
    // Ensure dir exists
    if (!is_dir($base_dir)) {
        mkdir($base_dir, 0755, true);
    }

    // Generate unique name
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('file_', true) . '.' . $ext;
    $target_file = $base_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return 'uploads/' . trim($target_subfolder, '/') . '/' . $filename;
    }

    return false;
}

/**
 * Format UTC database timestamp to student's local timezone (Asia/Kolkata)
 */
function format_to_local_time($utc_timestamp_str, $format = 'M d, h:i A') {
    try {
        if (empty($utc_timestamp_str)) {
            return '';
        }
        $utc_date = new DateTime($utc_timestamp_str, new DateTimeZone('UTC'));
        $utc_date->setTimezone(new DateTimeZone('Asia/Kolkata'));
        return $utc_date->format($format);
    } catch (Exception $e) {
        return $utc_timestamp_str;
    }
}
?>
