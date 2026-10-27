<?php
$pageTitle = 'Book Order';
require 'header.php';

$books = $pdo->query('SELECT BookID, Title, Price, Category FROM Books ORDER BY Title')->fetchAll();
$selected = (int)($_GET['book'] ?? 0);

$flashOk  = $_SESSION['order_success'] ?? null;
$flashErr = $_SESSION['order_error'] ?? null;
unset($_SESSION['order_success'], $_SESSION['order_error']);
?>
            <h2>Book Order Form</h2>

            <?php if ($flashOk): ?><p class="message"><?= e($flashOk) ?></p><?php endif; ?>
            <?php if ($flashErr): ?><p class="error"><?= e($flashErr) ?></p><?php endif; ?>

            <?php if (!$loggedIn): ?>
                <p class="message">Please <a href="register.php">sign in</a> or use the Log-in button to place an order.</p>
            <?php else: ?>
            <script>
                // Book data from the database, used by script.js to filter the book list
                window.BOOKS = <?= json_encode($books) ?>;
                window.SELECTED_BOOK = <?= $selected ?>;
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
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
                <br>

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
                    <label class="form-label" for="decoration">Decoration:</label>
                    <select id="decoration" name="decoration_type">
                        <option value="">None</option>
                        <option value="Ribbon">Ribbon</option>
                        <option value="Gift wrap">Gift wrap</option>
                    </select>
                    <br>
                </div>

                <p id="order-total" class="message"></p>
                <button type="submit" class="btn">Place Order</button>
            </form>
            <?php endif; ?>

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
<?php require 'footer.php'; ?>
