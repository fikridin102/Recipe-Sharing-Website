<?php 
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/feedback_model.php';

$success_message = false;

// Handle form submission first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Validate session
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }

    // Validate inputs
    $recipe_id = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);
    $comment = trim($_POST['comment'] ?? '');

    if (!$recipe_id || !$rating) {
        $error_message = "Please select a rating before submitting.";
    } else {
        // Save feedback
        FeedbackModel::insertFeedback($_SESSION['user_id'], $recipe_id, $rating, $comment);
        
        // Set success flag and continue rendering the page
        $success_message = true;
        $passed_recipe_id = $recipe_id; // Ensure recipe_id is available for page rendering
    }
}

// Continue with normal page rendering if not a POST request
$database = new Database();
$pdo = $database->getConnection();

if (!isset($passed_recipe_id)) {
    echo "Recipe not specified.";
    exit;
}

$recipe_id = intval($passed_recipe_id);

$query = "SELECT f.*, u.username, u.profile_image
          FROM feedback f 
          JOIN users u ON f.user_id = u.id 
          WHERE f.recipe_id = ? 
          ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$recipe_id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$average_rating = null;

if ($recipe_id) {
    $average_rating = FeedbackModel::getAverageRating($recipe_id);
}
?>


<?php if (isset($error_message)): ?>
  <div class="error-message">
    ❌ <?= $error_message ?>
  </div>
<?php endif; ?>

<?php if ($success_message): ?>
  <div class="success-message" id="successMessage">
    ✅ Your rating and comment have been submitted successfully!
  </div>
<?php endif; ?>

<div class="feedback-container">
    <form id="feedbackForm" method="POST" action="">
        <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
        <input type="hidden" name="rating" id="ratingInput" value="0">
        
        <div class="panel-wrapper">
            <!-- LEFT PANEL -->
            <div class="left-panel">
                <div class="rating-box">
                    <h2>Rate recipe</h2>
                    <div class="stars" id="star-rating">
                        <span data-value="1">★</span>
                        <span data-value="2">★</span>
                        <span data-value="3">★</span>
                        <span data-value="4">★</span>
                        <span data-value="5">★</span>
                    </div>
                    <button type="submit" class="send-btn" style="background-color: #ffa500; 
                           color: white; 
                           margin: 10px; 
                           border: none; 
                           border-radius: 12px; 
                           padding: 10px 30px; 
                           font-size: 16px; 
                           cursor: pointer; 
                           box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
                           transition: all 0.3s ease;" disabled>Send</button>
                </div>

                <?php if ($average_rating !== null): ?>
                    <div class="average-rating">
                        <span>Average rating</span>
                        <div class="badge">
                            <span><?= $average_rating ?></span> <span class="orange-star">★</span>
                        </div>
                    </div>
                <?php endif; ?>

                <p class="info-text">
                    💡 Leaving a comment is optional. You can submit your star rating alone, but not comment or tags alone. Don't worry — you're always in control!
                </p>
            </div>

            <!-- RIGHT PANEL -->
            <div class="right-panel">
                <h2>Leave a comment</h2>
                <textarea id="commentBox" name="comment" maxlength="280" placeholder="How did it turn out for you?"></textarea>
                <small id="charCount" style="display: block; margin-top: 5px; text-align: right;">0/280 characters</small>

                <div class="tags">
                    <button type="button" class="tag">Worth a try!</button>
                    <button type="button" class="tag">Delicious</button>
                    <button type="button" class="tag">Sweet</button>
                    <button type="button" class="tag">Savory</button>
                    <button type="button" class="tag">Spicy</button>
                    <button type="button" class="tag">Family Favorite</button>
                    <button type="button" class="tag">Time-Consuming</button>
                    <button type="button" class="tag">Beginner-Friendly</button>
                    <button type="button" class="tag">Needs Improvement</button>
                    <button type="button" class="tag">Vegan</button>
                </div>

                <h2 style="text-align:center;">Recipe Feedback</h2>
                <div class="comment-list">
                    <?php
                    if (empty($result)) {
                        echo "<p style='text-align:center; font-style: italic; color: #666; margin-top: 30px;'>No feedback yet – let's try it and be the first one to give feedback for this recipe!</p>";
                    } else {
                        foreach ($result as $row) {
                            $profile_pic = $row['profile_pic'] ?? null;
                            $username = htmlspecialchars($row['username']);
                            $created_at = date('d/m/Y', strtotime($row['created_at']));
                            $comment = htmlspecialchars($row['comment']);
                            $labels = isset($row['labels']) && !empty($row['labels']) ? explode(',', $row['labels']) : [];
                            
                            $avatar_letter = strtoupper($username[0] ?? 'A');
                            
                            echo '<div class="comment">';
                            
                            // Avatar (image or letter)
                            if ($profile_pic && file_exists(__DIR__ . "/path_to_profile_pics/$profile_pic")) {
                                echo "<div class='avatar'><img src='path_to_profile_pics/$profile_pic' alt='$username'></div>";
                            } else {
                                echo "<div class='avatar'>$avatar_letter</div>";
                            }
                            
                            echo '<div class="comment-body">';
                            echo "<strong>$username</strong><br />";
                            echo "<small class='comment-date'>$created_at</small>";
                            echo "<p>$comment</p>";
                            
                            if (!empty($labels)) {
                                foreach ($labels as $tag) {
                                    $cleanTag = htmlspecialchars(trim($tag));
                                    echo "<span class='static-tag'>$cleanTag</span> ";
                                }
                            }
                            
                            echo '</div>'; // comment-body
                            echo '</div>'; // comment
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Auto-hide success message after 5 seconds
<?php if ($success_message): ?>
setTimeout(function() {
    const successMsg = document.getElementById('successMessage');
    if (successMsg) {
        successMsg.style.opacity = '0';
        successMsg.style.transform = 'translateY(-10px)';
        setTimeout(() => successMsg.remove(), 500);
    }
}, 5000);
<?php endif; ?>
</script>