<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    $profile_image = $_FILES['profile_image'] ?? null;
    
    try {
        // Check if username or email already exists (excluding current user)
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check_stmt->execute([$username, $email, $user_id]);
        
        if ($check_stmt->fetch()) {
            $error_message = "Username or email already exists!";
        } else {
            $image_filename = null;
            
            // Handle profile image upload
            if ($profile_image && $profile_image['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (in_array($profile_image['type'], $allowed_types) && $profile_image['size'] <= $max_size) {
                    $upload_dir = '../assets/images/profiles/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($profile_image['name'], PATHINFO_EXTENSION);
                    $image_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                    
                    if (move_uploaded_file($profile_image['tmp_name'], $upload_dir . $image_filename)) {
                        // Delete old profile image if it exists and isn't default
                        $old_image_stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
                        $old_image_stmt->execute([$user_id]);
                        $old_image = $old_image_stmt->fetchColumn();
                        
                        if ($old_image && $old_image !== 'default.jpg' && file_exists($upload_dir . $old_image)) {
                            unlink($upload_dir . $old_image);
                        }
                    } else {
                        $error_message = "Failed to upload image. Please try again.";
                    }
                } else {
                    $error_message = "Invalid image format or size. Please upload a JPEG, PNG, or GIF under 5MB.";
                }
            }
            
            if (!$error_message) {
                // Update profile
                if ($image_filename) {
                    $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ?, profile_image = ? WHERE id = ?");
                    $update_stmt->execute([$username, $email, $bio, $image_filename, $user_id]);
                } else {
                    $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?");
                    $update_stmt->execute([$username, $email, $bio, $user_id]);
                }
                
                $_SESSION['username'] = $username;
                $success_message = "Profile updated successfully!";
            }
        }
    } catch (Exception $e) {
        $error_message = "An error occurred while updating your profile.";
    }
}

// Get user data
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get user statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM recipes WHERE user_id = ?) as recipe_count,
    (SELECT COUNT(*) FROM likes l JOIN recipes r ON l.recipe_id = r.id WHERE r.user_id = ?) as total_likes,
    (SELECT COUNT(*) FROM recipe_requests WHERE user_id = ?) as request_count,
    (SELECT COUNT(*) FROM comments WHERE user_id = ?) as comment_count";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get user's recent recipes
$recipes_stmt = $pdo->prepare("SELECT * FROM recipes WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
$recipes_stmt->execute([$user_id]);
$recent_recipes = $recipes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's recent requests
$requests_stmt = $pdo->prepare("SELECT * FROM recipe_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
$requests_stmt->execute([$user_id]);
$recent_requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile - RecipeHub</title>
    
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
            --info-color: #0ea5e9;
            --purple-color: #8b5cf6;
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
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
        }

        .profile-hero {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
            border-radius: var(--border-radius);
            position: relative;
            overflow: hidden;
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        .profile-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            box-shadow: var(--shadow-lg);
            transition: transform 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        .profile-info h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .profile-bio {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
            max-width: 500px;
        }

        .member-since {
            font-size: 0.95rem;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.recipes::before {
            background: linear-gradient(90deg, var(--primary-color), #3b82f6);
        }

        .stat-card.likes::before {
            background: linear-gradient(90deg, var(--danger-color), #f87171);
        }

        .stat-card.requests::before {
            background: linear-gradient(90deg, var(--warning-color), #fbbf24);
        }

        .stat-card.comments::before {
            background: linear-gradient(90deg, var(--success-color), #34d399);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--light-gray);
        }

        .section-title-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .section-title {
            font-size: 1.6rem;
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
            margin-bottom: 1.5rem;
        }

        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .recipe-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .recipe-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .recipe-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .recipe-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .recipe-card:hover .recipe-image {
            transform: scale(1.05);
        }

        .recipe-content {
            padding: 1.25rem;
        }

        .recipe-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
            line-height: 1.4;
        }

        .recipe-meta {
            color: var(--text-muted);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .edit-profile-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 3rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
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
            border-color: var(--purple-color);
            box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.25);
            transform: translateY(-1px);
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .btn-update {
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-update:hover {
            background: linear-gradient(135deg, #7c3aed, var(--purple-color));
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-edit-toggle {
            background: linear-gradient(135deg, var(--info-color), #0284c7);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .btn-edit-toggle:hover {
            background: linear-gradient(135deg, #0284c7, var(--info-color));
            color: white;
        }

        .request-item {
            background: var(--light-gray);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--warning-color);
            transition: all 0.2s ease;
        }

        .request-item:hover {
            background: #e2e8f0;
            transform: translateX(4px);
        }

        .request-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .request-meta {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .file-upload-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload-input {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: var(--light-gray);
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        .file-upload-label:hover {
            background: #e2e8f0;
            border-color: var(--purple-color);
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
            .profile-content {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-info h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .recipe-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Profile Hero Section -->
        <div class="profile-hero fade-in">
            <div class="profile-content">
                <img src="../assets/images/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                     class="profile-avatar" alt="<?php echo htmlspecialchars($user['username']); ?>">
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="profile-bio">
                        <?php echo $user['bio'] ? nl2br(htmlspecialchars($user['bio'])) : 'Welcome to my culinary journey! I love sharing and discovering amazing recipes.'; ?>
                    </p>
                    <div class="member-since">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger fade-in">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid fade-in-delay">
            <div class="stat-card recipes">
                <div class="stat-icon">
                    <i class="fas fa-utensils" style="color: var(--primary-color);"></i>
                </div>
                <div class="stat-number"><?php echo $stats['recipe_count']; ?></div>
                <div class="stat-label">Recipes Shared</div>
            </div>
            <div class="stat-card likes">
                <div class="stat-icon">
                    <i class="fas fa-heart" style="color: var(--danger-color);"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_likes']; ?></div>
                <div class="stat-label">Total Likes</div>
            </div>
            <div class="stat-card requests">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list" style="color: var(--warning-color);"></i>
                </div>
                <div class="stat-number"><?php echo $stats['request_count']; ?></div>
                <div class="stat-label">Requests Made</div>
            </div>
            <div class="stat-card comments">
                <div class="stat-icon">
                    <i class="fas fa-comments" style="color: var(--success-color);"></i>
                </div>
                <div class="stat-number"><?php echo $stats['comment_count']; ?></div>
                <div class="stat-label">Comments Posted</div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Edit Profile Section -->
                <div class="edit-profile-section" id="editProfileSection" style="display: none;">
                    <div class="section-header">
                        <div class="section-title-group">
                            <div class="section-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <h2 class="section-title">Edit Profile</h2>
                        </div>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user text-purple me-2"></i>Username
                                </label>
                                <input type="text" id="username" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope text-purple me-2"></i>Email
                                </label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">
                                <i class="fas fa-align-left text-purple me-2"></i>Bio
                            </label>
                            <textarea id="bio" name="bio" class="form-control" rows="4" 
                                      placeholder="Tell us about yourself, your cooking style, and favorite cuisines..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-camera text-purple me-2"></i>Profile Picture
                            </label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="profile_image" name="profile_image" class="file-upload-input" accept="image/*">
                                <label for="profile_image" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Choose new profile picture...</span>
                                </label>
                            </div>
                            <small class="text-muted">Accepted formats: JPEG, PNG, GIF. Max size: 5MB</small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="update_profile" class="btn-update">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleEditProfile()">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Recent Recipes -->
                <div class="section-header">
                    <div class="section-title-group">
                        <div class="section-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h2 class="section-title">My Recipes</h2>
                    </div>
                    <a href="index.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>View All
                    </a>
                </div>

                <?php if (empty($recent_recipes)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                            <h5>No Recipes Yet</h5>
                            <p class="text-muted">Start sharing your favorite recipes with the community!</p>
                            <a href="create-recipe.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Your First Recipe
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="recipe-grid">
                <?php foreach ($recent_recipes as $recipe): ?>
                    <div class="recipe-card">
                        <img src="../assets/images/recipes/<?php echo htmlspecialchars($recipe['image'] ?? 'default.jpg'); ?>" class="recipe-image" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                        <div class="recipe-content">
                            <div class="recipe-title"><?php echo htmlspecialchars($recipe['title']); ?></div>
                            <div class="recipe-meta">
                                <span><i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($recipe['cook_time']); ?> mins</span>
                                <span><i class="fas fa-calendar-alt me-1"></i><?php echo date('M j, Y', strtotime($recipe['created_at'])); ?></span>
                            </div>
                            <a href="recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-outline-primary btn-sm mt-2">
                                <i class="fas fa-eye me-1"></i>View Recipe
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <!-- End of recipe-grid and My Recipes section -->
                
                <!-- Recent Requests Section -->
                <div class="section-header mt-5">
                    <div class="section-title-group">
                        <div class="section-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h2 class="section-title">My Requests</h2>
                    </div>
                    <a href="recipe-requests.php" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-eye me-1"></i>View All
                    </a>
                </div>
                <?php if (empty($recent_requests)): ?>
                    <p class="text-muted mb-0">No requests made yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_requests as $request): ?>
                        <div class="request-item">
                            <div class="request-title"><?php echo htmlspecialchars($request['title']); ?></div>
                            <div class="request-meta">
                                <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Achievement Badges -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-trophy text-warning me-2"></i>Achievements
                        </h5>
                        <div class="row g-2">
                            <?php if ($stats['recipe_count'] >= 1): ?>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <i class="fas fa-star text-warning fa-2x mb-1"></i>
                                        <div class="small fw-bold">First Recipe</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($stats['recipe_count'] >= 5): ?>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <i class="fas fa-fire text-danger fa-2x mb-1"></i>
                                        <div class="small fw-bold">Recipe Master</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($stats['total_likes'] >= 10): ?>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <i class="fas fa-heart text-danger fa-2x mb-1"></i>
                                        <div class="small fw-bold">Popular Chef</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($stats['comment_count'] >= 25): ?>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <i class="fas fa-comments text-primary fa-2x mb-1"></i>
                                        <div class="small fw-bold">Community Helper</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (empty(array_filter([$stats['recipe_count'] >= 1, $stats['recipe_count'] >= 5, $stats['total_likes'] >= 10, $stats['comment_count'] >= 25]))): ?>
                                <div class="col-12">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="fas fa-medal text-muted fa-2x mb-2"></i>
                                        <div class="small text-muted">Start cooking to earn achievements!</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Activity Summary -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chart-line text-info me-2"></i>Activity Summary
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h4 text-primary mb-1"><?php echo $stats['recipe_count']; ?></div>
                                    <div class="small text-muted">Recipes</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h4 text-danger mb-1"><?php echo $stats['total_likes']; ?></div>
                                    <div class="small text-muted">Likes</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h4 text-warning mb-1"><?php echo $stats['request_count']; ?></div>
                                    <div class="small text-muted">Requests</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h4 text-success mb-1"><?php echo $stats['comment_count']; ?></div>
                                    <div class="small text-muted">Comments</div>
                                </div>
                            </div>
                        </div>
                        
                        <?php 
                        $total_activity = $stats['recipe_count'] + $stats['request_count'] + $stats['comment_count'];
                        $activity_level = $total_activity >= 50 ? 'Very Active' : ($total_activity >= 20 ? 'Active' : ($total_activity >= 5 ? 'Getting Started' : 'New Member'));
                        $activity_color = $total_activity >= 50 ? 'success' : ($total_activity >= 20 ? 'primary' : ($total_activity >= 5 ? 'warning' : 'secondary'));
                        ?>
                        
                        <div class="mt-3 pt-3 border-top">
                            <div class="text-center">
                                <span class="badge bg-<?php echo $activity_color; ?> fs-6">
                                    <?php echo $activity_level; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Tips -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-lightbulb text-warning me-2"></i>Profile Tips
                        </h5>
                        <div class="small">
                            <div class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Complete your bio</strong> to tell others about your cooking style
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Upload a profile picture</strong> to make your profile more personal
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Share your first recipe</strong> to start building your collection
                            </div>
                            <div class="mb-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Engage with others</strong> by commenting and liking recipes
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        function toggleEditProfile() {
            const editSection = document.getElementById('editProfileSection');
            const isVisible = editSection.style.display !== 'none';
            
            if (isVisible) {
                editSection.style.display = 'none';
                // Scroll to top of page
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                editSection.style.display = 'block';
                // Scroll to edit section
                editSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // File upload preview
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const label = document.querySelector('.file-upload-label span');
            
            if (file) {
                label.textContent = file.name;
                label.style.color = 'var(--success-color)';
            } else {
                label.textContent = 'Choose new profile picture...';
                label.style.color = '';
            }
        });

        // Add hover effects to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Animate numbers on page load
        document.addEventListener('DOMContentLoaded', function() {
            const numbers = document.querySelectorAll('.stat-number');
            
            numbers.forEach(number => {
                const target = parseInt(number.textContent);
                let current = 0;
                const increment = target / 50;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        number.textContent = target;
                        clearInterval(timer);
                    } else {
                        number.textContent = Math.floor(current);
                    }
                }, 30);
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
<?php include '../includes/footer.php'; ?> 
</body>
</html>