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
    <title>Login - <?php echo htmlspecialchars($branding['app_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo $branding['primary_color'] ?? '#ffc500'; ?>;
            --secondary-color: <?php echo $branding['secondary_color'] ?? '#000000'; ?>;
            --accent-color: <?php echo $branding['accent_color'] ?? '#f8f9fa'; ?>;
            --background-color: <?php echo $branding['background_color'] ?? '#ffffff'; ?>;
            --card-background: <?php echo $branding['card_background'] ?? '#ffffff'; ?>;
            --text-color: <?php echo $branding['text_color'] ?? '#212529'; ?>;
            --success-color: <?php echo $branding['success_color'] ?? '#28a745'; ?>;
            --warning-color: <?php echo $branding['warning_color'] ?? '#ffc107'; ?>;
            --danger-color: <?php echo $branding['danger_color'] ?? '#dc3545'; ?>;
            --info-color: <?php echo $branding['info_color'] ?? '#17a2b8'; ?>;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), #ffdb4d);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .login-card {
            background: var(--card-background);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        
        .login-header {
            background: var(--primary-color);
            color: var(--secondary-color);
            padding: 2rem;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .brand-logo {
            width: 60px;
            height: 60px;
            margin-bottom: 1rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 197, 0, 0.25);
        }
        
        .btn-login {
            background: var(--primary-color);
            border: none;
            color: var(--secondary-color);
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
        }
        
        .btn-login:hover {
            background: #e6b200;
            color: var(--secondary-color);
        }
        
        .dark-mode-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        
        .dark-mode-toggle:hover {
            background: var(--primary-color);
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <!-- Dark Mode Toggle -->
    <button class="dark-mode-toggle" id="darkModeToggle">
        <i class="fas fa-moon"></i>
    </button>

    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <?php if (!empty($branding['logo_url'])): ?>
                        <img src="<?php echo htmlspecialchars($branding['logo_url']); ?>" alt="<?php echo htmlspecialchars($branding['app_name']); ?>" class="brand-logo">
                    <?php else: ?>
                        <i class="fas fa-layer-group" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <?php endif; ?>
                    <h4><i class="fas fa-sign-in-alt me-2"></i>Login to <?php echo htmlspecialchars($branding['app_name']); ?></h4>
                </div>
                <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
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
                            <p class="mb-0">Don't have an account? <a href="signup.php">Sign up here</a></p>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-2"></i>Back to Home
                            </a>
                        </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple dark mode toggle for login page
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.body;
            
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function() {
                    body.classList.toggle('dark-mode');
                    const isDark = body.classList.contains('dark-mode');
                    const icon = darkModeToggle.querySelector('i');
                    if (icon) {
                        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
                    }
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                });
            }
            
            // Check for saved theme
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                body.classList.add('dark-mode');
                const icon = darkModeToggle?.querySelector('i');
                if (icon) icon.className = 'fas fa-sun';
            }
        });
    </script>
</body>
</html>
