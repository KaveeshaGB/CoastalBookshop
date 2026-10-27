<?php
// Shared template for the three book type pages.
// The page sets $category (e.g. 'Fiction') then includes this file.
$pageTitle = $category . ' Books - Coastal Bookshop';
require 'header.php';

$stmt = $pdo->prepare('SELECT * FROM Books WHERE Category = ? ORDER BY Title');
$stmt->execute([$category]);
$books = $stmt->fetchAll();
?>
            <h2><?= e($category) ?></h2>
            <?php if (!$books): ?>
                <p class="message">No books in this category yet.</p>
            <?php endif; ?>
            <?php foreach ($books as $b) renderBook($b); ?>
<?php require 'footer.php'; ?>
