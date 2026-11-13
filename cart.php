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
 
    $stmt = $pdo->prepare('SELECT Title, Stock FROM Books WHERE BookID = ?');
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();
    if (!$book) {
        echo json_encode(['ok' => false, 'message' => 'Unknown book.']);
        exit;
    }
 
    // Stock check (the server always has the final say, not the button on the page)
    $stock = (int)$book['Stock'];
    $have  = (int)($cart[$bookId] ?? 0);
    if ($stock <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Sorry, "' . $book['Title'] . '" is out of stock.']);
        exit;
    }
    if ($have + $qty > $stock) {
        $msg = 'Only ' . $stock . ' cop' . ($stock === 1 ? 'y' : 'ies') . ' of "' . $book['Title'] . '" available';
        $msg .= $have ? ' (you already have ' . $have . ' in your cart).' : '.';
        echo json_encode(['ok' => false, 'message' => $msg]);
        exit;
    }

    $cart[$bookId] = min(20, $have + $qty);
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
            $st = $pdo->prepare('SELECT Stock FROM Books WHERE BookID = ?');
            $st->execute([$bookId]);
            $stock = (int)$st->fetchColumn();
            if ($qty > $stock) fail($stock > 0 ? 'Only ' . $stock . ' in stock.' : 'That book is out of stock.');
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
$stockProblem = false;   // true if something in the cart is sold out (or not enough copies)
if ($loggedInNow && $cart) {
    $ids  = array_map('strval', array_keys($cart));
    $in   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT BookID, Title, Author, Price, ImageFile, Stock, DiscountPercent, OfferEnds FROM Books WHERE BookID IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $b) {
        $b['Qty']  = $cart[$b['BookID']];
        $b['Unit'] = finalPrice($b);
        $b['Line'] = $b['Unit'] * $b['Qty'];
        $b['Short'] = $b['Qty'] > (int)$b['Stock'];
        if ($b['Short']) $stockProblem = true;
        else $subtotal += $b['Line'];
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
                        <?php if (isOutOfStock($b)): ?>
                            <br><span class="stock-warn">Out of stock</span>
                        <?php elseif ($b['Short']): ?>
                            <br><span class="stock-warn">Only <?= (int)$b['Stock'] ?> in stock</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($b['Author']) ?></td>
                    <td>
                        <?php if (hasOffer($b)): ?>
                            <span class="old-price">Rs.<?= number_format($b['Price'], 2) ?></span><br>
                            <span class="new-price">Rs.<?= number_format($b['Unit'], 2) ?></span>
                        <?php else: ?>
                            Rs.<?= number_format($b['Price'], 2) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" action="cart.php" class="cart-inline">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="book" value="<?= e($b['BookID']) ?>">
                            <input type="number" name="quantity" value="<?= (int)$b['Qty'] ?>" min="1" max="<?= max(1, min(20, (int)$b['Stock'])) ?>">
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
            <?php if ($stockProblem): ?>
                <p class="error">Some books in your cart are out of stock or have fewer copies than you chose. Please remove them or lower the quantity to continue.</p>
            <?php else: ?>
            <a class="btn" href="order.php?from=cart" style="text-decoration:none;display:inline-block;">Place Order</a>
            <?php endif; ?>
            <?php endif; ?>
<?php require __DIR__.'/includes/footer.php'; ?>