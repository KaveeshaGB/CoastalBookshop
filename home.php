<?php
$pageTitle = 'Coastal Bookshop';
require 'header.php';

$categories = ['Fiction' => 'fiction.php', 'Novel' => 'novel.php', 'Short Story' => 'short_story.php'];
?>
            <div class="background-section">
                <p><strong>Coastal Bookshop</strong>
                    <br><em> a place for every book...</em>
                </p>
            </div>

            <br>
            <ul class="type-list">
                <?php foreach ($categories as $name => $page): ?>
                    <li><a href="<?= $page ?>"><?= e($name) ?></a></li>
                <?php endforeach; ?>
            </ul>

            <?php foreach ($categories as $name => $page):
                $stmt = $pdo->prepare('SELECT * FROM Books WHERE Category = ? ORDER BY BookID LIMIT 3');
                $stmt->execute([$name]);
                $books = $stmt->fetchAll();
            ?>
                <h2><a class="plain" href="<?= $page ?>"><?= e($name) ?></a></h2>
                <?php foreach ($books as $b) renderBook($b); ?>
            <?php endforeach; ?>
<?php require 'footer.php'; ?>
