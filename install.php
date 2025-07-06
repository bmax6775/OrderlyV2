<?php
/**
 * Orderlyy Professional Installer
 * One-click installation with licensing and MySQL support
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Installation steps
$steps = [
    1 => 'System Requirements',
    2 => 'Database Configuration', 
    3 => 'License Verification',
    4 => 'Admin Account Setup',
    5 => 'Branding Configuration',
    6 => 'Installation Complete'
];

$currentStep = (int)($_GET['step'] ?? 1);
$errors = [];
$success = [];

// Check if already installed
if (file_exists('config.php') && !isset($_GET['force'])) {
    $configContent = file_get_contents('config.php');
    if (strpos($configContent, 'ORDERLYY_INSTALLED') !== false) {
        die('<div style="padding: 40px; text-align: center; font-family: Arial;">
                <h2>🎉 Orderlyy Already Installed!</h2>
                <p>Installation completed successfully. Access your system:</p>
                <a href="index.php" style="background: #ffc500; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Open Orderlyy</a>
                <p style="margin-top: 20px;"><small>To reinstall, delete config.php or add ?force=1 to URL</small></p>
            </div>');
    }
}

/**
 * Step 1: System Requirements Check
 */
function checkSystemRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL' => extension_loaded('pdo_mysql'),
        'cURL Extension' => extension_loaded('curl'),
        'JSON Extension' => extension_loaded('json'),
        'GD Extension' => extension_loaded('gd'),
        'File Uploads' => ini_get('file_uploads'),
        'Writable Directory' => is_writable('./'),
        'Uploads Directory' => (is_dir('uploads') && is_writable('uploads')) || mkdir('uploads', 0755, true),
        'Screenshots Directory' => (is_dir('uploads/screenshots') && is_writable('uploads/screenshots')) || mkdir('uploads/screenshots', 0755, true)
    ];
    
    return $requirements;
}

/**
 * Step 2: Database Configuration (MySQL)
 */
function testDatabaseConnection($host, $dbname, $username, $password) {
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Step 3: License Verification
 */
function verifyLicense($licenseKey, $domain, $pdo = null) {
    // Clean license key
    $licenseKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $licenseKey));
    
    // Validate format
    if (strlen($licenseKey) !== 32) {
        return ['valid' => false, 'error' => 'License key must be 32 characters'];
    }
    
    // For installer demo - generate valid demo license
    if ($licenseKey === 'ORDERLYYDEMO' . str_repeat('0', 20)) {
        return [
            'valid' => true, 
            'message' => 'Demo license verified',
            'features' => [
                'unlimited_orders' => true,
                'courier_integrations' => true,
                'white_label' => true,
                'priority_support' => true
            ]
        ];
    }
    
    // TODO: Implement remote license verification
    // For now, accept any properly formatted key
    return [
        'valid' => true, 
        'message' => 'License verified successfully',
        'features' => [
            'unlimited_orders' => true,
            'courier_integrations' => true,
            'white_label' => true,
            'priority_support' => true
        ]
    ];
}

/**
 * Step 4: Database Schema Installation
 */
function installDatabase($pdo) {
    try {
        // Create licensing tables first
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS licenses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                license_key VARCHAR(32) UNIQUE NOT NULL,
                customer_email VARCHAR(255) NOT NULL,
                customer_name VARCHAR(255) NOT NULL,
                domain VARCHAR(255) NULL,
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'inactive',
                features JSON,
                activation_date TIMESTAMP NULL,
                expiry_date TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS license_validations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                license_key VARCHAR(32) NOT NULL,
                domain VARCHAR(255) NOT NULL,
                status ENUM('valid', 'invalid', 'domain_mismatch', 'error') NOT NULL,
                message TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Run the main database setup
        if (file_exists('setup_database.php')) {
            // Temporarily include to run schema
            ob_start();
            include 'setup_database.php';
            $output = ob_get_clean();
        }
        
        return ['success' => true, 'message' => 'Database installed successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Step 5: Create Configuration File
 */
function createConfigFile($dbConfig, $licenseKey, $adminData, $brandingData) {
    $configTemplate = '<?php
// Orderlyy Configuration File - Generated by Installer
// Do not modify manually

// Installation marker
define(\'ORDERLYY_INSTALLED\', true);

// License information
define(\'ORDERLYY_LICENSE_KEY\', \'' . $licenseKey . '\');

// Database configuration - MySQL
$host = \'' . $dbConfig['host'] . '\';
$dbname = \'' . $dbConfig['dbname'] . '\';
$username = \'' . $dbConfig['username'] . '\';
$password = \'' . addslashes($dbConfig['password']) . '\';

// Create MySQL connection
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Application settings
define(\'APP_NAME\', \'' . addslashes($brandingData['app_name']) . '\');
define(\'APP_VERSION\', \'1.0.0\');
define(\'UPLOAD_PATH\', \'uploads/\');
define(\'SCREENSHOT_PATH\', \'uploads/screenshots/\');

// Session configuration
ini_set(\'session.cookie_httponly\', 1);
ini_set(\'session.use_strict_mode\', 1);
if (isset($_SERVER[\'HTTPS\']) && $_SERVER[\'HTTPS\'] === \'on\') {
    ini_set(\'session.cookie_secure\', 1);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION[\'user_id\']);
}

function getUserRole() {
    return $_SESSION[\'role\'] ?? \'guest\';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header(\'Location: login.php\');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (getUserRole() !== $role) {
        $user_role = getUserRole();
        $dashboard_file = $user_role === \'super_admin\' ? \'dashboard_superadmin.php\' : \'dashboard_\' . $user_role . \'.php\';
        header(\'Location: \' . $dashboard_file);
        exit();
    }
}

function logActivity($user_id, $action, $details = \'\') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $action, $details]);
    } catch (PDOException $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

function formatDateTime($datetime) {
    return date(\'M j, Y g:i A\', strtotime($datetime));
}

function getStatusBadge($status) {
    $badges = [
        \'new\' => \'<span class="badge bg-primary">New</span>\',
        \'called\' => \'<span class="badge bg-info">Called</span>\',
        \'confirmed\' => \'<span class="badge bg-warning">Confirmed</span>\',
        \'in_transit\' => \'<span class="badge bg-secondary">In Transit</span>\',
        \'delivered\' => \'<span class="badge bg-success">Delivered</span>\',
        \'failed\' => \'<span class="badge bg-danger">Failed</span>\'
    ];
    return $badges[$status] ?? \'<span class="badge bg-light">Unknown</span>\';
}

// Include license system
require_once \'license_system.php\';
?>';

    return file_put_contents('config.php', $configTemplate) !== false;
}

/**
 * Store License in Database
 */
function storeLicenseInDatabase($pdo, $licenseKey, $domain, $features) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO licenses (license_key, customer_email, customer_name, domain, status, features, activation_date)
            VALUES (?, ?, ?, ?, 'active', ?, NOW())
            ON DUPLICATE KEY UPDATE
            domain = VALUES(domain),
            status = VALUES(status),
            activation_date = VALUES(activation_date)
        ");
        
        $stmt->execute([
            $licenseKey,
            $_SESSION['install_data']['admin']['email'] ?? 'admin@orderlyy.com',
            $_SESSION['install_data']['admin']['name'] ?? 'Administrator',
            $domain,
            json_encode($features)
        ]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($currentStep) {
        case 1:
            $requirements = checkSystemRequirements();
            if (array_product($requirements)) {
                header('Location: ?step=2');
                exit;
            } else {
                $errors[] = 'Please fix the system requirements before continuing.';
            }
            break;
            
        case 2:
            $dbConfig = [
                'host' => $_POST['db_host'] ?? 'localhost',
                'dbname' => $_POST['db_name'] ?? '',
                'username' => $_POST['db_user'] ?? '',
                'password' => $_POST['db_pass'] ?? ''
            ];
            
            $dbTest = testDatabaseConnection($dbConfig['host'], $dbConfig['dbname'], $dbConfig['username'], $dbConfig['password']);
            
            if ($dbTest['success']) {
                $_SESSION['install_data']['database'] = $dbConfig;
                header('Location: ?step=3');
                exit;
            } else {
                $errors[] = 'Database connection failed: ' . $dbTest['error'];
            }
            break;
            
        case 3:
            $licenseKey = $_POST['license_key'] ?? '';
            $domain = $_SERVER['HTTP_HOST'];
            
            $licenseValidation = verifyLicense($licenseKey, $domain);
            
            if ($licenseValidation['valid']) {
                $_SESSION['install_data']['license'] = [
                    'key' => $licenseKey,
                    'features' => $licenseValidation['features']
                ];
                header('Location: ?step=4');
                exit;
            } else {
                $errors[] = $licenseValidation['error'];
            }
            break;
            
        case 4:
            $adminData = [
                'username' => $_POST['admin_username'] ?? '',
                'email' => $_POST['admin_email'] ?? '',
                'password' => $_POST['admin_password'] ?? '',
                'name' => $_POST['admin_name'] ?? ''
            ];
            
            if (empty($adminData['username']) || empty($adminData['email']) || empty($adminData['password'])) {
                $errors[] = 'All admin fields are required.';
            } else {
                $_SESSION['install_data']['admin'] = $adminData;
                header('Location: ?step=5');
                exit;
            }
            break;
            
        case 5:
            $brandingData = [
                'app_name' => $_POST['app_name'] ?? 'Orderlyy',
                'tagline' => $_POST['tagline'] ?? 'Professional Order Management',
                'primary_color' => $_POST['primary_color'] ?? '#ffc500',
                'secondary_color' => $_POST['secondary_color'] ?? '#000000'
            ];
            
            $_SESSION['install_data']['branding'] = $brandingData;
            
            // Perform installation - Re-establish database connection
            $dbConfig = $_SESSION['install_data']['database'];
            $connectionTest = testDatabaseConnection($dbConfig['host'], $dbConfig['dbname'], $dbConfig['username'], $dbConfig['password']);
            
            if (!$connectionTest['success']) {
                $errors[] = 'Database connection lost: ' . $connectionTest['error'];
                break;
            }
            
            $pdo = $connectionTest['pdo'];
            $dbInstall = installDatabase($pdo);
            
            if ($dbInstall['success']) {
                // Create config file
                $configCreated = createConfigFile(
                    $_SESSION['install_data']['database'],
                    $_SESSION['install_data']['license']['key'],
                    $_SESSION['install_data']['admin'],
                    $_SESSION['install_data']['branding']
                );
                
                if ($configCreated) {
                    // Store license in database
                    storeLicenseInDatabase(
                        $pdo, 
                        $_SESSION['install_data']['license']['key'],
                        $_SERVER['HTTP_HOST'],
                        $_SESSION['install_data']['license']['features']
                    );
                    
                    // Update branding settings
                    $stmt = $pdo->prepare("
                        UPDATE branding_settings 
                        SET setting_value = ? 
                        WHERE setting_key = ?
                    ");
                    
                    foreach ($_SESSION['install_data']['branding'] as $key => $value) {
                        $stmt->execute([$value, $key]);
                    }
                    
                    // Update super admin details
                    $hashedPassword = password_hash($_SESSION['install_data']['admin']['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, password = ?, full_name = ?
                        WHERE role = 'super_admin' AND id = 1
                    ");
                    $stmt->execute([
                        $_SESSION['install_data']['admin']['username'],
                        $_SESSION['install_data']['admin']['email'],
                        $hashedPassword,
                        $_SESSION['install_data']['admin']['name']
                    ]);
                    
                    // Clear session data
                    unset($_SESSION['install_data']);
                    
                    header('Location: ?step=6');
                    exit;
                } else {
                    $errors[] = 'Failed to create configuration file. Check file permissions.';
                }
            } else {
                $errors[] = 'Database installation failed: ' . $dbInstall['error'];
            }
            break;
    }
}

// Generate demo license key for easy testing
$demoLicenseKey = 'ORDERLYYDEMO' . str_repeat('0', 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orderlyy Professional Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #ffc500 0%, #ff8f00 100%); min-height: 100vh; }
        .installer-container { max-width: 800px; margin: 40px auto; }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step { 
            padding: 10px 15px; 
            background: rgba(255,255,255,0.3); 
            border-radius: 25px; 
            color: white; 
            font-size: 14px;
            flex: 1;
            text-align: center;
            margin: 0 5px;
        }
        .step.active { background: rgba(255,255,255,0.9); color: #333; font-weight: bold; }
        .step.completed { background: #28a745; }
        .installer-card { 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .card-header { 
            background: linear-gradient(45deg, #333, #555); 
            color: white; 
            padding: 20px; 
            text-align: center;
        }
        .requirement { 
            display: flex; 
            justify-content: space-between; 
            padding: 10px 0; 
            border-bottom: 1px solid #eee;
        }
        .requirement:last-child { border-bottom: none; }
        .status-ok { color: #28a745; }
        .status-fail { color: #dc3545; }
        .form-control:focus { border-color: #ffc500; box-shadow: 0 0 0 0.2rem rgba(255, 197, 0, 0.25); }
        .btn-primary { background: #ffc500; border-color: #ffc500; color: #333; font-weight: bold; }
        .btn-primary:hover { background: #e6b800; border-color: #e6b800; }
    </style>
</head>
<body>
    <div class="container installer-container">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <?php foreach ($steps as $num => $title): ?>
                <div class="step <?php echo $num === $currentStep ? 'active' : ($num < $currentStep ? 'completed' : ''); ?>">
                    <?php echo $num; ?>. <?php echo $title; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Main Installation Card -->
        <div class="installer-card">
            <div class="card-header">
                <h2><i class="fas fa-cogs"></i> Orderlyy Professional Installer</h2>
                <p class="mb-0">Step <?php echo $currentStep; ?>: <?php echo $steps[$currentStep]; ?></p>
            </div>
            <div class="card-body p-4">
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php foreach ($success as $msg): ?>
                            <div><i class="fas fa-check"></i> <?php echo htmlspecialchars($msg); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php switch ($currentStep): 
                    case 1: // System Requirements ?>
                        <h4>System Requirements Check</h4>
                        <p class="text-muted">Please ensure your server meets all requirements.</p>
                        
                        <?php $requirements = checkSystemRequirements(); ?>
                        <div class="requirements-list">
                            <?php foreach ($requirements as $requirement => $status): ?>
                                <div class="requirement">
                                    <span><?php echo $requirement; ?></span>
                                    <span class="<?php echo $status ? 'status-ok' : 'status-fail'; ?>">
                                        <i class="fas fa-<?php echo $status ? 'check' : 'times'; ?>"></i>
                                        <?php echo $status ? 'OK' : 'FAIL'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (array_product($requirements)): ?>
                            <form method="post" class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    Continue to Database Setup <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning mt-4">
                                <strong>Requirements Failed:</strong> Please contact your hosting provider to ensure all requirements are met.
                            </div>
                        <?php endif; ?>
                        
                        <?php break;
                    case 2: // Database Configuration ?>
                        <h4>Database Configuration</h4>
                        <p class="text-muted">Enter your MySQL database details from cPanel.</p>
                        
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Database Host</label>
                                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                                    <small class="text-muted">Usually 'localhost' for shared hosting</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Database Name</label>
                                    <input type="text" name="db_name" class="form-control" placeholder="your_database_name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Database Username</label>
                                    <input type="text" name="db_user" class="form-control" placeholder="your_db_username" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Database Password</label>
                                    <input type="password" name="db_pass" class="form-control" placeholder="your_db_password" required>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <strong>Need Help?</strong> Find these details in your hosting cPanel under "MySQL Databases".
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                Test Connection & Continue <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                        
                        <?php break;
                    case 3: // License Verification ?>
                        <h4>License Verification</h4>
                        <p class="text-muted">Enter your Orderlyy license key to activate the system.</p>
                        
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">License Key</label>
                                <input type="text" name="license_key" class="form-control" placeholder="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX" required maxlength="32">
                                <small class="text-muted">32-character license key from your purchase</small>
                            </div>
                            
                            <div class="alert alert-warning">
                                <strong>Demo License:</strong> For testing, use: <code><?php echo $demoLicenseKey; ?></code>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="document.getElementsByName('license_key')[0].value='<?php echo $demoLicenseKey; ?>'">Use Demo</button>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Domain:</strong> <?php echo $_SERVER['HTTP_HOST']; ?>
                                <small class="text-muted d-block">License will be activated for this domain</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                Verify License & Continue <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                        
                        <?php break;
                    case 4: // Admin Account Setup ?>
                        <h4>Admin Account Setup</h4>
                        <p class="text-muted">Create your super administrator account.</p>
                        
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="admin_name" class="form-control" placeholder="John Doe" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="admin_username" class="form-control" placeholder="admin" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="admin_email" class="form-control" placeholder="admin@yourcompany.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="admin_password" class="form-control" placeholder="Strong password" required minlength="6">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                Create Account & Continue <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                        
                        <?php break;
                    case 5: // Branding Configuration ?>
                        <h4>Branding Configuration</h4>
                        <p class="text-muted">Customize your Orderlyy installation.</p>
                        
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Application Name</label>
                                    <input type="text" name="app_name" class="form-control" value="Orderlyy" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tagline</label>
                                    <input type="text" name="tagline" class="form-control" value="Professional Order Management">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Primary Color</label>
                                    <input type="color" name="primary_color" class="form-control form-control-color" value="#ffc500">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Secondary Color</label>
                                    <input type="color" name="secondary_color" class="form-control form-control-color" value="#000000">
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <strong>Note:</strong> You can change these settings later from the admin panel.
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-rocket"></i> Install Orderlyy Now!
                            </button>
                        </form>
                        
                        <?php break;
                    case 6: // Installation Complete ?>
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="text-success">Installation Complete!</h3>
                            <p class="text-muted">Orderlyy has been successfully installed and configured.</p>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <h5>🎯 Access Your System</h5>
                                            <a href="index.php" class="btn btn-success btn-lg">Open Orderlyy</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <h5>👤 Admin Login</h5>
                                            <a href="login.php" class="btn btn-primary btn-lg">Admin Panel</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning mt-4">
                                <strong>Security:</strong> Delete this installer file (install.php) after installation.
                            </div>
                            
                            <div class="mt-4">
                                <h5>📋 What's Included:</h5>
                                <ul class="list-unstyled">
                                    <li>✅ Complete order management system</li>
                                    <li>✅ Multi-user roles (Super Admin, Admin, Agent)</li>
                                    <li>✅ Pakistani courier integrations</li>
                                    <li>✅ Custom branding system</li>
                                    <li>✅ Analytics and reporting</li>
                                    <li>✅ Professional licensing system</li>
                                </ul>
                            </div>
                        </div>
                        <?php break;
                endswitch; ?>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-white">
                <i class="fas fa-shield-alt"></i> Orderlyy Professional v1.0.0 | 
                <i class="fas fa-support"></i> Professional Order Management System
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>