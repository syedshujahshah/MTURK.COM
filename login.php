<?php
require_once 'db.php';

$error = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        // Check if user exists by username or email
        $user = $db->fetch("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'", [$username, $username]);
        
        if ($user) {
            // Debug: Check password verification
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Redirect based on user type
                if ($user['user_type'] === 'worker') {
                    header('Location: worker_dashboard.php');
                } else {
                    header('Location: requester_dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid password. Please check your password.';
                // Add debug info in development
                $debug_info = "User found but password doesn't match. Try: password123 or admin123";
            }
        } else {
            $error = 'User not found. Please check your username/email.';
            // Add debug info
            $debug_info = "No user found with username/email: " . htmlspecialchars($username);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MTurk Clone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .login-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .debug-info {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            transition: opacity 0.3s ease;
        }

        .back-home:hover {
            opacity: 0.8;
        }

        .test-accounts {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .test-accounts h4 {
            color: #0c5460;
            margin-bottom: 0.5rem;
        }

        .test-account {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            margin: 0.3rem 0;
            background: rgba(255,255,255,0.7);
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .test-account:hover {
            background: rgba(255,255,255,0.9);
        }

        .test-account span {
            color: #0c5460;
            font-weight: 500;
        }

        .test-account small {
            color: #6c757d;
        }

        .setup-link {
            text-align: center;
            margin-bottom: 1rem;
        }

        .setup-link a {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>

    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your account</p>
        </div>

        <form class="login-form" method="POST" id="loginForm">
            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($debug_info): ?>
                <div class="debug-info">
                    <i class="fas fa-info-circle"></i> <?= $debug_info ?>
                </div>
            <?php endif; ?>

            <!-- Setup Link -->
            <div class="setup-link">
                <a href="setup_test_accounts.php">
                    <i class="fas fa-cog"></i> Setup Test Accounts
                </a>
            </div>

            <!-- Test Accounts -->
            <div class="test-accounts">
                <h4><i class="fas fa-key"></i> Test Accounts (Click to fill):</h4>
                <div class="test-account" onclick="fillLogin('test_worker', 'password123')">
                    <span><i class="fas fa-user"></i> Worker</span>
                    <small>test_worker / password123</small>
                </div>
                <div class="test-account" onclick="fillLogin('test_requester', 'password123')">
                    <span><i class="fas fa-briefcase"></i> Requester</span>
                    <small>test_requester / password123</small>
                </div>
                <div class="test-account" onclick="fillLogin('admin', 'admin123')">
                    <span><i class="fas fa-crown"></i> Admin</span>
                    <small>admin / admin123</small>
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" class="form-control" required 
                       value="<?= $_POST['username'] ?? '' ?>" placeholder="Enter username or email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required
                       placeholder="Enter your password">
            </div>

            <button type="submit" class="btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>

            <div class="register-link">
                Don't have an account? <a href="register.php">Sign up here</a> | 
                <a href="debug_login.php">Debug Login</a>
            </div>
        </form>
    </div>

    <script>
        function fillLogin(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }

        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            
            // Disable button to prevent double submission
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            
            // Re-enable button after 3 seconds in case of error
            setTimeout(function() {
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            }, 3000);
        });
    </script>
</body>
</html>
