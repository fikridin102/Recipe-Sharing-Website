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
$stmt = $pdo->prepare("
    SELECT r.*, u.username
    FROM recipe_requests r
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: recipe-requests.php');
    exit();
}

// Handle form submission for new recipe response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_recipe'])) {
    $recipe_id = $_POST['recipe_id'];
    
    // Check if recipe exists and belongs to the user
    $stmt = $pdo->prepare("SELECT id FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->execute([$recipe_id, $user_id]);
    if ($stmt->fetch()) {
        // Add response
        $stmt = $pdo->prepare("INSERT INTO recipe_request_responses (request_id, user_id, recipe_id) VALUES (?, ?, ?)");
        $stmt->execute([$request_id, $user_id, $recipe_id]);
        header("Location: view-request.php?id=$request_id");
        exit();
    }
}

// Get all responses for this request
$stmt = $pdo->prepare("
    SELECT r.*, u.username, resp.created_at as response_date
    FROM recipe_request_responses resp
    JOIN recipes r ON resp.recipe_id = r.id
    JOIN users u ON r.user_id = u.id
    WHERE resp.request_id = ?
    ORDER BY resp.created_at DESC
");
$stmt->execute([$request_id]);
$responses = $stmt->fetchAll();

// Get user's recipes for the response form
$stmt = $pdo->prepare("SELECT id, title FROM recipes WHERE user_id = ? ORDER BY title");
$stmt->execute([$user_id]);
$user_recipes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($request['title']); ?> - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="request-details">
            <h1><?php echo htmlspecialchars($request['title']); ?></h1>
            <p class="request-meta">
                Requested by <?php echo htmlspecialchars($request['username']); ?> • 
                <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
            </p>
            <div class="request-description">
                <?php echo nl2br(htmlspecialchars($request['description'])); ?>
            </div>
        </div>

        <?php if ($request['user_id'] !== $user_id): ?>
            <section class="submit-response">
                <h2>Submit Your Recipe</h2>
                <a href="create-recipe.php?request_id=<?php echo $request_id; ?>" class="btn btn-success mb-3">Add New Recipe for This Request</a>
                
            </section>
        <?php endif; ?>

        <section class="responses">
            <h2>Submitted Recipes</h2>
            <?php if (empty($responses)): ?>
                <p>No recipes have been submitted yet.</p>
            <?php else: ?>
                <?php foreach ($responses as $response): ?>
                    <div class="response-card">
                        <h3><?php echo htmlspecialchars($response['title']); ?></h3>
                        <p class="response-meta">
                            Submitted by <?php echo htmlspecialchars($response['username']); ?> • 
                            <?php echo date('M j, Y', strtotime($response['response_date'])); ?>
                        </p>
                        <p class="response-description"><?php echo htmlspecialchars($response['description']); ?></p>
                        <div class="response-actions">
                            <a href="view-recipe.php?id=<?php echo $response['id']; ?>" class="btn btn-secondary">View Recipe</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html> 