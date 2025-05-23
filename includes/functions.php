<?php
// User authentication functions
function registerUser($username, $email, $password, $profile_image) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (username, email, password, profile_image) VALUES (:username, :email, :password, :profile_image)";
    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $hashed_password);
    $stmt->bindParam(":profile_image", $profile_image);
    
    return $stmt->execute();
}

function loginUser($email, $password) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    if($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
    }
    return false;
}

// Recipe functions
function createRecipe($user_id, $title, $description, $ingredients, $instructions, $image) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "INSERT INTO recipes (user_id, title, description, ingredients, instructions, image) 
              VALUES (:user_id, :title, :description, :ingredients, :instructions, :image)";
    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":title", $title);
    $stmt->bindParam(":description", $description);
    $stmt->bindParam(":ingredients", $ingredients);
    $stmt->bindParam(":instructions", $instructions);
    $stmt->bindParam(":image", $image);
    
    return $stmt->execute();
}

function getRecipe($recipe_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT r.*, u.username, u.profile_image 
              FROM recipes r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id", $recipe_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Friend functions
function sendFriendRequest($sender_id, $receiver_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "INSERT INTO friend_requests (sender_id, receiver_id) VALUES (:sender_id, :receiver_id)";
    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(":sender_id", $sender_id);
    $stmt->bindParam(":receiver_id", $receiver_id);
    
    return $stmt->execute();
}

function acceptFriendRequest($request_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get the request details
    $query = "SELECT * FROM friend_requests WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id", $request_id);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($request) {
        // Add to friends table
        $query = "INSERT INTO friends (user_id, friend_id) VALUES (:user_id, :friend_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":user_id", $request['receiver_id']);
        $stmt->bindParam(":friend_id", $request['sender_id']);
        $stmt->execute();
        
        // Delete the request
        $query = "DELETE FROM friend_requests WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $request_id);
        return $stmt->execute();
    }
    return false;
}

// Message functions
function sendMessage($sender_id, $receiver_id, $message) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (:sender_id, :receiver_id, :message)";
    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(":sender_id", $sender_id);
    $stmt->bindParam(":receiver_id", $receiver_id);
    $stmt->bindParam(":message", $message);
    
    return $stmt->execute();
}

function getMessages($user_id, $friend_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT m.*, u.username, u.profile_image 
              FROM messages m 
              JOIN users u ON m.sender_id = u.id 
              WHERE (m.sender_id = :user_id AND m.receiver_id = :friend_id) 
              OR (m.sender_id = :friend_id AND m.receiver_id = :user_id) 
              ORDER BY m.created_at ASC";
    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":friend_id", $friend_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Utility functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function uploadImage($file, $target_dir) {
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return false;
    }
    
    // Check file size
    if ($file["size"] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return false;
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }
    
    return false;
}
?> 