<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Add database connection
$db = new Database();
$pdo = $db->getConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get request ID from URL
if (!isset($_GET['id'])) {
    header('Location: recipe-requests.php');
    exit();
}

$request_id = $_GET['id'];

// Get request details
$stmt = $pdo->prepare("SELECT * FROM recipe_requests WHERE id = ? AND user_id = ?");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: recipe-requests.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    if (!empty($title) && !empty($description)) {
        $stmt = $pdo->prepare("UPDATE recipe_requests SET title = ?, description = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $description, $status, $request_id, $user_id]);
        header("Location: view-request.php?id=$request_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Recipe Request - RecipeHub</title>
    
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
            background-color: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }

        .edit-hero {
            background: linear-gradient(135deg, var(--warning-color) 0%, #ea580c 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
            position: relative;
            overflow: hidden;
        }

        .edit-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .edit-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .edit-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }

        .edit-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .edit-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin: 0;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1.5rem;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #1e40af;
        }

        .edit-form-card {
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .form-label i {
            color: var(--primary-color);
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #fafafa;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
            outline: none;
        }

        .form-control.is-valid {
            border-color: var(--success-color);
            background: #f0fdf4;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .char-counter {
            font-size: 0.875rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: 0.5rem;
        }

        .status-select {
            position: relative;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .status-badge.open {
            background: #dbeafe;
            color: var(--primary-color);
        }

        .status-badge.fulfilled {
            background: #dcfce7;
            color: var(--success-color);
        }

        .status-badge.closed {
            background: #fee2e2;
            color: var(--danger-color);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .btn-update {
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            justify-content: center;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-cancel {
            background: white;
            color: var(--secondary-color);
            border: 2px solid var(--border-color);
            padding: 0.875rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-cancel:hover {
            border-color: var(--secondary-color);
            color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .form-help {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .preview-card {
            background: var(--light-gray);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .preview-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preview-content {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .fade-in-delay {
            animation: fadeIn 0.6s ease-out 0.2s both;
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
            .edit-title {
                font-size: 2rem;
            }
            
            .edit-form-card {
                padding: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-update,
            .btn-cancel {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4 fade-in">
        <!-- Back Link -->
        <a href="view-request.php?id=<?php echo $request_id; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Request
        </a>

        <!-- Edit Hero Section -->
        <div class="edit-hero">
            <div class="edit-content">
                <div class="edit-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <h1 class="edit-title">Edit Recipe Request</h1>
                <p class="edit-subtitle">Update your request details and manage its status</p>
            </div>
        </div>

        <div class="row fade-in-delay">
            <div class="col-lg-8">
                <!-- Edit Form -->
                <div class="edit-form-card">
                    <form method="POST" action="" id="editRequestForm">
                        <div class="form-group">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading"></i>
                                Request Title
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo htmlspecialchars($request['title']); ?>" 
                                   required
                                   maxlength="100"
                                   placeholder="Enter a clear, descriptive title for your request">
                            <div class="char-counter">
                                <span id="titleCount">0</span>/100 characters
                            </div>
                            <div class="form-help">
                                Make your title specific and engaging to attract more responses
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left"></i>
                                Description
                            </label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      required
                                      maxlength="1000"
                                      placeholder="Describe what kind of recipe you're looking for. Include details like cuisine type, dietary restrictions, cooking method, or any specific ingredients you'd like to use."><?php echo htmlspecialchars($request['description']); ?></textarea>
                            <div class="char-counter">
                                <span id="descCount">0</span>/1000 characters
                            </div>
                            <div class="form-help">
                                The more details you provide, the better recipes you'll receive
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">
                                <i class="fas fa-flag"></i>
                                Request Status
                            </label>
                            <select class="form-control" id="status" name="status">
                                <option value="open" <?php echo $request['status'] === 'open' ? 'selected' : ''; ?>>
                                    Open - Still accepting recipe submissions
                                </option>
                                <option value="fulfilled" <?php echo $request['status'] === 'fulfilled' ? 'selected' : ''; ?>>
                                    Fulfilled - Found the perfect recipe
                                </option>
                                <option value="closed" <?php echo $request['status'] === 'closed' ? 'selected' : ''; ?>>
                                    Closed - No longer accepting submissions
                                </option>
                            </select>
                            <div class="status-badge <?php echo $request['status']; ?>" id="statusBadge">
                                <i class="fas fa-circle"></i>
                                <span id="statusText">
                                    <?php 
                                    echo $request['status'] === 'open' ? 'Open' : 
                                        ($request['status'] === 'fulfilled' ? 'Fulfilled' : 'Closed'); 
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_request" class="btn-update">
                                <i class="fas fa-save"></i>
                                Update Request
                            </button>
                            <a href="view-request.php?id=<?php echo $request_id; ?>" class="btn-cancel">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Preview Card -->
                <div class="preview-card">
                    <div class="preview-title">
                        <i class="fas fa-eye"></i>
                        Live Preview
                    </div>
                    <div class="preview-content">
                        <h4 id="previewTitle"><?php echo htmlspecialchars($request['title']); ?></h4>
                        <p id="previewDescription"><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                        <div class="status-badge <?php echo $request['status']; ?>" id="previewStatus">
                            <i class="fas fa-circle"></i>
                            <span><?php echo ucfirst($request['status']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Tips Card -->
                <div class="preview-card">
                    <div class="preview-title">
                        <i class="fas fa-lightbulb"></i>
                        Tips for Better Requests
                    </div>
                    <div class="preview-content">
                        <div style="margin-bottom: 1rem;">
                            <strong>Be Specific</strong><br>
                            <small>Mention cuisine type, cooking methods, or dietary needs</small>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <strong>Include Context</strong><br>
                            <small>Share the occasion or why you need this recipe</small>
                        </div>
                        <div>
                            <strong>Set Clear Status</strong><br>
                            <small>Update status when you find the perfect recipe</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Character counters
        function updateCharCount(input, counter) {
            const count = input.value.length;
            const max = input.getAttribute('maxlength');
            counter.textContent = count;
            
            // Color coding
            if (count > max * 0.9) {
                counter.style.color = 'var(--danger-color)';
            } else if (count > max * 0.7) {
                counter.style.color = 'var(--warning-color)';
            } else {
                counter.style.color = 'var(--text-muted)';
            }
        }

        // Title character counter
        const titleInput = document.getElementById('title');
        const titleCounter = document.getElementById('titleCount');
        updateCharCount(titleInput, titleCounter);
        titleInput.addEventListener('input', () => updateCharCount(titleInput, titleCounter));

        // Description character counter
        const descInput = document.getElementById('description');
        const descCounter = document.getElementById('descCount');
        updateCharCount(descInput, descCounter);
        descInput.addEventListener('input', () => updateCharCount(descInput, descCounter));

        // Live preview updates
        titleInput.addEventListener('input', function() {
            document.getElementById('previewTitle').textContent = this.value || 'Recipe Request Title';
        });

        descInput.addEventListener('input', function() {
            const preview = document.getElementById('previewDescription');
            preview.innerHTML = this.value.replace(/\n/g, '<br>') || 'Request description will appear here...';
        });

        // Status badge updates
        const statusSelect = document.getElementById('status');
        const statusBadge = document.getElementById('statusBadge');
        const statusText = document.getElementById('statusText');
        const previewStatus = document.getElementById('previewStatus');

        statusSelect.addEventListener('change', function() {
            const status = this.value;
            const statusLabels = {
                'open': 'Open',
                'fulfilled': 'Fulfilled', 
                'closed': 'Closed'
            };
            
            // Update main badge
            statusBadge.className = `status-badge ${status}`;
            statusText.textContent = statusLabels[status];
            
            // Update preview badge
            previewStatus.className = `status-badge ${status}`;
            previewStatus.querySelector('span').textContent = statusLabels[status];
        });

        // Form validation
        document.getElementById('editRequestForm').addEventListener('submit', function(e) {
            const title = titleInput.value.trim();
            const description = descInput.value.trim();
            
            if (!title || !description) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            if (title.length < 5) {
                e.preventDefault();
                alert('Title must be at least 5 characters long.');
                titleInput.focus();
                return;
            }
            
            if (description.length < 20) {
                e.preventDefault();
                alert('Description must be at least 20 characters long.');
                descInput.focus();
                return;
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>