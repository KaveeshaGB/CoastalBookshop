<?php
require_once __DIR__.'/includes/club_functions.php';
$pageTitle = 'Book Club - Coastal Bookshop';

$me       = $_SESSION['customer_id'] ?? null;
$isMember = isMember($pdo, $me);

$msg = $_SESSION['club_msg'] ?? null;
$err = $_SESSION['club_error'] ?? null;
unset($_SESSION['club_msg'], $_SESSION['club_error']);

// Everyone sees public sessions. Members also see the sessions that are not public.
$sql = 'SELECT e.*, b.Title AS BookTitle, b.Author AS BookAuthor, c.Name AS HostName,
               (SELECT COUNT(*) FROM Member_Event x
                 WHERE x.EventID = e.EventID AND x.LeftAt IS NULL) AS Joined
        FROM Event e
        LEFT JOIN Books b    ON b.BookID = e.BookID
        LEFT JOIN Customer c ON c.CustomerID = e.HostID'
     . ($isMember ? '' : ' WHERE e.IsPublic = 1')
     . ' ORDER BY COALESCE(e.StartTime, e.EventDate) DESC, e.EventID DESC';
$sessions = $pdo->query($sql)->fetchAll();

$groups = ['live' => [], 'upcoming' => [], 'ended' => []];
foreach ($sessions as $s) {
    $groups[sessionState($s)][] = $s;
}
$titles = ['live' => 'Happening now', 'upcoming' => 'Coming up', 'ended' => 'Past sessions'];

require __DIR__.'/includes/header.php';
?>
            <div class="club-page">
                <h2>Book Club</h2>
                <p>Pick a book you like, join a session and talk about it with other members.</p>

                <?php if ($msg): ?><p class="message"><?= e($msg) ?></p><?php endif; ?>
                <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

                <?php if ($isMember): ?>
                    <a class="order-link" href="create_session.php">+ Start a session</a>
                <?php elseif ($me): ?>
                    <p class="message">You can see the public sessions. Only members can join them.</p>
                <?php else: ?>
                    <p class="message">Log in as a member to join a session, or register and tick "Join as member".</p>
                <?php endif; ?>

                <?php if (!$sessions): ?>
                    <p class="message">No sessions yet.</p>
                <?php endif; ?>

                <?php foreach ($groups as $state => $list): ?>
                    <?php if ($list): ?>
                        <h3><?= e($titles[$state]) ?></h3>
                        <?php foreach ($list as $s): ?>
                            <div class="club-card">
                                <h3><?= e($s['EventName']) ?> <?= sessionBadge($state) ?>
                                    <?php if (!$s['IsPublic']): ?><span class="club-badge private">Members only</span><?php endif; ?>
                                </h3>
                                <p><strong>Book:</strong>
                                    <?= $s['BookTitle'] ? e($s['BookTitle']) . ' by ' . e($s['BookAuthor']) : 'Not listed' ?></p>
                                <p><strong>When:</strong> <?= e(sessionWhen($s)) ?></p>
                                <p><strong>Started by:</strong> <?= e($s['HostName'] ?? 'A member') ?>
                                   &nbsp;|&nbsp; <strong>Members in session:</strong> <?= (int)$s['Joined'] ?></p>
                                <?php if ($isMember): ?>
                                    <a class="order-link" href="session.php?id=<?= urlencode($s['EventID']) ?>">Open session</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
<?php require __DIR__.'/includes/footer.php'; ?>
