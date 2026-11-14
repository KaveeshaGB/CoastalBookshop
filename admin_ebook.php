<?php
require __DIR__ . '/includes/admin_check.php';
$pageTitle = 'Manage Ebooks - Coastal Bookshop';

$msg = $_SESSION['admin_msg'] ?? null;
unset($_SESSION['admin_msg']);
$err = null;

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare('SELECT FileName FROM Ebook WHERE EbookID = ?');
    $stmt->execute([$_POST['delete_id']]);
    $file = $stmt->fetchColumn();

    try {
        $pdo->prepare('DELETE FROM Ebook WHERE EbookID = ?')->execute([$_POST['delete_id']]);

        // Remove the ebook file from the ebooks folder too
        if ($file) {
            $path = EBOOK_DIR . '/' . basename($file);
            if (is_file($path)) unlink($path);
        }

        $_SESSION['admin_msg'] = 'Ebook deleted.';
        header('Location: admin_ebooks.php');
        exit;
    } catch (PDOException $ex) {
        $err = 'Members have already bought this ebook, so it cannot be deleted.';
    }
}

// READ
$ebooks = $pdo->query(
    'SELECT e.*,
            (SELECT COUNT(*) FROM Member_Ebook m WHERE m.EbookID = e.EbookID) AS Buyers
       FROM Ebook e
      ORDER BY e.EbookID'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>
            <h2>Manage Ebooks</h2>

            <?php if ($msg): ?><p class="message"><?= e($msg) ?></p><?php endif; ?>
            <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

            <a class="order-link" href="admin_ebooks_form.php">+ Add New Ebook</a>
            <br><br>

            <table>
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
                <?php foreach ($ebooks as $b): ?>
                <tr>
                    <td><?= e($b['EbookID']) ?></td>
                    <td>
                        <?php if ($b['CoverImage']): ?>
                            <img src="images/covers/<?= e($b['CoverImage']) ?>" alt="" width="40">
                        <?php endif; ?>
                    </td>
                    <td><?= e($b['Title']) ?></td>
                    <td><?= e($b['Author']) ?></td>
                    <td>Rs.<?= number_format($b['Price'], 2) ?></td>
                    <td>
                        <?php if ((int)$b['DiscountPercent'] > 0): ?>
                            -<?= (int)$b['DiscountPercent'] ?>% (Rs.<?= number_format($b['Price'] * (1 - $b['DiscountPercent'] / 100), 2) ?>)
                            <?php if ($b['OfferEnds']): ?>
                                <br><small>until <?= e($b['OfferEnds']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= e($b['FileName']) ?>
                        <?php if (!is_file(__DIR__ . '/ebooks/' . basename($b['FileName']))): ?>
                            <br><span class="error">File missing</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$b['Buyers'] ?></td>
                    <td>
                        <a class="order-link" href="admin_ebooks_form.php?id=<?= urlencode($b['EbookID']) ?>">Edit</a>
                        <form method="post" style="display:inline"
                              onsubmit="return confirm('Delete this ebook?');">
                            <input type="hidden" name="delete_id" value="<?= e($b['EbookID']) ?>">
                            <button type="submit" class="btn" style="margin:0;padding:10px;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
<?php require __DIR__ . '/includes/footer.php'; ?>