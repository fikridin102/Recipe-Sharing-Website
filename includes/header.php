<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

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

    .navbar {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-bottom: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        padding: 0;
        position: sticky;
        top: 0;
        z-index: 1000;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .navbar.scrolled {
        box-shadow: var(--shadow-md);
        background: rgba(248, 250, 252, 0.95);
    }

    .navbar .container {
        padding: 0 1rem;
    }

    .nav-menu {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        width: 100%;
    }

    .navbar-brand {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--primary-color);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        position: relative;
    }

    .navbar-brand::before {
        content: '🍳';
        font-size: 1.5rem;
        animation: bounce 2s infinite;
    }

    .navbar-brand:hover {
        color: #1e40af;
        transform: translateY(-2px);
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-3px);
        }
        60% {
            transform: translateY(-2px);
        }
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .nav-links a {
        color: #475569;
        text-decoration: none;
        font-weight: 500;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        transition: all 0.2s ease;
        position: relative;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.95rem;
    }

    .nav-links a::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: var(--primary-color);
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }

    .nav-links a:hover {
        color: var(--primary-color);
        background: rgba(37, 99, 235, 0.08);
        transform: translateY(-2px);
    }

    .nav-links a:hover::before {
        width: 80%;
    }

    /* Special styling for different link types */
    .nav-links a[href*="recipes"]::after {
        content: '🍽️';
        font-size: 0.9rem;
        opacity: 0.7;
    }

    .nav-links a[href*="recipe-requests"]::after {
        content: '📝';
        font-size: 0.9rem;
        opacity: 0.7;
    }

    .nav-links a[href*="profile"]::after {
        content: '👤';
        font-size: 0.9rem;
        opacity: 0.7;
    }

    .nav-links a[href*="login"] {
        background: linear-gradient(135deg, var(--primary-color), #3b82f6);
        color: white;
        border: 2px solid transparent;
    }

    .nav-links a[href*="login"]:hover {
        background: linear-gradient(135deg, #1e40af, var(--primary-color));
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        color: white;
    }

    .nav-links a[href*="register"] {
        background: linear-gradient(135deg, var(--success-color), #10b981);
        color: white;
        border: 2px solid transparent;
    }

    .nav-links a[href*="register"]:hover {
        background: linear-gradient(135deg, #047857, var(--success-color));
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        color: white;
    }

    .nav-links a[href*="logout"] {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: var(--danger-color);
        border: 2px solid #fca5a5;
    }

    .nav-links a[href*="logout"]:hover {
        background: var(--danger-color);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    /* Mobile Menu Toggle */
    .mobile-menu-toggle {
        display: none;
        flex-direction: column;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        background: transparent;
        border: none;
    }

    .mobile-menu-toggle:hover {
        background: rgba(37, 99, 235, 0.08);
    }

    .mobile-menu-toggle span {
        width: 25px;
        height: 3px;
        background: var(--primary-color);
        margin: 3px 0;
        transition: all 0.3s ease;
        border-radius: 2px;
    }

    .mobile-menu-toggle.active span:nth-child(1) {
        transform: rotate(-45deg) translate(-5px, 6px);
    }

    .mobile-menu-toggle.active span:nth-child(2) {
        opacity: 0;
    }

    .mobile-menu-toggle.active span:nth-child(3) {
        transform: rotate(45deg) translate(-5px, -6px);
    }

    /* User Badge for logged-in users */
    .user-badge {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        border: 1px solid #bae6fd;
        border-radius: 20px;
        padding: 0.5rem 1rem;
        color: #0369a1;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-right: 1rem;
    }

    .user-badge::before {
        content: '👋';
        font-size: 1rem;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .nav-links {
            gap: 0.25rem;
        }
        
        .nav-links a {
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 768px) {
        .mobile-menu-toggle {
            display: flex;
        }

        .nav-links {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            flex-direction: column;
            gap: 0;
            padding: 1rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        .nav-links.show {
            display: flex;
            animation: slideDown 0.3s ease-out;
        }

        .nav-links a {
            width: 100%;
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .user-badge {
            margin-right: 0;
            margin-bottom: 1rem;
            justify-content: center;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    }

    @media (max-width: 480px) {
        .navbar-brand {
            font-size: 1.5rem;
        }
        
        .nav-menu {
            padding: 0.75rem 0;
        }
    }

    /* Active page indicator */
    .nav-links a.active {
        background: var(--primary-color);
        color: white;
    }

    .nav-links a.active::before {
        width: 0;
    }

    /* Notification badge (for future use) */
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: var(--danger-color);
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
</style>

<header class="navbar" id="navbar">
    <div class="container">
        <nav class="nav-menu">
            <a href="../public/index.php" class="navbar-brand">RecipeHub</a>
            
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="nav-links" id="navLinks">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['username'])): ?>
                        <div class="user-badge">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>
                    <?php endif; ?>
                    <a href="../public/index.php">Recipes</a>
                    <a href="../public/recipe-requests.php">Recipe Requests</a>
                    <a href="../public/profile.php">Profile</a>
                    <a href="../public/logout.php">Logout</a>
                <?php else: ?>
                    <a href="../public/login.php">Login</a>
                    <a href="../public/register.php">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.getElementById('navbar');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('navLinks');

    // Mobile menu toggle
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navLinks.classList.toggle('show');
        });
    }

    // Close mobile menu when clicking on a link
    const navLinkItems = navLinks.querySelectorAll('a');
    navLinkItems.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                mobileMenuToggle.classList.remove('active');
                navLinks.classList.remove('show');
            }
        });
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!navbar.contains(e.target) && navLinks.classList.contains('show')) {
            mobileMenuToggle.classList.remove('active');
            navLinks.classList.remove('show');
        }
    });

    // Navbar scroll effect
    let lastScrollTop = 0;
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        
        lastScrollTop = scrollTop;
    });

    // Add active class to current page
    const currentPage = window.location.pathname.split('/').pop();
    navLinkItems.forEach(link => {
        const linkPage = link.getAttribute('href').split('/').pop();
        if (linkPage === currentPage || 
            (currentPage === '' && linkPage === 'index.php') ||
            (currentPage === 'index.php' && linkPage === 'index.php')) {
            link.classList.add('active');
        }
    });

    // Add hover sound effect (optional)
    navLinkItems.forEach(link => {
        link.addEventListener('mouseenter', function() {
            // You can add a subtle sound effect here if desired
            this.style.transition = 'all 0.2s cubic-bezier(0.4, 0, 0.2, 1)';
        });
    });
});
</script>r