<?php
require_once __DIR__.'/includes/club_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['customer_id'])) {
    header('Location: bookclub.php');
    exit;
}

$me     = $_SESSION['customer_id'];
$action = $_POST['action'] ?? 'join';   // the old buttons send nothing, so they still mean "join"

// ---------------------------------------------------------------
// RESIGN
// ---------------------------------------------------------------
if ($action === 'resign') {
    if (!isMember($pdo, $me)) {
        $_SESSION['club_error'] = 'You are not a member.';
        header('Location: bookclub.php');
        exit;
    }

    // A host must end their sessions first, so no session is left without a host
    $stmt = $pdo->prepare('SELECT * FROM Event WHERE HostID = ?');
    $stmt->execute([$me]);
    foreach ($stmt->fetchAll() as $ev) {
        if (sessionState($ev) !== 'ended') {
            $_SESSION['club_error'] = 'You are hosting "' . $ev['EventName'] . '". End it before you resign.';
            header('Location: session.php?id=' . urlencode($ev['EventID']));
            exit;
        }
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE Member_Event SET LeftAt = NOW() WHERE CustomerID = ? AND LeftAt IS NULL')
            ->execute([$me]);
        $pdo->prepare('UPDATE Member SET ResignedAt = NOW() WHERE CustomerID = ?')
            ->execute([$me]);
        $pdo->commit();
        $_SESSION['club_msg'] = 'You have resigned from your membership.';
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['club_error'] = 'Could not resign. Please try again.';
    }

    header('Location: bookclub.php');
    exit;
}

// ---------------------------------------------------------------
// JOIN / REJOIN (the "Become a member" button)
// ---------------------------------------------------------------

// The button on the My Bookshelf tab sends return=bookshelf, so the answer shows on that tab.
// The button on the Sessions tab sends nothing, so the answer shows on the Sessions tab as before.
$fromShelf = ($_POST['return'] ?? '') === 'bookshelf';
$okKey     = $fromShelf ? 'shelf_msg'   : 'club_msg';
$errKey    = $fromShelf ? 'shelf_error' : 'club_error';

if (isMember($pdo, $me)) {
    $_SESSION[$okKey] = 'You are already a member.';
} else {
    try {
        // New member: adds the row. Returning member: clears ResignedAt and restarts JoinDate.
        $pdo->prepare('INSERT INTO Member (CustomerID) VALUES (?)
                       ON DUPLICATE KEY UPDATE ResignedAt = NULL, JoinDate = CURDATE()')
            ->execute([$me]);
        $_SESSION[$okKey] = 'You are now a member! You can join book club sessions and buy ebooks.';
    } catch (Exception $ex) {
        $_SESSION[$errKey] = 'Could not make you a member. Please try again.';
    }
}

header('Location: bookclub.php' . ($fromShelf ? '#bookshelf' : ''));
exit;