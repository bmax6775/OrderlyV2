<?php
session_start();
require_once 'config.php';
require_once 'get_branding.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle form submission
if ($_POST) {
    $updated = false;
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/branding/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['logo']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // Check if file is an image
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
        
        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
                if (updateBrandingSetting('logo_url', $targetFile, $_SESSION['user_id'])) {
                    $updated = true;
                }
            }
        }
    }
    
    // Handle other branding settings
    $settings = [
        'app_name', 'tagline', 'footer_text',
        'primary_color', 'secondary_color', 'accent_color',
        'background_color', 'card_background', 'text_color',
        'success_color', 'warning_color', 'danger_color', 'info_color'
    ];
    
    foreach ($settings as $setting) {
        if (isset($_POST[$setting]) && !empty($_POST[$setting])) {
            if (updateBrandingSetting($setting, $_POST[$setting], $_SESSION['user_id'])) {
                $updated = true;
            }
        }
    }
    
    if ($updated) {
        $message = 'Branding settings updated successfully!';
        $messageType = 'success';
        // Refresh branding settings
        $branding = getBrandingSettings();
    } else {
        $message = 'No changes were made or an error occurred.';
        $messageType = 'warning';
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? 'guest';
}

function getUserFullName() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn() ?: 'User';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branding Settings - <?php echo htmlspecialchars($branding['app_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo $branding['primary_color']; ?>;
            --secondary-color: <?php echo $branding['secondary_color']; ?>;
            --accent-color: <?php echo $branding['accent_color']; ?>;
            --background-color: <?php echo $branding['background_color']; ?>;
            --card-background: <?php echo $branding['card_background']; ?>;
            --text-color: <?php echo $branding['text_color']; ?>;
            --success-color: <?php echo $branding['success_color']; ?>;
            --warning-color: <?php echo $branding['warning_color']; ?>;
            --danger-color: <?php echo $branding['danger_color']; ?>;
            --info-color: <?php echo $branding['info_color']; ?>;
        }
        
        .color-picker {
            width: 60px;
            height: 40px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .preview-section {
            position: sticky;
            top: 20px;
            background: var(--card-background);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .brand-preview {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color), #e6b800);
            color: var(--secondary-color);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .logo-preview {
            max-width: 200px;
            max-height: 80px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard_superadmin.php">
                <?php if (!empty($branding['logo_url'])): ?>
                    <img src="<?php echo htmlspecialchars($branding['logo_url']); ?>" alt="<?php echo htmlspecialchars($branding['app_name']); ?>" style="height: 40px; margin-right: 10px;">
                <?php else: ?>
                    <i class="fas fa-crown me-2"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($branding['app_name']); ?> Super Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_superadmin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="approve_users.php">User Approvals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_pricing.php">Pricing Plans</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="branding_settings.php">Branding</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="audit_logs.php">Audit Logs</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo getUserFullName(); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-outline-light btn-sm ms-2" id="darkModeToggle">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-palette me-2"></i>Branding Settings
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <!-- Basic Settings -->
                            <div class="mb-4">
                                <h5 class="text-primary">Basic Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="app_name" class="form-label">Application Name</label>
                                        <input type="text" class="form-control" id="app_name" name="app_name" 
                                               value="<?php echo htmlspecialchars($branding['app_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="logo" class="form-label">Logo Upload</label>
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                        <small class="form-text text-muted">Upload PNG, JPG, or SVG files (max 2MB)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="tagline" class="form-label">Tagline</label>
                                <input type="text" class="form-control" id="tagline" name="tagline" 
                                       value="<?php echo htmlspecialchars($branding['tagline']); ?>">
                            </div>

                            <div class="mb-4">
                                <label for="footer_text" class="form-label">Footer Text</label>
                                <input type="text" class="form-control" id="footer_text" name="footer_text" 
                                       value="<?php echo htmlspecialchars($branding['footer_text']); ?>">
                            </div>

                            <!-- Color Scheme -->
                            <div class="mb-4">
                                <h5 class="text-primary">Color Scheme</h5>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="primary_color" class="form-label">Primary Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" class="color-picker" id="primary_color" name="primary_color" 
                                                   value="<?php echo $branding['primary_color']; ?>">
                                            <input type="text" class="form-control" value="<?php echo $branding['primary_color']; ?>" 
                                                   onchange="document.getElementById('primary_color').value = this.value">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="secondary_color" class="form-label">Secondary Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" class="color-picker" id="secondary_color" name="secondary_color" 
                                                   value="<?php echo $branding['secondary_color']; ?>">
                                            <input type="text" class="form-control" value="<?php echo $branding['secondary_color']; ?>" 
                                                   onchange="document.getElementById('secondary_color').value = this.value">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="accent_color" class="form-label">Accent Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" class="color-picker" id="accent_color" name="accent_color" 
                                                   value="<?php echo $branding['accent_color']; ?>">
                                            <input type="text" class="form-control" value="<?php echo $branding['accent_color']; ?>" 
                                                   onchange="document.getElementById('accent_color').value = this.value">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="background_color" class="form-label">Background Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" class="color-picker" id="background_color" name="background_color" 
                                                   value="<?php echo $branding['background_color']; ?>">
                                            <input type="text" class="form-control" value="<?php echo $branding['background_color']; ?>" 
                                                   onchange="document.getElementById('background_color').value = this.value">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Colors -->
                            <div class="mb-4">
                                <h5 class="text-primary">Status Colors</h5>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="success_color" class="form-label">Success Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" class="color-picker" id="success_color" name="success_color" 
                                                   value="<?php echo $branding['success_color']; ?>">
                                            <input type="text" class="form-control" value="<?php echo $branding['success_color']; ?>" 
                                                   onchange="document.getElementById('success_color').value = this.value">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="warning_color" class="form-label">Warning Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" class="color-picker" id="warning_color" name="warning_color" 
                                                   value="<?php echo $branding['warning_color']; ?>">
                                            <input type="text" class="form-control" value="<?php echo $branding['warning_color']; ?>" 
                                                   onchange="document.getElementById('warning_color').value = this.value">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="danger_color" class="form-label">Danger Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" class="color-picker" id="danger_color" name="danger_color" 
                                                   value="<?php echo $branding['danger_color']; ?>">
                                            <input type="text" class="form-control" value="<?php echo $branding['danger_color']; ?>" 
                                                   onchange="document.getElementById('danger_color').value = this.value">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="info_color" class="form-label">Info Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" class="color-picker" id="info_color" name="info_color" 
                                                   value="<?php echo $branding['info_color']; ?>">
                                            <input type="text" class="form-control" value="<?php echo $branding['info_color']; ?>" 
                                                   onchange="document.getElementById('info_color').value = this.value">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Settings
                                </button>
                                <a href="dashboard_superadmin.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="preview-section">
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-eye me-2"></i>Live Preview
                    </h5>
                    
                    <div class="brand-preview">
                        <?php if (!empty($branding['logo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($branding['logo_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($branding['app_name']); ?>" 
                                 class="logo-preview mb-3">
                        <?php else: ?>
                            <i class="fas fa-layer-group" style="font-size: 3rem;"></i>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($branding['app_name']); ?></h3>
                        <p><?php echo htmlspecialchars($branding['tagline']); ?></p>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <div class="btn btn-primary flex-fill">Primary</div>
                        <div class="btn btn-secondary flex-fill">Secondary</div>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <div class="btn btn-success flex-fill">Success</div>
                        <div class="btn btn-warning flex-fill">Warning</div>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <div class="btn btn-danger flex-fill">Danger</div>
                        <div class="btn btn-info flex-fill">Info</div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <strong>Sample Card</strong>
                        </div>
                        <div class="card-body">
                            <p>This is how your cards will look with the current color scheme.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        // Real-time color preview
        document.querySelectorAll('.color-picker').forEach(picker => {
            picker.addEventListener('input', function() {
                const colorName = this.name;
                const colorValue = this.value;
                
                // Update the corresponding text input
                const textInput = this.parentElement.querySelector('input[type="text"]');
                if (textInput) {
                    textInput.value = colorValue;
                }
                
                // Update CSS variable for live preview
                document.documentElement.style.setProperty(`--${colorName.replace('_', '-')}`, colorValue);
            });
        });

        // Update color picker when text input changes
        document.querySelectorAll('input[type="text"]').forEach(input => {
            input.addEventListener('change', function() {
                const colorPicker = this.parentElement.querySelector('.color-picker');
                if (colorPicker) {
                    colorPicker.value = this.value;
                    const colorName = colorPicker.name;
                    document.documentElement.style.setProperty(`--${colorName.replace('_', '-')}`, this.value);
                }
            });
        });
    </script>
</body>
</html>