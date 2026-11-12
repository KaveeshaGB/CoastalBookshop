<?php
// Cart page: add (JSON, called by js/book-actions.js), update, remove, and the list of books.
// "Place Order" sends the visitor to order.php?from=cart (the Order tab).
require __DIR__.'/includes/config.php';
 
$loggedInNow = !empty($_SESSION['customer_id']);
$cart = $_SESSION['cart'] ?? [];
 
function fail(string $msg): void {
    $_SESSION['order_error'] = $msg;
    header('Location: cart.php');
    exit;
}
 
// ---------- "Add to Cart" icon under a book (replies with JSON) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    header('Content-Type: application/json');
 
    if (!$loggedInNow) {
        echo json_encode(['ok' => false, 'message' => 'Please log in or sign in to add books to your cart.', 'login' => true]);
        exit;
    }
 
    $bookId = trim($_POST['book_id'] ?? '');
    $qty    = max(1, (int)($_POST['quantity'] ?? 1));
 
    $stmt = $pdo->prepare('SELECT Title FROM Books WHERE BookID = ?');
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();
    if (!$book) {
        echo json_encode(['ok' => false, 'message' => 'Unknown book.']);
        exit;
    }
 
    $cart[$bookId] = min(20, ($cart[$bookId] ?? 0) + $qty);
    $_SESSION['cart'] = $cart;
    echo json_encode(['ok' => true, 'title' => $book['Title'], 'count' => array_sum($cart)]);
    exit;
}
 
// ---------- Update / remove ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
 
    if (in_array($action, ['update', 'remove'], true)) {
        $bookId = trim($_POST['book'] ?? '');
        $qty    = (int)($_POST['quantity'] ?? 1);
 
        if ($action === 'update') {
            if ($qty < 1 || $qty > 20) fail('Quantity must be between 1 and 20.');
            if (isset($cart[$bookId])) $cart[$bookId] = $qty;
        } else {
            unset($cart[$bookId]);
        }
        $_SESSION['cart'] = $cart;
        header('Location: cart.php');
        exit;
    }
}
 
// ---------- Show the cart page ----------
$items = [];
$subtotal = 0;
if ($loggedInNow && $cart) {
    $ids  = array_map('strval', array_keys($cart));
    $in   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT BookID, Title, Author, Price, ImageFile FROM Books WHERE BookID IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $b) {
        $b['Qty']  = $cart[$b['BookID']];
        $b['Line'] = $b['Price'] * $b['Qty'];
        $subtotal += $b['Line'];
        $items[]   = $b;
    }
}
 
$flashErr = $_SESSION['order_error'] ?? null;
unset($_SESSION['order_error']);
 
$pageTitle = 'My Cart - Coastal Bookshop';
require __DIR__.'/includes/header.php';
?>
            <h2>My Cart</h2>
 
            <?php if ($flashErr): ?><p class="error"><?= e($flashErr) ?></p><?php endif; ?>
 
            <?php if (!$loggedInNow): ?>
                <p class="message">Please log in or sign in to see your cart.</p>
                <script>
                    // open the log-in pop-up automatically
                    document.addEventListener('DOMContentLoaded', function () {
                        var link = document.getElementById('open-login');
                        if (link) link.click();
                    });
                </script>
 
            <?php elseif (!$items): ?>
                <p class="message">Your cart is empty. <a href="home.php">Browse books</a></p>
 
            <?php else: ?>
            <table class="cart-table">
                <tr><th>Book</th><th>Author</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr>
                <?php foreach ($items as $b): ?>
                <tr>
                    <td>
                        <?php if ($b['ImageFile']): ?>
                            <img src="images/covers/<?= e($b['ImageFile']) ?>" alt="" width="40">
                        <?php endif; ?>
                        <?= e($b['Title']) ?>
                    </td>
                    <td><?= e($b['Author']) ?></td>
                    <td>Rs.<?= number_format($b['Price'], 2) ?></td>
                    <td>
                        <form method="post" action="cart.php" class="cart-inline">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="book" value="<?= e($b['BookID']) ?>">
                            <input type="number" name="quantity" value="<?= (int)$b['Qty'] ?>" min="1" max="20">
                            <button type="submit" class="btn">Update</button>
                        </form>
                    </td>
                    <td>Rs.<?= number_format($b['Line'], 2) ?></td>
                    <td>
                        <form method="post" action="cart.php" class="cart-inline">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="book" value="<?= e($b['BookID']) ?>">
                            <button type="submit" class="btn">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr><td colspan="4"><strong>Subtotal</strong></td>
                    <td colspan="2"><strong>Rs.<?= number_format($subtotal, 2) ?></strong></td></tr>
            </table>
 
            <br>
            <a class="btn" href="order.php?from=cart" style="text-decoration:none;display:inline-block;">Place Order</a>
            <?php endif; ?>
<?php require __DIR__.'/includes/footer.php'; ?>