<?php
$pageTitle = 'Coastal Bookshop';
require __DIR__.'/includes/header.php';

$likedIds  = getLikedIds($pdo);
$suggested = getSuggestedBooks($pdo, $likedIds);
// Books with a running offer that are still in stock
$offerBooks = $pdo->query('SELECT * FROM Books
                           WHERE DiscountPercent > 0 AND Stock > 0 AND (OfferEnds IS NULL OR OfferEnds >= CURDATE())
                           ORDER BY DiscountPercent DESC, Title LIMIT 5')->fetchAll();
$newBooks  = $pdo->query('SELECT * FROM Books ORDER BY DateAdded DESC, BookID DESC LIMIT 5')->fetchAll();
?>
            <div class="background-section">
                <p><em> a place for every book...</em></p>
            </div>

            <nav class="sub-nav">
                <ul class="type-list">
                    <?php
                    foreach ($categories as $name => $page) {
                        echo '<li><a href="' . $page . '">' . e($name) . '</a></li>';
                    }
                    ?>
                </ul>
            </nav>

            <?php if ($offerBooks): ?>
                <h2>Special Offers</h2>
                <?php foreach ($offerBooks as $b) renderBook($b); ?>
                <p><a class="order-link" href="offers.php">See all offers</a></p>
            <?php endif; ?>

            <?php if ($newBooks): ?>
                <h2>New Arrivals</h2>
                <?php foreach ($newBooks as $b) renderBook($b); ?>
            <?php endif; ?>

            <?php if ($suggested): ?>
                <h2>Suggestions</h2>
                <?php foreach ($suggested as $b) renderBook($b); ?>
            <?php endif; ?>
<?php require __DIR__.'/includes/footer.php'; ?>