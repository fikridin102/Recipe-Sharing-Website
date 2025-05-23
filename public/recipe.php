<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if recipe ID is provided
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

// Get comments for the recipe
$db = new Database();
$conn = $db->getConnection();
$query = "SELECT c.*, u.username, u.profile_image 
          FROM comments c 
          JOIN users u ON c.user_id = u.id 
          WHERE c.recipe_id = :recipe_id 
          ORDER BY c.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(":recipe_id", $recipe_id);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get like count
$query = "SELECT COUNT(*) as like_count FROM likes WHERE recipe_id = :recipe_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(":recipe_id", $recipe_id);
$stmt->execute();
$like_count = $stmt->fetch(PDO::FETCH_ASSOC)['like_count'];

// Check if current user has liked the recipe
$has_liked = false;
if (isset($_SESSION['user_id'])) {
    $query = "SELECT id FROM likes WHERE recipe_id = :recipe_id AND user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":recipe_id", $recipe_id);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    $has_liked = $stmt->fetch() ? true : false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['title']); ?> - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <?php if ($recipe['image']): ?>
                        <img src="../assets/images/recipes/<?php echo htmlspecialchars($recipe['image']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="../assets/images/profiles/<?php echo htmlspecialchars($recipe['profile_image']); ?>" 
                                 class="rounded-circle me-2" width="40" height="40">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($recipe['username']); ?></h5>
                        </div>
                        
                        <h2 class="card-title"><?php echo htmlspecialchars($recipe['title']); ?></h2>
                        
                        <?php if ($recipe['description']): ?>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h4>Ingredients</h4>
                            <ul class="list-unstyled">
                                <?php foreach (explode("\n", $recipe['ingredients']) as $ingredient): ?>
                                    <li><?php echo htmlspecialchars(trim($ingredient)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="mb-4">
                            <h4>Instructions</h4>
                            <ol>
                                <?php foreach (explode("\n", $recipe['instructions']) as $step): ?>
                                    <li><?php echo htmlspecialchars(trim($step)); ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button class="btn btn-outline-primary" id="like-<?php echo $recipe_id; ?>"
                                        onclick="toggleLike(<?php echo $recipe_id; ?>)"
                                        <?php echo $has_liked ? 'class="liked"' : ''; ?>>
                                    <i class="fas fa-heart"></i> 
                                    <span id="like-count-<?php echo $recipe_id; ?>"><?php echo $like_count; ?></span>
                                </button>
                            </div>
                            <small class="text-muted">
                                Posted on <?php echo date('F j, Y', strtotime($recipe['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Comments Section -->
                <div class="card">
                    <div class="card-header">
                        <h4>Comments</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form id="comment-form-<?php echo $recipe_id; ?>" class="mb-4">
                                <div class="mb-3">
                                    <textarea class="form-control" id="comment-input-<?php echo $recipe_id; ?>" 
                                              rows="3" placeholder="Write a comment..."></textarea>
                                </div>
                                <button type="button" class="btn btn-primary" 
                                        onclick="submitComment(<?php echo $recipe_id; ?>)">
                                    Post Comment
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div id="comments-<?php echo $recipe_id; ?>">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment">
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="../assets/images/profiles/<?php echo htmlspecialchars($comment['profile_image']); ?>" 
                                             class="rounded-circle me-2" width="32" height="32">
                                        <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                    </div>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                    <small class="text-muted">
                                        <?php echo date('F j, Y g:i a', strtotime($comment['created_at'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <?php if (isset($_SESSION['user_id'])): ?>
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
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    <script src="../assets/js/main.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html> 