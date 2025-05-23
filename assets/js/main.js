// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Password strength checker
function checkPasswordStrength(password) {
    const strength = {
        length: password.length >= 8,
        hasUpperCase: /[A-Z]/.test(password),
        hasLowerCase: /[a-z]/.test(password),
        hasNumbers: /\d/.test(password),
        hasSpecialChar: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };

    const strengthScore = Object.values(strength).filter(Boolean).length;
    return strengthScore;
}

// Image preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Like/Unlike recipe
function toggleLike(recipeId) {
    fetch(`/api/like.php?recipe_id=${recipeId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const likeButton = document.querySelector(`#like-${recipeId}`);
            const likeCount = document.querySelector(`#like-count-${recipeId}`);
            
            if (likeButton && likeCount) {
                likeButton.classList.toggle('liked');
                likeCount.textContent = data.likes;
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Comment submission
function submitComment(recipeId) {
    const commentForm = document.getElementById(`comment-form-${recipeId}`);
    const commentInput = document.getElementById(`comment-input-${recipeId}`);
    
    if (!commentForm || !commentInput) return;
    
    const comment = commentInput.value.trim();
    if (!comment) return;

    fetch('/api/comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            recipe_id: recipeId,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add new comment to the list
            const commentsList = document.getElementById(`comments-${recipeId}`);
            if (commentsList) {
                const newComment = document.createElement('div');
                newComment.className = 'comment fade-in';
                newComment.innerHTML = `
                    <div class="d-flex align-items-center mb-2">
                        <img src="${data.user_image}" class="rounded-circle me-2" width="32" height="32">
                        <strong>${data.username}</strong>
                    </div>
                    <p class="mb-1">${data.comment}</p>
                    <small class="text-muted">Just now</small>
                `;
                commentsList.insertBefore(newComment, commentsList.firstChild);
            }
            
            // Clear input
            commentInput.value = '';
        }
    })
    .catch(error => console.error('Error:', error));
}

// Friend request handling
function handleFriendRequest(userId, action) {
    fetch('/api/friend-request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_id: userId,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const requestElement = document.getElementById(`friend-request-${userId}`);
            if (requestElement) {
                requestElement.remove();
            }
            
            // Show success message
            const message = action === 'accept' ? 'Friend request accepted!' : 'Friend request rejected';
            showNotification(message, 'success');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification fade-in`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

// Message handling
function sendMessage(receiverId) {
    const messageInput = document.getElementById(`message-input-${receiverId}`);
    if (!messageInput) return;
    
    const message = messageInput.value.trim();
    if (!message) return;

    fetch('/api/message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            receiver_id: receiverId,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add message to chat
            const chatContainer = document.getElementById(`chat-${receiverId}`);
            if (chatContainer) {
                const newMessage = document.createElement('div');
                newMessage.className = 'message message-sent fade-in';
                newMessage.textContent = message;
                chatContainer.appendChild(newMessage);
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
            
            // Clear input
            messageInput.value = '';
        }
    })
    .catch(error => console.error('Error:', error));
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}); 