<?php
session_start();
require_once 'config.php';
require_once 'get_branding.php';

// Check if user is logged in and is agent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle status updates
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $courierOrderId = (int)$_POST['courier_order_id'];
    $newStatus = $_POST['status'];
    $remarks = trim($_POST['remarks']);
    
    try {
        $stmt = $pdo->prepare("
            UPDATE courier_orders 
            SET status = ?, last_updated = NOW() 
            WHERE id = ? AND assigned_agent_id = ?
        ");
        $stmt->execute([$newStatus, $courierOrderId, $_SESSION['user_id']]);
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, details) 
            VALUES (?, 'courier_order_updated', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], "Updated courier order ID: $courierOrderId to status: $newStatus. Remarks: $remarks"]);
        
        $message = 'Order status updated successfully!';
        $messageType = 'success';
        
    } catch (PDOException $e) {
        $message = 'Error updating order status: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get agent's assigned courier orders with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = "WHERE co.assigned_agent_id = ?";
$params = [$_SESSION['user_id']];

if (!empty($_GET['status_filter'])) {
    $whereClause .= " AND co.status = ?";
    $params[] = $_GET['status_filter'];
}

if (!empty($_GET['courier_filter'])) {
    $whereClause .= " AND co.courier_id = ?";
    $params[] = $_GET['courier_filter'];
}

$stmt = $pdo->prepare("
    SELECT co.*, ci.courier_name, o.order_id as original_order_id, o.customer_name, 
           o.customer_phone, o.customer_city, o.product_name, o.product_price
    FROM courier_orders co
    JOIN courier_integrations ci ON co.courier_id = ci.id
    JOIN orders o ON co.order_id = o.id
    $whereClause
    ORDER BY co.last_updated DESC
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$courierOrders = $stmt->fetchAll();

// Get total count for pagination
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM courier_orders co
    JOIN courier_integrations ci ON co.courier_id = ci.id
    JOIN orders o ON co.order_id = o.id
    " . str_replace(['LIMIT ? OFFSET ?'], [''], $whereClause)
);
$countParams = array_slice($params, 0, -2);
$countStmt->execute($countParams);
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

// Get couriers for filter
$stmt = $pdo->prepare("SELECT DISTINCT ci.id, ci.courier_name FROM courier_integrations ci 
                       JOIN courier_orders co ON ci.id = co.courier_id 
                       WHERE co.assigned_agent_id = ? ORDER BY ci.courier_name");
$stmt->execute([$_SESSION['user_id']]);
$availableCouriers = $stmt->fetchAll();

// Get agent statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN co.status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN co.status = 'in_transit' THEN 1 ELSE 0 END) as in_transit_orders,
        SUM(CASE WHEN co.status = 'returned' THEN 1 ELSE 0 END) as returned_orders
    FROM courier_orders co
    WHERE co.assigned_agent_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

function getUserFullName() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn() ?: 'Agent';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courier Orders - <?php echo htmlspecialchars($branding['app_name']); ?></title>
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
        
        .courier-status {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-unbooked { background-color: #e9ecef; color: #495057; }
        .status-in_transit { background-color: #fff3cd; color: #664d03; }
        .status-delivered { background-color: #d1e7dd; color: #0f5132; }
        .status-returned { background-color: #f8d7da; color: #721c24; }
        
        .order-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard_agent.php">
                <?php if (!empty($branding['logo_url'])): ?>
                    <img src="<?php echo htmlspecialchars($branding['logo_url']); ?>" alt="<?php echo htmlspecialchars($branding['app_name']); ?>" style="height: 40px; margin-right: 10px;">
                <?php else: ?>
                    <i class="fas fa-headset me-2"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($branding['app_name']); ?> Agent
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_agent.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_orders.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="agent_courier_dashboard.php">Courier Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="agent_performance.php">Performance</a>
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-box fa-2x mb-2"></i>
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p>Total Assigned</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card" style="background: linear-gradient(135deg, var(--success-color), #1e7e34);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h3><?php echo $stats['delivered_orders']; ?></h3>
                        <p>Delivered</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card" style="background: linear-gradient(135deg, var(--warning-color), #d39e00);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-truck fa-2x mb-2"></i>
                        <h3><?php echo $stats['in_transit_orders']; ?></h3>
                        <p>In Transit</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card" style="background: linear-gradient(135deg, var(--danger-color), #bd2130);">
                    <div class="card-body text-center text-white">
                        <i class="fas fa-undo fa-2x mb-2"></i>
                        <h3><?php echo $stats['returned_orders']; ?></h3>
                        <p>Returned</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-filter me-2"></i>Filters
                            </h5>
                            <select class="form-select" style="width: 200px;" id="courierFilter" onchange="filterOrders()">
                                <option value="">All Couriers</option>
                                <?php foreach ($availableCouriers as $courier): ?>
                                <option value="<?php echo $courier['id']; ?>" <?php echo $_GET['courier_filter'] == $courier['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($courier['courier_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select" style="width: 200px;" id="statusFilter" onchange="filterOrders()">
                                <option value="">All Statuses</option>
                                <option value="unbooked" <?php echo $_GET['status_filter'] == 'unbooked' ? 'selected' : ''; ?>>Unbooked</option>
                                <option value="in_transit" <?php echo $_GET['status_filter'] == 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                <option value="delivered" <?php echo $_GET['status_filter'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="returned" <?php echo $_GET['status_filter'] == 'returned' ? 'selected' : ''; ?>>Returned</option>
                            </select>
                            <button class="btn btn-outline-primary" onclick="clearFilters()">
                                <i class="fas fa-times me-1"></i>Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courier Orders -->
        <div class="row">
            <?php foreach ($courierOrders as $order): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card order-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Order #<?php echo htmlspecialchars($order['original_order_id']); ?></h6>
                        <span class="courier-status status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                            <strong>City:</strong> <?php echo htmlspecialchars($order['customer_city']); ?><br>
                            <strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?><br>
                            <strong>Price:</strong> Rs. <?php echo number_format($order['product_price'], 2); ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Courier:</strong> <?php echo htmlspecialchars($order['courier_name']); ?><br>
                            <?php if ($order['tracking_number']): ?>
                            <strong>Tracking:</strong> 
                            <code><?php echo htmlspecialchars($order['tracking_number']); ?></code><br>
                            <?php endif; ?>
                            <?php if ($order['courier_status']): ?>
                            <strong>Courier Status:</strong> <?php echo htmlspecialchars($order['courier_status']); ?><br>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="courier_order_id" value="<?php echo $order['id']; ?>">
                            
                            <div class="mb-2">
                                <label class="form-label">Update Status</label>
                                <select class="form-select form-select-sm" name="status" required>
                                    <option value="unbooked" <?php echo $order['status'] == 'unbooked' ? 'selected' : ''; ?>>Unbooked</option>
                                    <option value="in_transit" <?php echo $order['status'] == 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="returned" <?php echo $order['status'] == 'returned' ? 'selected' : ''; ?>>Returned</option>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control form-control-sm" name="remarks" rows="2" placeholder="Add remarks (optional)"></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                    <i class="fas fa-save me-1"></i>Update
                                </button>
                                <?php if ($order['customer_phone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-phone"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-muted">
                        <small>Last updated: <?php echo date('M j, Y H:i', strtotime($order['last_updated'])); ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($courierOrders)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No courier orders assigned</h5>
                        <p class="text-muted">You don't have any courier orders assigned to you yet.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&courier_filter=<?php echo $_GET['courier_filter'] ?? ''; ?>&status_filter=<?php echo $_GET['status_filter'] ?? ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        function filterOrders() {
            const courierFilter = document.getElementById('courierFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const params = new URLSearchParams();
            
            if (courierFilter) params.set('courier_filter', courierFilter);
            if (statusFilter) params.set('status_filter', statusFilter);
            
            window.location.href = 'agent_courier_dashboard.php?' + params.toString();
        }
        
        function clearFilters() {
            window.location.href = 'agent_courier_dashboard.php';
        }
        
        // Auto-refresh every 60 seconds
        setInterval(function() {
            if (!document.querySelector('form:focus-within')) {
                location.reload();
            }
        }, 60000);
    </script>
</body>
</html>