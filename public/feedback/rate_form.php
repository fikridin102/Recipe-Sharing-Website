<?php
// Handle success message from URL parameter
$feedback_message = '';
$feedback_type = '';

if (isset($_GET['rated']) && $_GET['rated'] == '1') {
    $feedback_message = 'Your rating has been saved successfully!';
    $feedback_type = 'success';
}

// Handle form submission
$form_submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    if ($user_id && isset($passed_recipe_id)) {
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);
        $labels = isset($_POST['labels']) ? implode(', ', $_POST['labels']) : '';
        
        if ($rating >= 1 && $rating <= 5) {
            try {
                // Check if user already has feedback for this recipe
                $stmt = $conn->prepare("SELECT feedback_id FROM feedback WHERE recipe_id = :recipe_id AND user_id = :user_id");
                $stmt->bindParam(":recipe_id", $passed_recipe_id);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->execute();
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // Update existing feedback
                    $stmt = $conn->prepare("UPDATE feedback SET rating = :rating, comment = :comment, labels = :labels, updated_at = NOW() WHERE feedback_id = :feedback_id");
                    $stmt->bindParam(":rating", $rating);
                    $stmt->bindParam(":comment", $comment);
                    $stmt->bindParam(":labels", $labels);
                    $stmt->bindParam(":feedback_id", $existing['feedback_id']);
                    $stmt->execute();
                    
                    $feedback_message = 'Your rating has been updated successfully!';
                    $feedback_type = 'success';
                } else {
                    // Insert new feedback
                    $stmt = $conn->prepare("INSERT INTO feedback (recipe_id, user_id, rating, comment, labels, created_at) VALUES (:recipe_id, :user_id, :rating, :comment, :labels, NOW())");
                    $stmt->bindParam(":recipe_id", $passed_recipe_id);
                    $stmt->bindParam(":user_id", $user_id);
                    $stmt->bindParam(":rating", $rating);
                    $stmt->bindParam(":comment", $comment);
                    $stmt->bindParam(":labels", $labels);
                    $stmt->execute();
                    
                    $feedback_message = 'Thank you for rating this recipe!';
                    $feedback_type = 'success';
                }
                
                $form_submitted = true;
                
            } catch (PDOException $e) {
                $feedback_message = 'Sorry, there was an error saving your rating. Please try again.';
                $feedback_type = 'error';
            }
        } else {
            $feedback_message = 'Please select a valid rating (1-5 stars).';
            $feedback_type = 'error';
        }
    }
}

// Get existing feedback for this user and recipe
$existing_feedback = null;
$average_rating = 0;
$total_ratings = 0;

if (isset($passed_recipe_id)) {
    $recipe_id = $passed_recipe_id;
    
    // Get existing feedback from current user
    if ($user_id) {
        $stmt = $conn->prepare("SELECT * FROM feedback WHERE recipe_id = :recipe_id AND user_id = :user_id");
        $stmt->bindParam(":recipe_id", $recipe_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $existing_feedback = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get average rating and total ratings
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM feedback WHERE recipe_id = :recipe_id AND rating IS NOT NULL");
    $stmt->bindParam(":recipe_id", $recipe_id);
    $stmt->execute();
    $rating_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $average_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
    $total_ratings = $rating_data['total_ratings'];
    
    // Get rating distribution
    $stmt = $conn->prepare("SELECT rating, COUNT(*) as count FROM feedback WHERE recipe_id = :recipe_id AND rating IS NOT NULL GROUP BY rating ORDER BY rating DESC");
    $stmt->bindParam(":recipe_id", $recipe_id);
    $stmt->execute();
    $rating_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create distribution array
    $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($rating_distribution as $dist) {
        $distribution[$dist['rating']] = $dist['count'];
    }
}
?>

<style>
.rating-card {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.rating-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.rating-overview {
    text-align: center;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
}

.average-rating {
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
    line-height: 1;
}

.rating-stars {
    display: flex;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.star {
    font-size: 1.5rem;
    color: #e5e7eb;
    transition: all 0.2s ease;
}

.star.filled {
    color: #fbbf24;
}

.total-ratings {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.rating-distribution {
    margin-top: 1rem;
}

.distribution-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.distribution-label {
    min-width: 60px;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.distribution-bar {
    flex: 1;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.distribution-fill {
    height: 100%;
    background: linear-gradient(90deg, #fbbf24, #f59e0b);
    transition: width 0.3s ease;
}

.distribution-count {
    min-width: 30px;
    text-align: right;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.rating-form {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid var(--border-color);
}

.form-section {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    display: block;
}

.star-rating {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.star-rating .star {
    font-size: 2rem;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #e5e7eb;
}

.star-rating .star:hover,
.star-rating .star.active {
    color: #fbbf24;
    transform: scale(1.1);
}

.star-rating .star:hover ~ .star {
    color: #e5e7eb;
}

.labels-section {
    margin: 1.5rem 0;
}

.label-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.label-chip {
    background: var(--light-gray);
    border: 2px solid transparent;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
    position: relative;
}

.label-chip input[type="checkbox"] {
    display: none;
}

.label-chip:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.label-chip.selected {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.comment-textarea {
    width: 100%;
    min-height: 100px;
    padding: 0.75rem;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    resize: vertical;
}

.comment-textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.submit-btn {
    background: linear-gradient(135deg, var(--primary-color), #3b82f6);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.feedback-message {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    animation: slideIn 0.3s ease-out;
}

.feedback-message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.feedback-message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.existing-feedback {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border: 1px solid #93c5fd;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.existing-feedback-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    color: var(--primary-color);
    font-weight: 600;
}

.hidden-rating-input {
    display: none;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .rating-card {
        padding: 1rem;
    }
    
    .average-rating {
        font-size: 2.5rem;
    }
    
    .star-rating .star {
        font-size: 1.75rem;
    }
    
    .label-chips {
        justify-content: center;
    }
}
</style>

<div class="rating-card">
    <div class="section-header">
        <div class="section-icon">
            <i class="fas fa-star"></i>
        </div>
        <h3 class="section-title">Recipe Rating</h3>
    </div>

    <!-- Rating Overview -->
    <div class="rating-overview">
        <div class="average-rating"><?php echo $average_rating; ?></div>
        <div class="rating-stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?php echo $i <= round($average_rating) ? 'filled' : ''; ?>">★</span>
            <?php endfor; ?>
        </div>
        <div class="total-ratings">
            Based on <?php echo $total_ratings; ?> rating<?php echo $total_ratings != 1 ? 's' : ''; ?>
        </div>

        <?php if ($total_ratings > 0): ?>
            <div class="rating-distribution">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <?php 
                    $count = $distribution[$i];
                    $percentage = $total_ratings > 0 ? ($count / $total_ratings) * 100 : 0;
                    ?>
                    <div class="distribution-row">
                        <div class="distribution-label"><?php echo $i; ?> star<?php echo $i != 1 ? 's' : ''; ?></div>
                        <div class="distribution-bar">
                            <div class="distribution-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="distribution-count"><?php echo $count; ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($user_id): ?>
        <!-- Feedback Message -->
        <?php if ($feedback_message): ?>
            <div class="feedback-message <?php echo $feedback_type; ?>">
                <i class="fas <?php echo $feedback_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($feedback_message); ?>
            </div>
            <?php if ($form_submitted && $feedback_type === 'success'): ?>
                <script>
                    setTimeout(function() {
                        document.querySelector('.feedback-message').style.display = 'none';
                        // Optionally reload to refresh the rating data
                        setTimeout(function() {
                            window.location.href = window.location.pathname + window.location.search.replace(/[?&]rated=1/, '');
                        }, 2000);
                    }, 3000);
                </script>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Existing Feedback Display -->
        <?php if ($existing_feedback): ?>
            <div class="existing-feedback">
                <div class="existing-feedback-header">
                    <i class="fas fa-check-circle"></i>
                    <span>Your Rating</span>
                </div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo $i <= $existing_feedback['rating'] ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="fw-semibold"><?php echo $existing_feedback['rating']; ?>/5</span>
                </div>
                <?php if ($existing_feedback['labels']): ?>
                    <div class="mb-2">
                        <small class="text-muted">Labels: </small>
                        <span class="fw-medium"><?php echo htmlspecialchars($existing_feedback['labels']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($existing_feedback['comment']): ?>
                    <div class="mb-2">
                        <small class="text-muted">Your comment:</small>
                        <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($existing_feedback['comment'])); ?></p>
                    </div>
                <?php endif; ?>
                <small class="text-muted">
                    Rated on <?php echo date('M j, Y', strtotime($existing_feedback['created_at'])); ?>
                </small>
            </div>
        <?php endif; ?>

        <!-- Rating Form -->
        <div class="rating-form">
            <form method="POST" id="rating-form">
                <input type="hidden" name="submit_rating" value="1">
                <input type="hidden" id="selected-rating" name="rating" value="<?php echo $existing_feedback ? $existing_feedback['rating'] : 0; ?>">
                
                <div class="form-section">
                    <label class="form-label">
                        <?php echo $existing_feedback ? 'Update Your Rating' : 'Rate This Recipe'; ?>
                    </label>
                    <div class="star-rating" id="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo $existing_feedback && $i <= $existing_feedback['rating'] ? 'active' : ''; ?>" data-rating="<?php echo $i; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <div id="rating-text" class="text-muted small"></div>
                </div>

                <div class="form-section">
                    <label class="form-label">Quick Labels (Optional)</label>
                    <div class="label-chips">
                        <?php 
                        $available_labels = ['Delicious', 'Easy', 'Healthy', 'Quick', 'Family Friendly', 'Impressive'];
                        $existing_labels = $existing_feedback && $existing_feedback['labels'] ? explode(', ', $existing_feedback['labels']) : [];
                        ?>
                        <?php foreach ($available_labels as $label): ?>
                            <label class="label-chip <?php echo in_array($label, $existing_labels) ? 'selected' : ''; ?>">
                                <input type="checkbox" name="labels[]" value="<?php echo $label; ?>" 
                                       <?php echo in_array($label, $existing_labels) ? 'checked' : ''; ?>>
                                <?php 
                                $emoji = [
                                    'Delicious' => '😋',
                                    'Easy' => '👍',
                                    'Healthy' => '🥗',
                                    'Quick' => '⚡',
                                    'Family Friendly' => '👨‍👩‍👧‍👦',
                                    'Impressive' => '✨'
                                ];
                                echo $emoji[$label] . ' ' . $label;
                                ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-section">
                    <label class="form-label" for="rating-comment">Add a Comment (Optional)</label>
                    <textarea 
                        id="rating-comment" 
                        name="comment"
                        class="comment-textarea" 
                        placeholder="Share your experience with this recipe. What did you like? Any tips for other cooks?"
                        maxlength="500"><?php echo $existing_feedback ? htmlspecialchars($existing_feedback['comment']) : ''; ?></textarea>
                    <div class="text-end mt-1">
                        <small class="text-muted"><span id="comment-count">0</span>/500 characters</small>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submit-btn" <?php echo !$existing_feedback ? 'disabled' : ''; ?>>
                    <i class="fas fa-star me-2"></i>
                    <?php echo $existing_feedback ? 'Update Rating' : 'Submit Rating'; ?>
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-sign-in-alt fa-2x text-muted mb-3"></i>
            <p class="text-muted">Please <a href="../auth/login.php" class="text-decoration-none">login</a> to rate this recipe</p>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const starRating = document.getElementById('star-rating');
    const stars = starRating?.querySelectorAll('.star');
    const ratingText = document.getElementById('rating-text');
    const submitBtn = document.getElementById('submit-btn');
    const selectedRatingInput = document.getElementById('selected-rating');
    const commentTextarea = document.getElementById('rating-comment');
    const commentCount = document.getElementById('comment-count');
    const labelChips = document.querySelectorAll('.label-chip');
    
    let currentRating = <?php echo $existing_feedback ? $existing_feedback['rating'] : 0; ?>;
    
    // Update star display
    function updateStarDisplay(rating) {
        stars.forEach((star, index) => {
            star.classList.toggle('active', index < rating);
        });
        
        const ratingTexts = {
            1: 'Poor - Not recommended',
            2: 'Fair - Could be better',
            3: 'Good - Decent recipe',
            4: 'Very Good - Would make again',
            5: 'Excellent - Amazing recipe!'
        };
        
        ratingText.textContent = rating > 0 ? ratingTexts[rating] : 'Click to rate';
        selectedRatingInput.value = rating;
        submitBtn.disabled = rating === 0;
    }
    
    // Initialize display
    updateStarDisplay(currentRating);
    
    // Star rating interaction
    if (stars) {
        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                currentRating = index + 1;
                updateStarDisplay(currentRating);
            });
            
            star.addEventListener('mouseenter', () => {
                const hoverRating = index + 1;
                stars.forEach((s, i) => {
                    s.style.color = i < hoverRating ? '#fbbf24' : '#e5e7eb';
                });
            });
        });
        
        starRating.addEventListener('mouseleave', () => {
            updateStarDisplay(currentRating);
        });
    }
    
    // Label chips interaction
    labelChips.forEach(chip => {
        const checkbox = chip.querySelector('input[type="checkbox"]');
        
        chip.addEventListener('click', (e) => {
            if (e.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
            }
            chip.classList.toggle('selected', checkbox.checked);
        });
        
        // Initialize state
        chip.classList.toggle('selected', checkbox.checked);
    });
    
    // Comment character count
    if (commentTextarea) {
        function updateCommentCount() {
            const count = commentTextarea.value.length;
            commentCount.textContent = count;
            commentCount.style.color = count > 450 ? '#dc2626' : '#64748b';
        }
        
        updateCommentCount();
        commentTextarea.addEventListener('input', updateCommentCount);
    }
    
    // Form validation
    const ratingForm = document.getElementById('rating-form');
    if (ratingForm) {
        ratingForm.addEventListener('submit', function(e) {
            if (currentRating === 0) {
                e.preventDefault();
                alert('Please select a rating before submitting.');
                return false;
            }
        });
    }
});
</script>