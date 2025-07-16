<?php
require_once 'db.php';

// Get featured tasks and statistics
$featuredTasks = $db->fetchAll("
    SELECT t.*, u.username as requester_name, c.name as category_name 
    FROM tasks t 
    JOIN users u ON t.requester_id = u.id 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.status = 'active' AND t.deadline > NOW() 
    ORDER BY t.payment_amount DESC 
    LIMIT 6
");

$stats = $db->fetch("
    SELECT 
        (SELECT COUNT(*) FROM tasks WHERE status = 'active') as active_tasks,
        (SELECT COUNT(*) FROM users WHERE user_type = 'worker') as total_workers,
        (SELECT COUNT(*) FROM users WHERE user_type = 'requester') as total_requesters,
        (SELECT SUM(payment_amount) FROM tasks WHERE status = 'completed') as total_paid
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTurk Clone - Micro-Tasking Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #667eea;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        /* Hero Section */
        .hero {
            padding: 120px 0 80px;
            text-align: center;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        /* Stats Section */
        .stats {
            background: white;
            padding: 60px 0;
            margin-top: -40px;
            border-radius: 20px 20px 0 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            padding: 2rem;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        /* Features Section */
        .features {
            padding: 80px 0;
            background: white;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            padding: 2rem;
            background: linear-gradient(135deg, #fff 0%, #f8f9ff 100%);
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        /* Featured Tasks */
        .featured-tasks {
            padding: 80px 0;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
        }

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .task-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
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
            font-size: 1.2rem;
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
        }

        .task-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .task-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        /* Footer */
        .footer {
            background: #333;
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .tasks-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav container">
            <a href="index.php" class="logo">
                <i class="fas fa-tasks"></i> MTurk Clone
            </a>
            
            <ul class="nav-links">
                <li><a href="marketplace.php">Browse Tasks</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            
            <div class="auth-buttons">
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    <a href="<?= $user['user_type'] ?>_dashboard.php" class="btn btn-primary">
                        Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                    <a href="register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Earn Money with Micro-Tasks</h1>
            <p>Join thousands of workers completing simple tasks and earning real money online</p>
            <div class="hero-buttons">
                <a href="register.php?type=worker" class="btn btn-primary btn-large">
                    <i class="fas fa-user-plus"></i> Start as Worker
                </a>
                <a href="register.php?type=requester" class="btn btn-secondary btn-large">
                    <i class="fas fa-briefcase"></i> Post Tasks
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['active_tasks'] ?? 0) ?></div>
                    <div class="stat-label">Active Tasks</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['total_workers'] ?? 0) ?></div>
                    <div class="stat-label">Active Workers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['total_requesters'] ?? 0) ?></div>
                    <div class="stat-label">Requesters</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= formatCurrency($stats['total_paid'] ?? 0) ?></div>
                    <div class="stat-label">Total Paid</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">Why Choose Our Platform?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3>Earn Real Money</h3>
                    <p>Complete simple tasks and get paid instantly. No minimum payout requirements.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Flexible Schedule</h3>
                    <p>Work whenever you want, wherever you are. Perfect for earning extra income.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Platform</h3>
                    <p>Your payments and personal information are protected with bank-level security.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Global Community</h3>
                    <p>Join thousands of workers and requesters from around the world.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Quality Tasks</h3>
                    <p>All tasks are reviewed and verified to ensure fair payment and clear instructions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Our support team is always ready to help you with any questions or issues.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Tasks -->
    <?php if (!empty($featuredTasks)): ?>
    <section class="featured-tasks">
        <div class="container">
            <h2 class="section-title">Featured Tasks</h2>
            <div class="tasks-grid">
                <?php foreach ($featuredTasks as $task): ?>
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
                        <?= htmlspecialchars(substr($task['description'], 0, 150)) ?>...
                    </div>
                    <div class="task-meta">
                        <span><i class="fas fa-clock"></i> <?= $task['estimated_time'] ?> min</span>
                        <span><i class="fas fa-calendar"></i> Due <?= date('M j', strtotime($task['deadline'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="marketplace.php" class="btn btn-primary btn-large">View All Tasks</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 MTurk Clone. All rights reserved. | Built with ❤️ for micro-tasking</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to header
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });

        // Animate stats on scroll
        function animateStats() {
            const stats = document.querySelectorAll('.stat-number');
            stats.forEach(stat => {
                const target = parseInt(stat.textContent.replace(/,/g, ''));
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.floor(current).toLocaleString();
                }, 50);
            });
        }

        // Trigger animation when stats section is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateStats();
                    observer.unobserve(entry.target);
                }
            });
        });

        const statsSection = document.querySelector('.stats');
        if (statsSection) {
            observer.observe(statsSection);
        }
    </script>
</body>
</html>
