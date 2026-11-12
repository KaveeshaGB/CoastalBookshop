<?php
// Member profile page: details, joined book-club sessions, order history.
require_once __DIR__.'/includes/config.php';   // starts session + gives $pdo and e()

if (empty($_SESSION['customer_id'])) { header('Location: login.php'); exit; }
$customerId = $_SESSION['customer_id'];

// LEFT JOIN so a customer who is not a club member yet still sees their details
$stmt = $pdo->prepare(
  'SELECT c.Name, c.Email, c.Phone, c.Address, m.JoinDate
   FROM customer c
   LEFT JOIN member m ON m.CustomerID = c.CustomerID
   WHERE c.CustomerID = ?');
$stmt->execute([$customerId]);
$member = $stmt->fetch();

$stmt = $pdo->prepare(
  'SELECT e.EventID, e.EventName, e.EventDate, e.EndTime, e.Status, me.LeftAt
   FROM member_event me
   JOIN event e ON e.EventID = me.EventID
   WHERE me.CustomerID = ?
   ORDER BY COALESCE(e.StartTime, e.EventDate) DESC');
$stmt->execute([$customerId]);
$events = $stmt->fetchAll();

$stmt = $pdo->prepare(
  'SELECT b.Title, b.Author, ob.Quantity, ob.UnitPrice, o.OrderDate, o.Status
   FROM orders o
   JOIN order_books ob ON ob.OrderID = o.OrderID
   JOIN books b ON b.BookID = ob.BookID
   WHERE o.CustomerID = ?
   ORDER BY o.OrderDate DESC');
$stmt->execute([$customerId]);
$books = $stmt->fetchAll();

$pageTitle = 'My Profile - Coastal Bookshop';
require __DIR__.'/includes/header.php';

$initial = strtoupper(substr(trim($member['Name'] ?? '?'), 0, 1));
?>
            <link rel="stylesheet" href="css/profile.css">

            <div class="profile-page">

                <!-- ---------- My details ---------- -->
                <section class="profile-card profile-hero">
                    <div class="profile-avatar" aria-hidden="true"><?= e($initial) ?></div>
                    <div class="profile-hero-text">
                        <h2><?= e($member['Name']) ?></h2>
                        <p class="profile-sub">
                            <?php if (!empty($member['JoinDate'])): ?>
                                Book Club member since <?= e(date('j F Y', strtotime($member['JoinDate']))) ?>
                            <?php else: ?>
                                Coastal Bookshop customer
                            <?php endif; ?>
                        </p>
                    </div>
                </section>

                <section class="profile-card">
                    <h3 class="profile-heading">My Details</h3>
                    <dl class="details-grid">
                        <div>
                            <dt>Email</dt>
                            <dd><?= e($member['Email']) ?></dd>
                        </div>
                        <div>
                            <dt>Phone</dt>
                            <dd><?= e($member['Phone']) ?></dd>
                        </div>
                        <div>
                            <dt>Address</dt>
                            <dd><?= e($member['Address']) ?></dd>
                        </div>
                        <div>
                            <dt>Member since</dt>
                            <dd><?= !empty($member['JoinDate']) ? e($member['JoinDate']) : '&mdash;' ?></dd>
                        </div>
                    </dl>
                </section>

                <!-- ---------- Events ---------- -->
                <section class="profile-card">
                    <h3 class="profile-heading">Events I Joined</h3>
                    <?php if (!$events): ?>
                        <p class="empty-note">You haven't joined any events yet.</p>
                        <a class="btn profile-btn" href="bookclub.php">Browse Book Club</a>
                    <?php else: ?>
                        <ul class="event-list">
                            <?php foreach ($events as $ev): ?>
                                <li>
                                    <span class="event-name"><?= e($ev['EventName']) ?></span>
                                    <span class="event-date"><?= e($ev['EventDate']) ?></span>
                                    <?php if (!empty($ev['LeftAt'])): ?>
                                        <span class="pill pill-muted">Left</span>
                                    <?php elseif ($ev['Status'] === 'Ended'): ?>
                                        <span class="pill pill-muted">Ended</span>
                                    <?php else: ?>
                                        <span class="pill pill-active">Joined</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <!-- ---------- Orders ---------- -->
                <section class="profile-card">
                    <h3 class="profile-heading">Books I Ordered</h3>
                    <?php if (!$books): ?>
                        <p class="empty-note">You haven't ordered any books yet.</p>
                        <a class="btn profile-btn" href="order.php">Place an order</a>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Book</th><th>Author</th><th class="num">Qty</th>
                                        <th class="num">Price</th><th>Date</th><th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $b): ?>
                                        <tr>
                                            <td data-label="Book"><?= e($b['Title']) ?></td>
                                            <td data-label="Author"><?= e($b['Author']) ?></td>
                                            <td data-label="Qty" class="num"><?= (int) $b['Quantity'] ?></td>
                                            <td data-label="Price" class="num">Rs. <?= number_format($b['UnitPrice'], 2) ?></td>
                                            <td data-label="Date"><?= e(date('j M Y, g:i A', strtotime($b['OrderDate']))) ?></td>
                                            <td data-label="Status">
                                                <span class="pill status-<?= e(strtolower($b['Status'])) ?>"><?= e($b['Status']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

            </div>
<?php require __DIR__.'/includes/footer.php'; ?>