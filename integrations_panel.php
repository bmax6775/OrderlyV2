<?php
session_start();
require_once 'config.php';
require_once 'get_branding.php';

// Check if user is logged in and is admin or super admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_courier':
                $courierId = (int)$_POST['courier_id'];
                $apiToken = trim($_POST['api_token']);
                $apiSecret = trim($_POST['api_secret']);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                $autoSync = isset($_POST['auto_sync']) ? 1 : 0;
                $syncFrequency = (int)$_POST['sync_frequency'];
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE courier_integrations 
                        SET api_token = ?, api_secret = ?, is_active = ?, auto_sync = ?, 
                            sync_frequency = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$apiToken, $apiSecret, $isActive, $autoSync, $syncFrequency, $courierId]);
                    
                    $message = 'Courier settings updated successfully!';
                    $messageType = 'success';
                    
                    // Log the action
                    $stmt = $pdo->prepare("
                        INSERT INTO audit_logs (user_id, action, details) 
                        VALUES (?, 'courier_updated', ?)
                    ");
                    $stmt->execute([$_SESSION['user_id'], "Updated courier ID: $courierId"]);
                    
                } catch (PDOException $e) {
                    $message = 'Error updating courier settings: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'sync_courier':
                $courierId = (int)$_POST['courier_id'];
                
                // Get courier details
                $stmt = $pdo->prepare("SELECT * FROM courier_integrations WHERE id = ?");
                $stmt->execute([$courierId]);
                $courier = $stmt->fetch();
                
                if ($courier && $courier['is_active']) {
                    $syncResult = syncCourierOrders($courier, $_SESSION['user_id']);
                    $message = $syncResult['message'];
                    $messageType = $syncResult['type'];
                } else {
                    $message = 'Courier is not active or not found.';
                    $messageType = 'warning';
                }
                break;
                
            case 'assign_agent':
                $orderIds = $_POST['order_ids'] ?? [];
                $agentId = (int)$_POST['agent_id'];
                
                if (!empty($orderIds) && $agentId > 0) {
                    try {
                        $stmt = $pdo->prepare("
                            UPDATE courier_orders 
                            SET assigned_agent_id = ?, last_updated = NOW() 
                            WHERE id IN (" . str_repeat('?,', count($orderIds) - 1) . "?)
                        ");
                        $params = array_merge([$agentId], $orderIds);
                        $stmt->execute($params);
                        
                        $message = 'Orders assigned to agent successfully!';
                        $messageType = 'success';
                        
                        // Log the action
                        $stmt = $pdo->prepare("
                            INSERT INTO audit_logs (user_id, action, details) 
                            VALUES (?, 'orders_assigned', ?)
                        ");
                        $stmt->execute([$_SESSION['user_id'], "Assigned " . count($orderIds) . " orders to agent ID: $agentId"]);
                        
                    } catch (PDOException $e) {
                        $message = 'Error assigning orders: ' . $e->getMessage();
                        $messageType = 'danger';
                    }
                } else {
                    $message = 'Please select orders and an agent.';
                    $messageType = 'warning';
                }
                break;
        }
    }
}

// Sync function
function syncCourierOrders($courier, $userId) {
    global $pdo;
    
    $startTime = microtime(true);
    $recordsProcessed = 0;
    $errorMessage = '';
    
    try {
        // Log sync start
        $stmt = $pdo->prepare("
            INSERT INTO courier_sync_logs (courier_id, sync_type, status, triggered_by) 
            VALUES (?, 'manual', 'running', ?)
        ");
        $stmt->execute([$courier['id'], $userId]);
        $logId = $pdo->lastInsertId();
        
        // Simulate API call (replace with actual courier API integration)
        $apiResult = simulateCourierAPICall($courier);
        
        if ($apiResult['success']) {
            foreach ($apiResult['orders'] as $orderData) {
                // Update or insert courier order
                $stmt = $pdo->prepare("
                    INSERT INTO courier_orders (order_id, courier_id, courier_order_id, tracking_number, status, courier_status, courier_response) 
                    VALUES (?, ?, ?, ?, ?, ?, ?) 
                    ON CONFLICT (order_id, courier_id) 
                    DO UPDATE SET tracking_number = ?, status = ?, courier_status = ?, courier_response = ?, last_updated = NOW()
                ");
                $stmt->execute([
                    $orderData['order_id'], $courier['id'], $orderData['courier_order_id'],
                    $orderData['tracking_number'], $orderData['status'], $orderData['courier_status'],
                    json_encode($orderData), $orderData['tracking_number'], $orderData['status'],
                    $orderData['courier_status'], json_encode($orderData)
                ]);
                $recordsProcessed++;
            }
            
            // Update last sync time
            $stmt = $pdo->prepare("UPDATE courier_integrations SET last_sync = NOW() WHERE id = ?");
            $stmt->execute([$courier['id']]);
            
            $status = 'success';
            $message = "Sync completed successfully. $recordsProcessed orders processed.";
            $type = 'success';
            
        } else {
            $status = 'error';
            $errorMessage = $apiResult['error'];
            $message = 'Sync failed: ' . $errorMessage;
            $type = 'danger';
        }
        
    } catch (Exception $e) {
        $status = 'error';
        $errorMessage = $e->getMessage();
        $message = 'Sync error: ' . $errorMessage;
        $type = 'danger';
    }
    
    // Update sync log
    $duration = (microtime(true) - $startTime) * 1000;
    $stmt = $pdo->prepare("
        UPDATE courier_sync_logs 
        SET status = ?, records_processed = ?, error_message = ?, sync_duration = ? 
        WHERE id = ?
    ");
    $stmt->execute([$status, $recordsProcessed, $errorMessage, $duration, $logId]);
    
    return ['message' => $message, 'type' => $type];
}

// Simulate courier API call (replace with actual implementation)
function simulateCourierAPICall($courier) {
    // This is a simulation - replace with actual courier API calls
    $sampleOrders = [
        [
            'order_id' => 1,
            'courier_order_id' => 'COR' . rand(100000, 999999),
            'tracking_number' => 'TRK' . rand(100000, 999999),
            'status' => 'in_transit',
            'courier_status' => 'Out for Delivery'
        ],
        [
            'order_id' => 2,
            'courier_order_id' => 'COR' . rand(100000, 999999),
            'tracking_number' => 'TRK' . rand(100000, 999999),
            'status' => 'delivered',
            'courier_status' => 'Delivered'
        ]
    ];
    
    return [
        'success' => true,
        'orders' => $sampleOrders
    ];
}

// Get courier integrations
$stmt = $pdo->prepare("SELECT * FROM courier_integrations ORDER BY courier_name");
$stmt->execute();
$couriers = $stmt->fetchAll();

// Get courier orders with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = "WHERE 1=1";
$params = [];

if (!empty($_GET['courier_filter'])) {
    $whereClause .= " AND co.courier_id = ?";
    $params[] = $_GET['courier_filter'];
}

if (!empty($_GET['status_filter'])) {
    $whereClause .= " AND co.status = ?";
    $params[] = $_GET['status_filter'];
}

$stmt = $pdo->prepare("
    SELECT co.*, ci.courier_name, o.order_id as original_order_id, o.customer_name, o.product_name,
           u.full_name as agent_name
    FROM courier_orders co
    JOIN courier_integrations ci ON co.courier_id = ci.id
    JOIN orders o ON co.order_id = o.id
    LEFT JOIN users u ON co.assigned_agent_id = u.id
    $whereClause
    ORDER BY co.created_at DESC
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

// Get agents for assignment
$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'agent' AND status = 'active' ORDER BY full_name");
$stmt->execute();
$agents = $stmt->fetchAll();

// Get recent sync logs
$stmt = $pdo->prepare("
    SELECT csl.*, ci.courier_name, u.full_name as triggered_by_name
    FROM courier_sync_logs csl
    JOIN courier_integrations ci ON csl.courier_id = ci.id
    LEFT JOIN users u ON csl.triggered_by = u.id
    ORDER BY csl.created_at DESC
    LIMIT 10
");
$stmt->execute();
$syncLogs = $stmt->fetchAll();

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
    <title>Courier Integrations - <?php echo htmlspecialchars($branding['app_name']); ?></title>
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
        
        .sync-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        
        .sync-success { background-color: var(--success-color); }
        .sync-error { background-color: var(--danger-color); }
        .sync-running { background-color: var(--warning-color); animation: pulse 1s infinite; }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $_SESSION['role'] == 'super_admin' ? 'dashboard_superadmin.php' : 'dashboard_admin.php'; ?>">
                <?php if (!empty($branding['logo_url'])): ?>
                    <img src="<?php echo htmlspecialchars($branding['logo_url']); ?>" alt="<?php echo htmlspecialchars($branding['app_name']); ?>" style="height: 40px; margin-right: 10px;">
                <?php else: ?>
                    <i class="fas fa-shipping-fast me-2"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($branding['app_name']); ?> - Integrations
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $_SESSION['role'] == 'super_admin' ? 'dashboard_superadmin.php' : 'dashboard_admin.php'; ?>">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_orders.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="integrations_panel.php">Courier Integrations</a>
                    </li>
                    <?php if ($_SESSION['role'] == 'super_admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="branding_settings.php">Branding</a>
                    </li>
                    <?php endif; ?>
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

        <!-- Courier Configuration -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Pakistani Courier Integrations
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($couriers as $courier): ?>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <form method="POST" class="h-100 d-flex flex-column">
                                            <input type="hidden" name="action" value="update_courier">
                                            <input type="hidden" name="courier_id" value="<?php echo $courier['id']; ?>">
                                            
                                            <div class="d-flex align-items-center mb-3">
                                                <h6 class="card-title mb-0 me-2"><?php echo htmlspecialchars($courier['courier_name']); ?></h6>
                                                <span class="sync-indicator <?php echo $courier['is_active'] ? 'sync-success' : 'sync-error'; ?>"></span>
                                                <small class="text-muted"><?php echo $courier['is_active'] ? 'Active' : 'Inactive'; ?></small>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label class="form-label">API Token</label>
                                                <input type="text" class="form-control form-control-sm" name="api_token" 
                                                       value="<?php echo htmlspecialchars($courier['api_token'] ?: ''); ?>" placeholder="Enter API token">
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label class="form-label">API Secret</label>
                                                <input type="password" class="form-control form-control-sm" name="api_secret" 
                                                       value="<?php echo htmlspecialchars($courier['api_secret'] ?: ''); ?>" placeholder="Enter API secret">
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label class="form-label">Sync Frequency (seconds)</label>
                                                <select class="form-select form-select-sm" name="sync_frequency">
                                                    <option value="1800" <?php echo $courier['sync_frequency'] == 1800 ? 'selected' : ''; ?>>30 minutes</option>
                                                    <option value="3600" <?php echo $courier['sync_frequency'] == 3600 ? 'selected' : ''; ?>>1 hour</option>
                                                    <option value="7200" <?php echo $courier['sync_frequency'] == 7200 ? 'selected' : ''; ?>>2 hours</option>
                                                    <option value="21600" <?php echo $courier['sync_frequency'] == 21600 ? 'selected' : ''; ?>>6 hours</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="active_<?php echo $courier['id']; ?>" 
                                                       <?php echo $courier['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="active_<?php echo $courier['id']; ?>">
                                                    Enable Integration
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" name="auto_sync" id="auto_<?php echo $courier['id']; ?>" 
                                                       <?php echo $courier['auto_sync'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="auto_<?php echo $courier['id']; ?>">
                                                    Auto Sync
                                                </label>
                                            </div>
                                            
                                            <div class="mt-auto">
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                                        <i class="fas fa-save me-1"></i>Save
                                                    </button>
                                                    <?php if ($courier['is_active']): ?>
                                                    <button type="submit" name="action" value="sync_courier" class="btn btn-success btn-sm">
                                                        <i class="fas fa-sync me-1"></i>Sync
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($courier['last_sync']): ?>
                                                <small class="text-muted d-block mt-1">
                                                    Last sync: <?php echo date('M j, H:i', strtotime($courier['last_sync'])); ?>
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courier Orders Management -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-truck me-2"></i>Courier Orders Management
                            </h4>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm" id="courierFilter" onchange="filterOrders()">
                                    <option value="">All Couriers</option>
                                    <?php foreach ($couriers as $courier): ?>
                                    <option value="<?php echo $courier['id']; ?>" <?php echo $_GET['courier_filter'] == $courier['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($courier['courier_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="form-select form-select-sm" id="statusFilter" onchange="filterOrders()">
                                    <option value="">All Statuses</option>
                                    <option value="unbooked" <?php echo $_GET['status_filter'] == 'unbooked' ? 'selected' : ''; ?>>Unbooked</option>
                                    <option value="in_transit" <?php echo $_GET['status_filter'] == 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                    <option value="delivered" <?php echo $_GET['status_filter'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="returned" <?php echo $_GET['status_filter'] == 'returned' ? 'selected' : ''; ?>>Returned</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="assignAgentForm">
                            <input type="hidden" name="action" value="assign_agent">
                            
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th width="5%">
                                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                            </th>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Product</th>
                                            <th>Courier</th>
                                            <th>Tracking #</th>
                                            <th>Status</th>
                                            <th>Assigned Agent</th>
                                            <th>Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courierOrders as $order): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="order_ids[]" value="<?php echo $order['id']; ?>" class="order-checkbox">
                                            </td>
                                            <td><?php echo htmlspecialchars($order['original_order_id']); ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['courier_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['tracking_number'] ?: '-'); ?></td>
                                            <td>
                                                <span class="courier-status status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['agent_name'] ?: 'Unassigned'); ?></td>
                                            <td><?php echo date('M j, H:i', strtotime($order['last_updated'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($courierOrders)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No courier orders found</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (!empty($courierOrders)): ?>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="d-flex gap-2 align-items-center">
                                    <select class="form-select form-select-sm" name="agent_id" style="width: 200px;">
                                        <option value="">Select Agent</option>
                                        <?php foreach ($agents as $agent): ?>
                                        <option value="<?php echo $agent['id']; ?>"><?php echo htmlspecialchars($agent['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-user-check me-1"></i>Assign Selected
                                    </button>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&courier_filter=<?php echo $_GET['courier_filter'] ?? ''; ?>&status_filter=<?php echo $_GET['status_filter'] ?? ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Logs -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Sync Logs
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Courier</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Records</th>
                                        <th>Duration</th>
                                        <th>Triggered By</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($syncLogs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['courier_name']); ?></td>
                                        <td><?php echo ucfirst($log['sync_type']); ?></td>
                                        <td>
                                            <span class="sync-indicator sync-<?php echo $log['status']; ?>"></span>
                                            <?php echo ucfirst($log['status']); ?>
                                        </td>
                                        <td><?php echo $log['records_processed']; ?></td>
                                        <td><?php echo $log['sync_duration'] ? round($log['sync_duration']) . 'ms' : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($log['triggered_by_name'] ?: 'System'); ?></td>
                                        <td><?php echo date('M j, H:i:s', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($syncLogs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No sync logs found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
            
            window.location.href = 'integrations_panel.php?' + params.toString();
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }
        
        // Auto-refresh courier orders every 30 seconds
        setInterval(function() {
            // Only refresh if no forms are being submitted
            if (!document.querySelector('form:target')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>