<?php
require_once __DIR__ . '/../config/database.php'; // Note: adjusted path

class FeedbackModel {
    private $conn;
    private $pdo;

    public function submitFeedback($recipe_id, $user_id, $rating, $comment, $tags)
    {
        $sql = "INSERT INTO feedback (recipe_id, user_id, rating, comment, labels, created_at, updated_at)
                VALUES (:recipe_id, :user_id, :rating, :comment, :labels, NOW(), NOW())";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':recipe_id' => $recipe_id,
            ':user_id'   => $user_id,
            ':rating'    => $rating,
            ':comment'   => $comment,
            ':labels'    => $tags
        ]);
    }

    
    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
            
            if (!$this->conn) {
                throw new Exception("Failed to establish database connection");
            }
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function insertFeedback($recipe_id, $user_id, $rating, $comment, $labels) {
        
        try {
            $stmt = $this->conn->prepare("INSERT INTO feedback (recipe_id, user_id, rating, comment, labels, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . implode(" ", $this->conn->errorInfo()));
            }
            
            $result = $stmt->execute([$recipe_id, $user_id, $rating, $comment, $labels]);
            
            if (!$result) {
                throw new Exception("Failed to execute statement: " . implode(" ", $stmt->errorInfo()));
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Database error in insertFeedback: " . $e->getMessage());
            throw new Exception("Database error occurred");
        }
    }
    
    public function getFeedbacksByRecipe($recipe_id) {
        try {
            $stmt = $this->conn->prepare("SELECT f.*, u.username
                                  FROM feedback f
                                  JOIN users u ON f.user_id = u.id
                                  WHERE f.recipe_id = ?
                                  ORDER BY f.created_at DESC");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            
            $stmt->execute([$recipe_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Database error in getFeedbacksByRecipe: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAverageRating($recipe_id) {
        try {
            $stmt = $this->conn->prepare("SELECT AVG(rating) AS avg_rating FROM feedback WHERE recipe_id = ?");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            
            $stmt->execute([$recipe_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['avg_rating'] ? round($row['avg_rating'], 1) : null;
            
        } catch (PDOException $e) {
            error_log("Database error in getAverageRating: " . $e->getMessage());
            return null;
        }
    }
}