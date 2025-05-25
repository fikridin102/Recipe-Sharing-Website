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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h2>Recipes</h2>
                <div id="recipe-feed">
                    <?php
                    // Fetch and display latest recipes
                    $db = new Database();
                    $conn = $db->getConnection();
                    
                    $query = "SELECT r.*, u.username, u.profile_image 
                             FROM recipes r 
                             JOIN users u ON r.user_id = u.id 
                             ORDER BY r.created_at DESC 
                             LIMIT 10";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    
                    while ($recipe = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
                            <a href="create-recipe.php" class="btn btn-success w-100 mb-2">Create  Recipe</a>
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