<?php
// Shared setup: session + database connection + small helpers.
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = new PDO(
    'mysql:host=localhost;dbname=coastal_bookshop;charset=utf8mb4',
    'root',          // change to your MySQL user
    '',              // change to your MySQL password
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// Escape output safely
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Delivery costs (match the table on the Order page)
const DELIVERY_COSTS = [
    'Near Colombo' => 300,
    'Island wide'  => 500,
    'Express'      => 800,
];

// Shows one book card (used by home + category pages)
function renderBook(array $b) {
    $img = $b['ImageFile'] ? 'images/' . $b['ImageFile'] : 'images/logo.jpg';
    echo '<div class="books">';
    echo '<img src="' . e($img) . '" alt="' . e($b['Title']) . '">';
    echo '<h4>' . e($b['Title']) . '</h4>';
    echo '<p>Rs.' . number_format($b['Price'], 2) . '</p>';
    echo '<a class="order-link" href="order.php?book=' . (int)$b['BookID'] . '">Order Now</a>';
    echo '</div>';
}
