<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/feedback_model.php';

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

  <div class="feedback-container">
    <form id="feedbackForm" method="POST" action="/recipe-hub/public/feedback/submit_rating.php">>
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
            <button type="submit" class="send-btn" style="background-color: #ffa500; /* Orange background */
               color: white; 
               margin: 10px; 
               border: none; 
               border-radius: 12px; 
               padding: 10px 30px; 
               font-size: 16px; 
               cursor: pointer; 
               box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
               transition: all 0.3s ease;"  disabled>Send</button>

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
              } 
              else {
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