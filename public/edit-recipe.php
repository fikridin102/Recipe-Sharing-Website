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

if (!isset($_GET['id'])) {
    header('Location: profile.php');
    exit();
}

$recipe_id = intval($_GET['id']);

// Fetch recipe and check ownership
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ?");
$stmt->execute([$recipe_id, $user_id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    header('Location: profile.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    $category = isset($_POST['category']) ? trim($_POST['category']) : 'Lain-lain';
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
    } else {
        $image = $recipe['image'];
    }

    if (empty($error)) {
        $stmt = $pdo->prepare("UPDATE recipes SET title = ?, description = ?, ingredients = ?, instructions = ?, image = ?, category = ?, time_duration = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $description, $ingredients, $instructions, $image, $category, $time_duration, $recipe_id, $user_id]);
        $success = 'Recipe updated successfully!';
        // Refresh recipe data
        $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ?");
        $stmt->execute([$recipe_id, $user_id]);
        $recipe = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Recipe - RecipeHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
    function validateEditForm() {
        let title = document.getElementById('title');
        let ingredients = document.getElementById('ingredients');
        let instructions = document.getElementById('instructions');
        let image = document.getElementById('image');
        let timeDuration = document.getElementById('time_duration');
        let errorMsg = '';

        if (!title.value.trim() || !ingredients.value.trim() || !instructions.value.trim()) {
            errorMsg = 'Please fill in all required fields.';
        } else if (title.value.length > 100) {
            errorMsg = 'Title must be 100 characters or less.';
        } else if (document.getElementById('description').value.length > 500) {
            errorMsg = 'Description must be 500 characters or less.';
        } else if (timeDuration.value < 1 || timeDuration.value > 1440) {
            errorMsg = 'Time duration must be between 1 and 1440 minutes.';
        } else if (image.value) {
            let ext = image.value.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png'].indexOf(ext) === -1) {
                errorMsg = 'Only JPG and PNG images are allowed.';
            }
        }
        if (errorMsg) {
            document.getElementById('form-error').innerText = errorMsg;
            document.getElementById('form-error').style.display = 'block';
            return false;
        }
        document.getElementById('form-error').style.display = 'none';
        return true;
    }
    </script>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0">Edit Recipe</h3>
                </div>
                <div class="card-body">
                    <div id="form-error" class="alert alert-danger" style="display:none"></div>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data" onsubmit="return validateEditForm();">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" maxlength="100" required data-bs-toggle="tooltip" title="Enter a descriptive recipe title (max 100 chars)" value="<?php echo htmlspecialchars($recipe['title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2" maxlength="500" data-bs-toggle="tooltip" title="Short description (max 500 chars)"><?php echo htmlspecialchars($recipe['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="ingredients" class="form-label">Ingredients <span class="text-danger">*</span> <small class="text-muted">(one per line)</small></label>
                            <textarea class="form-control" id="ingredients" name="ingredients" rows="4" required data-bs-toggle="tooltip" title="List each ingredient on a new line"><?php echo htmlspecialchars($recipe['ingredients']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="instructions" class="form-label">Instructions <span class="text-danger">*</span> <small class="text-muted">(one step per line)</small></label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="4" required data-bs-toggle="tooltip" title="List each step on a new line"><?php echo htmlspecialchars($recipe['instructions']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <?php
                                $categories = ['Nasi','Mee','Lauk','Kuih','Sup','Minuman','Pencuci Mulut','Lain-lain'];
                                foreach ($categories as $cat):
                                ?>
                                    <option value="<?php echo $cat; ?>" <?php if ($recipe['category'] === $cat) echo 'selected'; ?>>
                                        <?php echo $cat; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="time_duration" class="form-label">Time Duration (minutes) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="time_duration" name="time_duration" min="1" max="1440" required data-bs-toggle="tooltip" title="Estimated time to prepare this recipe (in minutes)" value="<?php echo htmlspecialchars($recipe['time_duration'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Recipe Image</label>
                            <?php if ($recipe['image']): ?>
                                <div class="mb-2">
                                    <img src="../assets/images/recipes/<?php echo htmlspecialchars($recipe['image']); ?>" alt="Recipe Image" style="height:100px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png" data-bs-toggle="tooltip" title="Upload a JPG or PNG image">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">Update Recipe</button>
                            <a href="profile.php" class="btn btn-secondary">Back to Profile</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
</body>
</html>
