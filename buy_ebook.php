<?php
// Called by the Buy pop-up on the My Bookshelf tab. Members only.
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

// Card details: checked here, never saved (same rules as the order form)
$cardName   = trim($_POST['card_name'] ?? '');
$cardNumber = preg_replace('/[\s-]+/', '', $_POST['card_number'] ?? '');
$cardExpiry = trim($_POST['card_expiry'] ?? '');
$cardCvv    = trim($_POST['card_cvv'] ?? '');

if ($cardName === '')                       backToShelf('shelf_error', 'Enter the name on the card.');
if (!preg_match('/^\d{16}$/', $cardNumber)) backToShelf('shelf_error', 'Enter a 16-digit card number.');
if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $cardExpiry, $m)) backToShelf('shelf_error', 'Enter the expiry date as MM/YY.');
// A card is valid until the end of its expiry month
if (strtotime('20' . $m[2] . '-' . $m[1] . '-01 +1 month') <= time()) backToShelf('shelf_error', 'That card has expired.');
if (!preg_match('/^\d{3,4}$/', $cardCvv))   backToShelf('shelf_error', 'Enter a valid 3 or 4 digit CVV.');

$stmt = $pdo->prepare('SELECT EbookID, Title, Price, DiscountPercent, OfferEnds FROM Ebook WHERE EbookID = ?');
$stmt->execute([trim($_POST['ebook_id'] ?? '')]);
$ebook = $stmt->fetch();
if (!$ebook) {
    backToShelf('shelf_error', 'That ebook was not found.');
}

try {
    // PricePaid is the price after any running offer
    $pdo->prepare('INSERT INTO Member_Ebook (CustomerID, EbookID, PricePaid) VALUES (?,?,?)')
        ->execute([$me, $ebook['EbookID'], finalPrice($ebook)]);
    backToShelf('shelf_msg', '"' . $ebook['Title'] . '" is now on your shelf.');
} catch (PDOException $ex) {
    // 23000 = this member already has this ebook
    if ($ex->getCode() === '23000') {
        backToShelf('shelf_error', 'You already own this ebook.');
    }
    backToShelf('shelf_error', 'Could not complete the purchase. Please try again.');
}