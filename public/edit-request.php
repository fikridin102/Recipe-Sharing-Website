<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Add database connection
$db = new Database();
$pdo = $db->getConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get request ID from URL
if (!isset($_GET['id'])) {
    header('Location: recipe-requests.php');
    exit();
}

$request_id = $_GET['id'];

// Get request details
$stmt = $pdo->prepare("SELECT * FROM recipe_requests WHERE id = ? AND user_id = ?");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: recipe-requests.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    if (!empty($title) && !empty($description)) {
        $stmt = $pdo->prepare("UPDATE recipe_requests SET title = ?, description = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $description, $status, $request_id, $user_id]);
        header("Location: view-request.php?id=$request_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Recipe Request - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <h1>Edit Recipe Request</h1>
        
        <form method="POST" action="" class="edit-request-form">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($request['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($request['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="open" <?php echo $request['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="fulfilled" <?php echo $request['status'] === 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                    <option value="closed" <?php echo $request['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_request" class="btn btn-primary">Update Request</button>
                <a href="view-request.php?id=<?php echo $request_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html> 