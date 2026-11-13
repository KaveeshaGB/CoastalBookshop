<?php
// Shared helpers for the Book Club (sessions, messages, likes).
require_once __DIR__.'/config.php';

// A member is a customer who has a row in the Member table.
function isMember(PDO $pdo, $customerId): bool {
    if (!$customerId) return false;
    $stmt = $pdo->prepare('SELECT 1 FROM Member WHERE CustomerID = ? AND ResignedAt IS NULL');
    $stmt->execute([$customerId]);
    return (bool)$stmt->fetchColumn();
}

// One session with its book and host name (false if it does not exist).
function getSession(PDO $pdo, string $eventId) {
    $stmt = $pdo->prepare(
        'SELECT e.*, b.Title AS BookTitle, b.Author AS BookAuthor, c.Name AS HostName
         FROM Event e
         LEFT JOIN Books b    ON b.BookID = e.BookID
         LEFT JOIN Customer c ON c.CustomerID = e.HostID
         WHERE e.EventID = ?');
    $stmt->execute([$eventId]);
    return $stmt->fetch();
}

// 'upcoming', 'live' or 'ended'.
// A session ends when the host ends it OR its end time passes.
function sessionState(array $ev): string {
    if ($ev['Status'] === 'Ended') return 'ended';
    $now = time();
    if (!empty($ev['EndTime'])   && $now >= strtotime($ev['EndTime']))   return 'ended';
    if (!empty($ev['StartTime']) && $now <  strtotime($ev['StartTime'])) return 'upcoming';
    return 'live';
}

// Is this member currently in the session? (joined and has not left)
function isParticipant(PDO $pdo, string $eventId, $customerId): bool {
    if (!$customerId) return false;
    $stmt = $pdo->prepare('SELECT 1 FROM Member_Event WHERE EventID = ? AND CustomerID = ? AND LeftAt IS NULL');
    $stmt->execute([$eventId, $customerId]);
    return (bool)$stmt->fetchColumn();
}

// Message IDs are numbered inside each event: M001, M002, ...
function nextMessageId(PDO $pdo, string $eventId): string {
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(CAST(SUBSTRING(MessageID, 2) AS UNSIGNED)), 0) FROM Message WHERE EventID = ?');
    $stmt->execute([$eventId]);
    return 'M' . str_pad((int)$stmt->fetchColumn() + 1, 3, '0', STR_PAD_LEFT);
}

function niceTime($dateTime): string {
    return date('d M Y, h:i A', strtotime($dateTime));
}

// "06 Nov 2026, 07:00 PM to 08:30 PM"
function sessionWhen(array $ev): string {
    if (empty($ev['StartTime']) || empty($ev['EndTime'])) {
        return date('d M Y', strtotime($ev['EventDate']));
    }
    $start = strtotime($ev['StartTime']);
    $end   = strtotime($ev['EndTime']);
    if (date('Y-m-d', $start) === date('Y-m-d', $end)) {
        return date('d M Y, h:i A', $start) . ' to ' . date('h:i A', $end);
    }
    return date('d M Y, h:i A', $start) . ' to ' . date('d M Y, h:i A', $end);
}

function sessionBadge(string $state): string {
    $labels = ['live' => 'Live now', 'upcoming' => 'Upcoming', 'ended' => 'Ended'];
    return '<span class="club-badge ' . e($state) . '">' . e($labels[$state]) . '</span>';
}

// Prints every message of a session (used by session.php and session_messages.php).
function renderMessages(PDO $pdo, array $ev, string $me): void {
    // Only people in the session can like or edit, and only while it is live.
    $canAct = sessionState($ev) === 'live' && isParticipant($pdo, $ev['EventID'], $me);

    $stmt = $pdo->prepare(
        'SELECT m.MessageID, m.CustomerID, m.Body, m.SentAt, m.EditedAt, c.Name,
                (SELECT COUNT(*) FROM Message_Like l
                  WHERE l.EventID = m.EventID AND l.MessageID = m.MessageID) AS Likes,
                (SELECT COUNT(*) FROM Message_Like l
                  WHERE l.EventID = m.EventID AND l.MessageID = m.MessageID AND l.CustomerID = ?) AS LikedByMe
         FROM Message m
         JOIN Customer c ON c.CustomerID = m.CustomerID
         WHERE m.EventID = ?
         ORDER BY m.SentAt, CAST(SUBSTRING(m.MessageID, 2) AS UNSIGNED)');
    $stmt->execute([$me, $ev['EventID']]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        echo '<p class="message">No messages yet.</p>';
        return;
    }

    foreach ($rows as $m) {
        $mine = $m['CustomerID'] === $me;
        echo '<div class="msg' . ($mine ? ' mine' : '') . '" data-id="' . e($m['MessageID']) . '" data-body="' . e($m['Body']) . '">';
        echo '<div class="msg-head"><strong>' . e($m['Name']) . '</strong>';
        echo '<span class="msg-time">' . e(niceTime($m['SentAt'])) . '</span>';
        if ($m['EditedAt']) {
            echo '<span class="msg-edited">edited ' . e(niceTime($m['EditedAt'])) . '</span>';
        }
        echo '</div>';
        echo '<p class="msg-body">' . nl2br(e($m['Body'])) . '</p>';
        echo '<div class="msg-tools">';
        echo '<button type="button" class="mini-btn like-btn' . ($m['LikedByMe'] ? ' liked' : '') . '"'
           . ($canAct ? '' : ' disabled') . ' aria-label="Like this message">'
           . '<span class="like-heart">&#9829;</span> '
           . '<span class="like-count">' . (int)$m['Likes'] . '</span></button>';
        if ($mine && $canAct) {
            echo '<button type="button" class="mini-btn edit-btn">Edit</button>';
        }
        echo '</div></div>';
    }
}
