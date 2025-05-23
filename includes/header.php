<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="navbar">
    <div class="container">
        <nav class="nav-menu">
            <a href="../public/index.php" class="navbar-brand">RecipeHub</a>
            <div class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="../public/recipe.php">Recipes</a>
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