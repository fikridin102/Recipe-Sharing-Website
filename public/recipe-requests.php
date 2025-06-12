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

// Handle form submission for new recipe request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (!empty($title) && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO recipe_requests (user_id, title, description) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $description]);
        header('Location: recipe-requests.php');
        exit();
    }
}

// Get all recipe requests
$stmt = $pdo->query("
    SELECT r.*, u.username, u.profile_image,
           (SELECT COUNT(*) FROM recipe_request_responses WHERE request_id = r.id) as response_count
    FROM recipe_requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Requests - RecipeHub</title>
    
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
            background: linear-gradient(135deg, var(--warning-color) 0%, #ea580c 100%);
            color: white;
            padding: 3rem 0;
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
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero-subtitle {
            font-size: 1.1rem;
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
            background: linear-gradient(135deg, var(--warning-color), #ea580c);
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

        .card {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .create-request-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            margin-bottom: 3rem;
        }

        .create-request-header {
            background: linear-gradient(135deg, var(--warning-color), #ea580c);
            color: white;
            padding: 1.5rem;
            border: none;
            position: relative;
        }

        .create-request-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 10px solid #ea580c;
        }

        .create-request-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-control:focus {
            border-color: var(--warning-color);
            box-shadow: 0 0 0 0.2rem rgba(217, 119, 6, 0.25);
            transform: translateY(-1px);
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .btn-submit-request {
            background: linear-gradient(135deg, var(--warning-color), #ea580c);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.2s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit-request:hover {
            background: linear-gradient(135deg, #c2410c, var(--warning-color));
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .request-card {
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }

        .request-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .request-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--warning-color), #ea580c);
        }

        .request-content {
            padding: 1.5rem;
            padding-left: 2rem;
        }

        .request-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .request-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            flex: 1;
            min-width: 250px;
        }

        .request-status {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: var(--warning-color);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .request-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .author-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }

        .request-description {
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

        .request-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-view {
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            color: white;
        }

        .btn-view:hover {
            background: linear-gradient(135deg, #1e40af, var(--primary-color));
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--success-color), #10b981);
            color: white;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #047857, var(--success-color));
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .no-requests {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
        }

        .no-requests i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            color: var(--warning-color);
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

        .quick-tip {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--light-gray);
            border-radius: 8px;
            margin-bottom: 0.75rem;
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
                font-size: 2rem;
            }
            
            .request-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .request-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .request-actions {
                flex-direction: column;
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
                <h1 class="hero-title">Recipe Requests</h1>
                <p class="hero-subtitle">Ask the community for their favorite recipes and cooking tips</p>
            </div>
        </div>
    </div>

    <main class="container fade-in-delay">
        <div class="row">
            <div class="col-lg-8">
                <!-- Create new request form -->
                <section class="create-request mb-5">
                    <div class="card create-request-card">
                        <div class="create-request-header">
                            <h2 class="create-request-title">
                                <i class="fas fa-plus-circle"></i>
                                Create New Recipe Request
                            </h2>
                        </div>
                        <div class="card-body" style="padding: 2rem;">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-heading text-warning me-2"></i>Request Title
                                    </label>
                                    <input type="text" id="title" name="title" class="form-control" 
                                           placeholder="e.g., Looking for the perfect chocolate chip cookie recipe" required>
                                </div>
                                <div class="mb-4">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left text-warning me-2"></i>Description
                                    </label>
                                    <textarea id="description" name="description" class="form-control" rows="4" 
                                              placeholder="Describe what you're looking for in detail. Include any dietary restrictions, preferred ingredients, or cooking methods..." required></textarea>
                                </div>
                                <button type="submit" name="create_request" class="btn-submit-request">
                                    <i class="fas fa-paper-plane"></i>
                                    Submit Request
                                </button>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- List of recipe requests -->
                <section class="requests-list">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <h2 class="section-title">Recent Requests</h2>
                    </div>

                    <?php if (empty($requests)): ?>
                        <div class="no-requests">
                            <i class="fas fa-clipboard-list"></i>
                            <h4>No Recipe Requests Yet</h4>
                            <p>Be the first to ask the community for their favorite recipes!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <div class="request-card">
                                <div class="request-content">
                                    <div class="request-header">
                                        <h3 class="request-title"><?php echo htmlspecialchars($request['title']); ?></h3>
                                        <div class="request-status">
                                            <i class="fas fa-clock"></i>
                                            Open
                                        </div>
                                    </div>

                                    <div class="request-meta">
                                        <div class="meta-item author-info">
                                            <img src="../assets/images/profiles/<?php echo htmlspecialchars($request['profile_image'] ?? 'default-avatar.png'); ?>" 
                                                 class="author-avatar" alt="<?php echo htmlspecialchars($request['username']); ?>">
                                            <span><strong><?php echo htmlspecialchars($request['username']); ?></strong></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('M j, Y', strtotime($request['created_at'])); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-comments"></i>
                                            <span><?php echo $request['response_count']; ?> responses</span>
                                        </div>
                                    </div>

                                    <p class="request-description"><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>

                                    <div class="request-actions">
                                        <a href="view-request.php?id=<?php echo $request['id']; ?>" class="btn-action btn-view">
                                            <i class="fas fa-eye"></i>View Details
                                        </a>
                                        <?php if ($request['user_id'] === $user_id): ?>
                                            <a href="edit-request.php?id=<?php echo $request['id']; ?>" class="btn-action btn-edit">
                                                <i class="fas fa-edit"></i>Edit Request
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <h5 class="sidebar-title">
                        <i class="fas fa-bolt text-warning"></i>
                        Quick Actions
                    </h5>
                    <a href="index.php" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-utensils me-2"></i>Browse Recipes
                    </a>
                    <a href="create-recipe.php" class="btn btn-success w-100">
                        <i class="fas fa-plus me-2"></i>Share a Recipe
                    </a>
                </div>

                <!-- Request Tips -->
                <div class="sidebar-card">
                    <h5 class="sidebar-title">
                        <i class="fas fa-lightbulb text-warning"></i>
                        Request Tips
                    </h5>
                    <div class="quick-tip">
                        <div class="tip-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <div>
                            <div class="fw-medium">Be Specific</div>
                            <small class="text-muted">Include details about flavors, dietary needs, or occasions</small>
                        </div>
                    </div>
                    <div class="quick-tip">
                        <div class="tip-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div>
                            <div class="fw-medium">Share Context</div>
                            <small class="text-muted">Mention why you need the recipe or what inspired you</small>
                        </div>
                    </div>
                    <div class="quick-tip">
                        <div class="tip-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="fw-medium">Engage with Responses</div>
                            <small class="text-muted">Thank contributors and ask follow-up questions</small>
                        </div>
                    </div>
                </div>

                <!-- Popular Categories -->
                <div class="sidebar-card">
                    <h5 class="sidebar-title">
                        <i class="fas fa-tags text-warning"></i>
                        Popular Request Types
                    </h5>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark border">Desserts</span>
                        <span class="badge bg-light text-dark border">Quick Meals</span>
                        <span class="badge bg-light text-dark border">Vegetarian</span>
                        <span class="badge bg-light text-dark border">Comfort Food</span>
                        <span class="badge bg-light text-dark border">Holiday Recipes</span>
                        <span class="badge bg-light text-dark border">Healthy Options</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>