<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all messages sent by the current user
$stmt = $pdo->prepare("
    SELECT m.*, 
           u.username as receiver_name
    FROM messages m
    JOIN users u ON m.receiver_id = u.id
    WHERE m.sender_id = ?
    ORDER BY m.sent_at DESC
");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sent Messages - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        .navbar-brand {
            font-weight: bold;
            color: #333;
        }
        .nav-link {
            color: #666;
            margin: 0 0.5rem;
            transition: color 0.3s;
        }
        .nav-link:hover {
            color: #0d6efd;
        }
        .message-nav {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .message-nav .nav-link {
            color: #495057;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
        }
        .message-nav .nav-link:hover {
            background-color: #e9ecef;
        }
        .message-nav .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .message-list {
            list-style: none;
            padding: 0;
        }
        .message-item {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.2s;
        }
        .message-item:hover {
            background-color: #f8f9fa;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .message-receiver {
            font-weight: bold;
        }
        .message-time {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .message-content {
            color: #212529;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">RecipeHub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">Messages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <h1 class="mb-4">Sent Messages</h1>
        
        <!-- Message Navigation -->
        <div class="message-nav">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link" href="messages.php">Inbox</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="sent-messages.php">Sent Messages</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="compose-message.php">Compose New Message</a>
                </li>
            </ul>
        </div>

        <!-- Messages List -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($messages)): ?>
                    <p class="text-center text-muted">No sent messages found.</p>
                <?php else: ?>
                    <ul class="message-list">
                        <?php foreach ($messages as $message): ?>
                            <li class="message-item">
                                <div class="message-header">
                                    <span class="message-receiver">
                                        To: <?php echo htmlspecialchars($message['receiver_name']); ?>
                                    </span>
                                    <span class="message-time">
                                        <?php echo date('M d, Y H:i', strtotime($message['sent_at'])); ?>
                                    </span>
                                </div>
                                <div class="message-content">
                                    <?php echo htmlspecialchars($message['message']); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 