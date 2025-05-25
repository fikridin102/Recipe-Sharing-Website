<?php
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$pdo = $database->getConnection();
class FeedbackModel {
    public static function insertFeedback($user_id, $recipe_id, $rating, $comment) {
    global $pdo;

    $sql = "INSERT INTO feedback (user_id, recipe_id, rating, comment, created_at) 
            VALUES (:user_id, :recipe_id, :rating, :comment, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':recipe_id' => $recipe_id,
        ':rating' => $rating,
        ':comment' => $comment
    ]);
}


    public static function getAllFeedback() {
        global $pdo;
        $stmt = $pdo->query("SELECT f.*, r.title AS recipe_title FROM feedback f JOIN recipe r ON f.recipe_id = r.id ORDER BY f.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAverageRating($recipe_id) {
        global $pdo;

        $sql = "SELECT AVG(rating) as avg_rating FROM feedback WHERE recipe_id = :recipe_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':recipe_id' => $recipe_id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return round($row['avg_rating'], 1);
    }
}
?>