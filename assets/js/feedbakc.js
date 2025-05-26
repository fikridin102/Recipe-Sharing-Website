document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll("#star-rating span");
    const sendBtn = document.querySelector(".send-btn");
    const ratingInput = document.getElementById("ratingInput");
    const commentBox = document.getElementById("commentBox");
    const charCount = document.getElementById("charCount");
    
    let selectedRating = 0;

    // Star rating functionality
    stars.forEach((star, index) => {
        // Click event - select rating
        star.addEventListener("click", function() {
            selectedRating = parseInt(this.dataset.value);
            
            // Update hidden input
            if (ratingInput) {
                ratingInput.value = selectedRating;
            }
            
            // Update star display
            updateStarDisplay(selectedRating);
            
            // Enable submit button
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.style.opacity = '1';
                sendBtn.style.cursor = 'pointer';
            }
        });

        // Hover effects
        star.addEventListener("mouseenter", function() {
            const hoverRating = parseInt(this.dataset.value);
            updateStarDisplay(hoverRating);
        });
    });

    // Reset stars on mouse leave to show selected rating
    const starContainer = document.getElementById("star-rating");
    if (starContainer) {
        starContainer.addEventListener("mouseleave", function() {
            updateStarDisplay(selectedRating);
        });
    }

    // Function to update star display
    function updateStarDisplay(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.style.color = '#ffa500'; // Orange for selected/hovered
                star.classList.add('selected');
            } else {
                star.style.color = '#ddd'; // Gray for unselected
                star.classList.remove('selected');
            }
        });
    }

    // Character counter for comment box
    if (commentBox && charCount) {
        commentBox.addEventListener("input", function() {
            const currentLength = this.value.length;
            charCount.textContent = `${currentLength}/280 characters`;
            
            // Change color when approaching limit
            if (currentLength > 250) {
                charCount.style.color = '#ff6b6b';
            } else if (currentLength > 200) {
                charCount.style.color = '#ffa500';
            } else {
                charCount.style.color = '#666';
            }
        });
    }

    // Form validation before submit
    const form = document.getElementById("feedbackForm");
    if (form) {
        form.addEventListener("submit", function(e) {
            if (selectedRating === 0) {
                e.preventDefault();
                alert("Please select a rating before submitting!");
                return false;
            }
        });
    }

    // Tag functionality (optional - if you want tags to be clickable)
    const tags = document.querySelectorAll(".tag");
    tags.forEach(tag => {
        tag.addEventListener("click", function() {
            this.classList.toggle("selected");
            // You can add logic here to handle selected tags if needed
        });
    });
});