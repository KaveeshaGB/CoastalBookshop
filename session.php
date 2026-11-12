<?php
require __DIR__.'/includes/member_check.php';   // members only ($me = the member's ID)

$ev = getSession($pdo, $_GET['id'] ?? '');
if (!$ev) {
    $_SESSION['club_error'] = 'That session was not found.';
    header('Location: bookclub.php');
    exit;
}

$state  = sessionState($ev);
$joined = isParticipant($pdo, $ev['EventID'], $me);
$isHost = $ev['HostID'] === $me;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM Member_Event WHERE EventID = ? AND LeftAt IS NULL');
$stmt->execute([$ev['EventID']]);
$inSession = (int)$stmt->fetchColumn();

$msg = $_SESSION['club_msg'] ?? null;
unset($_SESSION['club_msg']);

$pageTitle = $ev['EventName'] . ' - Coastal Bookshop';
require __DIR__.'/includes/header.php';
?>
            <div class="club-page" id="session-room" data-event="<?= e($ev['EventID']) ?>" data-state="<?= e($state) ?>">
                <p><a class="order-link" href="bookclub.php#sessions">Back to all sessions</a></p>

                <h2><?= e($ev['EventName']) ?> <?= sessionBadge($state) ?></h2>

                <div class="club-card">
                    <p><strong>Book:</strong>
                        <?= $ev['BookTitle'] ? e($ev['BookTitle']) . ' by ' . e($ev['BookAuthor']) : 'Not listed' ?></p>
                    <p><strong>When:</strong> <?= e(sessionWhen($ev)) ?></p>
                    <p><strong>Started by:</strong> <?= $isHost ? 'You' : e($ev['HostName'] ?? 'A member') ?>
                       &nbsp;|&nbsp; <strong>Members in session:</strong> <?= $inSession ?></p>
                </div>

                <p id="session-error" class="error" hidden></p>

                <div class="session-actions">
                    <?php if (!$joined && $state !== 'ended'): ?>
                        <button type="button" class="btn" data-action="join">Join session</button>
                    <?php endif; ?>
                    <?php if ($joined): ?>
                        <button type="button" class="btn" data-action="leave">Leave session</button>
                    <?php endif; ?>
                    <?php if ($isHost && $state !== 'ended'): ?>
                        <button type="button" class="btn" data-action="end">End session</button>
                    <?php endif; ?>
                </div>

                <h3>Messages</h3>
                <div class="chat-box">
                    <div id="message-list">
                        <?php
                        header('X-Session-State: ' . sessionState($ev));
                        renderMessages($pdo, $ev, $me); 
                        ?>
                    </div>

                    <?php if ($state === 'ended'): ?>
                        <p class="message chat-notice">This session has ended</p>
                    <?php elseif (!$joined): ?>
                        <p class="message chat-notice">Join this session to send messages.</p>
                    <?php elseif ($state === 'upcoming'): ?>
                        <p class="message chat-notice">This session starts on <?= e(niceTime($ev['StartTime'])) ?>. You can send messages then.</p>
                    <?php else: ?>
                        <form id="compose-form" class="chat-compose">
                            <textarea id="body" name="body" maxlength="1000" rows="1"
                                      placeholder="Type a message" aria-label="Your message" required></textarea>
                            <button type="submit" class="send-btn" aria-label="Send message">
                                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
                                    <path fill="currentColor" d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </form>
                        <script>
                            // Enter sends, Shift+Enter makes a new line; box grows as you type
                            (function () {
                                var form = document.getElementById('compose-form');
                                var box  = document.getElementById('body');
                                box.addEventListener('keydown', function (e) {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        if (box.value.trim() !== '') form.requestSubmit();
                                    }
                                });
                                box.addEventListener('input', function () {
                                    box.style.height = 'auto';
                                    box.style.height = Math.min(box.scrollHeight, 120) + 'px';
                                });
                            })();
                        </script>
                    <?php endif; ?>
                </div>
            </div>
            <script src="js/club.js" defer></script>
<?php require __DIR__.'/includes/footer.php'; ?>