<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    $category = isset($_POST['category']) ? trim($_POST['category']) : 'Lain-lain';
    $image = null;
    $time_duration = isset($_POST['time_duration']) ? intval($_POST['time_duration']) : null;

    // Server-side validation
    if (empty($title) || empty($ingredients) || empty($instructions)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($title) > 100) {
        $error = 'Title must be 100 characters or less.';
    } elseif (strlen($description) > 500) {
        $error = 'Description must be 500 characters or less.';
    } elseif ($time_duration !== null && ($time_duration < 1 || $time_duration > 1440)) {
        $error = 'Time duration must be between 1 and 1440 minutes.';
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $img_name = basename($_FILES['image']['name']);
        $ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed)) {
            $error = 'Only JPG and PNG images are allowed.';
        } else {
            $target_dir = '../assets/images/recipes/';
            $target_file = $target_dir . uniqid() . '_' . $img_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = basename($target_file);
            }
        }
    }

    if (empty($error)) {
        $stmt = $pdo->prepare("INSERT INTO recipes (user_id, title, description, ingredients, instructions, image, category, time_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $description, $ingredients, $instructions, $image, $category, $time_duration]);
        $new_recipe_id = $pdo->lastInsertId();
        // If request_id is present, link this recipe to the request
        if ($request_id) {
            $stmt = $pdo->prepare("INSERT INTO recipe_request_responses (request_id, user_id, recipe_id) VALUES (?, ?, ?)");
            $stmt->execute([$request_id, $user_id, $new_recipe_id]);
            header('Location: view-request.php?id=' . $request_id);
            exit();
        }
        $success = 'Recipe created successfully!';
        header('Location: profile.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Recipe - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Create New Recipe</h3>
                    </div>
                    <div class="card-body">
                        <div id="form-error" class="alert alert-danger" style="display:none"></div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateRecipeForm();">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" maxlength="100" required data-bs-toggle="tooltip" title="Enter a descriptive recipe title (max 100 chars)">
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2" maxlength="500" data-bs-toggle="tooltip" title="Short description (max 500 chars)"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="ingredients" class="form-label">Ingredients <span class="text-danger">*</span> <small class="text-muted">(one per line)</small></label>
                                <textarea class="form-control" id="ingredients" name="ingredients" rows="4" required data-bs-toggle="tooltip" title="List each ingredient on a new line"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="instructions" class="form-label">Instructions <span class="text-danger">*</span> <small class="text-muted">(one step per line)</small></label>
                                <textarea class="form-control" id="instructions" name="instructions" rows="4" required data-bs-toggle="tooltip" title="List each step on a new line"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="Nasi">Nasi</option>
                                    <option value="Mee">Mee</option>
                                    <option value="Lauk">Lauk</option>
                                    <option value="Kuih">Kuih</option>
                                    <option value="Sup">Sup</option>
                                    <option value="Minuman">Minuman</option>
                                    <option value="Pencuci Mulut">Pencuci Mulut</option>
                                    <option value="Lain-lain">Lain-lain</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="time_duration" class="form-label">Time Duration (minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="time_duration" name="time_duration" min="1" max="1440" required data-bs-toggle="tooltip" title="Estimated time to prepare this recipe (in minutes)">
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Recipe Image <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png" data-bs-toggle="tooltip" title="Upload a JPG or PNG image">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create Recipe</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Client-side validation
    function validateRecipeForm() {
        let title = document.getElementById('title');
        let ingredients = document.getElementById('ingredients');
        let instructions = document.getElementById('instructions');
        let image = document.getElementById('image');
        let time_duration = document.getElementById('time_duration');
        let errorMsg = '';

        if (!title.value.trim() || !ingredients.value.trim() || !instructions.value.trim()) {
            errorMsg = 'Please fill in all required fields.';
        } else if (title.value.length > 100) {
            errorMsg = 'Title must be 100 characters or less.';
        } else if (document.getElementById('description').value.length > 500) {
            errorMsg = 'Description must be 500 characters or less.';
        } else if (time_duration.value < 1 || time_duration.value > 1440) {
            errorMsg = 'Time duration must be between 1 and 1440 minutes.';
        } else if (image.value) {
            let ext = image.value.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png'].indexOf(ext) === -1) {
                errorMsg = 'Only JPG and PNG images are allowed.';
            }
        }
        if (errorMsg) {
            document.getElementById('form-error').innerText = errorMsg;
            return false;
        }
        return true;
    }

    // Enable Bootstrap tooltips
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</body>
</html>