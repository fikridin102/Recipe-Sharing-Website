document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const stars = document.querySelectorAll("#star-rating span");
    const sendBtn = document.querySelector(".send-btn");
    const ratingInput = document.getElementById("ratingInput");
    const commentBox = document.getElementById("commentBox");
    const charCount = document.getElementById("charCount");
    const tagButtons = document.querySelectorAll('.tag');
    const selectedTagsInput = document.getElementById('selectedTags');
    
    // State Variables
    let selectedRating = 0;
    let selectedTags = [];

    // ===== STAR RATING FUNCTIONALITY =====
    stars.forEach(star => {
        star.addEventListener("click", () => {
            selectedRating = parseInt(star.getAttribute("data-value"));
            ratingInput.value = selectedRating;

            stars.forEach(s => s.classList.remove("selected"));
            for (let i = 0; i < selectedRating; i++) {
                stars[i].classList.add("selected");
            }

            if (selectedRating > 0) {
                submitBtn.removeAttribute("disabled");
            }
        });
    });

    //form validation?
    const form = document.getElementById("feedbackForm");
    if (form) {
        form.addEventListener("submit", function (e) {
            if (selectedRating === 0) {
                e.preventDefault();
                alert("Please select a rating before submitting!");
            }
        });
    }

    // Reset stars on mouse leave
    const starContainer = document.getElementById("star-rating");
    if (starContainer) {
        starContainer.addEventListener("mouseleave", () => updateStarDisplay(selectedRating));
    }

    // ===== TAG FUNCTIONALITY =====
    tagButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tagValue = this.textContent.trim(); // Or this.dataset.value if you add it
            
            // Toggle selection
            this.classList.toggle('selected');
            
            // Update selectedTags array
            if (this.classList.contains('selected')) {
                selectedTags.push(tagValue);
            } else {
                selectedTags = selectedTags.filter(tag => tag !== tagValue);
            }
            
            // Update hidden input
            selectedTagsInput.value = selectedTags.join(',');
        });
    });

    // ===== COMMENT BOX CHARACTER COUNTER =====
    if (commentBox && charCount) {
        commentBox.addEventListener("input", function() {
            const currentLength = this.value.length;
            charCount.textContent = `${currentLength}/280 characters`;
            
            // Visual feedback for length
            charCount.style.color = 
                currentLength > 250 ? '#ff6b6b' : 
                currentLength > 200 ? '#ffa500' : '#666';
        });
    }


    // ===== HELPER FUNCTIONS =====
    function updateStarDisplay(rating) {
        stars.forEach((star, index) => {
            star.style.color = index < rating ? '#ffa500' : '#ddd';
            star.classList.toggle('selected', index < rating);
        });
    }

    function enableSubmitButton() {
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.style.opacity = '1';
            sendBtn.style.cursor = 'pointer';
        }
    }
});