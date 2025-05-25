<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/date_helper.php';

$db = new Database();
$pdo = $db->getConnection();

$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id != ?");
$stmt->execute([$current_user_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_user_id = $_GET['user_id'] ?? null;
$messages = [];

if ($selected_user_id) {
    $stmt = $pdo->prepare("
        SELECT sender_id, receiver_id, message, sent_at 
        FROM messages 
        WHERE (sender_id = :current_user AND receiver_id = :selected_user) 
           OR (sender_id = :selected_user AND receiver_id = :current_user)
        ORDER BY sent_at ASC
    ");
    $stmt->execute([
        'current_user' => $current_user_id,
        'selected_user' => $selected_user_id,
    ]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - RecipeHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .chat-container {
            display: flex;
            height: 80vh;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 2rem;
        }
        .user-list {
            width: 30%;
            background: #f1f1f1;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }
        .user-list .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        .chat-box {
            width: 70%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        .messages {
            flex-grow: 1;
            overflow-y: auto;
            padding-right: 10px;
        }
        .message-bubble {
            max-width: 70%;
            margin-bottom: 10px;
            padding: 10px 15px;
            border-radius: 20px;
            position: relative;
        }
        .me {
            align-self: flex-end;
            background-color: #dcf8c6;
            text-align: right;
        }
        .them {
            align-self: flex-start;
            background-color: #f1f0f0;
        }
        .date-label {
            text-align: center;
            font-size: 0.85em;
            color: #777;
            margin: 10px 0;
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

    <div class="container py-4">
        <h2 class="mb-3">Messages</h2>

        <div class="chat-container">
            <!-- Left Panel: User List -->
            <div class="user-list p-2">
                <form method="get">
                    <div class="list-group">
                        <?php foreach ($users as $user): ?>
                            <button type="submit" name="user_id" value="<?= $user['id'] ?>"
                                    class="list-group-item list-group-item-action <?= $selected_user_id == $user['id'] ? 'active' : '' ?>">
                                <?= htmlspecialchars($user['username']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>

            <!-- Right Panel: Messages -->
            <div class="chat-box">
                <?php if ($selected_user_id): ?>
                    <div class="messages" id="messageBox">
                        <?php
                        $lastDate = '';
                        foreach ($messages as $msg):
                            $is_me = $msg['sender_id'] == $current_user_id;
                            $date = (new DateTime($msg['sent_at']))->format('Y-m-d');
                            if ($date !== $lastDate):
                                echo '<div class="date-label">' . humanReadableDate($msg['sent_at']) . '</div>';
                                $lastDate = $date;
                            endif;
                        ?>
                            <div class="d-flex <?= $is_me ? 'justify-content-end' : 'justify-content-start' ?>">
                                <div class="message-bubble <?= $is_me ? 'me' : 'them' ?>">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                    <div class="text-muted small mt-1"><?= (new DateTime($msg['sent_at']))->format('H:i') ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Message Input -->
                    <form method="post" action="send_message.php" class="mt-3 d-flex gap-2">
                        <input type="hidden" name="receiver_id" value="<?= $selected_user_id ?>">
                        <textarea name="message" class="form-control" rows="1" placeholder="Type your message..." required></textarea>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                <?php elseif (!empty($users)): ?>
                    <p class="text-muted mt-5">Select a user to start chatting.</p>
                <?php else: ?>
                    <p class="text-muted mt-5">No users available to message.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        window.onload = function () {
            const box = document.getElementById('messageBox');
            if (box) box.scrollTop = box.scrollHeight;
        };
    </script>
</body>
</html>
