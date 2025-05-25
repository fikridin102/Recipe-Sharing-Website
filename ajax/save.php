<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['recipe_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$recipe_id = intval($_POST['recipe_id']);

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id FROM saved_recipes WHERE user_id = :user_id AND recipe_id = :recipe_id");
    $stmt->execute(['user_id' => $user_id, 'recipe_id' => $recipe_id]);

    if ($stmt->rowCount() > 0) {
        $delete = $conn->prepare("DELETE FROM saved_recipes WHERE user_id = :user_id AND recipe_id = :recipe_id");
        $delete->execute(['user_id' => $user_id, 'recipe_id' => $recipe_id]);
        echo json_encode(['success' => true, 'saved' => false]);
    } else {
        $insert = $conn->prepare("INSERT INTO saved_recipes (user_id, recipe_id, saved_at) VALUES (:user_id, :recipe_id, NOW())");
        $insert->execute(['user_id' => $user_id, 'recipe_id' => $recipe_id]);
        echo json_encode(['success' => true, 'saved' => true]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
