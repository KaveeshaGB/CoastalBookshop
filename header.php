<?php
// Usage: set $pageTitle before including this file.
require_once 'config.php';
$loggedIn = !empty($_SESSION['customer_id']);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($pageTitle ?? 'Coastal Bookshop') ?></title>
        <link rel="stylesheet" href="home_style.css">
        <script src="script.js" defer></script>
    </head>
    <body>
        <header>
            <img id="logo" src="images/logo.jpg" alt="logo image">
            <span id="heading">Coastal Bookshop</span>
            <nav class="main-nav">
                <a href="home.php">Home</a>
                <a href="fiction.php">Fiction</a>
                <a href="novel.php">Novel</a>
                <a href="short_story.php">Short Story</a>
                <a href="order.php">Order</a>
            </nav>
            <nav class="sign-in main-nav">
                <?php if ($loggedIn): ?>
                    <span class="welcome">Hi, <?= e($_SESSION['customer_name']) ?></span>
                    <a href="logout.php">Log-out</a>
                <?php else: ?>
                    <a href="register.php">Sign-in</a>
                    <a href="#" id="open-login">Log-in</a>
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
