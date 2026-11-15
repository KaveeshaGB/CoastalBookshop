<?php
require_once __DIR__ . '/club_functions.php';

$me = $_SESSION['customer_id'] ?? null;

if (!isMember($pdo, $me)) {
    $_SESSION['club_error'] = 'Book club sessions are for members only. You can join as a member when you register.';
    header('Location: bookclub.php');
    exit;
}
