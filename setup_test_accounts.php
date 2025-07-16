<?php
require_once 'db.php';

echo "<h2>Setting up test accounts...</h2>";

// Delete existing test accounts first
$db->execute("DELETE FROM users WHERE username IN ('admin', 'john_requester', 'jane_worker', 'test_worker', 'test_requester')");

// Create test accounts with known passwords
$test_accounts = [
    [
        'username' => 'test_worker',
        'email' => 'worker@test.com',
        'password' => 'password123',
        'user_type' => 'worker',
        'full_name' => 'Test Worker',
        'balance' => 50.00
    ],
    [
        'username' => 'test_requester', 
        'email' => 'requester@test.com',
        'password' => 'password123',
        'user_type' => 'requester',
        'full_name' => 'Test Requester',
        'balance' => 1000.00
    ],
    [
        'username' => 'admin',
        'email' => 'admin@test.com', 
        'password' => 'admin123',
        'user_type' => 'requester',
        'full_name' => 'System Admin',
        'balance' => 5000.00
    ]
];

foreach ($test_accounts as $account) {
    $hashed_password = password_hash($account['password'], PASSWORD_DEFAULT);
    
    $result = $db->insert("
        INSERT INTO users (username, email, password, user_type, full_name, balance, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ", [
        $account['username'],
        $account['email'], 
        $hashed_password,
        $account['user_type'],
        $account['full_name'],
        $account['balance']
    ]);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Created account: {$account['username']} / {$account['password']}</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create: {$account['username']}</p>";
    }
}

echo "<h3>Test these accounts:</h3>";
echo "<ul>";
echo "<li><strong>Worker:</strong> test_worker / password123</li>";
echo "<li><strong>Requester:</strong> test_requester / password123</li>";
echo "<li><strong>Admin:</strong> admin / admin123</li>";
echo "</ul>";

echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
