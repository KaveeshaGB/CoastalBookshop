<?php
$pageTitle = 'Coastal Bookshop';
require __DIR__.'/includes/header.php';

$likedIds  = getLikedIds($pdo);
$suggested = getSuggestedBooks($pdo, $likedIds);
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

            <?php if ($newBooks): ?>
                <h2>New Arrivals</h2>
                <?php foreach ($newBooks as $b) renderBook($b); ?>
            <?php endif; ?>

            <?php if ($suggested): ?>
                <h2>Suggestions</h2>
                <?php foreach ($suggested as $b) renderBook($b); ?>
            <?php endif; ?>
<?php require __DIR__.'/includes/footer.php'; ?>