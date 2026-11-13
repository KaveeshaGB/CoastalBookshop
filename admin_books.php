<?php
require __DIR__ .'/includes/admin_check.php';
$pageTitle = 'Manage Books - Coastal Bookshop';

$msg = $_SESSION['admin_msg'] ?? null;
unset($_SESSION['admin_msg']);
$err = null;

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM Books WHERE BookID = ?');
        $stmt->execute([$_POST['delete_id']]);
        $_SESSION['admin_msg'] = 'Book deleted.';
        header('Location: admin_books.php');
        exit;
    } catch (PDOException $ex) {
        $err = 'This book is part of an order, so it cannot be deleted.';
    }
}

// READ
$books = $pdo->query('SELECT * FROM Books ORDER BY CAST(SUBSTRING(BookID, 2) AS UNSIGNED)')->fetchAll();
require __DIR__.'/includes/header.php';
?>
            <h2>Manage Books</h2>

            <?php if ($msg): ?><p class="message"><?= e($msg) ?></p><?php endif; ?>
            <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

            <a class="order-link" href="admin_book_form.php">+ Add New Book</a>
            <br><br>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Offer</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($books as $b): ?>
                <tr>
                    <td><?= e($b['BookID']) ?></td>
                    <td>
                        <?php if ($b['ImageFile']): ?>
                            <img src="images/covers/<?= e($b['ImageFile']) ?>" alt="" width="40">
                        <?php endif; ?>
                    </td>
                    <td><?= e($b['Title']) ?></td>
                    <td><?= e($b['Author']) ?></td>
                    <td><?= e($b['Category']) ?></td>
                    <td>Rs.<?= number_format($b['Price'], 2) ?></td>
                    <td><?php if (isOutOfStock($b)): ?><span class="stock-warn">Out of stock</span><?php else: ?><?= (int)$b['Stock'] ?><?php endif; ?></td>
                    <td>
                        <?php if (hasOffer($b)): ?>
                            -<?= (int)$b['DiscountPercent'] ?>% (Rs.<?= number_format(finalPrice($b), 2) ?>)
                            <?php if ($b['OfferEnds']): ?><br>until <?= e($b['OfferEnds']) ?><?php endif; ?>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td><?= e($b['DateAdded']) ?></td>
                    <td>
                        <a class="order-link" href="admin_book_form.php?id=<?= urlencode($b['BookID']) ?>">Edit</a>
                        <form method="post" style="display:inline"
                              onsubmit="return confirm('Delete this book?');">
                            <input type="hidden" name="delete_id" value="<?= e($b['BookID']) ?>">
                            <button type="submit" class="btn" style="margin:0;padding:10px;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
<?php require __DIR__.'/includes/footer.php'; ?>