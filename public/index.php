<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Get all recipes with user information
$category = isset($_GET['category']) ? $_GET['category'] : '';
$where_clause = '';
$params = [];

if (!empty($category)) {
    $where_clause = "WHERE r.category = ?";
    $params[] = $category;
}

$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("
    SELECT r.*, u.username, u.profile_image 
    FROM recipes r 
    JOIN users u ON r.user_id = u.id 
    $where_clause
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// Get unique categories for filter
$stmt = $conn->query("SELECT DISTINCT category FROM recipes ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RecipeHub - Share Your Culinary Journey</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Recipe List</h1>
                    <form method="GET" action="" class="d-flex gap-2">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($category)): ?>
                            <a href="index.php" class="btn btn-secondary">Clear Filter</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div id="recipe-feed">
                    <?php
                    // Display recipes from the already fetched $recipes array
                    foreach ($recipes as $recipe) {
                        echo '<div class="card mb-3">';
                        echo '<div class="card-body">';
                        echo '<div class="d-flex align-items-center mb-2">';
                        if (!empty($recipe['image'])) {
                            echo '<img src="../assets/images/recipes/' . htmlspecialchars($recipe['image']) . '" class="rounded-circle me-2" width="40" height="40" style="object-fit:cover;">';
                        }
                        echo '<h5 class="card-title mb-0">' . htmlspecialchars($recipe['username']) . '</h5>';
                        echo '</div>';
                        echo '<h6 class="card-subtitle mb-2 text-muted">' . htmlspecialchars($recipe['title']) . '</h6>';
                        echo '<p class="card-text">' . htmlspecialchars($recipe['description']) . '</p>';
                        echo '<a href="recipe.php?id=' . $recipe['id'] . '" class="btn btn-primary">View Recipe</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-4">
                <?php if ($isLoggedIn): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Quick Actions</h5>
                            <a href="create-recipe.php" class="btn btn-success w-100 mb-2">Create New Recipe</a>
                            <a href="friends.php" class="btn btn-info w-100">View Friends</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html> 