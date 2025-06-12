<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Handle profile image upload
        $profile_image = 'default.jpg'; // Default image
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_result = uploadImage($_FILES['profile_image'], '../assets/images/profiles/');
            if ($upload_result) {
                $profile_image = basename($upload_result);
            }
        }
        
        // Register user
        if (registerUser($username, $email, $password, $profile_image)) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RecipeHub</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --light-gray: #f8fafc;
            --border-color: #e2e8f0;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --border-radius: 12px;
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }

        .auth-card {
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }

        .auth-header {
            background: linear-gradient(135deg, var(--success-color) 0%, #10b981 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .auth-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .auth-header-content {
            position: relative;
            z-index: 2;
        }

        .auth-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .auth-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            opacity: 0.9;
            margin: 0;
        }

        .auth-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #fafafa;
        }

        .form-control:focus {
            border-color: var(--success-color);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
            background: white;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 3;
        }

        .form-control.with-icon {
            padding-left: 2.75rem;
        }

        .btn-auth {
            background: linear-gradient(135deg, var(--success-color), #10b981);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-danger {
            background: #fef2f2;
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .alert-success {
            background: #f0fdf4;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .auth-footer {
            text-align: center;
            padding: 1.5rem 2rem;
            background: var(--light-gray);
            border-top: 1px solid var(--border-color);
        }

        .auth-link {
            color: var(--success-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .auth-link:hover {
            color: #047857;
            text-decoration: underline;
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .strength-indicator {
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            margin-top: 0.25rem;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak .strength-fill {
            width: 33%;
            background: var(--danger-color);
        }

        .strength-medium .strength-fill {
            width: 66%;
            background: var(--warning-color);
        }

        .strength-strong .strength-fill {
            width: 100%;
            background: var(--success-color);
        }

        .file-upload-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload-input {
            display: none;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            background: #fafafa;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--text-muted);
            font-weight: 500;
        }

        .file-upload-label:hover {
            border-color: var(--success-color);
            background: #f0fdf4;
            color: var(--success-color);
        }

        .file-upload-label.has-file {
            border-color: var(--success-color);
            background: #f0fdf4;
            color: var(--success-color);
        }

        .file-upload-icon {
            font-size: 1.2rem;
        }

        .file-upload-text {
            flex: 1;
        }

        .file-name {
            font-size: 0.9rem;
            color: var(--success-color);
            margin-top: 0.25rem;
        }

        .profile-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--success-color);
            margin-top: 1rem;
            display: none;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .requirement.met {
            color: var(--success-color);
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .auth-container {
                padding: 1rem;
            }
            
            .auth-body {
                padding: 1.5rem;
            }
            
            .auth-header {
                padding: 1.5rem;
            }
            
            .auth-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="auth-container fade-in">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="auth-card">
                        <div class="auth-header">
                            <div class="auth-header-content">
                                <div class="auth-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <h1 class="auth-title">Join RecipeHub</h1>
                                <p class="auth-subtitle">Start sharing your culinary creations today</p>
                            </div>
                        </div>

                        <div class="auth-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <?php echo htmlspecialchars($success); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" enctype="multipart/form-data" id="registerForm">
                                <div class="form-group">
                                    <label for="profile_image" class="form-label">
                                        <i class="fas fa-camera"></i>
                                        Profile Image (Optional)
                                    </label>
                                    <div class="file-upload-wrapper">
                                        <input type="file" class="file-upload-input" id="profile_image" name="profile_image" accept="image/*">
                                        <label for="profile_image" class="file-upload-label">
                                            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                                            <div class="file-upload-text">
                                                <div>Choose a profile picture</div>
                                                <small>JPG, PNG, or GIF (Max 5MB)</small>
                                            </div>
                                        </label>
                                        <div class="file-name" id="fileName" style="display: none;"></div>
                                        <img class="profile-preview" id="profilePreview" alt="Profile preview">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user"></i>
                                        Username
                                    </label>
                                    <div class="input-group">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" class="form-control with-icon" id="username" name="username" 
                                               placeholder="Choose a username" required 
                                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i>
                                        Email Address
                                    </label>
                                    <div class="input-group">
                                        <i class="fas fa-envelope input-icon"></i>
                                        <input type="email" class="form-control with-icon" id="email" name="email" 
                                               placeholder="Enter your email" required 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock"></i>
                                        Password
                                    </label>
                                    <div class="input-group">
                                        <i class="fas fa-lock input-icon"></i>
                                        <input type="password" class="form-control with-icon" id="password" name="password" 
                                               placeholder="Create a password" required>
                                    </div>
                                    <div class="password-strength" id="passwordStrength" style="display: none;">
                                        <div class="strength-indicator">
                                            <div class="strength-fill"></div>
                                        </div>
                                        <div class="password-requirements">
                                            <div class="requirement" id="lengthReq">
                                                <i class="fas fa-times"></i>
                                                At least 6 characters
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock"></i>
                                        Confirm Password
                                    </label>
                                    <div class="input-group">
                                        <i class="fas fa-lock input-icon"></i>
                                        <input type="password" class="form-control with-icon" id="confirm_password" name="confirm_password" 
                                               placeholder="Confirm your password" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn-auth">
                                    <i class="fas fa-user-plus"></i>
                                    Create Account
                                </button>
                            </form>
                        </div>

                        <div class="auth-footer">
                            <p class="mb-0">
                                Already have an account? 
                                <a href="login.php" class="auth-link">Sign in here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthIndicator = document.getElementById('passwordStrength');
        const lengthReq = document.getElementById('lengthReq');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = strengthIndicator.querySelector('.strength-indicator');
            
            if (password.length > 0) {
                strengthIndicator.style.display = 'block';
                
                // Check length requirement
                if (password.length >= 6) {
                    lengthReq.classList.add('met');
                    lengthReq.querySelector('i').className = 'fas fa-check';
                    strength.className = 'strength-indicator strength-strong';
                } else {
                    lengthReq.classList.remove('met');
                    lengthReq.querySelector('i').className = 'fas fa-times';
                    strength.className = 'strength-indicator strength-weak';
                }
            } else {
                strengthIndicator.style.display = 'none';
            }
        });

        // Confirm password validation
        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    this.style.borderColor = 'var(--success-color)';
                } else {
                    this.style.borderColor = 'var(--danger-color)';
                }
            } else {
                this.style.borderColor = 'var(--border-color)';
            }
        });

        // File upload handling
        const fileInput = document.getElementById('profile_image');
        const fileLabel = document.querySelector('.file-upload-label');
        const fileName = document.getElementById('fileName');
        const profilePreview = document.getElementById('profilePreview');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Show file name
                fileName.textContent = file.name;
                fileName.style.display = 'block';
                fileLabel.classList.add('has-file');
                
                // Update label text
                const textDiv = fileLabel.querySelector('.file-upload-text div');
                textDiv.textContent = 'Profile picture selected';
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePreview.src = e.target.result;
                    profilePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                // Reset to default state
                fileName.style.display = 'none';
                fileLabel.classList.remove('has-file');
                profilePreview.style.display = 'none';
                
                const textDiv = fileLabel.querySelector('.file-upload-text div');
                textDiv.textContent = 'Choose a profile picture';
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>