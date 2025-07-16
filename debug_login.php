<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<h3>Debug Information:</h3>";
    echo "<p><strong>Entered Username:</strong> " . htmlspecialchars($username) . "</p>";
    echo "<p><strong>Entered Password:</strong> " . htmlspecialchars($password) . "</p>";
    
    // Check if user exists
    $user = $db->fetch("SELECT * FROM users WHERE username = ? OR email = ?", [$username, $username]);
    
    if ($user) {
        echo "<p style='color: green;'>✅ User found in database</p>";
        echo "<p><strong>Database Username:</strong> " . htmlspecialchars($user['username']) . "</p>";
        echo "<p><strong>Database Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
        echo "<p><strong>User Type:</strong> " . htmlspecialchars($user['user_type']) . "</p>";
        echo "<p><strong>Status:</strong> " . htmlspecialchars($user['status']) . "</p>";
        
        // Test password verification
        if (password_verify($password, $user['password'])) {
            echo "<p style='color: green;'>✅ Password verification successful</p>";
            echo "<p style='color: green;'><strong>LOGIN SHOULD WORK!</strong></p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification failed</p>";
            echo "<p><strong>Stored hash:</strong> " . substr($user['password'], 0, 50) . "...</p>";
            
            // Test with common passwords
            $test_passwords = ['password123', 'admin123', '123456'];
            foreach ($test_passwords as $test_pass) {
                if (password_verify($test_pass, $user['password'])) {
                    echo "<p style='color: orange;'>🔍 Actual password might be: <strong>$test_pass</strong></p>";
                }
            }
        }
    } else {
        echo "<p style='color: red;'>❌ User not found in database</p>";
        
        // Show all users in database
        $all_users = $db->fetchAll("SELECT username, email, user_type FROM users LIMIT 10");
        echo "<h4>Available users in database:</h4>";
        if ($all_users) {
            echo "<ul>";
            foreach ($all_users as $u) {
                echo "<li>{$u['username']} ({$u['email']}) - {$u['user_type']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No users found in database!</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        input { padding: 8px; margin: 5px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Debug Login System</h2>
    
    <form method="POST">
        <div class="form-group">
            <label>Username or Email:</label><br>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Password:</label><br>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Test Login</button>
    </form>
    
    <hr>
    <p><a href="setup_test_accounts.php">Setup Test Accounts</a></p>
    <p><a href="login.php">Go to Main Login</a></p>
</body>
</html>
