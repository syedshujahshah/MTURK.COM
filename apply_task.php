<?php
require_once 'db.php';
requireLogin();

$user = getCurrentUser();
if ($user['user_type'] !== 'worker') {
    header('Location: requester_dashboard.php');
    exit;
}

$task_id = $_GET['task_id'] ?? 0;
$error = '';
$success = '';

// Get task details
$task = $db->fetch("
    SELECT t.*, u.username as requester_name, c.name as category_name
    FROM tasks t
    JOIN users u ON t.requester_id = u.id
    JOIN categories c ON t.category_id = c.id
    WHERE t.id = ? AND t.status = 'active' AND t.deadline > NOW()
", [$task_id]);

if (!$task) {
    header('Location: marketplace.php');
    exit;
}

// Check if already applied
$existing_application = $db->fetch("
    SELECT id FROM task_assignments 
    WHERE task_id = ? AND worker_id = ?
", [$task_id, $user['id']]);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$existing_application) {
    // Apply for task
    $application_id = $db->insert("
        INSERT INTO task_assignments (task_id, worker_id, status, applied_at)
        VALUES (?, ?, 'applied', NOW())
    ", [$task_id, $user['id']]);
    
    if ($application_id) {
        $success = 'Application submitted successfully! The requester will review your application.';
        
        // Create notification for requester
        $db->insert("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, 'info')
        ", [
            $task['requester_id'],
            'New Task Application',
            "Worker {$user['username']} has applied for your task: {$task['title']}"
        ]);
    } else {
        $error = 'Failed to submit application. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Task - MTurk Clone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .task-details {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1.5rem;
        }

        .task-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .task-payment {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            color: #666;
        }

        .task-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .task-description {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .task-instructions {
            background: #e8f4fd;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #007bff;
        }

        .application-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
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

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .difficulty-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .difficulty-easy { background: #d4edda; color: #155724; }
        .difficulty-medium { background: #fff3cd; color: #856404; }
        .difficulty-hard { background: #f8d7da; color: #721c24; }

        @media (max-width: 768px) {
            .task-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .task-meta {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="index.php" class="logo">
                <i class="fas fa-tasks"></i> MTurk Clone
            </a>
            <div class="nav-links">
                <a href="worker_dashboard.php">Dashboard</a>
                <a href="marketplace.php">Browse Tasks</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <!-- Task Details -->
        <div class="task-details">
            <div class="task-header">
                <div>
                    <h1 class="task-title"><?= htmlspecialchars($task['title']) ?></h1>
                    <div class="task-meta">
                        <span><i class="fas fa-tag"></i> <?= htmlspecialchars($task['category_name']) ?></span>
                        <span><i class="fas fa-user"></i> <?= htmlspecialchars($task['requester_name']) ?></span>
                        <span><i class="fas fa-clock"></i> <?= $task['estimated_time'] ?> minutes</span>
                        <span><i class="fas fa-calendar"></i> Due <?= date('M j, Y g:i A', strtotime($task['deadline'])) ?></span>
                    </div>
                </div>
                <div class="task-payment"><?= formatCurrency($task['payment_amount']) ?></div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <span class="difficulty-badge difficulty-<?= $task['difficulty_level'] ?>">
                    <?= ucfirst($task['difficulty_level']) ?> Level
                </span>
            </div>

            <div class="task-description">
                <h3 style="margin-bottom: 1rem;">Description</h3>
                <?= nl2br(htmlspecialchars($task['description'])) ?>
            </div>

            <?php if ($task['instructions']): ?>
                <div class="task-instructions">
                    <h3 style="margin-bottom: 1rem;"><i class="fas fa-list"></i> Instructions</h3>
                    <?= nl2br(htmlspecialchars($task['instructions'])) ?>
                </div>
            <?php endif; ?>

            <?php if ($task['required_skills']): ?>
                <div style="margin-top: 1.5rem;">
                    <h3 style="margin-bottom: 0.5rem;">Required Skills</h3>
                    <p style="color: #666;"><?= htmlspecialchars($task['required_skills']) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Application Section -->
        <div class="application-section">
            <?php if ($existing_application): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> You have already applied for this task. 
                    Check your dashboard for application status.
                </div>
                <a href="worker_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            <?php elseif ($success): ?>
                <div style="text-align: center;">
                    <h3>Application Submitted!</h3>
                    <p>The requester will review your application and get back to you soon.</p>
                    <div style="margin-top: 1.5rem;">
                        <a href="worker_dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                        <a href="marketplace.php" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Browse More Tasks
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <h3 style="margin-bottom: 1rem;">Apply for This Task</h3>
                <p style="margin-bottom: 1.5rem; color: #666;">
                    By applying for this task, you confirm that you understand the requirements 
                    and are able to complete it within the specified timeframe.
                </p>
                
                <form method="POST">
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-hand-paper"></i> Apply for Task
                        </button>
                        <a href="marketplace.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Marketplace
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-redirect after successful application
        <?php if ($success): ?>
        setTimeout(function() {
            window.location.href = 'worker_dashboard.php';
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
