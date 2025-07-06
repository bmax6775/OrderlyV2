<?php
session_start();
require_once 'config.php';
require_once 'get_branding.php';

requireRole('admin');

// Get admin's comprehensive info
$stmt = $pdo->prepare("SELECT u.*, pp.name as plan_name FROM users u LEFT JOIN pricing_plans pp ON u.plan_id = pp.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_info = $stmt->fetch();

// Get admin's stores
$stmt = $pdo->prepare("SELECT * FROM stores WHERE admin_id = ? ORDER BY name");
$stmt->execute([$_SESSION['user_id']]);
$stores = $stmt->fetchAll();

// Get comprehensive statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE admin_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_orders = $stmt->fetch()['total_orders'];

$stmt = $pdo->prepare("SELECT COUNT(*) as new_orders FROM orders WHERE admin_id = ? AND status = 'new'");
$stmt->execute([$_SESSION['user_id']]);
$new_orders = $stmt->fetch()['new_orders'];

$stmt = $pdo->prepare("SELECT COUNT(*) as delivered_orders FROM orders WHERE admin_id = ? AND status = 'delivered'");
$stmt->execute([$_SESSION['user_id']]);
$delivered_orders = $stmt->fetch()['delivered_orders'];

$stmt = $pdo->prepare("SELECT COUNT(*) as agents FROM users WHERE role = 'agent' AND created_by = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_agents = $stmt->fetch()['agents'];

$stmt = $pdo->prepare("SELECT COUNT(*) as active_stores FROM stores WHERE admin_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$active_stores = $stmt->fetch()['active_stores'];

$stmt = $pdo->prepare("SELECT COUNT(*) as orders_today FROM orders WHERE admin_id = ? AND DATE(created_at) = CURRENT_DATE");
$stmt->execute([$_SESSION['user_id']]);
$orders_today = $stmt->fetch()['orders_today'];

// Get order status breakdown
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM orders WHERE admin_id = ? GROUP BY status");
$stmt->execute([$_SESSION['user_id']]);
$order_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get recent orders
$stmt = $pdo->prepare("SELECT o.*, s.name as store_name, u.full_name as agent_name FROM orders o LEFT JOIN stores s ON o.store_id = s.id LEFT JOIN users u ON o.assigned_agent_id = u.id WHERE o.admin_id = ? ORDER BY o.created_at DESC LIMIT 8");
$stmt->execute([$_SESSION['user_id']]);
$recent_orders = $stmt->fetchAll();

// Get agent performance
$stmt = $pdo->prepare("SELECT u.full_name, COUNT(o.id) as total_orders, 
    SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
    FROM users u 
    LEFT JOIN orders o ON u.id = o.assigned_agent_id 
    WHERE u.role = 'agent' AND u.created_by = ? 
    GROUP BY u.id, u.full_name 
    ORDER BY total_orders DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$agent_performance = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($branding['app_name']); ?></title>
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
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
            position: relative;
            overflow: hidden;
        }
        
        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .admin-icon {
            font-size: 3.5rem;
            background: linear-gradient(45deg, #ffffff, #f8f9fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: float 3s ease-in-out infinite;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .performance-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: all var(--transition-normal);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }
        
        .performance-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--success-color), var(--primary-color));
            transform: scaleX(0);
            transition: transform var(--transition-fast);
        }
        
        .performance-card:hover::before {
            transform: scaleX(1);
        }
        
        .performance-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .order-item {
            background: var(--card-bg);
            border-radius: var(--border-radius-sm);
            padding: 1rem;
            margin-bottom: 0.75rem;
            box-shadow: var(--box-shadow);
            transition: all var(--transition-fast);
            border-left: 4px solid var(--primary-color);
        }
        
        .order-item:hover {
            transform: translateX(5px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .store-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--box-shadow);
            transition: all var(--transition-fast);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .store-card::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(255, 197, 0, 0.1), transparent);
            transition: all var(--transition-fast);
            transform: translate(-50%, -50%);
            border-radius: 50%;
        }
        
        .store-card:hover::after {
            width: 200px;
            height: 200px;
        }
        
        .store-card:hover {
            transform: scale(1.05);
            box-shadow: var(--box-shadow-hover);
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--box-shadow);
            transition: all var(--transition-fast);
        }
        
        .stat-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }
        
        .agent-progress {
            height: 8px;
            background: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .agent-progress-bar {
            height: 100%;
            background: var(--gradient-primary);
            transition: width var(--transition-normal);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_admin.php">
                <i class="fas fa-user-tie float-animation"></i>
                <?php echo htmlspecialchars($branding['app_name']); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard_admin.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_orders.php">
                            <i class="fas fa-shopping-cart me-2"></i>Orders
                            <?php if ($new_orders > 0): ?>
                                <span class="badge bg-danger notification-badge pulse-primary"><?php echo $new_orders; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_agents.php">
                            <i class="fas fa-users me-2"></i>Agents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_stores.php">
                            <i class="fas fa-store me-2"></i>Stores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="upload_orders.php">
                            <i class="fas fa-upload me-2"></i>Upload
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="store_analytics.php">
                            <i class="fas fa-chart-bar me-2"></i>Analytics
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
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
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
        <div class="admin-header text-center animate-fade-in">
            <div class="admin-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <h1 class="display-5 fw-bold text-white mb-2">Admin Control Panel</h1>
            <p class="lead text-white opacity-90">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>

        <!-- Quick Statistics -->
        <div class="quick-stats animate-scale-in">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-value stats-number"><?php echo $total_orders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value stats-number"><?php echo $new_orders; ?></div>
                <div class="stat-label">New Orders</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value stats-number"><?php echo $delivered_orders; ?></div>
                <div class="stat-label">Delivered</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value stats-number"><?php echo $total_agents; ?></div>
                <div class="stat-label">Team Agents</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-value stats-number"><?php echo $active_stores; ?></div>
                <div class="stat-label">Active Stores</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-value stats-number"><?php echo $orders_today; ?></div>
                <div class="stat-label">Today's Orders</div>
            </div>
        </div>

        <div class="row">
            <!-- Orders Chart -->
            <div class="col-xl-8 mb-4">
                <div class="card animate-slide-in-left">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-area me-2"></i>
                            Orders Performance
                        </h5>
                    </div>
                    <div class="card-body" style="height: 350px;">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Order Status Breakdown -->
            <div class="col-xl-4 mb-4">
                <div class="card animate-slide-in-right">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Order Status
                        </h5>
                    </div>
                    <div class="card-body" style="height: 350px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Orders -->
            <div class="col-xl-8 mb-4">
                <div class="card animate-fade-in">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Recent Orders
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart text-muted mb-2" style="font-size: 3rem;"></i>
                                <h6 class="text-muted">No orders yet</h6>
                                <p class="text-muted mb-3">Start by uploading your first orders</p>
                                <a href="upload_orders.php" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i>Upload Orders
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($order['customer_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($order['store_name'] ?? 'No Store'); ?> • 
                                                Agent: <?php echo htmlspecialchars($order['agent_name'] ?? 'Unassigned'); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('M j, g:i A', strtotime($order['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Agent Performance -->
            <div class="col-xl-4 mb-4">
                <div class="card animate-slide-in-right">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-trophy me-2"></i>
                            Top Agents
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($agent_performance)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users text-muted mb-2" style="font-size: 2.5rem;"></i>
                                <h6 class="text-muted">No agents yet</h6>
                                <p class="text-muted mb-3">Add team members to get started</p>
                                <a href="manage_agents.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Add Agents
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($agent_performance as $index => $agent): ?>
                                <div class="performance-card mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="me-3">
                                            <span class="badge bg-primary">#<?php echo $index + 1; ?></span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($agent['full_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $agent['total_orders']; ?> orders • 
                                                <?php echo $agent['delivered_orders']; ?> delivered
                                            </small>
                                        </div>
                                    </div>
                                    <div class="agent-progress">
                                        <div class="agent-progress-bar" style="width: <?php echo $agent['total_orders'] > 0 ? ($agent['delivered_orders'] / $agent['total_orders']) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stores Overview -->
        <?php if (!empty($stores)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card animate-bounce-in">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-store me-2"></i>
                                Your Stores
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($stores as $store): ?>
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="store-card">
                                            <i class="fas fa-store text-primary mb-3" style="font-size: 2.5rem;"></i>
                                            <h6 class="mb-2"><?php echo htmlspecialchars($store['name']); ?></h6>
                                            <p class="text-muted mb-3"><?php echo htmlspecialchars($store['platform']); ?></p>
                                            <span class="badge status-<?php echo $store['status']; ?>">
                                                <?php echo ucfirst($store['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="js/script.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Orders Performance Chart
            const ordersCtx = document.getElementById('ordersChart').getContext('2d');
            new Chart(ordersCtx, {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [{
                        label: 'Orders',
                        data: [<?php echo max(1, $total_orders - 30); ?>, <?php echo max(1, $total_orders - 20); ?>, <?php echo max(1, $total_orders - 10); ?>, <?php echo $total_orders; ?>],
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

            // Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['New', 'Processing', 'Delivered', 'Cancelled'],
                    datasets: [{
                        data: [
                            <?php echo $order_stats['new'] ?? 0; ?>,
                            <?php echo $order_stats['processing'] ?? 0; ?>,
                            <?php echo $order_stats['delivered'] ?? 0; ?>,
                            <?php echo $order_stats['cancelled'] ?? 0; ?>
                        ],
                        backgroundColor: [
                            'var(--info-color)',
                            'var(--warning-color)',
                            'var(--success-color)',
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