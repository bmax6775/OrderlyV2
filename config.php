<?php
// Database configuration - Using PostgreSQL
$database_url = getenv('DATABASE_URL');

// Parse the DATABASE_URL for PostgreSQL connection
if ($database_url) {
    $db_parts = parse_url($database_url);
    $host = $db_parts['host'];
    $port = $db_parts['port'] ?? 5432;
    $dbname = ltrim($db_parts['path'], '/');
    $username = $db_parts['user'];
    $password = $db_parts['pass'];
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
} else {
    // Fallback to environment variables
    $host = getenv('PGHOST') ?: 'localhost';
    $port = getenv('PGPORT') ?: '5432';
    $dbname = getenv('PGDATABASE') ?: 'orderdesk';
    $username = getenv('PGUSER') ?: 'postgres';
    $password = getenv('PGPASSWORD') ?: '';
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
}

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Application settings
define('APP_NAME', 'OrderDesk');
define('APP_VERSION', '1.0.0');
define('UPLOAD_PATH', 'uploads/');
define('SCREENSHOT_PATH', 'uploads/screenshots/');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(SCREENSHOT_PATH)) {
    mkdir(SCREENSHOT_PATH, 0755, true);
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

if (!function_exists('getUserRole')) {
    function getUserRole() {
        return $_SESSION['role'] ?? 'guest';
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (getUserRole() !== $role) {
        $user_role = getUserRole();
        $dashboard_file = $user_role === 'super_admin' ? 'dashboard_superadmin.php' : 'dashboard_' . $user_role . '.php';
        header('Location: ' . $dashboard_file);
        exit();
    }
}

function logActivity($user_id, $action, $details = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $action, $details]);
}

function getPlanLimits($plan_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM pricing_plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function formatDateTime($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}

function getStatusBadge($status) {
    $badges = [
        'new' => '<span class="badge bg-primary">New</span>',
        'called' => '<span class="badge bg-info">Called</span>',
        'confirmed' => '<span class="badge bg-warning">Confirmed</span>',
        'in_transit' => '<span class="badge bg-secondary">In Transit</span>',
        'delivered' => '<span class="badge bg-success">Delivered</span>',
        'failed' => '<span class="badge bg-danger">Failed</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-light">Unknown</span>';
}
?>
