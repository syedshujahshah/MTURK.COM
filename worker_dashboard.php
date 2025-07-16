<?php
require_once 'db.php';
requireLogin();

$user = getCurrentUser();
if ($user['user_type'] !== 'worker') {
    header('Location: requester_dashboard.php');
    exit;
}

// Get worker statistics
$stats = $db->fetch("
    SELECT 
        COUNT(CASE WHEN ta.status = 'completed' THEN 1 END) as completed_tasks,
        COUNT(CASE WHEN ta.status IN ('accepted', 'in_progress') THEN 1 END) as active_tasks,
        COUNT(CASE WHEN ta.status = 'applied' THEN 1 END) as pending_applications,
        COALESCE(SUM(CASE WHEN ta.status = 'completed' THEN t.payment_amount END), 0) as total_earned
    FROM task_assignments ta
    JOIN tasks t ON ta.task_id = t.id
    WHERE ta.worker_id = ?
", [$user['id']]);

// Get recent tasks
$recentTasks = $db->fetchAll("
    SELECT ta.*, t.title, t.payment_amount, t.deadline, u.username as requester_name
    FROM task_assignments ta
    JOIN tasks t ON ta.task_id = t.id
    JOIN users u ON t.requester_id = u.id
    WHERE ta.worker_id = ?
    ORDER BY ta.applied_at DESC
    LIMIT 10
", [$user['id']]);

// Get available tasks (not applied to)
$availableTasks = $db->fetchAll("
    SELECT t.*, u.username as requester_name, c.name as category_name
    FROM tasks t
    JOIN users u ON t.requester_id = u.id
    JOIN categories c ON t.category_id = c.id
    WHERE t.status = 'active' 
    AND t.deadline > NOW()
    AND t.id NOT IN (
        SELECT task_id FROM task_assignments WHERE worker_id = ?
    )
    ORDER BY t.payment_amount DESC
    LIMIT 6
", [$user['id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard - MTurk Clone</title>
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

        .nav-links a:hover {
            opacity: 0.8;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
        }

        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .welcome-title {
            font-size: 2rem;
            color: #333;
        }

        .balance {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1rem 2rem;
            border-radius: 10px;
            text-align: center;
        }

        .balance-amount {
            font-size: 2rem;
            font-weight: bold;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            color: #333;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .task-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .task-item {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem;
            transition: border-color 0.3s ease;
        }

        .task-item:hover {
            border-color: #667eea;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .task-title {
            font-weight: 600;
            color: #333;
        }

        .task-payment {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .task-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-applied { background: #fff3cd; color: #856404; }
        .status-accepted { background: #d1ecf1; color: #0c5460; }
        .status-in_progress { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .task-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        .task-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        @media (max-width: 768px) {
            .welcome-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .tasks-grid {
                grid-template-columns: 1fr;
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
                <a href="index.php">Home</a>
                <a href="marketplace.php">Browse Tasks</a>
                <a href="worker_dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-header">
                <div>
                    <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</h1>
                    <p>Ready to earn some money today?</p>
                </div>
                <div class="balance">
                    <div>Current Balance</div>
                    <div class="balance-amount"><?= formatCurrency($user['balance']) ?></div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?= $stats['completed_tasks'] ?></div>
                <div class="stat-label">Completed Tasks</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #007bff;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?= $stats['active_tasks'] ?></div>
                <div class="stat-label">Active Tasks</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #ffc107;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-number"><?= $stats['pending_applications'] ?></div>
                <div class="stat-label">Pending Applications</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number"><?= formatCurrency($stats['total_earned']) ?></div>
                <div class="stat-label">Total Earned</div>
            </div>
        </div>

        <!-- Recent Tasks -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">My Tasks</h2>
                <a href="marketplace.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Find More Tasks
                </a>
            </div>
            
            <?php if (!empty($recentTasks)): ?>
                <div class="task-list">
                    <?php foreach ($recentTasks as $task): ?>
                        <div class="task-item">
                            <div class="task-header">
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-payment"><?= formatCurrency($task['payment_amount']) ?></div>
                            </div>
                            <div class="task-meta">
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($task['requester_name']) ?></span>
                                <span><i class="fas fa-calendar"></i> Due <?= date('M j, Y', strtotime($task['deadline'])) ?></span>
                                <span class="status-badge status-<?= $task['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                                </span>
                            </div>
                            <?php if ($task['status'] === 'accepted'): ?>
                                <div style="margin-top: 1rem;">
                                    <a href="complete_task.php?assignment_id=<?= $task['id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Start Task
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No tasks yet</h3>
                    <p>Start by browsing available tasks in the marketplace.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Tasks -->
        <?php if (!empty($availableTasks)): ?>
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recommended Tasks</h2>
                <a href="marketplace.php" class="btn btn-primary">View All</a>
            </div>
            
            <div class="tasks-grid">
                <?php foreach ($availableTasks as $task): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                            <div class="task-payment"><?= formatCurrency($task['payment_amount']) ?></div>
                        </div>
                        <div class="task-meta">
                            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($task['category_name']) ?></span>
                            <span><i class="fas fa-user"></i> <?= htmlspecialchars($task['requester_name']) ?></span>
                        </div>
                        <div style="margin: 1rem 0; color: #666;">
                            <?= htmlspecialchars(substr($task['description'], 0, 100)) ?>...
                        </div>
                        <button class="btn btn-primary" onclick="applyForTask(<?= $task['id'] ?>)">
                            <i class="fas fa-hand-paper"></i> Apply Now
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function applyForTask(taskId) {
            if (confirm('Are you sure you want to apply for this task?')) {
                window.location.href = 'apply_task.php?task_id=' + taskId;
            }
        }

        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
