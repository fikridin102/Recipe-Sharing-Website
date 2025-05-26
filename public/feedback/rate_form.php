<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../http/controller/FeedbackController.php';

$controller = new FeedbackController();

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->submitFeedback($_POST);
}

// Get data
$recipe_id = intval($_GET['id'] ?? 1);
$user_id = intval($_SESSION['user_id'] ?? 0);

// Check if user is logged in
if ($user_id === 0) {
    echo "<div style='color: red; text-align: center; padding: 20px;'>Please log in to leave feedback.</div>";
    exit();
}

$average_rating = $controller->getAverageRating($recipe_id);
$result = $controller->getFeedbacks($recipe_id);

// Messages
$success_message = isset($_GET['success']) && $_GET['success'] == '1'
    ? "Thank you! Your feedback has been submitted successfully."
    : '';

$error_message = $_SESSION['feedback_error'] ?? '';
$debug_message = $_SESSION['feedback_debug'] ?? '';

unset($_SESSION['feedback_error'], $_SESSION['feedback_debug']);
// // Check for success message
// $success_message = '';
// if (isset($_GET['success']) && $_GET['success'] == '1') {
//     $success_message = "Thank you! Your feedback has been submitted successfully.";
// }

// // Check for error messages
// $error_message = '';
// if (isset($_SESSION['feedback_error'])) {
//     $error_message = $_SESSION['feedback_error'];
//     unset($_SESSION['feedback_error']);
// }

// $debug_message = '';
// if (isset($_SESSION['feedback_debug'])) {
//     $debug_message = $_SESSION['feedback_debug'];
//     unset($_SESSION['feedback_debug']);
// }
?>

<?php if ($success_message): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;">
        <?= htmlspecialchars($success_message) ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0;">
        <?= htmlspecialchars($error_message) ?>
        <?php if ($debug_message): ?>
            <br><small>Debug: <?= htmlspecialchars($debug_message) ?></small>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="feedback-container">
    <form id="feedbackForm" method="POST">
        <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
        <input type="hidden" name="user_id" value="<?= $user_id ?>">
        <input type="hidden" name="rating" id="ratingInput" value="0">
        <input type="hidden" name="tags" id="selectedTags" value="">

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
                    <button type="button" class="tag" data-value="Worth a try">Worth a try!</button>
                    <button type="button" class="tag" data-value="Delicious">Delicious</button>
                    <button type="button" class="tag" data-value="Sweet">Sweet</button>
                    <button type="button" class="tag" data-value="Savory">Savory</button>
                    <button type="button" class="tag" data-value="Spicy">Spicy</button>
                    <button type="button" class="tag" data-value="Family Favorite">Family Favorite</button>
                    <button type="button" class="tag" data-value="Time-Consuming">Time-Consuming</button>
                    <button type="button" class="tag" data-value="Beginner-Friendly">Beginner-Friendly</button>
                    <button type="button" class="tag" data-value="Needs Improvement">Needs Improvement</button>
                    <button type="button" class="tag" data-value="Vegan">Vegan</button>
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

<!-- Embedded JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const stars = document.querySelectorAll("#star-rating span");
    const sendBtn = document.querySelector(".send-btn");
    const ratingInput = document.getElementById("ratingInput");
    const commentBox = document.getElementById("commentBox");
    const charCount = document.getElementById("charCount");
    const tagButtons = document.querySelectorAll('.tag');
    const selectedTagsInput = document.getElementById('selectedTags');
    const form = document.getElementById("feedbackForm");

    let selectedRating = 0;
    let selectedTags = [];

    // Enable/disable submit button based on rating
    function updateSubmitButton() {
        sendBtn.disabled = selectedRating === 0;
        sendBtn.style.opacity = selectedRating === 0 ? '0.5' : '1';
    }

    stars.forEach((star) => {
        star.addEventListener("click", function () {
            selectedRating = parseInt(this.dataset.value);
            ratingInput.value = selectedRating;
            updateStarDisplay(selectedRating);
            updateSubmitButton();
        });

        star.addEventListener("mouseenter", function () {
            updateStarDisplay(parseInt(this.dataset.value));
        });
    });

    document.getElementById("star-rating").addEventListener("mouseleave", () => {
        updateStarDisplay(selectedRating);
    });

    tagButtons.forEach(button => {
        button.addEventListener('click', function () {
            const tagValue = this.textContent.trim();
            this.classList.toggle('selected');
            if (this.classList.contains('selected')) {
                selectedTags.push(tagValue);
            } else {
                selectedTags = selectedTags.filter(tag => tag !== tagValue);
            }
            selectedTagsInput.value = selectedTags.join(',');
        });
    });

    commentBox.addEventListener("input", function () {
        const len = this.value.length;
        charCount.textContent = `${len}/280 characters`;
        charCount.style.color = len > 250 ? '#ff6b6b' : len > 200 ? '#ffa500' : '#666';
    });

    form.addEventListener("submit", function (e) {
        if (parseInt(ratingInput.value) === 0) {
            e.preventDefault();
            alert("Please select a rating before submitting!");
            return false;
        }
        
        // Show loading state
        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending...';
        
        return true;
    });

    function updateStarDisplay(rating) {
        stars.forEach((star, index) => {
            star.style.color = index < rating ? '#ffa500' : '#ddd';
            star.classList.toggle('selected', index < rating);
        });
    }

    // Initialize submit button state
    updateSubmitButton();
});
</script>