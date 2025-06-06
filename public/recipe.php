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
    $stmt = $conn->prepare("SELECT id FROM likes WHERE recipe_id = :recipe_id AND user_id = :user_id");
    $stmt->bindParam(":recipe_id", $recipe_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $has_liked = $stmt->fetch() ? true : false;

    $stmt = $conn->prepare("SELECT id FROM saved_recipes WHERE recipe_id = :recipe_id AND user_id = :user_id");
    $stmt->bindParam(":recipe_id", $recipe_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $has_saved = $stmt->fetch() ? true : false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($recipe['title']); ?> - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .recipe-image {
            height: 350px;
            object-fit: cover;
            border-top-left-radius: .5rem;
            border-top-right-radius: .5rem;
        }
        .icon-list li::before {
            content: '🍽️ ';
            margin-right: 6px;
        }
        .icon-steps li::before {
            content: '📝 ';
            margin-right: 6px;
        }
        .like-btn.active,
        .save-btn.active {
            color: white !important;
        }
        .like-btn.active {
            background-color: #dc3545 !important;
        }
        .save-btn.active {
            background-color: #198754 !important;
        }
        .comment-box {
            background-color: #f8f9fa;
            border-radius: .5rem;
            padding: .75rem;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <?php if ($recipe['image']): ?>
                    <img src="../assets/images/recipes/<?php echo htmlspecialchars($recipe['image']); ?>" class="card-img-top recipe-image" alt="Recipe Image">
                <?php endif; ?>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="../assets/images/profiles/<?php echo htmlspecialchars($recipe['profile_image']); ?>" class="rounded-circle me-2" width="40" height="40">
                        <h6 class="mb-0 text-muted">By <?php echo htmlspecialchars($recipe['username']); ?></h6>
                    </div>
                    <h2 class="mb-3"><?php echo htmlspecialchars($recipe['title']); ?></h2>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>

                    <h6 class="mb-2">
                        <?php if (!empty($recipe['time_duration'])): ?>
                            <span class="badge bg-info text-dark" data-bs-toggle="tooltip" title="Estimated preparation time">
                                <i class="fas fa-clock"></i> <?php echo intval($recipe['time_duration']); ?> min
                            </span>
                        <?php endif; ?>
                    </h6>

                    <h4 class="mt-4">🛒 Ingredients</h4>
                    <ul class="icon-list">
                        <?php foreach (explode("\n", $recipe['ingredients']) as $ing): ?>
                            <li><?php echo htmlspecialchars(trim($ing)); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <h4 class="mt-4">📋 Instructions</h4>
                    <ol class="icon-steps">
                        <?php foreach (explode("\n", $recipe['instructions']) as $step): ?>
                            <li><?php echo htmlspecialchars(trim($step)); ?></li>
                        <?php endforeach; ?>
                    </ol>

                    <div class="d-flex justify-content-start gap-2 mt-4">
                        <button id="like-btn-<?php echo $recipe_id; ?>" class="btn btn-outline-danger like-btn <?php echo $has_liked ? 'active' : ''; ?>" onclick="toggleLike(<?php echo $recipe_id; ?>)">
                            <i class="fas fa-heart"></i> <span id="like-count-<?php echo $recipe_id; ?>"><?php echo $like_count; ?></span> Like
                        </button>
                        <button class="btn btn-outline-success save-btn <?php echo $has_saved ? 'active' : ''; ?>" onclick="saveRecipe(<?php echo $recipe_id; ?>)">
                            <i class="fas fa-bookmark"></i> Save
                        </button>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="card shadow-sm mb-5">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">💬 Comments</h5>
                </div>
                <div class="card-body">
                    <?php if ($user_id): ?>
                        <div class="mb-3">
                            <textarea id="comment-text" class="form-control" rows="3" placeholder="Write a comment..."></textarea>
                            <button class="btn btn-primary mt-2" onclick="submitComment(<?php echo $recipe_id; ?>)">Post</button>
                        </div>
                    <?php endif; ?>

                    <div id="comments">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-box mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($comment['profile_image']); ?>" width="32" height="32" class="rounded-circle me-2">
                                    <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                </div>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                <small class="text-muted"><?php echo date('F j, Y g:i a', strtotime($comment['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <?php if ($user_id): ?>
                <a href="index.php" class="btn btn-outline-secondary w-100 mb-3">
        <i class="fas fa-arrow-left me-1"></i> Back to Recipes
    </a>
            <?php endif; ?>
            <div class="card p-3">
                <h6>Quick Tips</h6>
                <ul class="small ps-3">
                    <li>Use fresh ingredients</li>
                    <li>Follow instructions step-by-step</li>
                    <li>Don't be afraid to experiment!</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>

<script>
function toggleLike(recipeId) {
    if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
        window.location.href = 'login.php';
        return;
    }

    fetch('../ajax/like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'recipe_id=' + recipeId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const likeButton = document.getElementById(`like-btn-${recipeId}`);
            const likeCount = document.getElementById(`like-count-${recipeId}`);
            
            if (likeButton && likeCount) {
                likeButton.classList.toggle('active');
                likeCount.textContent = data.count;
            }
        } else {
            console.error('Error:', data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function saveRecipe(recipeId) {
    fetch('../ajax/save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'recipe_id=' + recipeId
    }).then(() => {
        document.querySelector('.save-btn').classList.toggle('active');
    });
}

function submitComment(recipeId) {
    const text = document.getElementById('comment-text').value;
    if (!text.trim()) return;

    fetch('../ajax/comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'recipe_id=' + recipeId + '&comment=' + encodeURIComponent(text)
    })
    .then(res => res.text())
    .then(html => {
        document.getElementById('comments').innerHTML = html;
        document.getElementById('comment-text').value = '';
    });
}
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
