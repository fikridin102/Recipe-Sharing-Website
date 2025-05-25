<?php
require_once __DIR__ . '/../../model/feedback_model.php';

class FeedbackController {
    public function store() {
        $result = FeedbackModel::insertFeedback($_POST['recipe_id'], $_POST['user_name'], $_POST['user_email'], $_POST['comment'], $_POST['rating']);
        if ($result) {
            header('Location: /views/feedback/rate_success.php');
        } else {
            echo "Something went wrong.";
        }
    }

    public function index() {
        $feedback = FeedbackModel::getAllFeedback();
        include __DIR__ . '/../../views/feedback/all_review.php';
    }


}
?>