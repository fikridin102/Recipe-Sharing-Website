<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$recipe_id = $_GET['id'];
$recipe = getRecipe($recipe_id);

if (!$recipe) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Fetch comments
$comment_query = "SELECT c.*, u.username, u.profile_image 
                  FROM comments c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.recipe_id = :recipe_id 
                  ORDER BY c.created_at DESC";
$stmt = $conn->prepare($comment_query);
$stmt->bindParam(":recipe_id", $recipe_id);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Like count
$stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE recipe_id = :recipe_id");
$stmt->bindParam(":recipe_id", $recipe_id);
$stmt->execute();
$like_count = $stmt->fetch(PDO::FETCH_ASSOC)['like_count'];

// Like & Save status
$user_id = $_SESSION['user_id'] ?? null;
$has_liked = false;
$has_saved = false;

if ($user_id) {
    $stmt = $conn->prepare("SELECT id FROM likes WHERE id = :recipe_id AND user_id = :user_id");
    $stmt->bindParam(":recipe_id", $recipe_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $has_liked = $stmt->fetch() ? true : false;

    $stmt = $conn->prepare("SELECT id FROM recipes WHERE id = :recipe_id AND user_id = :user_id");
    $stmt->bindParam(":recipe_id", $recipe_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $has_saved = $stmt->fetch() ? true : false;
}

// Parse ingredients and instructions into arrays
$ingredients = array_filter(array_map('trim', explode("\n", $recipe['ingredients'])));
$instructions = array_filter(array_map('trim', explode("\n", $recipe['instructions'])));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['title']); ?> - RecipeHub</title>
    
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

        .hero-section {
            position: relative;
            height: 400px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            overflow: hidden;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }

        .hero-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.8;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.2) 100%);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            align-items: end;
            padding: 2rem;
            color: white;
        }

        .recipe-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .recipe-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            opacity: 0.95;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.8);
            object-fit: cover;
        }

        .card {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            background: white;
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
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
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: #1e293b;
        }

        .ingredient-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: var(--light-gray);
            border-radius: 8px;
            border-left: 4px solid var(--success-color);
            transition: all 0.2s ease;
        }

        .ingredient-item:hover {
            background: #e2e8f0;
            transform: translateX(2px);
        }

        .ingredient-icon {
            width: 20px;
            height: 20px;
            background: var(--success-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 0.75rem;
            color: white;
        }

        .instruction-step {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: var(--light-gray);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.2s ease;
        }

        .instruction-step:hover {
            background: #e2e8f0;
            transform: translateX(2px);
        }

        .step-number {
            min-width: 32px;
            height: 32px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .step-content {
            flex: 1;
            padding-top: 0.25rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin: 1.5rem 0;
        }

        .btn-action {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-like {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: var(--danger-color);
            border-color: #fecaca;
        }

        .btn-like:hover, .btn-like.active {
            background: var(--danger-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-save {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            color: var(--success-color);
            border-color: #bbf7d0;
        }

        .btn-save:hover, .btn-save.active {
            background: var(--success-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stats-badge {
            background: rgba(255,255,255,0.9);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            color: #374151;
            backdrop-filter: blur(10px);
        }

        .comment-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .comment-item {
            background: var(--light-gray);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .comment-item:hover {
            background: #e2e8f0;
        }

        .comment-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .comment-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        .comment-form {
            background: var(--light-gray);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .sidebar-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .quick-tip {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border-left: 3px solid var(--warning-color);
        }

        .tip-icon {
            width: 24px;
            height: 24px;
            background: var(--warning-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
        }

        .back-button {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .description-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .hero-section {
                height: 300px;
            }
            
            .recipe-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .recipe-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
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
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4 fade-in">
        <!-- Hero Section -->
        <div class="hero-section">
            <?php if ($recipe['image']): ?>
                <img src="../assets/images/recipes/<?php echo htmlspecialchars($recipe['image']); ?>" 
                     class="hero-image" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
            <?php endif; ?>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <div class="w-100">
                    <h1 class="recipe-title"><?php echo htmlspecialchars($recipe['title']); ?></h1>
                    <div class="recipe-meta">
                        <div class="author-info">
                            <img src="../assets/images/profiles/<?php echo htmlspecialchars($recipe['profile_image']); ?>" 
                                 class="author-avatar" alt="<?php echo htmlspecialchars($recipe['username']); ?>">
                            <span class="fw-medium">By <?php echo htmlspecialchars($recipe['username']); ?></span>
                        </div>
                        <div class="stats-badge">
                            <i class="fas fa-heart text-danger me-1"></i>
                            <span id="like-count-display"><?php echo $like_count; ?></span> likes
                        </div>
                        <div class="stats-badge">
                            <i class="fas fa-comment text-primary me-1"></i>
                            <?php echo count($comments); ?> comments
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Description -->
                <?php if (!empty($recipe['description'])): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fas fa-align-left"></i>
                                </div>
                                <h2 class="section-title">Description</h2>
                            </div>
                            <p class="description-text"><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php if ($user_id): ?>
                    <div class="action-buttons">
                        <button id="like-btn-<?php echo $recipe_id; ?>" 
                                class="btn-action btn-like <?php echo $has_liked ? 'active' : ''; ?>" 
                                onclick="toggleLike(<?php echo $recipe_id; ?>)">
                            <i class="fas fa-heart"></i>
                            <span id="like-count-<?php echo $recipe_id; ?>"><?php echo $like_count; ?></span>
                            Like<?php echo $like_count != 1 ? 's' : ''; ?>
                        </button>
                        <button class="btn-action btn-save <?php echo $has_saved ? 'active' : ''; ?>" 
                                onclick="saveRecipe(<?php echo $recipe_id; ?>)">
                            <i class="fas fa-bookmark"></i>
                            <?php echo $has_saved ? 'Saved' : 'Save Recipe'; ?>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Ingredients -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h2 class="section-title">Ingredients</h2>
                        </div>
                        <div class="ingredients-list">
                            <?php foreach ($ingredients as $ingredient): ?>
                                <div class="ingredient-item">
                                    <div class="ingredient-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <span><?php echo htmlspecialchars($ingredient); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-list-ol"></i>
                            </div>
                            <h2 class="section-title">Instructions</h2>
                        </div>
                        <div class="instructions-list">
                            <?php foreach ($instructions as $index => $instruction): ?>
                                <div class="instruction-step">
                                    <div class="step-number"><?php echo $index + 1; ?></div>
                                    <div class="step-content"><?php echo htmlspecialchars($instruction); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="comment-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h2 class="section-title">Comments</h2>
                    </div>

                    <?php if ($user_id): ?>
                        <div class="comment-form">
                            <textarea id="comment-text" class="form-control" rows="3" 
                                      placeholder="Share your thoughts about this recipe..."></textarea>
                            <button class="btn btn-primary mt-3" onclick="submitComment(<?php echo $recipe_id; ?>)">
                                <i class="fas fa-paper-plane me-2"></i>Post Comment
                            </button>
                        </div>
                    <?php endif; ?>

                    <div id="comments">
                        <?php if (empty($comments)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-comment-slash fa-2x mb-3"></i>
                                <p>No comments yet. Be the first to share your thoughts!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item">
                                    <div class="comment-header">
                                        <img src="../assets/images/profiles/<?php echo htmlspecialchars($comment['profile_image']); ?>" 
                                             class="comment-avatar" alt="<?php echo htmlspecialchars($comment['username']); ?>">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($comment['username']); ?></div>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <a href="index.php" class="btn back-button w-100">
                    <i class="fas fa-arrow-left me-2"></i>Back to Recipes
                </a>

                <?php if ($user_id): ?>
                    <div class="sidebar-card">
                        <h5 class="fw-semibold mb-3">
                            <i class="fas fa-bolt text-warning me-2"></i>Quick Actions
                        </h5>
                        <a href="create-recipe.php" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-plus me-2"></i>Create New Recipe
                        </a>
                        <a href="friends.php" class="btn btn-info w-100">
                            <i class="fas fa-users me-2"></i>View Friends
                        </a>
                    </div>
                <?php endif; ?>

                <div class="sidebar-card">
                    <h5 class="fw-semibold mb-3">
                        <i class="fas fa-lightbulb text-warning me-2"></i>Cooking Tips
                    </h5>
                    <div class="quick-tip">
                        <div class="tip-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <span>Use fresh, high-quality ingredients for best results</span>
                    </div>
                    <div class="quick-tip">
                        <div class="tip-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <span>Read all instructions before you start cooking</span>
                    </div>
                    <div class="quick-tip">
                        <div class="tip-icon">
                            <i class="fas fa-thermometer-half"></i>
                        </div>
                        <span>Preheat your oven and prepare ingredients first</span>
                    </div>
                    <div class="quick-tip">
                        <div class="tip-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <span>Don't be afraid to adjust seasoning to taste</span>
                    </div>
                </div>

                <!-- Rating Form -->
                <?php 
                $passed_recipe_id = $recipe_id;
                include '../public/feedback/rate_form.php'; 
                ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>