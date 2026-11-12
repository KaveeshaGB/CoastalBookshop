<?php
$pageTitle = 'Book Order';
require __DIR__.'/includes/header.php';

$books = $pdo->query('SELECT BookID, Title, Price, Category FROM Books ORDER BY Title')->fetchAll();
$selected = $_GET['book'] ?? '';

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
                        <tr><th>Book</th><td><?= e($receipt['title']) ?></td></tr>
                        <tr><th>Price</th><td>Rs.<?= number_format($receipt['price'], 2) ?> x <?= (int) $receipt['qty'] ?></td></tr>
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
                    <option value="Parcel">Parcel (for me)</option>
                    <option value="Gift">Gift (for someone else)</option>
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

                <p id="order-total" class="message"></p>
                <button type="submit" class="btn">Place Order</button>
            </form>
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