<?php
/**
 * Demo Login Test Script
 * Tests all demo accounts to ensure they work properly
 */

require_once 'config.php';

// Test credentials
$testCredentials = [
    'superadmin' => ['username' => 'superadmin', 'password' => 'password', 'expected_role' => 'super_admin'],
    'demoadmin' => ['username' => 'demoadmin', 'password' => 'password', 'expected_role' => 'admin'],
    'demoagent' => ['username' => 'demoagent', 'password' => 'password', 'expected_role' => 'agent']
];

$results = [];

echo "<h2>Demo Login Test Results</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background: #f0f0f0;'><th>Account</th><th>Username</th><th>Expected Role</th><th>Status</th><th>Details</th></tr>\n";

foreach ($testCredentials as $account => $creds) {
    $username = $creds['username'];
    $password = $creds['password'];
    $expectedRole = $creds['expected_role'];
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, username, password, role, status FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $results[$account] = [
                'status' => 'FAIL',
                'message' => 'User not found in database'
            ];
            continue;
        }
        
        // Check password
        if (!password_verify($password, $user['password'])) {
            $results[$account] = [
                'status' => 'FAIL',
                'message' => 'Password verification failed'
            ];
            continue;
        }
        
        // Check role
        if ($user['role'] !== $expectedRole) {
            $results[$account] = [
                'status' => 'FAIL',
                'message' => "Role mismatch: expected {$expectedRole}, got {$user['role']}"
            ];
            continue;
        }
        
        // Check status
        if ($user['status'] !== 'active') {
            $results[$account] = [
                'status' => 'WARNING',
                'message' => "User status is '{$user['status']}', should be 'active'"
            ];
            continue;
        }
        
        $results[$account] = [
            'status' => 'PASS',
            'message' => 'Login credentials working correctly'
        ];
        
    } catch (Exception $e) {
        $results[$account] = [
            'status' => 'ERROR',
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
    
    // Display result
    $status = $results[$account]['status'];
    $color = $status === 'PASS' ? 'green' : ($status === 'WARNING' ? 'orange' : 'red');
    
    echo "<tr>\n";
    echo "<td><strong>{$account}</strong></td>\n";
    echo "<td>{$username}</td>\n";
    echo "<td>{$expectedRole}</td>\n";
    echo "<td style='color: {$color}; font-weight: bold;'>{$status}</td>\n";
    echo "<td>{$results[$account]['message']}</td>\n";
    echo "</tr>\n";
}

echo "</table>\n";

// Summary
$passed = count(array_filter($results, function($r) { return $r['status'] === 'PASS'; }));
$total = count($results);

echo "<h3>Summary</h3>\n";
echo "<p><strong>{$passed}/{$total}</strong> demo accounts are working correctly.</p>\n";

if ($passed === $total) {
    echo "<p style='color: green; font-weight: bold;'>✓ All demo accounts are working properly!</p>\n";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Some demo accounts need attention.</p>\n";
    
    // Fix suggestions
    echo "<h4>Fix Suggestions:</h4>\n";
    echo "<ul>\n";
    foreach ($results as $account => $result) {
        if ($result['status'] !== 'PASS') {
            echo "<li><strong>{$account}:</strong> {$result['message']}</li>\n";
        }
    }
    echo "</ul>\n";
    
    // Auto-fix option
    echo "<h4>Auto-Fix</h4>\n";
    echo "<p>Run this SQL to fix common issues:</p>\n";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>\n";
    echo "-- Reset demo user passwords\n";
    echo "UPDATE users SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username IN ('superadmin', 'demoadmin', 'demoagent');\n\n";
    echo "-- Ensure users are active\n";
    echo "UPDATE users SET status = 'active' WHERE username IN ('superadmin', 'demoadmin', 'demoagent');\n\n";
    echo "-- Verify roles\n";
    echo "UPDATE users SET role = 'super_admin' WHERE username = 'superadmin';\n";
    echo "UPDATE users SET role = 'admin' WHERE username = 'demoadmin';\n";
    echo "UPDATE users SET role = 'agent' WHERE username = 'demoagent';\n";
    echo "</pre>\n";
}

echo "<hr>\n";
echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 20px 0; }
th, td { padding: 8px 12px; text-align: left; }
pre { overflow-x: auto; }
</style>