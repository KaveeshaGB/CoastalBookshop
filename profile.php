<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['customer_id'])) { header('Location: login.php'); exit; }
$customerId = $_SESSION['customer_id'];

$conn = new mysqli('localhost', 'root', '', 'coastal_bookshop');

$stmt = $conn->prepare(
  'SELECT c.Name, c.Email, c.Phone, c.Address, m.JoinDate
   FROM member m
   JOIN customer c ON c.CustomerID = m.CustomerID
   WHERE m.CustomerID = ?');
$stmt->bind_param('i', $customerId);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

include 'header.php';
?>

<main class="profile-page">
  <h2>My Details</h2>
  <p><strong>Name:</strong> <?= htmlspecialchars($member['Name']) ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($member['Email']) ?></p>
  <p><strong>Phone:</strong> <?= htmlspecialchars($member['Phone']) ?></p>
  <p><strong>Address:</strong> <?= htmlspecialchars($member['Address']) ?></p>
  <p><strong>Member since:</strong> <?= htmlspecialchars($member['JoinDate']) ?></p>
  <?php
$stmt = $conn->prepare(
  'SELECT e.EventName, e.EventDate
   FROM member_event me
   JOIN event e ON e.EventID = me.EventID
   WHERE me.CustomerID = ?
   ORDER BY e.EventDate DESC');
$stmt->bind_param('i', $customerId);
$stmt->execute();
$events = $stmt->get_result();

$stmt = $conn->prepare(
  'SELECT b.Title, b.Author, ob.Quantity, ob.UnitPrice, o.OrderDate, o.Status
   FROM orders o
   JOIN order_books ob ON ob.OrderID = o.OrderID
   JOIN books b ON b.BookID = ob.BookID
   WHERE o.CustomerID = ?
   ORDER BY o.OrderDate DESC');
$stmt->bind_param('i', $customerId);
$stmt->execute();
$books = $stmt->get_result();
?>

<h2>Events I Joined</h2>
<?php if ($events->num_rows === 0): ?>
  <p>You haven't joined any events yet.</p>
<?php else: ?>
  <ul>
    <?php while ($e = $events->fetch_assoc()): ?>
      <li><?= htmlspecialchars($e['EventName']) ?> (<?= htmlspecialchars($e['EventDate']) ?>)</li>
    <?php endwhile; ?>
  </ul>
<?php endif; ?>

<h2>Books I Ordered</h2>
<?php if ($books->num_rows === 0): ?>
  <p>You haven't ordered any books yet.</p>
<?php else: ?>
  <table>
    <tr><th>Book</th><th>Author</th><th>Qty</th><th>Price</th><th>Date</th><th>Status</th></tr>
    <?php while ($b = $books->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($b['Title']) ?></td>
        <td><?= htmlspecialchars($b['Author']) ?></td>
        <td><?= (int)$b['Quantity'] ?></td>
        <td>Rs. <?= number_format($b['UnitPrice'], 2) ?></td>
        <td><?= htmlspecialchars($b['OrderDate']) ?></td>
        <td><?= htmlspecialchars($b['Status']) ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
</main>


<?php endif; ?>
