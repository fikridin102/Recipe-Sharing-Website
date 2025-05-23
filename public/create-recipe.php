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

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $img_name = basename($_FILES['image']['name']);
        $target_dir = '../assets/images/recipes/';
        $target_file = $target_dir . uniqid() . '_' . $img_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image = basename($target_file);
        }
    }

    if (empty($title) || empty($ingredients) || empty($instructions)) {
        $error = 'Title, ingredients, and instructions are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO recipes (user_id, title, description, ingredients, instructions, image, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $description, $ingredients, $instructions, $image, $category]);
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
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="ingredients" class="form-label">Ingredients <small class="text-muted">(one per line)</small></label>
                                <textarea class="form-control" id="ingredients" name="ingredients" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="instructions" class="form-label">Instructions <small class="text-muted">(one step per line)</small></label>
                                <textarea class="form-control" id="instructions" name="instructions" rows="4" required></textarea>
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
                                <label for="image" class="form-label">Recipe Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
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
</body>
</html> 