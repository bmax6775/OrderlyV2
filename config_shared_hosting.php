<?php
// OrderDesk Configuration for Shared Hosting (MySQL)
// Replace these values with your actual hosting details

// Database Configuration
$db_host = 'localhost';  // Usually 'localhost' for shared hosting
$db_name = 'your_cpanel_username_orderdesk';  // Replace with your database name
$db_user = 'your_cpanel_username_admin';      // Replace with your database username
$db_pass = 'your_strong_password_here';       // Replace with your database password

// Application Configuration
$app_name = 'OrderDesk';
$app_url = 'https://yourdomain.com';  // Replace with your actual domain
$app_env = 'production';  // Change to 'development' for testing

// Security Configuration
$session_timeout = 3600;  // 1 hour in seconds
$max_login_attempts = 5;
$lockout_duration = 900;  // 15 minutes in seconds

// File Upload Configuration
$upload_max_size = 5 * 1024 * 1024;  // 5MB in bytes
$allowed_image_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$upload_path = 'uploads/';
$screenshot_path = 'uploads/screenshots/';

// Email Configuration (if needed)
$smtp_host = 'mail.yourdomain.com';
$smtp_port = 587;
$smtp_username = 'noreply@yourdomain.com';
$smtp_password = 'your_email_password';
$smtp_from_email = 'noreply@yourdomain.com';
$smtp_from_name = 'OrderDesk System';

// Timezone Configuration
date_default_timezone_set('Asia/Karachi');  // Change to your timezone

// Error Reporting (set to false in production)
$debug_mode = false;
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database Connection
try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
} catch (PDOException $e) {
    if ($debug_mode) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection failed. Please check your configuration.");
    }
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($required_role) {
    requireAuth();
    $user_role = getUserRole();
    
    $role_hierarchy = [
        'agent' => 1,
        'admin' => 2,
        'super_admin' => 3
    ];
    
    if ($role_hierarchy[$user_role] < $role_hierarchy[$required_role]) {
        header('Location: dashboard_' . $user_role . '.php');
        exit();
    }
}

function redirectToDashboard($role = null) {
    $role = $role ?? getUserRole();
    $dashboard_file = $role === 'super_admin' ? 'dashboard_superadmin.php' : 'dashboard_' . $role . '.php';
    header('Location: ' . $dashboard_file);
    exit();
}

function logActivity($user_id, $action, $details = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        // Log error but don't break the application
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[\+]?[0-9\s\-\(\)]+$/', $phone);
}

function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

function formatCurrency($amount) {
    return 'PKR ' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}

// Set session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout check
if (isLoggedIn() && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $session_timeout) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }
}

// Update last activity time
if (isLoggedIn()) {
    $_SESSION['last_activity'] = time();
}

// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Initialize CSRF token for forms
$csrf_token = generateCSRFToken();
?>