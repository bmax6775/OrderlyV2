<?php
session_start();
require_once 'config.php';

requireRole('admin');

$admin_id = $_SESSION['user_id'];

// Get filters
$selected_store = $_GET['store_id'] ?? '';
$date_range = $_GET['date_range'] ?? '';

// Get admin's stores for filter
$stmt = $pdo->prepare("SELECT * FROM stores WHERE admin_id = ? ORDER BY name");
$stmt->execute([$admin_id]);
$stores = $stmt->fetchAll();

// Build date filter
$date_filter = "";
$date_params = [];
switch ($date_range) {
    case '7':
        $date_filter = "AND o.created_at >= NOW() - INTERVAL '7 days'";
        break;
    case '30':
        $date_filter = "AND o.created_at >= NOW() - INTERVAL '30 days'";
        break;
    case '90':
        $date_filter = "AND o.created_at >= NOW() - INTERVAL '90 days'";
        break;
    default:
        $date_filter = "";
}

// Build store filter
$store_filter = "";
$base_params = [$admin_id];
if ($selected_store) {
    $store_filter = "AND o.store_id = ?";
    $base_params[] = $selected_store;
}

// Get analytics data
$analytics = [];

// Total orders
$sql = "SELECT COUNT(*) as total_orders FROM orders o WHERE o.admin_id = ? $store_filter $date_filter";
$stmt = $pdo->prepare($sql);
$stmt->execute($base_params);
$analytics['total_orders'] = $stmt->fetch()['total_orders'];

// Orders by status
$sql = "SELECT status, COUNT(*) as count FROM orders o WHERE o.admin_id = ? $store_filter $date_filter GROUP BY status";
$stmt = $pdo->prepare($sql);
$stmt->execute($base_params);
$status_data = $stmt->fetchAll();

$analytics['status_breakdown'] = [];
foreach ($status_data as $status) {
    $analytics['status_breakdown'][$status['status']] = $status['count'];
}

// Revenue data
$sql = "SELECT SUM(product_price) as total_revenue, COUNT(*) as delivered_orders FROM orders o WHERE o.admin_id = ? AND o.status = 'delivered' $store_filter $date_filter";
$revenue_params = array_merge([$admin_id], array_slice($base_params, 1));
$stmt = $pdo->prepare($sql);
$stmt->execute($revenue_params);
$revenue_data = $stmt->fetch();
$analytics['total_revenue'] = $revenue_data['total_revenue'] ?? 0;
$analytics['delivered_orders'] = $revenue_data['delivered_orders'] ?? 0;

// Average order value
$analytics['avg_order_value'] = $analytics['delivered_orders'] > 0 ? 
    $analytics['total_revenue'] / $analytics['delivered_orders'] : 0;

// Store performance
$sql = "SELECT s.name, COUNT(o.id) as total_orders, 
        SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN o.status = 'delivered' THEN o.product_price ELSE 0 END) as revenue
        FROM stores s 
        LEFT JOIN orders o ON s.id = o.store_id AND o.admin_id = ? $date_filter
        WHERE s.admin_id = ? 
        GROUP BY s.id, s.name 
        ORDER BY total_orders DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$admin_id, $admin_id]);
$store_performance = $stmt->fetchAll();

// Agent performance
$agent_params = [$admin_id];
if ($selected_store) {
    $agent_params[] = $selected_store;
}
$agent_params[] = $admin_id;

$sql = "SELECT u.full_name, COUNT(o.id) as total_orders,
        SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN o.status = 'failed' THEN 1 ELSE 0 END) as failed_orders
        FROM users u 
        LEFT JOIN orders o ON u.id = o.assigned_agent_id AND o.admin_id = ? $store_filter $date_filter
        WHERE u.role = 'agent' AND u.created_by = ?
        GROUP BY u.id, u.full_name
        ORDER BY total_orders DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($agent_params);
$agent_performance = $stmt->fetchAll();

// Orders by day (last 30 days)
$daily_params = [$admin_id];
if ($selected_store) {
    $daily_params[] = $selected_store;
}

$sql = "SELECT DATE(o.created_at) as order_date, COUNT(*) as orders_count
        FROM orders o 
        WHERE o.admin_id = ? AND o.created_at >= NOW() - INTERVAL '30 days' $store_filter
        GROUP BY DATE(o.created_at)
        ORDER BY order_date DESC
        LIMIT 30";
$stmt = $pdo->prepare($sql);
$stmt->execute($daily_params);
$daily_orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Analytics - OrderDesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard_admin.php">
                <i class="fas fa-shopping-cart me-2"></i>OrderDesk
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_admin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_orders.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_stores.php">Stores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_agents.php">Agents</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="store_analytics.php">Analytics</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i><?php echo $_SESSION['full_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-outline-light btn-sm" onclick="toggleDarkMode()">
                            <i class="fas fa-moon" id="darkModeIcon"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-bar me-2"></i>Store Analytics</h2>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="store_id" class="form-label">Store</label>
                                <select name="store_id" class="form-select">
                                    <option value="">All Stores</option>
                                    <?php foreach ($stores as $store): ?>
                                        <option value="<?php echo $store['id']; ?>" <?php echo $selected_store == $store['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($store['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="date_range" class="form-label">Date Range</label>
                                <select name="date_range" class="form-select">
                                    <option value="">All Time</option>
                                    <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                                    <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-shopping-bag stats-icon"></i>
                                <div class="stats-number"><?php echo number_format($analytics['total_orders']); ?></div>
                                <div class="stats-label">Total Orders</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-truck stats-icon"></i>
                                <div class="stats-number"><?php echo number_format($analytics['delivered_orders']); ?></div>
                                <div class="stats-label">Delivered</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign stats-icon"></i>
                                <div class="stats-number">$<?php echo number_format($analytics['total_revenue'], 0); ?></div>
                                <div class="stats-label">Revenue</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line stats-icon"></i>
                                <div class="stats-number">$<?php echo number_format($analytics['avg_order_value'], 0); ?></div>
                                <div class="stats-label">Avg Order Value</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Orders by Status</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Daily Orders (Last 30 Days)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="dailyOrdersChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables Row -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Store Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Store</th>
                                                <th>Orders</th>
                                                <th>Delivered</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($store_performance as $store): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($store['name']); ?></td>
                                                    <td><?php echo number_format($store['total_orders']); ?></td>
                                                    <td><?php echo number_format($store['delivered_orders']); ?></td>
                                                    <td>$<?php echo number_format($store['revenue'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Agent Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Agent</th>
                                                <th>Orders</th>
                                                <th>Delivered</th>
                                                <th>Failed</th>
                                                <th>Success Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($agent_performance as $agent): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($agent['full_name']); ?></td>
                                                    <td><?php echo number_format($agent['total_orders']); ?></td>
                                                    <td><?php echo number_format($agent['delivered_orders']); ?></td>
                                                    <td><?php echo number_format($agent['failed_orders']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $success_rate = $agent['total_orders'] > 0 ? 
                                                            ($agent['delivered_orders'] / $agent['total_orders']) * 100 : 0;
                                                        echo number_format($success_rate, 1) . '%';
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($analytics['status_breakdown'])); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($analytics['status_breakdown'])); ?>,
                    backgroundColor: [
                        '#ffc500',
                        '#000000',
                        '#28a745',
                        '#dc3545',
                        '#6c757d',
                        '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Daily Orders Chart
        const dailyCtx = document.getElementById('dailyOrdersChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_reverse(array_column($daily_orders, 'order_date'))); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode(array_reverse(array_column($daily_orders, 'orders_count'))); ?>,
                    borderColor: '#ffc500',
                    backgroundColor: 'rgba(255, 197, 0, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>