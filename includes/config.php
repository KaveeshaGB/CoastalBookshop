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
    global $likedIds;
    $liked = in_array($b['BookID'], $likedIds ?? [], true);
    $img = $b['ImageFile'] ? 'images/covers/' . $b['ImageFile'] : 'images/logo.png';

    echo '<div class="books">';
    echo '<img src="' . e($img) . '" alt="' . e($b['Title']) . '">';
    echo '<h4>' . e($b['Title']) . '</h4>';
    echo '<p>Rs.' . number_format($b['Price'], 2) . '</p>';

    echo '<div class="book-actions">';
    echo '<button type="button" class="heart-btn' . ($liked ? ' liked' : '') . '" data-book="' . e($b['BookID']) . '" aria-label="Like this book">&#9829;</button>';
    echo '<button type="button" class="cart-btn" data-book="' . e($b['BookID']) . '" aria-label="Add to cart"><img src="images/cart.jpg" alt=""></button>';
    echo '</div>';

    echo '<a class="order-link" href="order.php?book=' . urlencode($b['BookID']) . '">Order Now</a>';
    echo '</div>';
}

// IDs of the books this person liked (customer: BookLike table, visitor: session)
function getLikedIds(PDO $pdo): array {
    if (empty($_SESSION['customer_id'])) {
        return $_SESSION['guest_likes'] ?? [];
    }
    $st = $pdo->prepare('SELECT BookID FROM BookLike WHERE CustomerID = ?');
    $st->execute([$_SESSION['customer_id']]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

// The liked books themselves, newest first
function getSuggestedBooks(PDO $pdo, array $likedIds, int $limit = 4): array {
    if (!$likedIds) return [];
    $marks = implode(',', array_fill(0, count($likedIds), '?'));
    $st = $pdo->prepare(
        "SELECT * FROM Books
         WHERE BookID IN ($marks)
         ORDER BY DateAdded DESC
         LIMIT $limit"
    );
    $st->execute($likedIds);
    return $st->fetchAll();
}

// Optional: call right after login so a visitor's likes are kept
function mergeGuestLikes(PDO $pdo): void {
    if (empty($_SESSION['customer_id']) || empty($_SESSION['guest_likes'])) return;
    $st = $pdo->prepare('INSERT IGNORE INTO BookLike (CustomerID, BookID) VALUES (?, ?)');
    foreach ($_SESSION['guest_likes'] as $bookId) {
        $st->execute([$_SESSION['customer_id'], $bookId]);
    }
    unset($_SESSION['guest_likes']);
}

function nextId(PDO $pdo, string $table, string $column, string $prefix) {
    $start = strlen($prefix) + 1;
    $sql   = "SELECT COALESCE(MAX(CAST(SUBSTRING($column, $start) AS UNSIGNED)), 0) FROM $table";
    $next  = (int)$pdo->query($sql)->fetchColumn() + 1;
    return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
}