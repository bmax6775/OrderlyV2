<?php
session_start();
require_once 'get_branding.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($branding['app_name']); ?> - Professional Order Management System</title>
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
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #e6b800 100%);
            color: var(--secondary-color);
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,100 1000,0 1000,100"/></svg>') no-repeat;
            background-size: cover;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .brand-logo {
            height: 60px;
            width: auto;
            margin-right: 10px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }
        
        .feature-card {
            background: var(--card-background);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 197, 0, 0.2);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), #e6b800);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 30px;
            color: var(--secondary-color);
        }
        
        .login-card {
            background: var(--card-background);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 197, 0, 0.3);
        }
        
        .stats-section {
            background: var(--background-color);
            padding: 80px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-color);
            display: block;
        }
        
        .stat-label {
            color: var(--text-color);
            font-size: 1.1rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: rgba(0, 0, 0, 0.9); backdrop-filter: blur(10px);">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <?php if (!empty($branding['logo_url'])): ?>
                    <img src="<?php echo htmlspecialchars($branding['logo_url']); ?>" alt="<?php echo htmlspecialchars($branding['app_name']); ?>" class="brand-logo">
                <?php else: ?>
                    <i class="fas fa-layer-group me-2" style="font-size: 2rem;"></i>
                <?php endif; ?>
                <span style="font-size: 1.8rem; font-weight: bold;"><?php echo htmlspecialchars($branding['app_name']); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Pricing</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_SESSION['role'] == 'super_admin' ? 'dashboard_superadmin.php' : ($_SESSION['role'] == 'admin' ? 'dashboard_admin.php' : 'dashboard_agent.php'); ?>">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="signup.php">
                                <i class="fas fa-user-plus me-1"></i>Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button class="btn btn-outline-light btn-sm ms-2" id="darkModeToggle">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center hero-content">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        <?php echo htmlspecialchars($branding['app_name']); ?>
                    </h1>
                    <p class="lead mb-4" style="font-size: 1.3rem;">
                        <?php echo htmlspecialchars($branding['tagline']); ?>
                    </p>
                    <div class="d-flex gap-3 mb-4">
                        <a href="signup.php" class="btn btn-light btn-lg px-4 py-3" style="color: var(--primary-color); font-weight: bold;">
                            <i class="fas fa-rocket me-2"></i>Get Started Free
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg px-4 py-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-star text-warning me-1"></i>
                            <i class="fas fa-star text-warning me-1"></i>
                            <i class="fas fa-star text-warning me-1"></i>
                            <i class="fas fa-star text-warning me-1"></i>
                            <i class="fas fa-star text-warning me-2"></i>
                            <small>Trusted by 500+ businesses</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="login-card p-4">
                        <h3 class="text-center mb-4" style="color: var(--primary-color);">
                            <i class="fas fa-sign-in-alt me-2"></i>Quick Login
                        </h3>
                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>
                        <div class="text-center">
                            <p class="text-muted mb-0">
                                <small>Don't have an account? <a href="signup.php" class="text-decoration-none">Sign up here</a></small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <span class="stat-number">10K+</span>
                        <div class="stat-label">Orders Processed</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <span class="stat-number">99.9%</span>
                        <div class="stat-label">Uptime</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <div class="stat-label">Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3" style="color: var(--primary-color);">
                    Why Choose <?php echo htmlspecialchars($branding['app_name']); ?>?
                </h2>
                <p class="lead">Complete order management solution for modern ecommerce businesses</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h4 class="mb-3">Multi-Role Management</h4>
                    <p>Streamlined workflow with Super Admin, Admin, and Agent roles. Each user gets precisely the tools they need.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-csv"></i>
                    </div>
                    <h4 class="mb-3">Bulk Order Import</h4>
                    <p>Import thousands of orders instantly with our intelligent CSV parser. Support for Excel and various formats.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="mb-3">Advanced Analytics</h4>
                    <p>Real-time dashboards with order tracking, agent performance metrics, and business insights.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4 class="mb-3">Mobile Responsive</h4>
                    <p>Access your orders anywhere, anytime. Fully responsive design works perfectly on all devices.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="mb-3">Secure & Reliable</h4>
                    <p>Enterprise-grade security with role-based access control and comprehensive audit logging.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h4 class="mb-3">Easy Integration</h4>
                    <p>Built for shared hosting. No complex setup required. Works with cPanel, Namecheap, and more.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-5" id="pricing" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3" style="color: var(--primary-color);">
                    Choose Your Plan
                </h2>
                <p class="lead">Simple, transparent pricing for businesses of all sizes</p>
            </div>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title">Basic</h5>
                            <div class="mb-3">
                                <span class="display-4 fw-bold text-primary">$29</span>
                                <small class="text-muted">/month</small>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Up to 3 agents</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>500 orders/month</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Basic analytics</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Email support</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>CSV export</li>
                            </ul>
                            <a href="signup.php" class="btn btn-outline-primary w-100">Get Started</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-lg" style="border: 3px solid var(--primary-color) !important;">
                        <div class="card-body text-center p-4">
                            <div class="badge bg-primary mb-3">Most Popular</div>
                            <h5 class="card-title">Professional</h5>
                            <div class="mb-3">
                                <span class="display-4 fw-bold text-primary">$59</span>
                                <small class="text-muted">/month</small>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Up to 10 agents</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>2,000 orders/month</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Advanced analytics</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Priority support</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>All export formats</li>
                            </ul>
                            <a href="signup.php" class="btn btn-primary w-100">Get Started</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title">Enterprise</h5>
                            <div class="mb-3">
                                <span class="display-4 fw-bold text-primary">$99</span>
                                <small class="text-muted">/month</small>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Unlimited agents</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Unlimited orders</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Premium analytics</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>24/7 support</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Custom features</li>
                            </ul>
                            <a href="signup.php" class="btn btn-outline-primary w-100">Get Started</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <?php if (!empty($branding['logo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($branding['logo_url']); ?>" alt="<?php echo htmlspecialchars($branding['app_name']); ?>" class="brand-logo">
                        <?php else: ?>
                            <i class="fas fa-layer-group me-2" style="font-size: 2rem; color: var(--primary-color);"></i>
                        <?php endif; ?>
                        <h5 class="mb-0"><?php echo htmlspecialchars($branding['app_name']); ?></h5>
                    </div>
                    <p class="text-muted"><?php echo htmlspecialchars($branding['tagline']); ?></p>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="text-uppercase mb-3">Product</h6>
                    <ul class="list-unstyled">
                        <li><a href="#features" class="text-muted text-decoration-none">Features</a></li>
                        <li><a href="#pricing" class="text-muted text-decoration-none">Pricing</a></li>
                        <li><a href="login.php" class="text-muted text-decoration-none">Login</a></li>
                        <li><a href="signup.php" class="text-muted text-decoration-none">Sign Up</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="text-uppercase mb-3">Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Help Center</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Contact Us</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Documentation</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">API</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6 class="text-uppercase mb-3">Stay Connected</h6>
                    <p class="text-muted">Get updates on new features and product releases.</p>
                    <div class="d-flex">
                        <input type="email" class="form-control me-2" placeholder="Your email">
                        <button class="btn btn-primary">Subscribe</button>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($branding['footer_text']); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($branding['app_name']); ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
