<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once __DIR__ . '/../http/controller/FeedbackController.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RecipeHub - Share Your Culinary Journey</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --light-gray: #f8fafc;
            --border-color: #e2e8f0;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --border-radius: 12px;
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }

        .hero-banner {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
            border-radius: var(--border-radius);
            position: relative;
            overflow: hidden;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--light-gray);
        }

        .section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
            color: #1e293b;
        }

        .recipe-card {
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            overflow: hidden;
            position: relative;
        }

        .recipe-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .recipe-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .recipe-card:hover .recipe-image {
            transform: scale(1.05);
        }

        .recipe-content {
            padding: 1.5rem;
        }

        .recipe-author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .author-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-gray);
        }

        .author-info h6 {
            margin: 0;
            font-weight: 600;
            color: #1e293b;
        }

        .author-info small {
            color: var(--text-muted);
        }

        .recipe-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #1e293b;
            line-height: 1.4;
        }

        .recipe-description {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .recipe-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-view-recipe {
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-view-recipe:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .recipe-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .sidebar-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }

        .sidebar-card:hover {
            box-shadow: var(--shadow-md);
        }

        .sidebar-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-action-btn {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .btn-create {
            background: linear-gradient(135deg, var(--success-color), #10b981);
            color: white;
            border: none;
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-friends {
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            color: white;
            border: none;
        }

        .btn-friends:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .trending-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--light-gray);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
        }

        .trending-item:hover {
            background: #e2e8f0;
            transform: translateX(4px);
        }

        .trending-number {
            width: 28px;
            height: 28px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .no-recipes {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .no-recipes i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .fade-in-delay {
            animation: fadeIn 0.6s ease-out 0.2s both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .recipe-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .recipe-stats {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <!-- Hero Banner -->
    <div class="container mt-4">
        <div class="hero-banner fade-in">
            <div class="hero-content">
                <h1 class="hero-title">Discover Amazing Recipes</h1>
                <p class="hero-subtitle">Share your culinary journey and explore delicious creations from our community</p>
            </div>
        </div>
    </div>

    <div class="container fade-in-delay">
        <div class="row">
            <div class="col-lg-8">
                <!-- Recipes Section -->
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h2 class="section-title">Latest Recipes</h2>
                </div>

                <div id="recipe-feed">
                    <?php
                    // Fetch and display latest recipes
                    $db = new Database();
                    $conn = $db->getConnection();
                    
                    $query = "SELECT r.*, u.username, u.profile_image,
                             (SELECT COUNT(*) FROM likes l WHERE l.recipe_id = r.id) as like_count,
                             (SELECT COUNT(*) FROM comments c WHERE c.recipe_id = r.id) as comment_count
                             FROM recipes r 
                             JOIN users u ON r.user_id = u.id 
                             ORDER BY r.created_at DESC 
                             LIMIT 10";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($recipes)) {
                        echo '<div class="no-recipes">';
                        echo '<i class="fas fa-utensils"></i>';
                        echo '<h4>No recipes found</h4>';
                        echo '<p>Be the first to share a delicious recipe with our community!</p>';
                        if ($isLoggedIn) {
                            echo '<a href="create-recipe.php" class="btn btn-primary">Create Your First Recipe</a>';
                        }
                        echo '</div>';
                    } else {
                        foreach ($recipes as $recipe) {
                            echo '<div class="recipe-card">';
                            
                            // Recipe Image
                            if (!empty($recipe['image'])) {
                                echo '<img src="../assets/images/recipes/' . htmlspecialchars($recipe['image']) . '" class="recipe-image" alt="' . htmlspecialchars($recipe['title']) . '">';
                            }
                            
                            echo '<div class="recipe-content">';
                            
                            // Author Info
                            echo '<div class="recipe-author">';
                            $profileImage = !empty($recipe['profile_image']) ? $recipe['profile_image'] : 'default-avatar.png';
                            echo '<img src="../assets/images/profiles/' . htmlspecialchars($profileImage) . '" class="author-avatar" alt="' . htmlspecialchars($recipe['username']) . '">';
                            echo '<div class="author-info">';
                            echo '<h6>' . htmlspecialchars($recipe['username']) . '</h6>';
                            echo '<small>' . date('M j, Y', strtotime($recipe['created_at'])) . '</small>';
                            echo '</div>';
                            echo '</div>';
                            
                            // Recipe Title
                            echo '<h3 class="recipe-title">' . htmlspecialchars($recipe['title']) . '</h3>';
                            
                            // Recipe Description
                            if (!empty($recipe['description'])) {
                                echo '<p class="recipe-description">' . htmlspecialchars($recipe['description']) . '</p>';
                            }
                            
                            // Recipe Actions
                            echo '<div class="recipe-actions">';
                            echo '<div class="recipe-stats">';
                            echo '<span class="stat-item"><i class="fas fa-heart text-danger"></i> ' . $recipe['like_count'] . '</span>';
                            echo '<span class="stat-item"><i class="fas fa-comment text-primary"></i> ' . $recipe['comment_count'] . '</span>';
                            echo '</div>';
                            echo '<a href="recipe.php?id=' . $recipe['id'] . '" class="btn-view-recipe">';
                            echo '<i class="fas fa-eye"></i>View Recipe';
                            echo '</a>';
                            echo '</div>';
                            
                            echo '</div>'; // recipe-content
                            echo '</div>'; // recipe-card
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <?php if ($isLoggedIn): ?>
                    <div class="sidebar-card">
                        <h5 class="sidebar-title">
                            <i class="fas fa-bolt text-warning"></i>
                            Quick Actions
                        </h5>
                        <a href="create-recipe.php" class="btn quick-action-btn btn-create">
                            <i class="fas fa-plus"></i>Create New Recipe
                        </a>
                        <a href="friends.php" class="btn quick-action-btn btn-friends">
                            <i class="fas fa-users"></i>View Friends
                        </a>
                    </div>
                <?php else: ?>
                    <div class="sidebar-card">
                        <h5 class="sidebar-title">
                            <i class="fas fa-sign-in-alt text-primary"></i>
                            Join RecipeHub
                        </h5>
                        <p class="text-muted mb-3">Create an account to share your recipes and connect with fellow food enthusiasts!</p>
                        <a href="register.php" class="btn quick-action-btn btn-create">
                            <i class="fas fa-user-plus"></i>Sign Up Now
                        </a>
                        <a href="login.php" class="btn quick-action-btn btn-friends">
                            <i class="fas fa-sign-in-alt"></i>Login
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Trending Recipes -->
                <div class="sidebar-card">
                    <h5 class="sidebar-title">
                        <i class="fas fa-fire text-danger"></i>
                        Trending Now
                    </h5>
                    <?php
                    // Fetch trending recipes (most liked in the past week)
                    $trending_query = "SELECT r.title, r.id, COUNT(l.id) as likes 
                                      FROM recipes r 
                                      LEFT JOIN likes l ON r.id = l.recipe_id 
                                      WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                      GROUP BY r.id 
                                      ORDER BY likes DESC 
                                      LIMIT 5";
                    $trending_stmt = $conn->prepare($trending_query);
                    $trending_stmt->execute();
                    $trending_recipes = $trending_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($trending_recipes)) {
                        echo '<p class="text-muted">No trending recipes this week. Be the first!</p>';
                    } else {
                        foreach ($trending_recipes as $index => $trending) {
                            echo '<div class="trending-item">';
                            echo '<div class="trending-number">' . ($index + 1) . '</div>';
                            echo '<div class="flex-grow-1">';
                            echo '<a href="recipe.php?id=' . $trending['id'] . '" class="text-decoration-none text-dark">';
                            echo '<div class="fw-medium">' . htmlspecialchars($trending['title']) . '</div>';
                            echo '<small class="text-muted">' . $trending['likes'] . ' likes</small>';
                            echo '</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>

                <!-- Recipe Tips -->
                <div class="sidebar-card">
                    <h5 class="sidebar-title">
                        <i class="fas fa-lightbulb text-warning"></i>
                        Recipe Tips
                    </h5>
                    <div class="trending-item">
                        <div class="trending-number">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div>
                            <div class="fw-medium">Great Photos</div>
                            <small class="text-muted">Good lighting makes recipes irresistible</small>
                        </div>
                    </div>
                    <div class="trending-item">
                        <div class="trending-number">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <div class="fw-medium">Clear Instructions</div>
                            <small class="text-muted">Step-by-step guides help everyone succeed</small>
                        </div>
                    </div>
                    <div class="trending-item">
                        <div class="trending-number">
                            <i class="fas fa-star"></i>
                        </div>
                        <div>
                            <div class="fw-medium">Personal Touch</div>
                            <small class="text-muted">Share your story and cooking tips</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>