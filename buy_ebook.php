<?php
// Called by the Buy button on the My Bookshelf tab. Members only.
require_once __DIR__.'/includes/club_functions.php';

// Send the person back to the My Bookshelf tab with a message
function backToShelf(string $key, string $message): void {
    $_SESSION[$key] = $message;
    header('Location: bookclub.php#bookshelf');
    exit;
}

$me = $_SESSION['customer_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$me) {
    header('Location: bookclub.php#bookshelf');
    exit;
}
if (!isMember($pdo, $me)) {
    backToShelf('shelf_error', 'You are not a member, so you can\'t buy ebooks.');
}

$stmt = $pdo->prepare('SELECT EbookID, Title, Price FROM Ebook WHERE EbookID = ?');
$stmt->execute([trim($_POST['ebook_id'] ?? '')]);
$ebook = $stmt->fetch();
if (!$ebook) {
    backToShelf('shelf_error', 'That ebook was not found.');
}

try {
    $pdo->prepare('INSERT INTO Member_Ebook (CustomerID, EbookID, PricePaid) VALUES (?,?,?)')
        ->execute([$me, $ebook['EbookID'], $ebook['Price']]);
    backToShelf('shelf_msg', '"' . $ebook['Title'] . '" is now on your shelf.');
} catch (PDOException $ex) {
    // 23000 = this member already has this ebook
    if ($ex->getCode() === '23000') {
        backToShelf('shelf_error', 'You already own this ebook.');
    }
    backToShelf('shelf_error', 'Could not complete the purchase. Please try again.');
}