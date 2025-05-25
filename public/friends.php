<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $action = sanitizeInput($_POST['action']);
        $other_user_id = sanitizeInput($_POST['user_id']);

        switch ($action) {
            case 'send_request':
                $stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (:sender_id, :receiver_id)");
                $stmt->execute([':sender_id' => $_SESSION['user_id'], ':receiver_id' => $other_user_id]);
                break;
            case 'accept_request':
                $conn->beginTransaction();
                $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id) VALUES (:user_id, :friend_id), (:friend_id, :user_id)");
                $stmt->execute([':user_id' => $_SESSION['user_id'], ':friend_id' => $other_user_id]);

                $stmt = $conn->prepare("DELETE FROM friend_requests WHERE sender_id = :sender_id AND receiver_id = :receiver_id");
                $stmt->execute([':sender_id' => $other_user_id, ':receiver_id' => $_SESSION['user_id']]);
                $conn->commit();
                break;
            case 'reject_request':
                $stmt = $conn->prepare("DELETE FROM friend_requests WHERE sender_id = :sender_id AND receiver_id = :receiver_id");
                $stmt->execute([':sender_id' => $other_user_id, ':receiver_id' => $_SESSION['user_id']]);
                break;
            case 'remove_friend':
                $stmt = $conn->prepare("DELETE FROM friends WHERE (user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id AND friend_id = :user_id)");
                $stmt->execute([':user_id' => $_SESSION['user_id'], ':friend_id' => $other_user_id]);
                break;
        }

        header('Location: friends.php');
        exit();
    }
}

$uid = $_SESSION['user_id'];

$friends = $conn->prepare("SELECT u.* FROM users u JOIN friends f ON f.friend_id = u.id WHERE f.user_id = :user_id ORDER BY u.username ASC");
$friends->execute([':user_id' => $uid]);
$friends = $friends->fetchAll(PDO::FETCH_ASSOC);

$pending_requests = $conn->prepare("SELECT u.* FROM users u JOIN friend_requests fr ON fr.sender_id = u.id WHERE fr.receiver_id = :user_id");
$pending_requests->execute([':user_id' => $uid]);
$pending_requests = $pending_requests->fetchAll(PDO::FETCH_ASSOC);

$sent_requests = $conn->prepare("SELECT u.* FROM users u JOIN friend_requests fr ON fr.receiver_id = u.id WHERE fr.sender_id = :user_id");
$sent_requests->execute([':user_id' => $uid]);
$sent_requests = $sent_requests->fetchAll(PDO::FETCH_ASSOC);

$suggested_friends = $conn->prepare("
    SELECT u.* FROM users u 
    WHERE u.id != :user_id 
    AND u.id NOT IN (SELECT friend_id FROM friends WHERE user_id = :user_id)
    AND u.id NOT IN (SELECT sender_id FROM friend_requests WHERE receiver_id = :user_id)
    AND u.id NOT IN (SELECT receiver_id FROM friend_requests WHERE sender_id = :user_id)
    ORDER BY u.username ASC LIMIT 10
");
$suggested_friends->execute([':user_id' => $uid]);
$suggested_friends = $suggested_friends->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Friends - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container mt-4">
    <div class="row g-4">
        <!-- Friends -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-user-friends me-2"></i>My Friends</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($friends)): ?>
                        <p class="text-center text-muted">You haven’t added any friends yet.</p>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-sm-2 g-3">
                            <?php foreach ($friends as $friend): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body d-flex align-items-center">
                                            <img src="../assets/images/profiles/<?php echo htmlspecialchars($friend['profile_image']); ?>"
                                                 class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($friend['username']); ?></h6>
                                                <div class="d-flex gap-2">
                                                    <a href="messages.php?user=<?php echo $friend['id']; ?>" class="btn btn-sm btn-primary" title="Message">
                                                        <i class="fas fa-comment"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Remove this friend?');">
                                                        <input type="hidden" name="action" value="remove_friend">
                                                        <input type="hidden" name="user_id" value="<?php echo $friend['id']; ?>">
                                                        <button class="btn btn-sm btn-outline-danger" title="Remove Friend"><i class="fas fa-user-minus"></i></button>
                                                    </form>
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

        <!-- Requests + Suggestions -->
        <div class="col-lg-6">
            <?php if (!empty($pending_requests)): ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-user-clock me-2"></i>Friend Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="../assets/images/profiles/<?php echo htmlspecialchars($request['profile_image']); ?>"
                                     class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($request['username']); ?></h6>
                                    <div class="d-flex gap-2">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="accept_request">
                                            <input type="hidden" name="user_id" value="<?php echo $request['id']; ?>">
                                            <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Accept</button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="reject_request">
                                            <input type="hidden" name="user_id" value="<?php echo $request['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i> Reject</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($sent_requests)): ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Sent Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($sent_requests as $request): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="../assets/images/profiles/<?php echo htmlspecialchars($request['profile_image']); ?>"
                                     class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($request['username']); ?></h6>
                                    <small class="text-muted">Request sent</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Suggested Friends</h5>
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
                                    <form method="POST">
                                        <input type="hidden" name="action" value="send_request">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-user-plus"></i> Add Friend</button>
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

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
