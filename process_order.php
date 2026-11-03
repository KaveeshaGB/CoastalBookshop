<?php
// Receives the order form and saves it to Orders, Order_Books, PaymentMethod, Parcel/Gift.
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['customer_id'])) {
    header('Location: order.php');
    exit;
}

function fail($msg) {
    $_SESSION['order_error'] = $msg;
    header('Location: order.php');
    exit;
}
$bookId   = trim($_POST['book_id'] ?? '');
$qty      = (int)($_POST['quantity'] ?? 1);
$delivery = $_POST['delivery_option'] ?? '';
$pack     = $_POST['packaging_type'] ?? 'Standard';
$address  = trim($_POST['delivery_address'] ?? '');
$instr    = trim($_POST['delivery_instructions'] ?? '');
$payment  = $_POST['payment_method'] ?? '';
$type     = $_POST['order_type'] ?? 'Parcel';

if ($qty < 1 || $qty > 20)                              fail('Quantity must be between 1 and 20.');
if (!isset(DELIVERY_COSTS[$delivery]))                  fail('Choose a valid delivery method.');
if (!in_array($pack, ['Standard', 'Protective'], true)) fail('Choose a valid packaging type.');
if (!in_array($payment, ['Card', 'Cash on Delivery', 'Bank Transfer'], true)) fail('Choose a valid payment method.');
if (!in_array($type, ['Parcel', 'Gift'], true))         fail('Choose a valid order type.');
if ($address === '')                                    fail('Delivery address is required.');

$recipient = trim($_POST['recipient_name'] ?? '');
if ($type === 'Gift' && $recipient === '')              fail('Recipient name is required for a gift.');

$stmt = $pdo->prepare('SELECT BookID, Price FROM Books WHERE BookID = ?');
$stmt->execute([$bookId]);
$book = $stmt->fetch();
if (!$book) fail('That book was not found.');

try {
    $pdo->beginTransaction();

    $orderId = nextId($pdo, 'Orders', 'OrderID', 'D');

    $pdo->prepare('INSERT INTO Orders (OrderID, DeliveryOption, PackagingType, DeliveryAddress, DeliveryInstructions, CustomerID)
                   VALUES (?,?,?,?,?,?)')
        ->execute([$orderId, $delivery, $pack, $address, $instr ?: null, $_SESSION['customer_id']]);

    $pdo->prepare('INSERT INTO Order_Books (OrderID, BookID, Quantity, UnitPrice) VALUES (?,?,?,?)')
        ->execute([$orderId, $book['BookID'], $qty, $book['Price']]);

    $pdo->prepare('INSERT INTO PaymentMethod (OrderID, MethodType) VALUES (?,?)')
        ->execute([$orderId, $payment]);

    if ($type === 'Gift') {
        $pdo->prepare('INSERT INTO Gift (OrderID, RecipientName, RecipientPhone, Message, DecorationType)
                       VALUES (?,?,?,?,?)')
            ->execute([
                $orderId, $recipient,
                trim($_POST['recipient_phone'] ?? '') ?: null,
                trim($_POST['gift_message'] ?? '') ?: null,
                ($_POST['decoration_type'] ?? '') ?: null,
            ]);
    } else {
        $pdo->prepare('INSERT INTO Parcel (OrderID) VALUES (?)')->execute([$orderId]);
    }

    $pdo->commit();
} catch (Exception $ex) {
    $pdo->rollBack();
    fail('Sorry, the order could not be saved. Please try again.');
}

$total = $book['Price'] * $qty + DELIVERY_COSTS[$delivery];
$_SESSION['order_success'] = 'Order ' . $orderId . ' placed! Total (including delivery): Rs.' . number_format($total, 2);
header('Location: order.php');
exit;
