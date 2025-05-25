<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['recipe_id']) || !isset($_POST['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or missing data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$recipe_id = intval($_POST['recipe_id']);
$comment = trim($_POST['comment']);

if ($comment === '') {
    echo json_encode(['success' => false, 'message' => 'Empty comment']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $insert = $conn->prepare("INSERT INTO comments (user_id, recipe_id, comment, commented_at) VALUES (:user_id, :recipe_id, :comment, NOW())");
    $insert->execute([
        'user_id' => $user_id,
        'recipe_id' => $recipe_id,
        'comment' => $comment
    ]);

    echo json_encode(['success' => true, 'message' => 'Comment posted']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
