<?php
session_start();
require_once 'config.php';

requireRole('admin');

$admin_id = $_SESSION['user_id'];

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_agent':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $full_name = trim($_POST['full_name']);
                $phone = trim($_POST['phone']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role, status, created_by) VALUES (?, ?, ?, ?, ?, 'agent', 'active', ?)");
                    $stmt->execute([$username, $email, $password, $full_name, $phone, $admin_id]);
                    
                    $agent_id = $pdo->lastInsertId();
                    logActivity($_SESSION['user_id'], 'agent_created', "Created agent: $full_name ($username)");
                    
                    $success = "Agent created successfully!";
                } catch (PDOException $e) {
                    $error = "Error creating agent: " . $e->getMessage();
                }
                break;
                
            case 'assign_to_store':
                $agent_id = $_POST['agent_id'];
                $store_id = $_POST['store_id'];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO store_agents (store_id, agent_id, assigned_by) VALUES (?, ?, ?) ON CONFLICT (store_id, agent_id) DO NOTHING");
                    $stmt->execute([$store_id, $agent_id, $admin_id]);
                    
                    logActivity($_SESSION['user_id'], 'agent_assigned_to_store', "Assigned agent ID $agent_id to store ID $store_id");
                    $success = "Agent assigned to store successfully!";
                } catch (PDOException $e) {
                    $error = "Error assigning agent: " . $e->getMessage();
                }
                break;
                
            case 'remove_from_store':
                $agent_id = $_POST['agent_id'];
                $store_id = $_POST['store_id'];
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM store_agents WHERE store_id = ? AND agent_id = ?");
                    $stmt->execute([$store_id, $agent_id]);
                    
                    logActivity($_SESSION['user_id'], 'agent_removed_from_store', "Removed agent ID $agent_id from store ID $store_id");
                    $success = "Agent removed from store successfully!";
                } catch (PDOException $e) {
                    $error = "Error removing agent: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get admin's agents
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'agent' AND created_by = ? ORDER BY full_name");
$stmt->execute([$admin_id]);
$agents = $stmt->fetchAll();

// Get admin's stores
$stmt = $pdo->prepare("SELECT * FROM stores WHERE admin_id = ? ORDER BY name");
$stmt->execute([$admin_id]);
$stores = $stmt->fetchAll();

// Get agent-store assignments
$stmt = $pdo->prepare("
    SELECT sa.*, u.full_name as agent_name, s.name as store_name 
    FROM store_agents sa 
    JOIN users u ON sa.agent_id = u.id 
    JOIN stores s ON sa.store_id = s.id 
    WHERE s.admin_id = ? 
    ORDER BY s.name, u.full_name
");
$stmt->execute([$admin_id]);
$assignments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Agents - OrderDesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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
                        <a class="nav-link active" href="manage_agents.php">Agents</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="store_analytics.php">Analytics</a>
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

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users me-2"></i>Manage Agents</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAgentModal">
                        <i class="fas fa-plus me-2"></i>Add New Agent
                    </button>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Agents List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Your Agents</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($agents)): ?>
                            <p class="text-muted">No agents found. Create your first agent to get started.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($agents as $agent): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($agent['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($agent['username']); ?></td>
                                                <td><?php echo htmlspecialchars($agent['email']); ?></td>
                                                <td><?php echo htmlspecialchars($agent['phone'] ?? '-'); ?></td>
                                                <td><?php echo formatDateTime($agent['created_at']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="showAssignStoreModal(<?php echo $agent['id']; ?>, '<?php echo htmlspecialchars($agent['full_name']); ?>')">
                                                        <i class="fas fa-store me-1"></i>Assign to Store
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Store Assignments -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-link me-2"></i>Store Assignments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignments)): ?>
                            <p class="text-muted">No agent-store assignments found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Agent</th>
                                            <th>Store</th>
                                            <th>Assigned On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($assignment['agent_name']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['store_name']); ?></td>
                                                <td><?php echo formatDateTime($assignment['created_at']); ?></td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="remove_from_store">
                                                        <input type="hidden" name="agent_id" value="<?php echo $assignment['agent_id']; ?>">
                                                        <input type="hidden" name="store_id" value="<?php echo $assignment['store_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Remove this assignment?')">
                                                            <i class="fas fa-unlink me-1"></i>Remove
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Agent Modal -->
    <div class="modal fade" id="createAgentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Agent</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_agent">
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Agent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign to Store Modal -->
    <div class="modal fade" id="assignStoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Agent to Store</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="assign_to_store">
                        <input type="hidden" name="agent_id" id="assign_agent_id">
                        
                        <p>Assign <strong id="assign_agent_name"></strong> to store:</p>
                        
                        <div class="mb-3">
                            <label for="store_id" class="form-label">Select Store</label>
                            <select class="form-select" name="store_id" required>
                                <option value="">Choose a store...</option>
                                <?php foreach ($stores as $store): ?>
                                    <option value="<?php echo $store['id']; ?>"><?php echo htmlspecialchars($store['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Agent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        function showAssignStoreModal(agentId, agentName) {
            document.getElementById('assign_agent_id').value = agentId;
            document.getElementById('assign_agent_name').textContent = agentName;
            new bootstrap.Modal(document.getElementById('assignStoreModal')).show();
        }
    </script>
</body>
</html>