<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (loginUser($email, $password)) {
        header('Location: index.php');
        exit();
    } else {
        $error = 'Invalid email or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RecipeHub</title>
    
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
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
        }

        .auth-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
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
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
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

        .auth-footer {
            text-align: center;
            padding: 1.5rem 2rem;
            background: var(--light-gray);
            border-top: 1px solid var(--border-color);
        }

        .auth-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .auth-link:hover {
            color: #1e40af;
            text-decoration: underline;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--text-muted);
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .divider span {
            padding: 0 1rem;
            font-size: 0.9rem;
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
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <h1 class="auth-title">Welcome Back</h1>
                                <p class="auth-subtitle">Sign in to continue your culinary journey</p>
                            </div>
                        </div>

                        <div class="auth-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i>
                                        Email Address
                                    </label>
                                    <div class="input-group">
                                        <i class="fas fa-envelope input-icon"></i>
                                        <input type="email" class="form-control with-icon" id="email" name="email" 
                                               placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
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
                                               placeholder="Enter your password" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn-auth">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Sign In
                                </button>
                            </form>
                        </div>

                        <div class="auth-footer">
                            <p class="mb-0">
                                Don't have an account? 
                                <a href="register.php" class="auth-link">Create one here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>r