<?php
// Called by club.js with fetch(); replies with JSON.
// Actions: join, leave, end, post, edit, like
require_once 'club_functions.php';
header('Content-Type: application/json');

function reply(bool $ok, string $message = ''): void {
    echo json_encode(['ok' => $ok, 'message' => $message]);
    exit;
}

// Undo the open transaction (if any) and send an error back.
function stop(PDO $pdo, string $message): void {
    if ($pdo->inTransaction()) $pdo->rollBack();
    reply(false, $message);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(false, 'Invalid request.');

$me = $_SESSION['customer_id'] ?? null;
if (!isMember($pdo, $me)) reply(false, 'Only members can use book club sessions.');

$action  = $_POST['action'] ?? '';
$eventId = trim($_POST['event_id'] ?? '');

try {
    $pdo->beginTransaction();

    // Lock the session row so two requests cannot clash
    // (for example ending the session while someone is posting).
    $stmt = $pdo->prepare('SELECT * FROM Event WHERE EventID = ? FOR UPDATE');
    $stmt->execute([$eventId]);
    $ev = $stmt->fetch();
    if (!$ev) stop($pdo, 'Session not found.');

    $state  = sessionState($ev);
    $joined = isParticipant($pdo, $eventId, $me);
    $isHost = $ev['HostID'] === $me;

    switch ($action) {

        case 'join':
            if ($state === 'ended') stop($pdo, 'This session has ended.');
            if ($joined)            stop($pdo, 'You are already in this session.');
            // Coming back after leaving: clear LeftAt. First time: add the row.
            $upd = $pdo->prepare('UPDATE Member_Event SET LeftAt = NULL WHERE EventID = ? AND CustomerID = ?');
            $upd->execute([$eventId, $me]);
            if ($upd->rowCount() === 0) {
                $pdo->prepare('INSERT INTO Member_Event (CustomerID, EventID) VALUES (?,?)')
                    ->execute([$me, $eventId]);
            }
            $_SESSION['club_msg'] = 'You joined this session.';
            break;

        case 'leave':
            if (!$joined) stop($pdo, 'You are not in this session.');
            // The person who started it must end it before leaving.
            if ($isHost && $state !== 'ended') {
                stop($pdo, 'You started this session, so end it before you leave.');
            }
            $pdo->prepare('UPDATE Member_Event SET LeftAt = NOW() WHERE EventID = ? AND CustomerID = ?')
                ->execute([$eventId, $me]);
            $_SESSION['club_msg'] = 'You left the session.';
            break;

        case 'end':
            if (!$isHost)           stop($pdo, 'Only the person who started the session can end it.');
            if ($state === 'ended') stop($pdo, 'This session has already ended.');
            $pdo->prepare("UPDATE Event SET Status = 'Ended' WHERE EventID = ?")->execute([$eventId]);
            $_SESSION['club_msg'] = 'The session has ended. Messages can no longer be changed.';
            break;

        case 'post':
            if (!$joined)              stop($pdo, 'Join the session to send messages.');
            if ($state === 'upcoming') stop($pdo, 'This session has not started yet.');
            if ($state === 'ended')    stop($pdo, 'This session has ended.');
            $body = trim($_POST['body'] ?? '');
            if ($body === '')              stop($pdo, 'Write a message first.');
            if (mb_strlen($body) > 1000)   stop($pdo, 'Messages can be up to 1000 characters.');
            $messageId = nextMessageId($pdo, $eventId);
            $pdo->prepare('INSERT INTO Message (EventID, MessageID, CustomerID, Body) VALUES (?,?,?,?)')
                ->execute([$eventId, $messageId, $me, $body]);
            break;

        case 'edit':
            $messageId = trim($_POST['message_id'] ?? '');
            $stmt = $pdo->prepare('SELECT * FROM Message WHERE EventID = ? AND MessageID = ?');
            $stmt->execute([$eventId, $messageId]);
            $m = $stmt->fetch();
            if (!$m)                       stop($pdo, 'Message not found.');
            if ($m['CustomerID'] !== $me)  stop($pdo, 'You can only edit your own messages.');
            if ($state !== 'live')         stop($pdo, 'This session is not live, so messages can no longer be changed.');
            if (!$joined)                  stop($pdo, 'Join the session to edit your messages.');
            $body = trim($_POST['body'] ?? '');
            if ($body === '')              stop($pdo, 'A message cannot be empty.');
            if (mb_strlen($body) > 1000)   stop($pdo, 'Messages can be up to 1000 characters.');
            if ($body !== $m['Body']) {
                $pdo->prepare('UPDATE Message SET Body = ?, EditedAt = NOW() WHERE EventID = ? AND MessageID = ?')
                    ->execute([$body, $eventId, $messageId]);
            }
            break;

        case 'like':
            $messageId = trim($_POST['message_id'] ?? '');
            if (!$joined)          stop($pdo, 'Join the session to like messages.');
            if ($state !== 'live') stop($pdo, 'This session is not live, so messages can no longer be liked.');
            $stmt = $pdo->prepare('SELECT 1 FROM Message WHERE EventID = ? AND MessageID = ?');
            $stmt->execute([$eventId, $messageId]);
            if (!$stmt->fetchColumn()) stop($pdo, 'Message not found.');

            // Click once to like, click again to remove the like
            $del = $pdo->prepare('DELETE FROM Message_Like WHERE EventID = ? AND MessageID = ? AND CustomerID = ?');
            $del->execute([$eventId, $messageId, $me]);
            if ($del->rowCount() === 0) {
                $pdo->prepare('INSERT INTO Message_Like (EventID, MessageID, CustomerID) VALUES (?,?,?)')
                    ->execute([$eventId, $messageId, $me]);
            }
            break;

        default:
            stop($pdo, 'Unknown action.');
    }

    $pdo->commit();
    reply(true);

} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    reply(false, 'Something went wrong. Please try again.');
}
