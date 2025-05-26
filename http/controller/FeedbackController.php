<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/feedback_model.php';

class FeedbackController {
    private $model;

    public function __construct() {
        $this->model = new FeedbackModel();
    }

    public function submitFeedback() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("DEBUG: Incoming POST => " . print_r($_POST, true));

            $data = $_POST;

            $recipe_id = isset($data['recipe_id']) ? intval($data['recipe_id']) : 0;
            $user_id   = isset($data['user_id']) ? intval($data['user_id']) : 0;
            $rating    = isset($data['rating']) ? intval($data['rating']) : 0;
            $tags      = isset($data['tags']) ? trim($data['tags']) : '';
            $comment   = isset($data['comment']) ? trim($data['comment']) : '';

            // Validation
            if ($recipe_id === 0 || $user_id === 0 || $rating === 0 || empty($comment)) {
                $_SESSION['feedback_error'] = "Invalid input. All fields must be filled in.";
                $_SESSION['feedback_debug'] = "recipe_id=$recipe_id, user_id=$user_id, rating=$rating, comment=" . htmlspecialchars($comment);
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }

            try {
                $success = $this->model->insertFeedback($recipe_id, $user_id, $rating, $comment, $tags);
                if ($success) {
                    header("Location: rate_form.php?id=$recipe_id&success=1");
                    exit;
                } else {
                    $_SESSION['feedback_error'] = "Failed to submit feedback. Please try again.";
                    $_SESSION['feedback_debug'] = "Insert returned false.";
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['feedback_error'] = "An unexpected error occurred.";
                $_SESSION['feedback_debug'] = $e->getMessage();
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }
        }
    }


    public function getAverageRating(int $recipe_id) {
        return $this->model->getAverageRating($recipe_id);
    }

    public function getFeedbacks(int $recipe_id) {
        return $this->model->getFeedbacksByRecipe($recipe_id);
    }
}
?>