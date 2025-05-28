<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Handle recipe deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipe_id'])) {
    $delete_id = intval($_POST['delete_recipe_id']);
    // Only allow deleting own recipes
    $stmt = $conn->prepare("DELETE FROM recipes WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(":id", $delete_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    header("Location: profile.php");
    exit();
}

// Get user profile
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's recipes
$query = "SELECT * FROM recipes WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get saved recipes
$query = "
    SELECT r.*, s.saved_at
    FROM saved_recipes s
    JOIN recipes r ON s.recipe_id = r.id
    WHERE s.user_id = :user_id
    ORDER BY s.saved_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$saved_recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent messages
$query = "SELECT m.*, u.username AS sender_name 
          FROM messages m 
          JOIN users u ON m.sender_id = u.id 
          WHERE m.receiver_id = :user_id 
          ORDER BY m.sent_at DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get friend and recipe count
$query = "SELECT COUNT(*) as friend_count FROM friends WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$friend_count = $stmt->fetch(PDO::FETCH_ASSOC)['friend_count'];

$query = "SELECT COUNT(*) as recipe_count FROM recipes WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$recipe_count = $stmt->fetch(PDO::FETCH_ASSOC)['recipe_count'];

// Handle profile update
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $bio = sanitizeInput($_POST['bio']);
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';

    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_result = uploadImage($_FILES['profile_image'], '../assets/images/profiles/');
        if ($upload_result) {
            $profile_image = basename($upload_result);
        }
    }

    $query = "UPDATE users SET username = :username, bio = :bio, gender = :gender, profile_image = :profile_image WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":bio", $bio);
    $stmt->bindParam(":gender", $gender);
    $stmt->bindParam(":profile_image", $profile_image);
    $stmt->bindParam(":user_id", $user_id);

    if ($stmt->execute()) {
        $success = 'Profile updated successfully!';
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = 'Failed to update profile. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - RecipeHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- Profile Summary -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" class="profile-image mb-3" alt="Profile Image" width="120" style="border-radius: 50%;">
                    <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p class="text-muted">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    <p><strong>Gender:</strong> <?php echo !empty($user['gender']) ? htmlspecialchars($user['gender']) : 'Not specified'; ?></p>

                    <div class="row text-center mt-3">
                        <div class="col">
                            <h5><?php echo $recipe_count; ?></h5>
                            <small class="text-muted">Recipes</small>
                        </div>
                        <div class="col">
                            <h5><?php echo $friend_count; ?></h5>
                            <small class="text-muted">Friends</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Edit -->
            <div class="card">
                <div class="card-header"><h5>Edit Profile</h5></div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select name="gender" id="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php if ($user['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                                <option value="Female" <?php if ($user['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                                <option value="Other" <?php if ($user['gender'] == 'Other') echo 'selected'; ?>>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea name="bio" id="bio" class="form-control" rows="3"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <input type="file" name="profile_image" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- My Recipes -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">My Recipes</h4>
                    <a href="create-recipe.php" class="btn btn-success">Create New Recipe</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recipes)): ?><p class="text-center text-muted">You haven't created any recipes yet.</p><?php endif; ?>
                    <?php foreach ($recipes as $recipe): ?>
                        <div class="card mb-3">
                            <?php if ($recipe['image']): ?>
                                <img src="../assets/images/recipes/<?php echo htmlspecialchars($recipe['image']); ?>" class="card-img-top">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($recipe['description'], 0, 150))); ?>...</p>
                                <div class="d-flex gap-2">
                                    <a href="recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-primary">View</a>
                                    <a href="edit-recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-warning">Edit</a>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $recipe['id']; ?>">Delete</button>
                                </div>
                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $recipe['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $recipe['id']; ?>" aria-hidden="true">
                                  <div class="modal-dialog">
                                    <div class="modal-content">
                                      <div class="modal-header">
                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $recipe['id']; ?>">Delete Recipe</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                      </div>
                                      <div class="modal-body">
                                        Are you sure you want to delete "<strong><?php echo htmlspecialchars($recipe['title']); ?></strong>"?
                                      </div>
                                      <div class="modal-footer">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="delete_recipe_id" value="<?php echo $recipe['id']; ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                                <!-- End Modal -->
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Saved Recipes -->
            <!-- Saved Recipes -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Saved Recipes</h4>
    </div>
    <div class="card-body">
        <?php if (empty($saved_recipes)): ?>
            <p class="text-center text-muted">You haven't saved any recipes yet.</p>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
                <?php foreach ($saved_recipes as $recipe): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm border-0 recipe-card">
                            <?php if ($recipe['image']): ?>
                                <img src="../assets/images/recipes/<?php echo htmlspecialchars($recipe['image']); ?>" 
                                     class="card-img-top" 
                                     alt="Recipe Image" 
                                     style="height: 180px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                                <p class="card-text text-muted" style="flex-grow: 1;">
                                    <?php echo nl2br(htmlspecialchars(substr($recipe['description'], 0, 100))); ?>...
                                </p>
                                <a href="recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-outline-primary mt-auto">View Recipe</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


            <!-- Recent Messages -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Recent Messages</h4>
                    <a href="messages.php" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($messages)): ?><p class="text-center text-muted">No messages received yet.</p><?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($messages as $msg): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($msg['sender_name']); ?>:</strong>
                                    <?php echo htmlspecialchars(substr($msg['message'], 0, 50)); ?>...<br>
                                    <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($msg['sent_at'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>
</body>
</html>

-- Run this SQL in your MySQL database (e.g., via phpMyAdmin or MySQL CLI)

CREATE TABLE `saved_recipes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `recipe_id` INT NOT NULL,
    `saved_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recipe_id`) REFERENCES `recipes`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `user_recipe_unique` (`user_id`, `recipe_id`)
);
