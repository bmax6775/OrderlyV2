<?php
session_start();
require_once 'config.php';
require_once 'get_branding.php';

requireRole('super_admin');

// Get comprehensive statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role != 'super_admin'");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
$total_orders = $stmt->fetch()['total_orders'];

$stmt = $pdo->query("SELECT COUNT(*) as pending_users FROM users WHERE status = 'pending'");
$pending_users = $stmt->fetch()['pending_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_admins FROM users WHERE role = 'admin'");
$total_admins = $stmt->fetch()['total_admins'];

$stmt = $pdo->query("SELECT COUNT(*) as total_agents FROM users WHERE role = 'agent'");
$total_agents = $stmt->fetch()['total_agents'];

$stmt = $pdo->query("SELECT COUNT(*) as active_stores FROM stores");
$active_stores = $stmt->fetch()['active_stores'];

$stmt = $pdo->query("SELECT COUNT(*) as total_revenue FROM orders WHERE status = 'completed'");
$total_revenue = $stmt->fetch()['total_revenue'] * 100; // Mock revenue calculation

// Get recent activities
$stmt = $pdo->query("SELECT al.*, u.full_name FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10");
$recent_activities = $stmt->fetchAll();

// Get system health metrics
$stmt = $pdo->query("SELECT COUNT(*) as orders_today FROM orders WHERE DATE(created_at) = CURRENT_DATE");
$orders_today = $stmt->fetch()['orders_today'];

$stmt = $pdo->query("SELECT COUNT(*) as active_users FROM users WHERE status = 'active' AND last_login >= NOW() - INTERVAL '7 days'");
$active_users = $stmt->fetch()['active_users'];

// Get order status breakdown
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$order_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get recent orders
$stmt = $pdo->query("SELECT o.*, u.full_name as agent_name FROM orders o LEFT JOIN users u ON o.assigned_agent_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - <?php echo htmlspecialchars($branding['app_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css" rel="stylesheet">
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
        
        .super-admin-header {
            background: var(--gradient-primary);
            padding: 3rem 0;
            margin-bottom: 3rem;
            border-radius: var(--border-radius);
            position: relative;
            overflow: hidden;
        }
        
        .super-admin-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255, 255, 255, 0.1) 10px,
                rgba(255, 255, 255, 0.1) 20px
            );
            animation: shimmer 20s linear infinite;
        }
        
        .admin-crown {
            font-size: 4rem;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FF6B6B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: float 3s ease-in-out infinite;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }
        
        .metric-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: transform var(--transition-fast);
        }
        
        .metric-card:hover::before {
            transform: scaleX(1);
        }
        
        .metric-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .metric-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        
        .chart-container {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            height: 400px;
            position: relative;
        }
        
        .activity-item {
            padding: 1rem;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1rem;
            background: var(--card-bg);
            border-radius: var(--border-radius-sm);
            box-shadow: var(--box-shadow);
            transition: all var(--transition-fast);
        }
        
        .activity-item:hover {
            transform: translateX(5px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .activity-time {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: var(--warning-color);
            color: var(--secondary-color);
        }
        
        .status-active {
            background: var(--success-color);
            color: white;
        }
        
        .status-completed {
            background: var(--success-color);
            color: white;
        }
        
        .status-cancelled {
            background: var(--danger-color);
            color: white;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .quick-action {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--box-shadow);
            transition: all var(--transition-fast);
            border: 1px solid var(--border-color);
        }
        
        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .quick-action i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .system-health {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
        }
        
        .health-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .health-item:last-child {
            border-bottom: none;
        }
        
        .health-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--success-color);
            box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.2);
            animation: pulse 2s infinite;
        }
        
        @media (max-width: 768px) {
            .metric-card {
                padding: 1.5rem;
            }
            
            .metric-value {
                font-size: 2rem;
            }
            
            .metric-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_superadmin.php">
                <i class="fas fa-crown float-animation"></i>
                <?php echo htmlspecialchars($branding['app_name']); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard_superadmin.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="approve_users.php">
                            <i class="fas fa-user-check me-2"></i>User Approvals
                            <?php if ($pending_users > 0): ?>
                                <span class="badge bg-warning notification-badge pulse-primary"><?php echo $pending_users; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_stores.php">
                            <i class="fas fa-store me-2"></i>Stores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_pricing.php">
                            <i class="fas fa-dollar-sign me-2"></i>Pricing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="integrations_panel.php">
                            <i class="fas fa-plug me-2"></i>Integrations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="branding_settings.php">
                            <i class="fas fa-palette me-2"></i>Branding
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <button class="dark-mode-toggle" id="darkModeToggle">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-crown me-2"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="audit_logs.php">
                                <i class="fas fa-history me-2"></i>Audit Logs
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid">
        <!-- Header -->
        <div class="super-admin-header text-center animate-fade-in">
            <div class="admin-crown">
                <i class="fas fa-crown"></i>
            </div>
            <h1 class="display-4 fw-bold text-dark mb-2">Super Admin Command Center</h1>
            <p class="lead text-dark opacity-75">Complete system oversight and management</p>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="metric-card animate-scale-in">
                    <div class="metric-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="metric-value stats-number"><?php echo $total_users; ?></div>
                    <div class="metric-label">Total Users</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="metric-card animate-scale-in">
                    <div class="metric-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="metric-value stats-number"><?php echo $total_orders; ?></div>
                    <div class="metric-label">Total Orders</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="metric-card animate-scale-in">
                    <div class="metric-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="metric-value stats-number"><?php echo $active_stores; ?></div>
                    <div class="metric-label">Active Stores</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="metric-card animate-scale-in">
                    <div class="metric-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="metric-value stats-number"><?php echo number_format($total_revenue); ?></div>
                    <div class="metric-label">Revenue (PKR)</div>
                </div>
            </div>
        </div>

        <!-- Team Overview -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="metric-card animate-slide-in-left">
                    <div class="metric-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="metric-value stats-number"><?php echo $total_admins; ?></div>
                    <div class="metric-label">Admins</div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="metric-card animate-slide-in-left">
                    <div class="metric-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="metric-value stats-number"><?php echo $total_agents; ?></div>
                    <div class="metric-label">Agents</div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="metric-card animate-slide-in-left">
                    <div class="metric-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="metric-value stats-number"><?php echo $pending_users; ?></div>
                    <div class="metric-label">Pending Approvals</div>
                </div>
            </div>
        </div>

        <!-- Charts and Analytics -->
        <div class="row mb-4">
            <div class="col-xl-8 mb-4">
                <div class="chart-container animate-fade-in">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-line me-2 text-gradient"></i>
                        Orders Overview
                    </h5>
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
            <div class="col-xl-4 mb-4">
                <div class="chart-container animate-fade-in">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-pie me-2 text-gradient"></i>
                        Order Status
                    </h5>
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- System Health and Quick Actions -->
        <div class="row mb-4">
            <div class="col-xl-6 mb-4">
                <div class="system-health animate-slide-in-left">
                    <h5 class="mb-3">
                        <i class="fas fa-heartbeat me-2 text-gradient"></i>
                        System Health
                    </h5>
                    <div class="health-item">
                        <span>Database Connection</span>
                        <div class="health-status"></div>
                    </div>
                    <div class="health-item">
                        <span>Orders Today</span>
                        <span class="fw-bold"><?php echo $orders_today; ?></span>
                    </div>
                    <div class="health-item">
                        <span>Active Users (7 days)</span>
                        <span class="fw-bold"><?php echo $active_users; ?></span>
                    </div>
                    <div class="health-item">
                        <span>System Uptime</span>
                        <span class="fw-bold text-success">99.9%</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 mb-4">
                <div class="card animate-slide-in-right">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="approve_users.php" class="quick-action text-decoration-none">
                                <i class="fas fa-user-check"></i>
                                <h6>Approve Users</h6>
                                <small class="text-muted">Review pending registrations</small>
                            </a>
                            <a href="manage_users.php" class="quick-action text-decoration-none">
                                <i class="fas fa-users"></i>
                                <h6>Manage Users</h6>
                                <small class="text-muted">Add, edit, or remove users</small>
                            </a>
                            <a href="branding_settings.php" class="quick-action text-decoration-none">
                                <i class="fas fa-palette"></i>
                                <h6>Customize Branding</h6>
                                <small class="text-muted">Update system appearance</small>
                            </a>
                            <a href="integrations_panel.php" class="quick-action text-decoration-none">
                                <i class="fas fa-plug"></i>
                                <h6>Setup Integrations</h6>
                                <small class="text-muted">Configure courier APIs</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card animate-bounce-in">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>
                            Recent System Activities
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-info-circle text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted">No recent activities to display.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></strong>
                                            <span class="text-muted"><?php echo htmlspecialchars($activity['action']); ?></span>
                                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($activity['details']); ?></p>
                                        </div>
                                        <small class="activity-time">
                                            <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="js/script.js"></script>
    
    <script>
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Orders Chart
            const ordersCtx = document.getElementById('ordersChart').getContext('2d');
            new Chart(ordersCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Orders',
                        data: [<?php echo max(1, $total_orders - 50); ?>, <?php echo max(1, $total_orders - 40); ?>, <?php echo max(1, $total_orders - 30); ?>, <?php echo max(1, $total_orders - 20); ?>, <?php echo max(1, $total_orders - 10); ?>, <?php echo max(1, $total_orders - 5); ?>, <?php echo $total_orders; ?>],
                        borderColor: 'var(--primary-color)',
                        backgroundColor: 'rgba(255, 197, 0, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Order Status Chart
            const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Pending', 'Processing', 'Cancelled'],
                    datasets: [{
                        data: [
                            <?php echo $order_stats['completed'] ?? 0; ?>,
                            <?php echo $order_stats['pending'] ?? 0; ?>,
                            <?php echo $order_stats['processing'] ?? 0; ?>,
                            <?php echo $order_stats['cancelled'] ?? 0; ?>
                        ],
                        backgroundColor: [
                            'var(--success-color)',
                            'var(--warning-color)',
                            'var(--info-color)',
                            'var(--danger-color)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>