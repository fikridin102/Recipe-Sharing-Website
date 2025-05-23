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

// Handle form submission for new recipe request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (!empty($title) && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO recipe_requests (user_id, title, description) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $description]);
        header('Location: recipe-requests.php');
        exit();
    }
}

// Get all recipe requests
$stmt = $pdo->query("
    SELECT r.*, u.username, 
           (SELECT COUNT(*) FROM recipe_request_responses WHERE request_id = r.id) as response_count
    FROM recipe_requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Requests - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <h1>Recipe Requests</h1>
        
        <!-- Create new request form -->
        <section class="create-request mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0" style="font-size:1.3rem;">Create New Recipe Request</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" name="create_request" class="btn btn-primary w-100">Submit Request</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- List of recipe requests -->
        <section class="requests-list">
            <h2>Recent Requests</h2>
            <?php if (empty($requests)): ?>
                <p>No recipe requests found.</p>
            <?php else: ?>
                <?php foreach ($requests as $request): ?>
                    <div class="request-card">
                        <h3><?php echo htmlspecialchars($request['title']); ?></h3>
                        <p class="request-meta">
                            Requested by <?php echo htmlspecialchars($request['username']); ?> • 
                            <?php echo date('M j, Y', strtotime($request['created_at'])); ?> •
                            <?php echo $request['response_count']; ?> responses
                        </p>
                        <p class="request-description"><?php echo htmlspecialchars($request['description']); ?></p>
                        <div class="request-actions">
                            <a href="view-request.php?id=<?php echo $request['id']; ?>" class="btn btn-secondary">View Details</a>
                            <?php if ($request['user_id'] === $user_id): ?>
                                <a href="edit-request.php?id=<?php echo $request['id']; ?>" class="btn btn-secondary">Edit</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html> 