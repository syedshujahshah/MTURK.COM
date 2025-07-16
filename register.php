<?php
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitizeInput($_POST['full_name']);
    $user_type = sanitizeInput($_POST['user_type']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if username already exists
        $existing_username = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
        
        // Check if email already exists
        $existing_email = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        
        if ($existing_username) {
            $error = 'Username already exists. Please choose a different username.';
        } elseif ($existing_email) {
            $error = 'Email already exists. Please use a different email address.';
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $user_id = $db->insert("
                INSERT INTO users (username, email, password, user_type, full_name, status, balance) 
                VALUES (?, ?, ?, ?, ?, 'active', 0.00)
            ", [$username, $email, $hashed_password, $user_type, $full_name]);
            
            if ($user_id) {
                $success = 'Account created successfully! You can now login.';
                
                // Auto-login the user
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = $user_type;
                
                // Redirect after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '" . ($user_type === 'worker' ? 'worker_dashboard.php' : 'requester_dashboard.php') . "';
                    }, 2000);
                </script>";
            } else {
                $error = 'Failed to create account. Please try again.';
            }
        }
    }
}

$user_type = $_GET['type'] ?? 'worker';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - MTurk Clone</title>
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

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }

        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .register-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            opacity: 0.9;
        }

        .register-form {
            padding: 2rem;
        }

        .user-type-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .type-option {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .type-option.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
        }

        .type-option i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 0.5rem;
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

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
        }

        .login-link a {
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

        @media (max-width: 480px) {
            .register-container {
                margin: 10px;
            }
            
            .user-type-selector {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>

    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Join our micro-tasking platform today</p>
        </div>

        <form class="register-form" method="POST" id="registerForm">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                    <br><small>Redirecting to your dashboard...</small>
                </div>
            <?php endif; ?>

            <div class="user-type-selector">
                <div class="type-option <?= $user_type === 'worker' ? 'active' : '' ?>" onclick="selectUserType('worker')">
                    <i class="fas fa-user"></i>
                    <div><strong>Worker</strong></div>
                    <div>Complete tasks & earn money</div>
                </div>
                <div class="type-option <?= $user_type === 'requester' ? 'active' : '' ?>" onclick="selectUserType('requester')">
                    <i class="fas fa-briefcase"></i>
                    <div><strong>Requester</strong></div>
                    <div>Post tasks & hire workers</div>
                </div>
            </div>

            <input type="hidden" name="user_type" id="user_type" value="<?= $user_type ?>">

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required 
                       value="<?= $_POST['full_name'] ?? '' ?>" placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required 
                       value="<?= $_POST['username'] ?? '' ?>" placeholder="Choose a unique username">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required 
                       value="<?= $_POST['email'] ?? '' ?>" placeholder="Enter your email address">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required
                       placeholder="Minimum 6 characters">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                       placeholder="Re-enter your password">
            </div>

            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </form>
    </div>

    <script>
        function selectUserType(type) {
            // Update visual selection
            document.querySelectorAll('.type-option').forEach(option => {
                option.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Update hidden input
            document.getElementById('user_type').value = type;
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submitBtn');
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }

            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
        });
    </script>
</body>
</html>
