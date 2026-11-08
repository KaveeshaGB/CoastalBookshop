<?php
// Shared setup: session + database connection + small helpers.
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('Asia/Colombo');

$pdo = new PDO(
    'mysql:host=localhost;dbname=coastal_bookshop;charset=utf8mb4',
    'root',          // change to your MySQL user
    '',              // change to your MySQL password
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+05:30'",
    ]
);

// Escape output safely
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
$categories = [
    'Fiction'     => 'fiction.php',
    'Novel'       => 'novel.php',
    'Short Story' => 'short_story.php',
    'Kids Story'  => 'kids_story.php',
];

// Delivery costs (match the table on the Order page)
const DELIVERY_COSTS = [
    'Near Colombo' => 300,
    'Island wide'  => 500,
    'Express'      => 800,
];

// Shows one book card (used by home + category pages)
function renderBook(array $b) {
    $img = $b['ImageFile'] ? 'images/covers/' . $b['ImageFile'] : 'images/logo.jpg';
    echo '<div class="books">';
    echo '<img src="' . e($img) . '" alt="' . e($b['Title']) . '">';
    echo '<h4>' . e($b['Title']) . '</h4>';
    echo '<p>Rs.' . number_format($b['Price'], 2) . '</p>';
    echo '<a class="order-link" href="order.php?book=' . urlencode($b['BookID']) . '">Order Now</a>';    echo '</div>';
}

function nextId(PDO $pdo, string $table, string $column, string $prefix) {
    $sql  = "SELECT COALESCE(MAX(CAST(SUBSTRING($column, 2) AS UNSIGNED)), 0) FROM $table";
    $next = (int)$pdo->query($sql)->fetchColumn() + 1;
    return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
}