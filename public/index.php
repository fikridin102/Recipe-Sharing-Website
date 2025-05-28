<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;

// Build the query
$db = new Database();
$conn = $db->getConnection();

// Get all categories for the filter
$cat_query = "SELECT DISTINCT category FROM recipes WHERE category IS NOT NULL AND category != '' ORDER BY category";
$cat_stmt = $conn->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

// Build the main query
$query = "SELECT r.*, u.username, u.profile_image, 
          (SELECT COUNT(*) FROM likes WHERE recipe_id = r.id) as like_count
          FROM recipes r 
          JOIN users u ON r.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (r.title LIKE :search OR r.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($category) {
    $query .= " AND r.category = :category";
    $params[':category'] = $category;
}

// Add sorting
switch ($sort) {
    case 'likes':
        $query .= " ORDER BY like_count DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY r.created_at ASC";
        break;
    default: // newest
        $query .= " ORDER BY r.created_at DESC";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM recipes r 
                JOIN users u ON r.user_id = u.id 
                WHERE 1=1";
if ($search) {
    $count_query .= " AND (r.title LIKE :search OR r.description LIKE :search)";
}
if ($category) {
    $count_query .= " AND r.category = :category";
}

$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_recipes = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_recipes / $per_page);

// Add pagination (inject integers directly, safe because they're calculated)
$offset = ($page - 1) * $per_page;
$query .= " LIMIT $offset, $per_page";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RecipeHub - Share Your Culinary Journey</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .recipe-card {
            transition: transform 0.2s;
        }
        .recipe-card:hover {
            transform: translateY(-5px);
        }
        .recipe-image {
            height: 200px;
            object-fit: cover;
        }
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .profile-thumb {
            width: 40px !important;
            height: 40px !important;
            object-fit: cover !important;
            border-radius: 50% !important;
            display: block !important;
            flex-shrink: 0 !important;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <!-- Search and Filter Section -->
                <div class="filter-section">                                                                                                                     
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search recipes by name or ingredient..." aria-label="Search recipes" value="<?php echo htmlspecialchars($search); ?>" data-bs-toggle="tooltip" title="Type a recipe name or ingredient">
                                <button class="btn btn-primary" type="submit" aria-label="Search">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select" onchange="this.form.submit()" data-bs-toggle="tooltip" title="Filter by category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="sort" class="form-select" onchange="this.form.submit()" data-bs-toggle="tooltip" title="Sort recipes">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="likes" <?php echo $sort === 'likes' ? 'selected' : ''; ?>>Most Liked</option>
                                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Recipe List -->
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($recipes as $recipe): ?>
                        <div class="col">
                            <div class="card h-100 recipe-card shadow-sm">
                                <?php if ($recipe['image']): ?>
                                    <img src="../assets/images/recipes/<?php echo htmlspecialchars($recipe['image']); ?>" 
                                         class="card-img-top recipe-image" 
                                         alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="../assets/images/profiles/<?php echo htmlspecialchars($recipe['profile_image']); ?>" class="profile-thumb me-2" alt="Profile Picture">
                                        <small class="text-muted"><?php echo htmlspecialchars($recipe['username']); ?></small>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        <?php echo nl2br(htmlspecialchars(substr($recipe['description'], 0, 100))); ?>...
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-heart text-danger"></i> <?php echo $recipe['like_count']; ?> likes
                                        </small>
                                        <a href="recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-outline-primary btn-sm">View Recipe</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Quick Actions</h5>
                        <?php if ($isLoggedIn): ?>
                            <a href="create-recipe.php" class="btn btn-success w-100 mb-2">Create Recipe</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-success w-100 mb-2" data-bs-toggle="tooltip" title="Login to create a recipe">Create Recipe</a>
                        <?php endif; ?>
                        <a href="friends.php" class="btn btn-info w-100">View Friends</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>