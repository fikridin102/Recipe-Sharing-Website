<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Add database connection
$db = new Database();
$pdo = $db->getConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get request ID from URL
if (!isset($_GET['id'])) {
    header('Location: recipe-requests.php');
    exit();
}

$request_id = $_GET['id'];

// Get request details
$stmt = $pdo->prepare("
    SELECT r.*, u.username, u.profile_image
    FROM recipe_requests r
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: recipe-requests.php');
    exit();
}

// Handle form submission for new recipe response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_recipe'])) {
    $recipe_id = $_POST['recipe_id'];
    
    // Check if recipe exists and belongs to the user
    $stmt = $pdo->prepare("SELECT id FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->execute([$recipe_id, $user_id]);
    if ($stmt->fetch()) {
        // Add response
        $stmt = $pdo->prepare("INSERT INTO recipe_request_responses (request_id, user_id, recipe_id) VALUES (?, ?, ?)");
        $stmt->execute([$request_id, $user_id, $recipe_id]);
        header("Location: view-request.php?id=$request_id");
        exit();
    }
}

// Get all responses for this request
$stmt = $pdo->prepare("
    SELECT r.*, u.username, u.profile_image, resp.created_at as response_date,
           (SELECT COUNT(*) FROM likes l WHERE l.recipe_id = r.id) as like_count,
           (SELECT COUNT(*) FROM comments c WHERE c.recipe_id = r.id) as comment_count
    FROM recipe_request_responses resp
    JOIN recipes r ON resp.recipe_id = r.id
    JOIN users u ON r.user_id = u.id
    WHERE resp.request_id = ?
    ORDER BY resp.created_at DESC
");
$stmt->execute([$request_id]);
$responses = $stmt->fetchAll();

// Get user's recipes for the response form
$stmt = $pdo->prepare("SELECT id, title FROM recipes WHERE user_id = ? ORDER BY title");
$stmt->execute([$user_id]);
$user_recipes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($request['title']); ?> - RecipeHub</title>
    
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

        .request-hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
            position: relative;
            overflow: hidden;
        }

        .request-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .request-content {
            position: relative;
            z-index: 2;
        }

        .request-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .request-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .requester-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .requester-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .request-description {
            font-size: 1.1rem;
            line-height: 1.7;
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
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

        .submit-response-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 2px solid #bbf7d0;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .submit-response-card h3 {
            color: var(--success-color);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .submit-response-card p {
            color: #059669;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .btn-create-recipe {
            background: linear-gradient(135deg, var(--success-color), #10b981);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-create-recipe:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .response-card {
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            overflow: hidden;
            position: relative;
        }

        .response-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .response-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .response-card:hover .response-image {
            transform: scale(1.05);
        }

        .response-content {
            padding: 1.5rem;
        }

        .response-author {
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

        .response-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #1e293b;
            line-height: 1.4;
        }

        .response-description {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .response-actions {
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

        .response-stats {
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

        .no-responses {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
        }

        .no-responses i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1.5rem;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #1e40af;
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
            .request-title {
                font-size: 2rem;
            }
            
            .request-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .response-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .response-stats {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4 fade-in">
        <!-- Back Link -->
        <a href="recipe-requests.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Recipe Requests
        </a>

        <!-- Request Hero Section -->
        <div class="request-hero">
            <div class="request-content">
                <h1 class="request-title"><?php echo htmlspecialchars($request['title']); ?></h1>
                <div class="request-meta">
                    <div class="requester-info">
                        <?php 
                        $profileImage = !empty($request['profile_image']) ? $request['profile_image'] : 'default-avatar.png';
                        ?>
                        <img src="../assets/images/profiles/<?php echo htmlspecialchars($profileImage); ?>" 
                             class="requester-avatar" alt="<?php echo htmlspecialchars($request['username']); ?>">
                        <div>
                            <div>Requested by <strong><?php echo htmlspecialchars($request['username']); ?></strong></div>
                        </div>
                    </div>
                    <div class="request-date">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                    </div>
                </div>
                <?php if (!empty($request['description'])): ?>
                    <div class="request-description">
                        <?php echo nl2br(htmlspecialchars($request['description'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="fade-in-delay">
            <!-- Submit Response Section (only for other users) -->
            <?php if ($request['user_id'] !== $user_id): ?>
                <div class="submit-response-card">
                    <h3><i class="fas fa-plus-circle"></i> Have a Perfect Recipe?</h3>
                    <p>Share your culinary creation and help fulfill this recipe request!</p>
                    <a href="create-recipe.php?request_id=<?php echo $request_id; ?>" class="btn-create-recipe">
                        <i class="fas fa-utensils"></i>
                        Create Recipe for This Request
                    </a>
                </div>
            <?php endif; ?>

            <!-- Responses Section -->
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h2 class="section-title">Submitted Recipes</h2>
            </div>

            <div id="responses-feed">
                <?php if (empty($responses)): ?>
                    <div class="no-responses">
                        <i class="fas fa-clipboard-list"></i>
                        <h4>No Recipes Submitted Yet</h4>
                        <p>Be the first to share a delicious recipe for this request!</p>
                        <?php if ($request['user_id'] !== $user_id): ?>
                            <a href="create-recipe.php?request_id=<?php echo $request_id; ?>" class="btn-create-recipe">
                                <i class="fas fa-plus"></i>
                                Submit First Recipe
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($responses as $response): ?>
                        <div class="response-card">
                            <!-- Recipe Image -->
                            <?php if (!empty($response['image'])): ?>
                                <img src="../assets/images/recipes/<?php echo htmlspecialchars($response['image']); ?>" 
                                     class="response-image" alt="<?php echo htmlspecialchars($response['title']); ?>">
                            <?php endif; ?>
                            
                            <div class="response-content">
                                <!-- Author Info -->
                                <div class="response-author">
                                    <?php 
                                    $profileImage = !empty($response['profile_image']) ? $response['profile_image'] : 'default-avatar.png';
                                    ?>
                                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($profileImage); ?>" 
                                         class="author-avatar" alt="<?php echo htmlspecialchars($response['username']); ?>">
                                    <div class="author-info">
                                        <h6><?php echo htmlspecialchars($response['username']); ?></h6>
                                        <small>Submitted <?php echo date('M j, Y', strtotime($response['response_date'])); ?></small>
                                    </div>
                                </div>
                                
                                <!-- Recipe Title -->
                                <h3 class="response-title"><?php echo htmlspecialchars($response['title']); ?></h3>
                                
                                <!-- Recipe Description -->
                                <?php if (!empty($response['description'])): ?>
                                    <p class="response-description"><?php echo htmlspecialchars($response['description']); ?></p>
                                <?php endif; ?>
                                
                                <!-- Response Actions -->
                                <div class="response-actions">
                                    <div class="response-stats">
                                        <span class="stat-item">
                                            <i class="fas fa-heart text-danger"></i> 
                                            <?php echo $response['like_count']; ?>
                                        </span>
                                        <span class="stat-item">
                                            <i class="fas fa-comment text-primary"></i> 
                                            <?php echo $response['comment_count']; ?>
                                        </span>
                                    </div>
                                    <a href="recipe.php?id=<?php echo $response['id']; ?>" class="btn-view-recipe">
                                        <i class="fas fa-eye"></i>
                                        View Recipe
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>