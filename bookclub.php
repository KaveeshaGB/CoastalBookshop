<?php
require_once __DIR__.'/includes/club_functions.php';
$pageTitle = 'Book Club - Coastal Bookshop';

$me       = $_SESSION['customer_id'] ?? null;
$isMember = isMember($pdo, $me);

$msg      = $_SESSION['club_msg'] ?? null;
$err      = $_SESSION['club_error'] ?? null;
$shelfErr = $_SESSION['shelf_error'] ?? null;
$shelfMsg = $_SESSION['shelf_msg'] ?? null;
unset($_SESSION['club_msg'], $_SESSION['club_error'], $_SESSION['shelf_error'], $_SESSION['shelf_msg']);

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

// ---------- Bookshelf ----------
// Members see two groups: ebooks they have bought (on top) and ebooks they can still buy (under).
$owned     = [];   // bought
$available = [];   // not bought yet
if ($isMember) {
    $stmt = $pdo->prepare(
        'SELECT e.EbookID, e.Title, e.Author, e.Price, e.DiscountPercent, e.OfferEnds, e.CoverImage, m.PurchasedAt
           FROM Ebook e
           LEFT JOIN Member_Ebook m ON m.EbookID = e.EbookID AND m.CustomerID = ?
          ORDER BY m.PurchasedAt DESC, e.Title'
    );
    $stmt->execute([$me]);
    foreach ($stmt->fetchAll() as $b) {
        if ($b['PurchasedAt']) $owned[] = $b; else $available[] = $b;
    }
}

// One ebook standing on the shelf
function shelfBook(array $b, bool $bought): void { ?>
    <div class="shelf-book">
        <?php if (!$bought && hasOffer($b)): ?>
            <span class="book-badge badge-offer">-<?= (int)$b['DiscountPercent'] ?>%</span>
        <?php endif; ?>
        <?php if ($b['CoverImage']): ?>
            <img class="shelf-cover" src="images/covers/<?= e($b['CoverImage']) ?>" alt="Cover of <?= e($b['Title']) ?>">
        <?php else: ?>
            <div class="shelf-cover no-cover"><?= e($b['Title']) ?></div>
        <?php endif; ?>
            <p class="shelf-title"><?= e($b['Title']) ?></p>
            <p class="shelf-author"><?= e($b['Author']) ?></p>
        <?php if ($bought): ?>
            <?php if (hasOffer($b)): ?>
            <p class="shelf-date">
                <span class="old-price">Rs.<?= number_format($b['Price'], 2) ?></span>
                <span class="new-price">Rs.<?= number_format(finalPrice($b), 2) ?></span>
            </p>
            <?php if (!empty($b['OfferEnds'])): ?>
                <p class="offer-ends">Offer ends <?= e($b['OfferEnds']) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p class="shelf-date">Rs.<?= number_format($b['Price'], 2) ?></p>
        <?php endif; ?>
        <?php // target="_blank" opens the ebook in a new tab ?>
            <a class="order-link" href="read_ebook.php?id=<?= urlencode($b['EbookID']) ?>"
            target="_blank" rel="noopener">Read</a>
        <?php else: ?>
            <p class="shelf-date">Rs.<?= number_format($b['Price'], 2) ?></p>
            <button type="button" class="btn buy-btn"
            data-id="<?= e($b['EbookID']) ?>"
            data-title="<?= e($b['Title']) ?>"
            data-price="<?= number_format(finalPrice($b), 2) ?>">Buy</button>
        <?php endif; ?>
    </div>
<?php }

// Which tab opens by itself (after a message or an error)
$start = ($shelfErr || $shelfMsg) ? 'bookshelf' : (($msg || $err) ? 'sessions' : '');

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
                        <button type="button" role="tab" id="tab-bookshelf" data-tab="bookshelf" data-label="My Bookshelf"
                                aria-controls="panel-bookshelf" aria-selected="false">
                            <span class="tab-label">My Bookshelf</span>
                        </button>
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
                    <form method="post" action="become_member.php"
                    onsubmit="return confirm('Become a book club member?');">
                        <button type="submit" class="btn">Become a member</button>
                    </form>
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

                <!-- MY BOOKSHELF -->
                <section id="panel-bookshelf" role="tabpanel" aria-labelledby="tab-bookshelf" class="shelf-page" hidden>
                    <?php if ($shelfMsg): ?><p class="message"><?= e($shelfMsg) ?></p><?php endif; ?>
                    <?php if ($shelfErr): ?><p class="error"><?= e($shelfErr) ?></p><?php endif; ?>

                    <?php if (!$me): ?>
                        <p class="message">Log in to see your bookshelf. New here? Register and tick "Join as member".</p>

                    <?php elseif (!$isMember): ?>
                        <p class="message">You are not a member yet, so you can't buy ebooks.
                            Become a member to buy ebooks and read them here.</p>
                        <form method="post" action="become_member.php"
                              onsubmit="return confirm('Become a book club member?');">
                            <input type="hidden" name="return" value="bookshelf">
                            <button type="submit" class="btn">Become a member</button>
                        </form>

                    <?php else: ?>
                        <h3>On My shelf</h3>
                        <?php if (!$owned): ?>
                            <p class="message">You don't own any ebooks yet. Once you buy one, it will show up here.</p>
                        <?php else: ?>
                            <div class="shelf-row">
                                <?php foreach ($owned as $b) shelfBook($b, true); ?>
                            </div>
                        <?php endif; ?>

                        <h3>More ebooks to buy</h3>
                        <?php if (!$available): ?>
                            <p class="message">You own every ebook we have right now.</p>
                        <?php else: ?>
                            <div class="shelf-row waiting">
                                <?php foreach ($available as $b) shelfBook($b, false); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
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

                    // The Ebooks link in the top nav only changes the #hash when you are already on this page
                    window.addEventListener('hashchange', function () {
                        show(location.hash.replace('#', ''));
                    });
                    })();
            </script>
            <?php if ($isMember): ?>
            <div id="buy-modal" class="modal" hidden>
                <div class="modal-box" role="dialog" aria-labelledby="buy-title">
                    <button type="button" class="modal-close" id="close-buy" aria-label="Close">&times;</button>
                    <h3 id="buy-title">Complete your purchase</h3>
                    <p id="buy-summary"></p>

                    <form id="buy-form" method="post" action="buy_ebook.php">
                        <input type="hidden" name="ebook_id" id="buy-ebook-id">

                        <label class="form-label">Payment Method:</label>
                        <input type="text" value="Card" disabled>

                        <label class="form-label" for="buy-card-name">Name on Card:</label>
                        <input type="text" id="buy-card-name" name="card_name" autocomplete="cc-name" maxlength="60" required>

                        <label class="form-label" for="buy-card-number">Card Number:</label>
                        <input type="text" id="buy-card-number" name="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="1234 5678 9012 3456" maxlength="19" required>

                        <div class="card-row">
                            <div>
                                <label class="form-label" for="buy-card-expiry">Expiry Date:</label>
                                <input type="text" id="buy-card-expiry" name="card_expiry" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" maxlength="5" required>
                            </div>
                            <div>
                                <label class="form-label" for="buy-card-cvv">CVV:</label>
                                <input type="password" id="buy-card-cvv" name="card_cvv" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4" required>
                            </div>
                        </div>

                        <button type="submit" class="btn">Pay and add to my shelf</button>
                    </form>
                </div>
            </div>

            <script>
                (function () {
                        var modal   = document.getElementById('buy-modal');
                        var form    = document.getElementById('buy-form');
                        var idBox   = document.getElementById('buy-ebook-id');
                        var summary = document.getElementById('buy-summary');
                        var num     = document.getElementById('buy-card-number');
                        var exp     = document.getElementById('buy-card-expiry');
                        var cvv     = document.getElementById('buy-card-cvv');

                    function close() { modal.hidden = true; form.reset(); }

                    document.querySelectorAll('.buy-btn').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            idBox.value = btn.dataset.id;
                            summary.textContent = btn.dataset.title + ' - Rs.' + btn.dataset.price;
                            modal.hidden = false;
                            document.getElementById('buy-card-name').focus();
                        });
                    });
                    document.getElementById('close-buy').addEventListener('click', close);
                    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
                    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });

                    // Card number: digits only, grouped in 4s
                    num.addEventListener('input', function () {
                        var d = num.value.replace(/\D/g, '').slice(0, 16);
                        num.value = d.replace(/(.{4})/g, '$1 ').trim();
                        num.setCustomValidity('');
                    });
                    // Expiry: digits only, slash added automatically
                    exp.addEventListener('input', function () {
                        var d = exp.value.replace(/\D/g, '').slice(0, 4);
                        exp.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
                        exp.setCustomValidity('');
                    });
                    // CVV: digits only
                    cvv.addEventListener('input', function () { cvv.value = cvv.value.replace(/\D/g, ''); });

                    form.addEventListener('submit', function (e) {
                        num.setCustomValidity(num.value.replace(/\s/g, '').length === 16 ? '' : 'Enter a 16-digit card number.');

                        var m = /^(0[1-9]|1[0-2])\/(\d{2})$/.exec(exp.value);
                        var msg = 'Enter the expiry date as MM/YY.';
                        if (m) {
                            // valid until the end of the expiry month
                            msg = new Date(2000 + Number(m[2]), Number(m[1]), 1) > new Date() ? '' : 'That card has expired.';
                        }
                        exp.setCustomValidity(msg);

                        cvv.setCustomValidity(/^\d{3,4}$/.test(cvv.value) ? '' : 'Enter a 3 or 4 digit CVV.');

                        if (!form.checkValidity()) {
                            e.preventDefault();
                            form.reportValidity();
                        }
                    });
                })();
            </script>
            <?php endif; ?>