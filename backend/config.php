<?php
/**
 * Global configurations
 */

// Gemini API Configuration
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?:'');

// Application Details
define('APP_NAME', 'Buddy - Your Digital Senior');
define('COLLEGE_NAME', 'Saranathan College of Engineering');

// Session Settings
define('SESSION_LIFETIME', 86400); // 1 day

// Database Settings (Local + Production)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'buddy_senior_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
?>
