<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle friend request actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $action = sanitizeInput($_POST['action']);
        $other_user_id = sanitizeInput($_POST['user_id']);
        
        switch ($action) {
            case 'send_request':
                $query = "INSERT INTO friend_requests (sender_id, receiver_id) VALUES (:sender_id, :receiver_id)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(":sender_id", $_SESSION['user_id']);
                $stmt->bindParam(":receiver_id", $other_user_id);
                $stmt->execute();
                break;
                
            case 'accept_request':
                // Add to friends table
                $query = "INSERT INTO friends (user_id, friend_id) VALUES (:user_id, :friend_id), (:friend_id, :user_id)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(":user_id", $_SESSION['user_id']);
                $stmt->bindParam(":friend_id", $other_user_id);
                $stmt->execute();
                
                // Delete the request
                $query = "DELETE FROM friend_requests WHERE sender_id = :sender_id AND receiver_id = :receiver_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(":sender_id", $other_user_id);
                $stmt->bindParam(":receiver_id", $_SESSION['user_id']);
                $stmt->execute();
                break;
                
            case 'reject_request':
                $query = "DELETE FROM friend_requests WHERE sender_id = :sender_id AND receiver_id = :receiver_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(":sender_id", $other_user_id);
                $stmt->bindParam(":receiver_id", $_SESSION['user_id']);
                $stmt->execute();
                break;
                
            case 'remove_friend':
                $query = "DELETE FROM friends WHERE (user_id = :user_id AND friend_id = :friend_id) 
                         OR (user_id = :friend_id AND friend_id = :user_id)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(":user_id", $_SESSION['user_id']);
                $stmt->bindParam(":friend_id", $other_user_id);
                $stmt->execute();
                break;
        }
        
        // Redirect to refresh the page
        header('Location: friends.php');
        exit();
    }
}

// Get current friends
$query = "SELECT u.* FROM users u 
          JOIN friends f ON (f.friend_id = u.id AND f.user_id = :user_id)
          ORDER BY u.username ASC";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending friend requests
$query = "SELECT u.* FROM users u 
          JOIN friend_requests fr ON fr.sender_id = u.id 
          WHERE fr.receiver_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sent friend requests
$query = "SELECT u.* FROM users u 
          JOIN friend_requests fr ON fr.receiver_id = u.id 
          WHERE fr.sender_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$sent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get suggested friends (users who are not friends and haven't received/sent requests)
$query = "SELECT u.* FROM users u 
          WHERE u.id != :user_id 
          AND u.id NOT IN (
              SELECT friend_id FROM friends WHERE user_id = :user_id
          )
          AND u.id NOT IN (
              SELECT sender_id FROM friend_requests WHERE receiver_id = :user_id
          )
          AND u.id NOT IN (
              SELECT receiver_id FROM friend_requests WHERE sender_id = :user_id
          )
          ORDER BY u.username ASC
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$suggested_friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container mt-4">
        <div class="row">
            <!-- Current Friends -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">My Friends</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($friends)): ?>
                            <p class="text-center text-muted">You haven't added any friends yet.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($friends as $friend): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($friend['profile_image']); ?>" 
                                                         class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($friend['username']); ?></h6>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="messages.php?user=<?php echo $friend['id']; ?>" 
                                                               class="btn btn-primary">Message</a>
                                                            <form method="POST" action="" class="d-inline">
                                                                <input type="hidden" name="action" value="remove_friend">
                                                                <input type="hidden" name="user_id" value="<?php echo $friend['id']; ?>">
                                                                <button type="submit" class="btn btn-danger" 
                                                                        onclick="return confirm('Are you sure you want to remove this friend?')">
                                                                    Remove
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Friend Requests and Suggestions -->
            <div class="col-md-6">
                <!-- Pending Requests -->
                <?php if (!empty($pending_requests)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Friend Requests</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($pending_requests as $request): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($request['profile_image']); ?>" 
                                         class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($request['username']); ?></h6>
                                        <div class="btn-group btn-group-sm">
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="action" value="accept_request">
                                                <input type="hidden" name="user_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" class="btn btn-success">Accept</button>
                                            </form>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="action" value="reject_request">
                                                <input type="hidden" name="user_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" class="btn btn-danger">Reject</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Sent Requests -->
                <?php if (!empty($sent_requests)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Sent Requests</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($sent_requests as $request): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($request['profile_image']); ?>" 
                                         class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($request['username']); ?></h6>
                                        <span class="text-muted small">Request sent</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Suggested Friends -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Suggested Friends</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($suggested_friends)): ?>
                            <p class="text-center text-muted">No suggestions available.</p>
                        <?php else: ?>
                            <?php foreach ($suggested_friends as $user): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                         class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h6>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="action" value="send_request">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">Add Friend</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html> 