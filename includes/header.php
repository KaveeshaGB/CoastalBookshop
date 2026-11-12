<?php
// Usage: set $pageTitle before including this file.
require_once __DIR__.'/config.php';
$loggedIn = !empty($_SESSION['customer_id']);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($pageTitle ?? 'Coastal Bookshop') ?></title>
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/home.css">
        <link rel="stylesheet" href="css/club.css">
        <link rel="stylesheet" href="css/cart.css">
        <script src="js/script.js" defer></script>
    </head>
    <body>
        <header>
            <img id="logo" src="images/logo.png" alt="logo image">
            <span id="heading">Coastal Bookshop</span>
            <nav class="main-nav">
                <a href="home.php">Home</a>
                <a href="order.php">Order</a>
                <a href="bookclub.php">Book Club</a>
            </nav>
            <nav class="sign-in main-nav">
                <?php if ($loggedIn): ?>
                    <span class="welcome">Welcome back, <strong><?= e($_SESSION['customer_name']) ?></strong>!</span>
                <?php else: ?>
                    <a class="authentication-link" href="register.php">Sign-in</a>
                    <a class="authentication-link" href="#" id="open-login">Log-in</a>
                <?php endif; ?>
 
                <?php if ($loggedIn): ?>
                    <a class="icon-link" href="cart.php" title="Cart"><img src="images/cart.jpg" alt="Cart"></a>
                <?php else: ?>
                    <!-- not logged in: open the log-in pop-up instead of the cart -->
                    <a class="icon-link" href="#" title="Cart"
                       onclick="document.getElementById('open-login').click(); return false;"><img src="images/cart.jpg" alt="Cart"></a>
                <?php endif; ?>
 
                <?php if ($loggedIn): ?>
                    <div class="profile-menu">
                        <button type="button" class="icon-link" id="profile-btn" title="Profile">
                            <img src="images/profile.jpg" alt="Profile">
                        </button>
                        <ul class="profile-dropdown" id="profile-dropdown">
                            <li><a href="profile.php">Profile</a></li>
                            <?php if (!empty($_SESSION['is_admin'])): ?>
                            <li><a href="admin_books.php">Manage Books</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php">Log-out</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </nav>
        </header>
 
        <?php if (!$loggedIn): ?>
        <!-- Log-in pop-up box (submitted with JS to login.php) -->
        <div id="login-modal" class="modal" hidden>
            <div class="modal-box">
                <button type="button" class="modal-close" id="close-login" aria-label="Close">&times;</button>
                <h3>Log-in</h3>
                <form id="login-form">
                    <label class="form-label" for="login-email">Email:</label>
                    <input type="email" id="login-email" name="email" required>
                    <br>
                    <label class="form-label" for="login-password">Password:</label>
                    <input type="password" id="login-password" name="password" required>
                    <br>
                    <p id="login-error" class="error" hidden></p>
                    <button type="submit" class="btn">Log-in</button>
                </form>
                <p>No account? <a href="register.php">Sign-in here</a></p>
            </div>
        </div>
        <?php endif; ?>
 
        <main>