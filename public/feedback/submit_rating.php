<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/feedback_model.php';
file_put_contents('debug.log', print_r($_POST, true), FILE_APPEND);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_id = $_POST['recipe_id'] ?? null;
    $recipe_id = is_numeric($recipe_id) ? (int)$recipe_id : null;

    $rating = $_POST['rating'] ?? null;
    $comment = trim($_POST['comment'] ?? '');

    if (!isset($_SESSION['user_id'])) {
        echo "You must be logged in to submit feedback.";
        exit();
    }

    $user_id = $_SESSION['user_id'];

    if ($recipe_id && $rating) {
    FeedbackModel::insertFeedback($user_id, $recipe_id, $rating, $comment);
    header("Location: " . dirname($_SERVER['PHP_SELF']) . "/rate_success.php");
    exit();
} else {
    header("Location: " . dirname($_SERVER['PHP_SELF']) . "/rate_form.php?error=missing_fields&recipe_id=" . $recipe_id);
    exit();
}

    if (!$recipe_id || !$rating) {
        header("Location: rate_form.php?error=missing_fields&recipe_id=" . $recipe_id);
        exit();
    }

    
}
?>
