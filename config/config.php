<?php
// ---------------------------
// Session Handling
// ---------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------
// App Information
// ---------------------------
define('APP_NAME', 'Capstone Report Management System');
define('APP_VERSION', '1.0.0');

// ---------------------------
// Base URL Calculation
// ---------------------------
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['SCRIPT_NAME']);
$basePath = str_replace(['/school', '/admin', '/student', '/teacher'], '', $path);
define('BASE_URL', rtrim($protocol . $host . $basePath, '/') . '/');

// ---------------------------
// File Upload Configuration
// ---------------------------
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB for videos
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'txt', 'rtf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv']);

if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ---------------------------
// Email Configuration
// ---------------------------
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', ''); // Your SMTP username
define('SMTP_PASSWORD', ''); // Your SMTP password or app password
define('FROM_EMAIL', 'noreply@capstone-system.com');
define('FROM_NAME', 'Capstone System');

// ---------------------------
// Include Core Files
// ---------------------------
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ---------------------------
// Initialize Database
// ---------------------------
$db = new Database();

// ---------------------------
// Fallback Helper Functions
// ---------------------------
if (!function_exists('verifyPassword')) {
    function verifyPassword($plainPassword, $hashedPassword) {
        return password_verify($plainPassword, $hashedPassword);
    }
}

// Set default charset
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
?>