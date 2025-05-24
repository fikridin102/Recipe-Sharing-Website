<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get user profile
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's recipes 
$query = "SELECT * FROM recipes WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get friend count
$query = "SELECT COUNT(*) as friend_count FROM friends WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$friend_count = $stmt->fetch(PDO::FETCH_ASSOC)['friend_count'];

// Get recipe count
$query = "SELECT COUNT(*) as recipe_count FROM recipes WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$recipe_count = $stmt->fetch(PDO::FETCH_ASSOC)['recipe_count'];

// Handle profile update
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $bio = sanitizeInput($_POST['bio']);
    
    // Handle profile image upload
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_result = uploadImage($_FILES['profile_image'], '../assets/images/profiles/');
        if ($upload_result) {
            $profile_image = basename($upload_result);
        }
    }
    
    $query = "UPDATE users SET username = :username, bio = :bio, profile_image = :profile_image 
              WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":bio", $bio);
    $stmt->bindParam(":profile_image", $profile_image);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success = 'Profile updated successfully!';
        // Refresh user data
        $query = "SELECT * FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="../assets/images/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                             class="profile-image mb-3" alt="Profile Image">
                        <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                        <p class="text-muted">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                        
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
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" 
                                       accept="image/*" onchange="previewImage(this, 'profilePreview')">
                                <img id="profilePreview" src="#" alt="Preview" style="display: none; max-width: 100%; margin-top: 10px;">
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">My Recipes</h4>
                        <a href="create-recipe.php" class="btn btn-success">Create New Recipe</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recipes)): ?>
                            <p class="text-center text-muted">You haven't created any recipes yet.</p>
                        <?php else: ?>
                            <?php foreach ($recipes as $recipe): ?>
                                <div class="card mb-3 recipe-card">
                                    <?php if ($recipe['image']): ?>
                                        <img src="../assets/images/recipes/<?php echo htmlspecialchars($recipe['image']); ?>" 
                                             class="card-img-top" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($recipe['description'], 0, 150))); ?>...</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-primary">View Recipe</a>
                                            <small class="text-muted">
                                                Posted on <?php echo date('F j, Y', strtotime($recipe['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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