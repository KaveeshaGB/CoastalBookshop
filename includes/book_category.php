<?php
// Shared template for the three book type pages.
// The page sets $category (e.g. 'Fiction') then includes this file.
$pageTitle = $category . ' Books - Coastal Bookshop';
require __DIR__.'/header.php';
$likedIds = getLikedIds($pdo);

$stmt = $pdo->prepare('SELECT * FROM Books WHERE Category = ? ORDER BY Title');
$stmt->execute([$category]);
$books = $stmt->fetchAll();
?>

<nav class="sub-nav">
    <ul class="type-list">
        <?php foreach ($categories as $name => $page): ?>
            <li><a href="<?= $page ?>"><?= e($name) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>

<h2><?= e($category) ?></h2>
<?php if (!$books): ?>
<p class="message">No books in this category yet.</p>
<?php endif; ?>
<?php foreach ($books as $b) renderBook($b); ?>
<?php require __DIR__.'/footer.php'; ?>