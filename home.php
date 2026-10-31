<?php
$pageTitle = 'Coastal Bookshop';
require 'header.php';

$categories = ['Fiction' => 'fiction.php', 'Novel' => 'novel.php', 'Short Story' => 'short_story.php'];

$newBooks = $pdo->query('SELECT * FROM Books ORDER BY DateAdded DESC, BookID DESC LIMIT 5')->fetchAll();

$suggested = [];
if ($newBooks) {
    $newIds = array_column($newBooks, 'BookID');
    $marks  = implode(',', array_fill(0, count($newIds), '?'));
    $statement = $pdo->prepare("SELECT * FROM Books WHERE BookID NOT IN ($marks) ORDER BY RAND() LIMIT 3");
    $statement->execute($newIds);
    $suggested = $statement->fetchAll();
}
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
                <h2>You May Also Like</h2>
                <?php foreach ($suggested as $b) renderBook($b); ?>
            <?php endif; ?>

            <h2>Browse Books</h2>
            <?php foreach ($categories as $name => $page):
                $statement = $pdo->prepare('SELECT * FROM Books WHERE Category = ? ORDER BY BookID LIMIT 3');
                $statement->execute([$name]);
                $books = $statement->fetchAll();
            ?>
                <?php foreach ($books as $b) renderBook($b); ?>
            <?php endforeach; ?>
<?php require 'footer.php'; ?>