<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$pdo = $db->getConnection();

$current_user_id = $_SESSION['user_id'] ?? null;
$receiver_id = $_POST['receiver_id'] ?? null;
$message = trim($_POST['message'] ?? '');

if (!$current_user_id || !$receiver_id || !$message) {
    header("Location: messages.php");
    exit();
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())");
$stmt->execute([$current_user_id, $receiver_id, $message]);

header("Location: messages.php?user_id=" . $receiver_id);
exit();
