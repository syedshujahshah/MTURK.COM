<?php
require_once 'db.php';

echo "<h2>Registration Test Page</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h3>Form Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $user_type = $_POST['user_type'] ?? 'worker';
    
    echo "<h3>Processing Registration:</h3>";
    
    // Test database connection
    try {
        $test_query = $db->fetch("SELECT COUNT(*) as count FROM users");
        echo "<p style='color: green;'>✅ Database connection working. Current users: " . $test_query['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        exit;
    }
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        echo "<p style='color: red;'>❌ Missing required fields</p>";
    } else {
        echo "<p style='color: green;'>✅ All required fields provided</p>";
        
        // Check for existing users
        $existing_username = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
        $existing_email = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        
        if ($existing_username) {
            echo "<p style='color: red;'>❌ Username already exists</p>";
        } elseif ($existing_email) {
            echo "<p style='color: red;'>❌ Email already exists</p>";
        } else {
            echo "<p style='color: green;'>✅ Username and email are available</p>";
            
            // Try to create user
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                echo "<p style='color: green;'>✅ Password hashed successfully</p>";
                
                $user_id = $db->insert("
                    INSERT INTO users (username, email, password, user_type, full_name, status, balance) 
                    VALUES (?, ?, ?, ?, ?, 'active', 0.00)
                ", [$username, $email, $hashed_password, $user_type, $full_name]);
                
                if ($user_id) {
                    echo "<p style='color: green;'>✅ User created successfully! User ID: $user_id</p>";
                    echo "<p><a href='login.php'>Go to Login Page</a></p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to insert user into database</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Registration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        input, select { padding: 8px; margin: 5px; width: 200px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <form method="POST">
        <div class="form-group">
            <label>Full Name:</label><br>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>Username:</label><br>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Email:</label><br>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password:</label><br>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>User Type:</label><br>
            <select name="user_type">
                <option value="worker">Worker</option>
                <option value="requester">Requester</option>
            </select>
        </div>
        <button type="submit">Test Registration</button>
    </form>
    
    <hr>
    <p><a href="register.php">Go to Main Registration</a></p>
    <p><a href="setup_test_accounts.php">Setup Test Accounts</a></p>
</body>
</html>
