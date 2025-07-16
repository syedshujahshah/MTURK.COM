<?php
require_once 'db.php';
requireLogin();

$user = getCurrentUser();
if ($user['user_type'] !== 'requester') {
    header('Location: worker_dashboard.php');
    exit;
}

// Get requester statistics
$stats = $db->fetch("
    SELECT 
        COUNT(CASE WHEN t.status = 'active' THEN 1 END) as active_tasks,
        COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
        COUNT(CASE WHEN t.status = 'paused' THEN 1 END) as paused_tasks,
        COUNT(*) as total_tasks,
        COALESCE(SUM(CASE WHEN t.status = 'completed' THEN t.payment_amount END), 0) as total_spent,
        COALESCE(SUM(CASE WHEN t.status = 'active' THEN t.payment_amount * t.max_workers END), 0) as pending_budget
    FROM tasks t
    WHERE t.requester_id = ?
", [$user['id']]);

// Get recent tasks
$recentTasks = $db->fetchAll("
    SELECT t.*, c.name as category_name,
           COUNT(ta.id) as total_applications,
           COUNT(CASE WHEN ta.status = 'accepted' THEN 1 END) as accepted_workers,
           COUNT(CASE WHEN ta.status = 'completed' THEN 1 END) as completed_workers
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    WHERE t.requester_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
    LIMIT 10
", [$user['id']]);

// Get pending applications that need review
$pendingApplications = $db->fetchAll("
    SELECT ta.*, t.title as task_title, u.username, u.full_name, u.rating, u.total_ratings
    FROM task_assignments ta
    JOIN tasks t ON ta.task_id = t.id
    JOIN users u ON ta.worker_id = u.id
    WHERE t.requester_id = ? AND ta.status = 'applied'
    ORDER BY ta.applied_at DESC
    LIMIT 5
", [$user['id']]);

// Get recent notifications
$notifications = $db->fetchAll("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$user['id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requester Dashboard - MTurk Clone</title>
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

        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
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
            font-size: 1.1rem;
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

        .task-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-paused { background: #fff3cd; color: #856404; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .application-item {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: border-color 0.3s ease;
        }

        .application-item:hover {
            border-color: #667eea;
        }

        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .worker-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .worker-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .worker-details h4 {
            color: #333;
            margin-bottom: 0.2rem;
        }

        .worker-rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #ffc107;
        }

        .application-actions {
            display: flex;
            gap: 0.5rem;
        }

        .notification-item {
            padding: 1rem;
            border-left: 4px solid #667eea;
            background: #f8f9ff;
            border-radius: 0 8px 8px 0;
            margin-bottom: 0.5rem;
        }

        .notification-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .notification-message {
            color: #666;
            font-size: 0.9rem;
        }

        .notification-time {
            color: #999;
            font-size: 0.8rem;
            margin-top: 0.3rem;
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

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 768px) {
            .welcome-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .task-header {
                flex-direction: column;
                gap: 0.5rem;
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
                <a href="requester_dashboard.php">Dashboard</a>
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
                    <p>Manage your tasks and find the best workers for your projects.</p>
                </div>
                <div class="balance">
                    <div>Account Balance</div>
                    <div class="balance-amount"><?= formatCurrency($user['balance']) ?></div>
                </div>
            </div>
            
            <div class="quick-actions">
                <a href="post_task.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Post New Task
                </a>
                <a href="manage_tasks.php" class="btn btn-secondary">
                    <i class="fas fa-cog"></i> Manage Tasks
                </a>
                <a href="find_workers.php" class="btn btn-success">
                    <i class="fas fa-users"></i> Find Workers
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-number"><?= $stats['active_tasks'] ?></div>
                <div class="stat-label">Active Tasks</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #007bff;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?= $stats['completed_tasks'] ?></div>
                <div class="stat-label">Completed Tasks</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #ffc107;">
                    <i class="fas fa-pause-circle"></i>
                </div>
                <div class="stat-number"><?= $stats['paused_tasks'] ?></div>
                <div class="stat-label">Paused Tasks</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #dc3545;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number"><?= formatCurrency($stats['total_spent']) ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Main Content -->
            <div>
                <!-- Pending Applications -->
                <?php if (!empty($pendingApplications)): ?>
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-clock"></i> Pending Applications
                        </h2>
                        <a href="manage_applications.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    
                    <?php foreach ($pendingApplications as $app): ?>
                        <div class="application-item">
                            <div class="application-header">
                                <div class="worker-info">
                                    <div class="worker-avatar">
                                        <?= strtoupper(substr($app['full_name'], 0, 1)) ?>
                                    </div>
                                    <div class="worker-details">
                                        <h4><?= htmlspecialchars($app['full_name']) ?></h4>
                                        <div class="worker-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $app['rating'] ? '' : 'text-muted' ?>"></i>
                                            <?php endfor; ?>
                                            <span>(<?= $app['total_ratings'] ?> reviews)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="application-actions">
                                    <button class="btn btn-success btn-sm" onclick="acceptApplication(<?= $app['id'] ?>)">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectApplication(<?= $app['id'] ?>)">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <strong>Task:</strong> <?= htmlspecialchars($app['task_title']) ?>
                            </div>
                            <div style="margin-top: 0.3rem; color: #666; font-size: 0.9rem;">
                                Applied <?= timeAgo($app['applied_at']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Recent Tasks -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">My Tasks</h2>
                        <a href="post_task.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Post New Task
                        </a>
                    </div>
                    
                    <?php if (!empty($recentTasks)): ?>
                        <div class="task-list">
                            <?php foreach ($recentTasks as $task): ?>
                                <div class="task-item">
                                    <div class="task-header">
                                        <div>
                                            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                            <div class="task-meta">
                                                <span><i class="fas fa-tag"></i> <?= htmlspecialchars($task['category_name']) ?></span>
                                                <span><i class="fas fa-calendar"></i> Due <?= date('M j, Y', strtotime($task['deadline'])) ?></span>
                                                <span><i class="fas fa-users"></i> <?= $task['accepted_workers'] ?>/<?= $task['max_workers'] ?> workers</span>
                                                <span class="status-badge status-<?= $task['status'] ?>">
                                                    <?= ucfirst($task['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="task-payment"><?= formatCurrency($task['payment_amount']) ?></div>
                                    </div>
                                    
                                    <?php if ($task['total_applications'] > 0): ?>
                                        <div style="margin: 0.5rem 0; color: #666; font-size: 0.9rem;">
                                            <i class="fas fa-hand-paper"></i> <?= $task['total_applications'] ?> applications received
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="task-actions">
                                        <a href="view_task.php?id=<?= $task['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($task['status'] === 'active'): ?>
                                            <button class="btn btn-warning btn-sm" onclick="pauseTask(<?= $task['id'] ?>)">
                                                <i class="fas fa-pause"></i> Pause
                                            </button>
                                        <?php elseif ($task['status'] === 'paused'): ?>
                                            <button class="btn btn-success btn-sm" onclick="resumeTask(<?= $task['id'] ?>)">
                                                <i class="fas fa-play"></i> Resume
                                            </button>
                                        <?php endif; ?>
                                        <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>No tasks yet</h3>
                            <p>Start by posting your first task to find workers.</p>
                            <a href="post_task.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Post Your First Task
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Notifications -->
                <div class="section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-bell"></i> Notifications
                        </h3>
                        <a href="notifications.php" class="btn btn-secondary btn-sm">View All</a>
                    </div>
                    
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item">
                                <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
                                <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                                <div class="notification-time"><?= timeAgo($notification['created_at']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <p>No notifications</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Stats -->
                <div class="section">
                    <h3 class="section-title">
                        <i class="fas fa-chart-bar"></i> Quick Stats
                    </h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Total Tasks Posted:</span>
                            <strong><?= $stats['total_tasks'] ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Success Rate:</span>
                            <strong><?= $stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0 ?>%</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Pending Budget:</span>
                            <strong><?= formatCurrency($stats['pending_budget']) ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Avg. Task Value:</span>
                            <strong><?= $stats['total_tasks'] > 0 ? formatCurrency($stats['total_spent'] / $stats['total_tasks']) : '$0.00' ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function acceptApplication(applicationId) {
            if (confirm('Are you sure you want to accept this application?')) {
                // Send AJAX request to accept application
                fetch('manage_application.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'accept',
                        application_id: applicationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        }

        function rejectApplication(applicationId) {
            if (confirm('Are you sure you want to reject this application?')) {
                // Send AJAX request to reject application
                fetch('manage_application.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'reject',
                        application_id: applicationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        }

        function pauseTask(taskId) {
            if (confirm('Are you sure you want to pause this task?')) {
                window.location.href = 'manage_task.php?action=pause&id=' + taskId;
            }
        }

        function resumeTask(taskId) {
            if (confirm('Are you sure you want to resume this task?')) {
                window.location.href = 'manage_task.php?action=resume&id=' + taskId;
            }
        }

        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);

        // Mark notifications as read when viewed
        function markNotificationRead(notificationId) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            });
        }
    </script>
</body>
</html>
