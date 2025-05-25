<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['recipe_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to like recipes']);
    exit();
}

$user_id = $_SESSION['user_id'];
$recipe_id = intval($_POST['recipe_id']);

if ($recipe_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid recipe ID']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // First verify the recipe exists
    $check_recipe = $conn->prepare("SELECT id FROM recipes WHERE id = :recipe_id");
    $check_recipe->execute(['recipe_id' => $recipe_id]);
    if ($check_recipe->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Recipe not found']);
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = :user_id AND recipe_id = :recipe_id");
    $stmt->execute(['user_id' => $user_id, 'recipe_id' => $recipe_id]);

    if ($stmt->rowCount() > 0) {
        $delete = $conn->prepare("DELETE FROM likes WHERE user_id = :user_id AND recipe_id = :recipe_id");
        $delete->execute(['user_id' => $user_id, 'recipe_id' => $recipe_id]);
        
        // Get updated like count
        $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE recipe_id = :recipe_id");
        $count_stmt->execute(['recipe_id' => $recipe_id]);
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode(['success' => true, 'liked' => false, 'count' => $count]);
    } else {
        $insert = $conn->prepare("INSERT INTO likes (user_id, recipe_id, liked_at) VALUES (:user_id, :recipe_id, NOW())");
        $insert->execute(['user_id' => $user_id, 'recipe_id' => $recipe_id]);
        
        // Get updated like count
        $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE recipe_id = :recipe_id");
        $count_stmt->execute(['recipe_id' => $recipe_id]);
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode(['success' => true, 'liked' => true, 'count' => $count]);
    }
} catch (PDOException $e) {
    error_log("Like error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
}
