<?php
// Put "require 'member_check.php';" at the very top of every members-only page.
// After it runs, $me holds the logged-in member's CustomerID.
require_once 'club_functions.php';

$me = $_SESSION['customer_id'] ?? null;

if (!isMember($pdo, $me)) {
    $_SESSION['club_error'] = 'Book club sessions are for members only. You can join as a member when you register.';
    header('Location: bookclub.php');
    exit;
}
