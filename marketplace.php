<?php
require_once 'db.php';

// Get filter parameters
$category = $_GET['category'] ?? '';
$min_payment = $_GET['min_payment'] ?? '';
$max_payment = $_GET['max_payment'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["t.status = 'active'", "t.deadline > NOW()"];
$params = [];

if ($category) {
    $where_conditions[] = "t.category_id = ?";
    $params[] = $category;
}

if ($min_payment) {
    $where_conditions[] = "t.payment_amount >= ?";
    $params[] = $min_payment;
}

if ($max_payment) {
    $where_conditions[] = "t.payment_amount <= ?";
    $params[] = $max_payment;
}

if ($difficulty) {
    $where_conditions[] = "t.difficulty_level = ?";
    $params[] = $difficulty;
}

if ($search) {
    $where_conditions[] = "(t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get tasks
$tasks = $db->fetchAll("
    SELECT t.*, u.username as requester_name, c.name as category_name,
           (SELECT COUNT(*) FROM task_assignments ta WHERE ta.task_id = t.id AND ta.status IN ('applied', 'accepted', 'in_progress')) as applicants
    FROM tasks t 
    JOIN users u ON t.requester_id = u.id 
    JOIN categories c ON t.category_id = c.id 
    WHERE $where_clause
    ORDER BY t.created_at DESC
", $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Marketplace - MTurk Clone</title>
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

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-control {
            padding: 0.7rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
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

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .task-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            border-left: 4px solid #667eea;
        }

        .task-card:hover {
            transform: translateY(-3px);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .task-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .task-payment {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .task-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .task-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
        }

        .difficulty-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .difficulty-easy {
            background: #d4edda;
            color: #155724;
        }

        .difficulty-medium {
            background: #fff3cd;
            color: #856404;
        }

        .difficulty-hard {
            background: #f8d7da;
            color: #721c24;
        }

        .no-tasks {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-tasks i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        @media (max-width: 768px) {
            .tasks-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .task-header {
                flex-direction: column;
                align-items: start;
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
                <a href="index.php">Home</a>
                <a href="marketplace.php">Browse Tasks</a>
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    <a href="<?= $user['user_type'] ?>_dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>Task Marketplace</h1>
            <p>Find tasks that match your skills and start earning</p>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Search Tasks</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by title or description..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Min Payment</label>
                        <input type="number" name="min_payment" class="form-control" 
                               placeholder="$0" step="0.01" value="<?= htmlspecialchars($min_payment) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Max Payment</label>
                        <input type="number" name="max_payment" class="form-control" 
                               placeholder="$1000" step="0.01" value="<?= htmlspecialchars($max_payment) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Difficulty</label>
                        <select name="difficulty" class="form-control">
                            <option value="">All Levels</option>
                            <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>Easy</option>
                            <option value="medium" <?= $difficulty === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="hard" <?= $difficulty === 'hard' ? 'selected' : '' ?>>Hard</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter Tasks
                    </button>
                    <a href="marketplace.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Tasks Grid -->
        <?php if (!empty($tasks)): ?>
            <div class="tasks-grid">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <div>
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-meta">
                                    <span><i class="fas fa-tag"></i> <?= htmlspecialchars($task['category_name']) ?></span>
                                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($task['requester_name']) ?></span>
                                </div>
                            </div>
                            <div class="task-payment"><?= formatCurrency($task['payment_amount']) ?></div>
                        </div>
                        
                        <div class="task-description">
                            <?= htmlspecialchars(substr($task['description'], 0, 200)) ?>
                            <?= strlen($task['description']) > 200 ? '...' : '' ?>
                        </div>
                        
                        <div class="task-meta">
                            <span><i class="fas fa-clock"></i> <?= $task['estimated_time'] ?> min</span>
                            <span><i class="fas fa-calendar"></i> Due <?= date('M j, Y', strtotime($task['deadline'])) ?></span>
                            <span><i class="fas fa-users"></i> <?= $task['applicants'] ?>/<?= $task['max_workers'] ?> applied</span>
                        </div>
                        
                        <div class="task-footer">
                            <span class="difficulty-badge difficulty-<?= $task['difficulty_level'] ?>">
                                <?= ucfirst($task['difficulty_level']) ?>
                            </span>
                            
                            <?php if (isLoggedIn() && getCurrentUser()['user_type'] === 'worker'): ?>
                                <button class="btn btn-primary" onclick="applyForTask(<?= $task['id'] ?>)">
                                    <i class="fas fa-hand-paper"></i> Apply Now
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Login to Apply
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-tasks">
                <i class="fas fa-search"></i>
                <h3>No tasks found</h3>
                <p>Try adjusting your filters or check back later for new tasks.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function applyForTask(taskId) {
            if (confirm('Are you sure you want to apply for this task?')) {
                // Redirect to application page
                window.location.href = 'apply_task.php?task_id=' + taskId;
            }
        }

        // Auto-submit form on filter change
        document.querySelectorAll('select[name="category"], select[name="difficulty"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>
