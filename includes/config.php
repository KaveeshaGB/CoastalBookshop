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

// Extra charges added to the order total
const PROTECTIVE_BOX_FEE = 200;   // when "Protective box" packaging is chosen
const GIFT_FEE           = 500;   // when the order type is "Gift"
const EBOOK_DIR = __DIR__ . '/../ebooks/copy';
// ---------- Offers and stock ----------
// Is a discount running for this book right now? (DiscountPercent is set and OfferEnds has not passed)
function hasOffer(array $b): bool {
    if ((int)($b['DiscountPercent'] ?? 0) <= 0) return false;
    return empty($b['OfferEnds']) || $b['OfferEnds'] >= date('Y-m-d');
}

// The price the customer actually pays (discount applied while the offer is running)
function finalPrice(array $b): float {
    $price = (float)$b['Price'];
    if (!hasOffer($b)) return $price;
    return round($price * (100 - (int)$b['DiscountPercent']) / 100, 2);
}

// True when no copies are left
function isOutOfStock(array $b): bool {
    return (int)($b['Stock'] ?? 0) <= 0;
}

// Shows one book card (used by home + category pages)
function renderBook(array $b) {
    global $likedIds;
    $liked = in_array($b['BookID'], $likedIds ?? [], true);
    $img = $b['ImageFile'] ? 'images/covers/' . $b['ImageFile'] : 'images/logo.png';
    $out   = isOutOfStock($b);
    $offer = hasOffer($b);
    $left  = (int)($b['Stock'] ?? 0);

    echo '<div class="books' . ($out ? ' out-of-stock' : '') . '">';
    if ($out) {
        echo '<span class="book-badge badge-out">Out of Stock</span>';
    } elseif ($offer) {
        echo '<span class="book-badge badge-offer">-' . (int)$b['DiscountPercent'] . '%</span>';
    }
    echo '<img src="' . e($img) . '" alt="' . e($b['Title']) . '">';
    echo '<h4>' . e($b['Title']) . '</h4>';
    if ($offer) {
        echo '<p><span class="old-price">Rs.' . number_format($b['Price'], 2) . '</span> '
           . '<span class="new-price">Rs.' . number_format(finalPrice($b), 2) . '</span></p>';
        if (!empty($b['OfferEnds'])) {
            echo '<p class="offer-ends">Offer ends ' . e($b['OfferEnds']) . '</p>';
        }
    } else {
        echo '<p>Rs.' . number_format($b['Price'], 2) . '</p>';
    }
    if (!$out && $left <= 5) {
        echo '<p class="low-stock">Only ' . $left . ' left</p>';
    }

    echo '<div class="book-actions">';
    echo '<button type="button" class="heart-btn' . ($liked ? ' liked' : '') . '" data-book="' . e($b['BookID']) . '" aria-label="Like this book">&#9829;</button>';
    if ($out) {
        // No "cart-btn" class on purpose, so book-actions.js ignores it
        echo '<button type="button" class="cart-off" disabled title="Out of stock" aria-label="Out of stock"><img src="images/cart.jpg" alt=""></button>';
    } else {
        echo '<button type="button" class="cart-btn" data-book="' . e($b['BookID']) . '" aria-label="Add to cart"><img src="images/cart.jpg" alt=""></button>';
    }
    echo '</div>';

    if ($out) {
        echo '<span class="order-link disabled">Out of Stock</span>';
    } else {
        echo '<a class="order-link" href="order.php?book=' . urlencode($b['BookID']) . '">Order Now</a>';
    }
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