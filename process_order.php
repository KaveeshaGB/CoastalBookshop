<?php
// Receives the order form and saves it to Orders, Order_Books, PaymentMethod, Parcel/Gift.
require __DIR__ . '/includes/config.php';
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['customer_id'])) {
    header('Location: order.php');
    exit;
}
 
// Thrown inside the order transaction when a book has run out
class OutOfStockException extends Exception {}

function fail($msg) {
    $_SESSION['order_error'] = $msg;
    $back = !empty($_POST['from_cart']) ? 'order.php?from=cart' : 'order.php';
    header('Location: ' . $back);
    exit;
}
 
// Two modes: one book from the form (Order Now), or every book in the cart (from_cart=1)
$fromCart = !empty($_POST['from_cart']);
 
$bookId   = trim($_POST['book_id'] ?? '');
$qty      = (int)($_POST['quantity'] ?? 1);
$delivery = $_POST['delivery_option'] ?? '';
$pack     = $_POST['packaging_type'] ?? 'Standard';
$address  = trim($_POST['delivery_address'] ?? '');
$instr    = trim($_POST['delivery_instructions'] ?? '');
$payment  = $_POST['payment_method'] ?? '';
$type     = $_POST['order_type'] ?? 'Parcel';
 
if (!$fromCart && ($qty < 1 || $qty > 20))              fail('Quantity must be between 1 and 20.');
if (!isset(DELIVERY_COSTS[$delivery]))                  fail('Choose a valid delivery method.');
if (!in_array($pack, ['Standard', 'Protective'], true)) fail('Choose a valid packaging type.');
if (!in_array($payment, ['Card', 'Cash on Delivery'], true)) fail('Choose a valid payment method.');
if (!in_array($type, ['Parcel', 'Gift'], true))         fail('Choose a valid order type.');
if ($address === '')                                    fail('Delivery address is required.');
 
// Card details: checked here, but never saved (only the payment method is stored).
if ($payment === 'Card') {
    $cardName   = trim($_POST['card_name'] ?? '');
    $cardNumber = preg_replace('/[\s-]+/', '', $_POST['card_number'] ?? '');
    $cardExpiry = trim($_POST['card_expiry'] ?? '');
    $cardCvv    = trim($_POST['card_cvv'] ?? '');
 
    if ($cardName === '')                                fail('Enter the name on the card.');
    if (!preg_match('/^\d{16}$/', $cardNumber))        fail('Enter a 16-digit card number.');
    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $cardExpiry, $m)) fail('Enter the expiry date as MM/YY.');
    // A card is valid until the end of its expiry month
    if (strtotime('20' . $m[2] . '-' . $m[1] . '-01 +1 month') <= time())  fail('That card has expired.');
    if (!preg_match('/^\d{3,4}$/', $cardCvv))            fail('Enter a valid 3 or 4 digit CVV.');
}
 
$recipient = trim($_POST['recipient_name'] ?? '');
if ($type === 'Gift' && $recipient === '')              fail('Recipient name is required for a gift.');
 
// Decoration: Option 1-7 (photos in images/Decorations) or None
$decorations = ['Option 1', 'Option 2', 'Option 3', 'Option 4', 'Option 5', 'Option 6', 'Option 7', 'None'];
$decoration  = $_POST['decoration_type'] ?? 'None';
if ($type === 'Gift' && !in_array($decoration, $decorations, true)) fail('Choose a valid decoration.');
 
// The session can outlive the database row (e.g. after re-importing the database).
// Make sure this customer really exists, otherwise the Orders foreign key fails.
$chk = $pdo->prepare('SELECT 1 FROM Customer WHERE CustomerID = ?');
$chk->execute([$_SESSION['customer_id']]);
if (!$chk->fetch()) {
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['is_admin']);
    fail('Your account was not found. Please register again or log in with an existing account.');
}
 
// $lines = [[BookID, Title, Price, Qty], ...]
$lines = [];
if ($fromCart) {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) fail('Your cart is empty.');
 
    // Prices come from the database, never from the browser
    $ids  = array_map('strval', array_keys($cart));
    $in   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT BookID, Title, Price, DiscountPercent, OfferEnds FROM Books WHERE BookID IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $r) {
        $lines[] = [$r['BookID'], $r['Title'], finalPrice($r), (int)$cart[$r['BookID']]];
    }
    if (!$lines) fail('Your cart is empty.');
} else {
    $stmt = $pdo->prepare('SELECT BookID, Title, Price, DiscountPercent, OfferEnds FROM Books WHERE BookID = ?');
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();
    if (!$book) fail('That book was not found.');
    $lines[] = [$book['BookID'], $book['Title'], finalPrice($book), $qty];
}
 
try {
    $pdo->beginTransaction();
 
    $orderId = nextId($pdo, 'Orders', 'OrderID', 'D');
 
    $pdo->prepare('INSERT INTO Orders (OrderID, DeliveryOption, PackagingType, DeliveryAddress, DeliveryInstructions, CustomerID)
                   VALUES (?,?,?,?,?,?)')
        ->execute([$orderId, $delivery, $pack, $address, $instr ?: null, $_SESSION['customer_id']]);
 
    $ins = $pdo->prepare('INSERT INTO Order_Books (OrderID, BookID, Quantity, UnitPrice) VALUES (?,?,?,?)');
    // Takes the copies off the stock. The "AND Stock >= ?" part means it only works if enough are left,
    // so two customers cannot both buy the last copy.
    $take = $pdo->prepare('UPDATE Books SET Stock = Stock - ? WHERE BookID = ? AND Stock >= ?');
    $subtotal = 0;
    foreach ($lines as [$lineId, $lineTitle, $linePrice, $lineQty]) {
        $take->execute([$lineQty, $lineId, $lineQty]);
        if ($take->rowCount() === 0) {
            throw new OutOfStockException('Sorry, "' . $lineTitle . '" is out of stock or has fewer copies than you asked for.');
        }
        $ins->execute([$orderId, $lineId, $lineQty, $linePrice]);
        $subtotal += $linePrice * $lineQty;
    }
 
    $pdo->prepare('INSERT INTO PaymentMethod (OrderID, MethodType) VALUES (?,?)')
        ->execute([$orderId, $payment]);
 
    if ($type === 'Gift') {
        $pdo->prepare('INSERT INTO Gift (OrderID, RecipientName, RecipientPhone, Message, DecorationType)
                       VALUES (?,?,?,?,?)')
            ->execute([
                $orderId, $recipient,
                trim($_POST['recipient_phone'] ?? '') ?: null,
                trim($_POST['gift_message'] ?? '') ?: null,
                $decoration,
            ]);
    } else {
        $pdo->prepare('INSERT INTO Parcel (OrderID) VALUES (?)')->execute([$orderId]);
    }
 
    $pdo->commit();
} catch (OutOfStockException $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail($ex->getMessage());
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order save failed: ' . $ex->getMessage());
    fail('Sorry, the order could not be saved. Please try again.');
}
 
if ($fromCart) unset($_SESSION['cart']);   // order saved, empty the cart
 
$items = [];
foreach ($lines as [$lineId, $lineTitle, $linePrice, $lineQty]) {
    $items[] = ['title' => $lineTitle, 'price' => $linePrice, 'qty' => $lineQty];
}
 
$total = $subtotal + DELIVERY_COSTS[$delivery];
$_SESSION['order_receipt'] = [
    'id'         => $orderId,
    'items'      => $items,
    'delivery'   => $delivery,
    'delivery_fee' => (float) DELIVERY_COSTS[$delivery],
    'total'      => (float) $total,
    'decoration' => ($type === 'Gift' && $decoration !== 'None') ? $decoration : null,
];
header('Location: order.php');
exit;