document.addEventListener('DOMContentLoaded', function() {
    let selectedRating = 0;
    const ratingInput = document.getElementById('ratingInput');
    const feedbackForm = document.getElementById('feedbackForm');
    const commentBox = document.getElementById('commentBox');
    const charCount = document.getElementById('charCount');

    if (!feedbackForm) return;

    // Initialize on page load
    updateSendButton(selectedRating);
    if (charCount && commentBox) {
        charCount.textContent = `${commentBox.value.length}/280 characters`;
    }

    // Star click handler
    if (document.querySelector("#star-rating")) {
        document.querySelectorAll("#star-rating span").forEach((star) => {
            star.addEventListener("click", () => {
                selectedRating = parseInt(star.dataset.value);
                updateStars(selectedRating);
                updateSendButton(selectedRating);
                if (ratingInput) ratingInput.value = selectedRating;
            });
        });
    }

    // Tag buttons toggle
    document.querySelectorAll(".tag").forEach((btn) => {
        btn.addEventListener("click", () => {
            btn.classList.toggle("active");
        });
    });

    // Comment character count
    if (commentBox && charCount) {
        commentBox.addEventListener('input', () => {
            charCount.textContent = `${commentBox.value.length}/280 characters`;
        });
    }

    // Form submission
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', (e) => {
            if (selectedRating === 0) {
                e.preventDefault();
                alert("Please select a star rating before submitting.");
            }
        });
    }

    function updateStars(rating) {
        const stars = document.querySelectorAll("#star-rating span");
        if (stars.length > 0) {
            stars.forEach((star, index) => {
                star.classList.toggle("selected", index < rating);
            });
        }
    }

    function updateSendButton(rating) {
        const sendBtn = document.querySelector(".send-btn");
        if (sendBtn) {
            if (rating > 0) {
                sendBtn.disabled = false;
                sendBtn.classList.remove("disabled");
                sendBtn.style.backgroundColor = "orange";
                sendBtn.style.cursor = "pointer";
            } else {
                sendBtn.disabled = true;
                sendBtn.classList.add("disabled");
                sendBtn.style.backgroundColor = "grey";
                sendBtn.style.cursor = "not-allowed";
            }
        }
    }
});