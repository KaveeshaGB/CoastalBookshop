<?php
require_once __DIR__.'/includes/club_functions.php';
$pageTitle = 'Book Club - Coastal Bookshop';

$me       = $_SESSION['customer_id'] ?? null;
$isMember = isMember($pdo, $me);

$msg      = $_SESSION['club_msg'] ?? null;
$err      = $_SESSION['club_error'] ?? null;
$shelfErr = $_SESSION['shelf_error'] ?? null;
unset($_SESSION['club_msg'], $_SESSION['club_error'], $_SESSION['shelf_error']);

// ---------- Sessions ----------
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
$titles    = ['live' => 'Happening now', 'upcoming' => 'Coming up', 'ended' => 'Past sessions'];
$liveCount = count($groups['live']);

// ---------- Bookshelf (members only) ----------
// Only ebooks the member has BOUGHT: the book has an ebook file AND is in one of their orders.
$shelf = [];     // already downloaded
$waiting = [];   // bought, not downloaded yet
if ($isMember) {
    $stmt = $pdo->prepare(
        "SELECT b.BookID, b.Title, b.Author, b.ImageFile, d.DownloadedAt
           FROM Books b
           LEFT JOIN EbookDownload d ON d.BookID = b.BookID AND d.CustomerID = ?
          WHERE b.EbookFile IS NOT NULL AND b.EbookFile <> ''
            AND EXISTS (SELECT 1
                          FROM Orders o
                          JOIN Order_Books ob ON ob.OrderID = o.OrderID
                         WHERE o.CustomerID = ? AND ob.BookID = b.BookID)
          ORDER BY d.DownloadedAt DESC, b.Title"
    );
    $stmt->execute([$me, $me]);
    foreach ($stmt->fetchAll() as $b) {
        if ($b['DownloadedAt']) $shelf[] = $b; else $waiting[] = $b;
    }
}

// One book standing on the shelf
function shelfBook(array $b, bool $downloaded): void { ?>
                        <div class="shelf-book">
                            <?php if ($b['ImageFile']): ?>
                                <img class="shelf-cover" src="images/covers/<?= e($b['ImageFile']) ?>" alt="Cover of <?= e($b['Title']) ?>">
                            <?php else: ?>
                                <div class="shelf-cover no-cover"><?= e($b['Title']) ?></div>
                            <?php endif; ?>
                            <p class="shelf-title"><?= e($b['Title']) ?></p>
                            <p class="shelf-author"><?= e($b['Author']) ?></p>
                            <?php if ($downloaded): ?>
                                <p class="shelf-date">Downloaded <?= e(date('d M Y', strtotime($b['DownloadedAt']))) ?></p>
                                <a class="order-link" href="download_ebook.php?id=<?= urlencode($b['BookID']) ?>">Download again</a>
                            <?php else: ?>
                                <?php // after the first download, reload so the book moves onto the shelf ?>
                                <a class="order-link" href="download_ebook.php?id=<?= urlencode($b['BookID']) ?>"
                                   onclick="setTimeout(function () { location.reload(); }, 1500);">Download</a>
                            <?php endif; ?>
                        </div>
<?php }

// Which tab opens by itself (after a message or an error)
$start = $shelfErr ? 'bookshelf' : (($msg || $err) ? 'sessions' : '');

require __DIR__.'/includes/header.php';
?>
            <div class="club-hero" id="club-hero">
                <img class="hero-pic" src="images/bookclub-people.jpg" alt="People talking about a book">

                <div class="club-welcome">
                    <h2>Welcome to</h2>
                    <h2 class="line-two">Coastal BookClub</h2>

                    <div class="panel-banner">
                        <h1 class="panel-banner-title">Bookclub Session</h1>
                    </div>

                    <div class="club-tabs" role="tablist" aria-label="Book club">
                        <button type="button" role="tab" id="tab-sessions" data-tab="sessions" data-label="Sessions"
                                aria-controls="panel-sessions" aria-selected="false">
                            <span class="tab-label">Sessions</span>
                            <?php if ($liveCount): ?> 
                            <span class="live-count"><?= $liveCount ?> live</span>
                            <?php endif; ?>
                        </button>
                        <?php if ($isMember): ?>
                            <button type="button" role="tab" id="tab-bookshelf" data-tab="bookshelf" data-label="My Bookshelf"
                                    aria-controls="panel-bookshelf" aria-selected="false">
                                <span class="tab-label">My Bookshelf</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="club-page" id="club-content" data-start="<?= e($start) ?>">

                <!-- SESSIONS -->
                <section id="panel-sessions" role="tabpanel" aria-labelledby="tab-sessions" hidden>
                    <?php if ($msg): ?><p class="message"><?= e($msg) ?></p><?php endif; ?>
                    <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

                    <?php if ($isMember): ?>
                        <a class="order-link" href="create_session.php">+ Create session</a>
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
                </section>

                <!-- MY BOOKSHELF (members only) -->
                <?php if ($isMember): ?>
                <section id="panel-bookshelf" role="tabpanel" aria-labelledby="tab-bookshelf" class="shelf-page" hidden>
                    <?php if ($shelfErr): ?><p class="error"><?= e($shelfErr) ?></p><?php endif; ?>

                    <?php if (!$shelf && !$waiting): ?>
                        <p class="message">You don't own any ebooks yet. Once you buy one, it will show up here.</p>
                    <?php else: ?>
                        <h3>On your shelf</h3>
                        <?php if ($shelf): ?>
                            <div class="shelf-row">
                                <?php foreach ($shelf as $b) shelfBook($b, true); ?>
                            </div>
                        <?php else: ?>
                            <p class="message">Your shelf is empty. Download one of your ebooks below to place it here.</p>
                        <?php endif; ?>

                        <?php if ($waiting): ?>
                            <h3>Bought and ready to download</h3>
                            <div class="shelf-row waiting">
                                <?php foreach ($waiting as $b) shelfBook($b, false); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </div>

            <script>
                (function () {
                    var hero    = document.getElementById('club-hero');
                    var content = document.getElementById('club-content');
                    var tabs    = document.querySelectorAll('.club-tabs [data-tab]');
                    var panels  = {
                        sessions:  document.getElementById('panel-sessions'),
                        bookshelf: document.getElementById('panel-bookshelf')
                    };

                    // name = 'sessions', 'bookshelf', or '' (nothing selected: show the picture)
                    function show(name) {
                        if (!panels[name]) name = '';
                        hero.classList.toggle('has-panel', name !== '');
                        tabs.forEach(function (t) {
                            var on = (t.dataset.tab === name);
                            t.classList.toggle('is-active', on);
                            t.setAttribute('aria-selected', on ? 'true' : 'false');
                            var label = t.querySelector('.tab-label');
                            if (label) label.textContent = on ? 'Back' : t.dataset.label;
                        });
                        Object.keys(panels).forEach(function (key) {
                            if (panels[key]) panels[key].hidden = (key !== name);
                        });
                    }
                    tabs.forEach(function (t) {
                        t.addEventListener('click', function () {
                            var name = t.classList.contains('is-active') ? '' : t.dataset.tab;
                            show(name);
                            history.replaceState(null, '', name ? '#' + name : location.pathname + location.search);
                        });
                    });

                        // On load: use the #sessions / #bookshelf in the address, else whatever the server asked for
                    show(location.hash.replace('#', '') || content.dataset.start);
                    })();
            </script>