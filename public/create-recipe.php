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

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    $category = trim($_POST['category']);
    $user_id = $_SESSION['user_id'];
    
    // Basic validation
    if (empty($title) || empty($ingredients) || empty($instructions)) {
        $error = 'Title, ingredients, and instructions are required.';
    } else {
        // Handle image upload
        $image_name = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/recipe-hub/assets/images/recipes/';
                // Fallback to user's home directory if the default location is not writable
                if (!is_writable($upload_dir)) {
                    $upload_dir = $_SERVER['HOME'] . '/recipe-images/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                }
                
                // Check if directory is writable
                if (!is_writable($upload_dir)) {
                    $error = 'Upload directory is not writable. Please contact the administrator.';
                    error_log('Directory not writable: ' . $upload_dir);
                    $image_name = null;
                } else {
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $image_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $image_name;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $error = 'Failed to upload image. Error: ' . error_get_last()['message'];
                        error_log('Image upload failed: ' . error_get_last()['message'] . ' - Upload path: ' . $upload_path);
                        $image_name = null;
                    }
                }
            } else {
                $error = 'Invalid image file. Please upload JPG, PNG, or GIF files under 5MB.';
            }
        }
        
        // Insert recipe if no errors
        if (empty($error)) {
            try {
                $query = "INSERT INTO recipes (user_id, title, description, ingredients, instructions, image, category) 
                         VALUES (:user_id, :title, :description, :ingredients, :instructions, :image, :category)";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':ingredients', $ingredients);
                $stmt->bindParam(':instructions', $instructions);
                $stmt->bindParam(':image', $image_name);
                $stmt->bindParam(':category', $category);
                
                if ($stmt->execute()) {
                    $success = 'Recipe created successfully!';
                    // Redirect to the new recipe
                    $recipe_id = $conn->lastInsertId();
                    header("Location: recipe.php?id=$recipe_id");
                    exit();
                } else {
                    $error = 'Failed to create recipe. Please try again.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get categories for dropdown
$categories = [
    'Lain-lain', 'Appetizer', 'Main Course', 'Dessert', 'Breakfast', 
    'Lunch', 'Dinner', 'Snack', 'Beverage', 'Salad', 'Soup', 
    'Vegetarian', 'Vegan', 'Gluten-Free'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Recipe - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
        }
        .form-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section-title {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="form-container">
            <div class="d-flex align-items-center mb-4">
                <a href="index.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h2 class="mb-0">
                    <i class="fas fa-plus-circle text-success me-2"></i>
                    Create New Recipe
                </h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- Basic Information -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Basic Information
                    </h4>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">
                            Recipe Title <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                               placeholder="Enter a catchy recipe title" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Briefly describe your recipe..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>" 
                                            <?php echo (isset($_POST['category']) && $_POST['category'] == $cat) ? 'selected' : ''; ?>>
                                        <?php echo $cat; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="image" class="form-label">Recipe Image</label>
                            <input type="file" class="form-control" id="image" name="image" 
                                   accept="image/jpeg,image/png,image/gif" onchange="previewImage(this)">
                            <div class="form-text">Upload JPG, PNG, or GIF (max 5MB)</div>
                            <img id="imagePreview" class="image-preview" style="display: none;">
                        </div>
                    </div>
                </div>

                <!-- Ingredients -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Ingredients <span class="required">*</span>
                    </h4>
                    <textarea class="form-control" id="ingredients" name="ingredients" rows="8" 
                              placeholder="List each ingredient on a new line. Example:&#10;2 cups all-purpose flour&#10;1 tsp baking powder&#10;1/2 cup sugar&#10;2 large eggs" required><?php echo isset($_POST['ingredients']) ? htmlspecialchars($_POST['ingredients']) : ''; ?></textarea>
                    <div class="form-text">
                        <i class="fas fa-lightbulb text-warning me-1"></i>
                        Tip: List each ingredient on a separate line with measurements
                    </div>
                </div>

                <!-- Instructions -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-list-ol me-2"></i>
                        Instructions <span class="required">*</span>
                    </h4>
                    <textarea class="form-control" id="instructions" name="instructions" rows="10" 
                              placeholder="Write each step on a new line. Example:&#10;Preheat oven to 350°F (175°C)&#10;Mix dry ingredients in a large bowl&#10;Beat eggs and add to dry mixture&#10;Bake for 25-30 minutes" required><?php echo isset($_POST['instructions']) ? htmlspecialchars($_POST['instructions']) : ''; ?></textarea>
                    <div class="form-text">
                        <i class="fas fa-lightbulb text-warning me-1"></i>
                        Tip: Write clear, step-by-step instructions. Each line will be numbered automatically.
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex gap-3 justify-content-end mb-4">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Create Recipe
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        // Auto-resize textareas
        document.addEventListener('DOMContentLoaded', function() {
            const textareas = document.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = this.scrollHeight + 'px';
                });
            });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const ingredients = document.getElementById('ingredients').value.trim();
            const instructions = document.getElementById('instructions').value.trim();
            
            if (!title || !ingredients || !instructions) {
                e.preventDefault();
                alert('Please fill in all required fields (Title, Ingredients, and Instructions).');
                return false;
            }
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>