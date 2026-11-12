<?php
$pageTitle = 'Book Order';
require __DIR__.'/includes/header.php';
 
$books = $pdo->query('SELECT BookID, Title, Price, Category FROM Books ORDER BY Title')->fetchAll();
$selected = $_GET['book'] ?? '';
 
// Cart mode: opened from the cart's "Place Order" button (order.php?from=cart)
$fromCart     = ($_GET['from'] ?? '') === 'cart';
$cartItems    = [];
$cartSubtotal = 0;
if ($fromCart && $loggedIn && !empty($_SESSION['cart'])) {
    $ids  = array_map('strval', array_keys($_SESSION['cart']));
    $in   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT BookID, Title, Author, Price FROM Books WHERE BookID IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $b) {
        $b['Qty']  = $_SESSION['cart'][$b['BookID']];
        $b['Line'] = $b['Price'] * $b['Qty'];
        $cartSubtotal += $b['Line'];
        $cartItems[] = $b;
    }
}
$cartMode = $fromCart && $cartItems;
 
$receipt  = $_SESSION['order_receipt'] ?? null;
$flashOk  = $_SESSION['order_success'] ?? null;
$flashErr = $_SESSION['order_error'] ?? null;
unset($_SESSION['order_receipt'], $_SESSION['order_success'], $_SESSION['order_error']);
?>
            <h2>Book Order Form</h2>
 
            <?php if ($receipt): ?>
            <script src="js/confetti.js"></script>
            <div id="order-modal" class="modal">
                <div class="modal-box order-receipt" role="dialog" aria-labelledby="receipt-title">
                    <button type="button" class="modal-close" id="close-order-modal" aria-label="Close">&times;</button>
                    <h3 id="receipt-title">Your order has been placed.</h3>
                    <p class="receipt-id">Order <?= e($receipt['id']) ?></p>
                    <table class="receipt-table">
                        <?php foreach ($receipt['items'] as $it): ?>
                        <tr><th>Book</th><td><?= e($it['title']) ?></td></tr>
                        <tr><th>Price</th><td>Rs.<?= number_format($it['price'], 2) ?> x <?= (int) $it['qty'] ?></td></tr>
                        <?php endforeach; ?>
                        <?php if ($receipt['decoration']): ?>
                        <tr><th>Decoration</th><td>
                            <img class="receipt-deco" src="images/Decorations/<?= e(str_replace(' ', '', $receipt['decoration'])) ?>.jpeg" alt="<?= e($receipt['decoration']) ?>">
                        </td></tr>
                        <?php endif; ?>
                        <tr><th>Delivery</th><td><?= e($receipt['delivery']) ?> (Rs.<?= number_format($receipt['delivery_fee'], 2) ?>)</td></tr>
                        <tr><th>Total</th><td><strong>Rs.<?= number_format($receipt['total'], 2) ?></strong></td></tr>
                    </table>
                    <button type="button" class="btn" id="ok-order-modal">OK</button>
                </div>
            </div>
            <script>
                (function () {
                    var m = document.getElementById('order-modal');
                    if (typeof launchConfetti === 'function') launchConfetti();
                    function close() { m.hidden = true; }
                    document.getElementById('close-order-modal').addEventListener('click', close);
                    document.getElementById('ok-order-modal').addEventListener('click', close);
                    m.addEventListener('click', function (e) { if (e.target === m) close(); });
                })();
            </script>
            <?php endif; ?>
 
            <?php if ($flashOk): ?><p class="message"><?= e($flashOk) ?></p><?php endif; ?>
            <?php if ($flashErr): ?><p class="error"><?= e($flashErr) ?></p><?php endif; ?>
 
            <div class="order-layout">
            <?php if (!$loggedIn): ?>
                <p class="message">Please <a href="register.php">sign in</a> or use the Log-in button to place an order.</p>
            <?php else: ?>
            <script>
                // Book data from the database, used by script.js to filter the book list
                window.BOOKS = <?= json_encode($books) ?>;
                window.SELECTED_BOOK = <?= json_encode($selected) ?>;
                window.DELIVERY_COSTS = <?= json_encode(DELIVERY_COSTS) ?>;
            </script>
 
            <form id="order-form" method="post" action="process_order.php">
                <label class="form-label">Full Name:</label>
                <input type="text" value="<?= e($_SESSION['customer_name']) ?>" disabled>
                <br>
 
                <?php if ($fromCart && !$cartItems): ?>
                    <p class="message">Your cart is empty. <a href="home.php">Browse books</a></p>
                <?php endif; ?>
 
                <?php if ($cartMode): ?>
                    <!-- Books come from the cart -->
                    <input type="hidden" name="from_cart" value="1">
 
                    <table class="cart-table">
                        <tr><th>Book</th><th>Author</th><th>Price</th><th>Qty</th><th>Total</th></tr>
                        <?php foreach ($cartItems as $b): ?>
                        <tr>
                            <td><?= e($b['Title']) ?></td>
                            <td><?= e($b['Author']) ?></td>
                            <td>Rs.<?= number_format($b['Price'], 2) ?></td>
                            <td><?= (int)$b['Qty'] ?></td>
                            <td>Rs.<?= number_format($b['Line'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr><td colspan="4"><strong>Subtotal</strong></td>
                            <td><strong>Rs.<?= number_format($cartSubtotal, 2) ?></strong></td></tr>
                    </table>
                    <p><a class="order-link" href="cart.php">Change cart</a></p>
 
                    <!-- The single-book fields stay in the page (hidden and switched off) so script.js keeps working -->
                    <div hidden>
                        <select id="book-type" disabled>
                            <option value="Fiction">Fiction</option>
                            <option value="Novel">Novel</option>
                            <option value="Short Story">Short Story</option>
                            <option value="Kids Story">Kids Story</option>
                        </select>
                        <select id="book-name" disabled></select>
                        <input type="number" id="quantity" value="1" disabled>
                    </div>
                <?php else: ?>
                <label class="form-label" for="book-type">Book Type:</label>
                <select id="book-type" name="book_type">
                    <option value="Fiction">Fiction</option>
                    <option value="Novel">Novel</option>
                    <option value="Short Story">Short Story</option>
                    <option value="Kids Story">Kids Story</option>
                </select>
                <br>
 
                <label class="form-label" for="book-name">Book Name:</label>
                <select id="book-name" name="book_id" required></select>
                <br>
 
                <label class="form-label" for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="20" required>
                <br>
                <?php endif; ?>
 
                <label class="form-label" for="delivery-method">Delivery Method:</label>
                <select id="delivery-method" name="delivery_option">
                    <option value="Near Colombo">Near Colombo</option>
                    <option value="Island wide">Island wide</option>
                    <option value="Express">Express delivery</option>
                </select>
                <br>
 
                <label class="form-label" for="packaging">Packaging:</label>
                <select id="packaging" name="packaging_type">
                    <option value="Standard">Standard</option>
                    <option value="Protective">Protective box</option>
                </select>
                <br>
 
                <label class="form-label" for="delivery-address">Delivery Address:</label>
                <input type="text" id="delivery-address" name="delivery_address" required>
                <br>
 
                <label class="form-label" for="instructions">Instructions:</label>
                <input type="text" id="instructions" name="delivery_instructions">
                <br>
 
                <label class="form-label" for="payment">Payment Method:</label>
                <select id="payment" name="payment_method">
                    <option value="Card">Card</option>
                    <option value="Cash on Delivery">Cash on Delivery</option>
                </select>
                <br>
 
                <div id="card-fields">
                    <label class="form-label" for="card-name">Name on Card:</label>
                    <input type="text" id="card-name" name="card_name" autocomplete="cc-name" maxlength="60">
                    <br>
                    <label class="form-label" for="card-number">Card Number:</label>
                    <input type="text" id="card-number" name="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="1234 5678 9012 3456" maxlength="19">
                    <br>
                    <label class="form-label" for="card-expiry">Expiry Date:</label>
                    <input type="text" id="card-expiry" name="card_expiry" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" maxlength="5">
                    <br>
                    <label class="form-label" for="card-cvv">CVV:</label>
                    <input type="password" id="card-cvv" name="card_cvv" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4">
                    <br>
                </div>
 
                <label class="form-label" for="order-type">Order Type:</label>
                <select id="order-type" name="order_type">
                    <option value="Parcel">Parcel</option>
                    <option value="Gift">Gift</option>
                </select>
                <br>
 
                <div id="gift-fields" hidden>
                    <label class="form-label" for="recipient-name">Recipient Name:</label>
                    <input type="text" id="recipient-name" name="recipient_name">
                    <br>
                    <label class="form-label" for="recipient-phone">Recipient Phone:</label>
                    <input type="tel" id="recipient-phone" name="recipient_phone">
                    <br>
                    <label class="form-label" for="gift-message">Message:</label>
                    <input type="text" id="gift-message" name="gift_message" maxlength="500">
                    <br>
                    <span class="form-label deco-label">Decoration:</span>
                    <div class="deco-grid" role="radiogroup" aria-label="Decoration">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                        <label class="deco-card">
                            <input type="radio" name="decoration_type" value="Option <?= $i ?>">
                            <img src="images/Decorations/Option<?= $i ?>.jpeg" alt="Decoration option <?= $i ?>">
                            <span>Option <?= $i ?></span>
                        </label>
                        <?php endfor; ?>
                        <label class="deco-card">
                            <input type="radio" name="decoration_type" value="None" checked>
                            <span class="deco-blank">No decoration</span>
                            <span>Option 8 - None</span>
                        </label>
                    </div>
                    <br>
                </div>
 
                <?php if ($cartMode): ?>
                    <!-- script.js writes its own total into #order-total, so the cart total has its own box -->
                    <p id="order-total" hidden></p>
                    <p id="cart-order-total" class="message"></p>
                <?php else: ?>
                    <p id="order-total" class="message"></p>
                <?php endif; ?>
                <button type="submit" class="btn">Place Order</button>
            </form>
 
            <?php if ($cartMode): ?>
            <script>
                (function () {
                    var subtotal = <?= json_encode((float)$cartSubtotal) ?>;
                    var costs    = <?= json_encode(DELIVERY_COSTS) ?>;
                    var delivery = document.getElementById('delivery-method');
                    var out      = document.getElementById('cart-order-total');
 
                    function refresh() {
                        var cost = costs[delivery.value] || 0;
                        out.textContent = 'Total (including delivery): Rs.' + (subtotal + cost).toFixed(2);
                    }
                    delivery.addEventListener('change', refresh);
                    refresh();
                })();
            </script>
            <?php endif; ?>
            <?php endif; ?>
 
            <aside class="delivery-info">
                <h3>Delivery Fees</h3>
                <table border="1">
                    <tr>
                        <th>Method</th>
                        <th>Estimated Time</th>
                        <th>Cost</th>
                    </tr>
                    <tr>
                        <td>Near Colombo</td>
                        <td>1-2 days</td>
                        <td>Rs.300.00</td>
                    </tr>
                    <tr>
                        <td>Island wide</td>
                        <td>3-5 days</td>
                        <td>Rs.500.00</td>
                    </tr>
                    <tr>
                        <td>Express delivery</td>
                        <td>Next day</td>
                        <td>Rs.800.00</td>
                    </tr>
                </table>
 
            </aside>
            </div>
<?php require __DIR__.'/includes/footer.php'; ?>