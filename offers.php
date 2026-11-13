<?php
// All books with a running offer (same layout as the category pages).
$pageTitle = 'Special Offers - Coastal Bookshop';
require __DIR__.'/includes/header.php';
$likedIds = getLikedIds($pdo);

$books = $pdo->query('SELECT * FROM Books
                      WHERE DiscountPercent > 0 AND (OfferEnds IS NULL OR OfferEnds >= CURDATE())
                      ORDER BY Stock = 0, DiscountPercent DESC, Title')->fetchAll();
?>

<nav class="sub-nav">
    <ul class="type-list">
        <?php foreach ($categories as $name => $page): ?>
            <li><a href="<?= $page ?>"><?= e($name) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>

<h2>Special Offers</h2>
<?php if (!$books): ?>
<p class="message">There are no offers running right now. Please check back soon.</p>
<?php endif; ?>
<?php foreach ($books as $b) renderBook($b); ?>
<?php require __DIR__.'/includes/footer.php'; ?>