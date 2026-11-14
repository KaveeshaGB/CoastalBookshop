<?php
require __DIR__ .'/includes/admin_check.php';
$pageTitle = 'Manage Books - Coastal Bookshop';

$msg = $_SESSION['admin_msg'] ?? null;
unset($_SESSION['admin_msg']);
$err = null;
$ebookErr = null;
$ebookDir = EBOOK_DIR;

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

// DELETE EBOOK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ebook_id'])) {
    $stmt = $pdo->prepare('SELECT FileName FROM Ebook WHERE EbookID = ?');
    $stmt->execute([$_POST['delete_ebook_id']]);
    $file = $stmt->fetchColumn();

    try {
        $pdo->prepare('DELETE FROM Ebook WHERE EbookID = ?')->execute([$_POST['delete_ebook_id']]);
        if ($file) {
            $path = EBOOK_DIR . '/' . basename($file);
            if (is_file($path)) unlink($path);
        }
        $_SESSION['admin_msg'] = 'Ebook deleted.';
        header('Location: admin_books.php#ebooks');
        exit;
    } catch (PDOException $ex) {
        $ebookErr = 'Members have already bought this ebook, so it cannot be deleted.';
    }
}

// READ
$books = $pdo->query('SELECT * FROM Books ORDER BY CAST(SUBSTRING(BookID, 2) AS UNSIGNED)')->fetchAll();
$ebooks = $pdo->query(
    'SELECT e.*,
            (SELECT COUNT(*) FROM Member_Ebook m WHERE m.EbookID = e.EbookID) AS Buyers
       FROM Ebook e
      ORDER BY e.EbookID'
)->fetchAll();

require __DIR__.'/includes/header.php';
?>
            <h2>Manage Books</h2>

            <?php if ($msg): ?><p class="message"><?= e($msg) ?></p><?php endif; ?>
            <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

            <a class="order-link" href="admin_book_form.php">+ Add New Book</a>
            <a class="order-link" href="admin_ebook_form.php">+ Add New Ebook</a>
            <a class="order-link" href="home.php">Back</a>
            <br><br>

            <table class="admin-table">
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

            <h2 id="ebooks">Manage Ebooks</h2>

            <?php if ($ebookErr): ?><p class="error"><?= e($ebookErr) ?></p><?php endif; ?>

            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Cover</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Price</th>
                    <th>Offer</th>
                    <th>File</th>
                    <th>Bought by</th>
                    <th>Actions</th>
                </tr>
                <?php if (!$ebooks): ?>
                <tr>
                    <td colspan="9">No ebooks yet. Use "+ Add New Ebook" to add the first one.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($ebooks as $eb): ?>
                <tr>
                    <td><?= e($eb['EbookID']) ?></td>
                    <td>
                        <?php if ($eb['CoverImage']): ?>
                            <img src="images/covers/<?= e($eb['CoverImage']) ?>" alt="" width="40">
                        <?php endif; ?>
                    </td>
                    <td><?= e($eb['Title']) ?></td>
                    <td><?= e($eb['Author']) ?></td>
                    <td>Rs.<?= number_format($eb['Price'], 2) ?></td>
                    <td>
                        <?php if (hasOffer($eb)): ?>
                            -<?= (int)$eb['DiscountPercent'] ?>% (Rs.<?= number_format(finalPrice($eb), 2) ?>)
                            <?php if ($eb['OfferEnds']): ?><br>until <?= e($eb['OfferEnds']) ?><?php endif; ?>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td>
                        <?= e($eb['FileName']) ?>
                        <?php if (!is_file(EBOOK_DIR . '/' . basename($eb['FileName']))): ?>
                            <br><span class="stock-warn">File missing</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$eb['Buyers'] ?></td>
                    <td>
                        <a class="order-link" href="admin_ebook_form.php?id=<?= urlencode($eb['EbookID']) ?>">Edit</a>
                        <form method="post" action="admin_books.php#ebooks" style="display:inline"
                              onsubmit="return confirm('Delete this ebook?');">
                            <input type="hidden" name="delete_ebook_id" value="<?= e($eb['EbookID']) ?>">
                            <button type="submit" class="btn" style="margin:0;padding:10px;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
<?php require __DIR__.'/includes/footer.php'; ?>