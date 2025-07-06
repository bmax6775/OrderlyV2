<?php
session_start();
require_once 'config.php';
require_once 'get_branding.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    $dashboard_file = $role === 'super_admin' ? 'dashboard_superadmin.php' : 'dashboard_' . $role . '.php';
    header('Location: ' . $dashboard_file);
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role, status, full_name FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'active') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Log the login activity
                logActivity($user['id'], 'login', 'User logged in from IP: ' . $_SERVER['REMOTE_ADDR']);
                
                // Redirect to appropriate dashboard
                $dashboard_file = $user['role'] === 'super_admin' ? 'dashboard_superadmin.php' : 'dashboard_' . $user['role'] . '.php';
                header('Location: ' . $dashboard_file);
                exit();
            } elseif ($user['status'] === 'pending') {
                $error = 'Your account is pending approval. Please wait for admin approval.';
            } else {
                $error = 'Your account has been suspended. Please contact support.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login - <?php echo htmlspecialchars($branding['app_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ffc500, #ffdb4d);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .login-header {
            background: #ffc500;
            color: #000000;
            padding: 2rem;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .btn-login {
            background: #ffc500;
            border: none;
            color: #000000;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
        }
        
        .btn-login:hover {
            background: #e6b200;
            color: #000000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-layer-group" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h4>Simple Login Test</h4>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username or Email</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p><strong>Demo Credentials:</strong></p>
                    <p>Username: <code>superadmin</code> Password: <code>password</code></p>
                    <p>Username: <code>demoadmin</code> Password: <code>password</code></p>
                    <p>Username: <code>demoagent</code> Password: <code>password</code></p>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>