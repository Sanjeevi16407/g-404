<?php
/**
 * AJAX API to persist theme settings instantly in the database
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/db.php';

$response = ["status" => "error", "message" => "Unauthorized access."];

// Check if student session is active
if (isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true) {
    $student_id = (int)$_SESSION['student_id'];
    $theme = sanitize_input($_POST['theme'] ?? '');

    $allowed_themes = ['Light', 'Dark', 'Aurora', 'Liquid Glass', 'Spatial', 'System', 'Cyberpunk', 'Nord', 'Dracula', 'Constellation', 'Live'];
    
    if (!empty($theme) && in_array($theme, $allowed_themes)) {
        try {
            // Update theme settings
            $stmt = $db->prepare("UPDATE settings SET theme = ? WHERE student_id = ?");
            $stmt->execute([$theme, $student_id]);
            
            // Sync session theme variable
            $_SESSION['student_theme'] = $theme;

            $response = ["status" => "success", "message" => "Theme preference saved successfully."];
        } catch (PDOException $e) {
            $response = ["status" => "error", "message" => "Database write failure."];
        }
    } else {
        $response = ["status" => "error", "message" => "Invalid theme selected."];
    }
}

echo json_encode($response);
exit;
?>
