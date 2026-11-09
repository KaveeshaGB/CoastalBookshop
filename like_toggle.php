<?php
require __DIR__.'/includes/config.php';
header('Content-Type: application/json');

$bookId = $_POST['book'] ?? '';

$exists = $pdo->prepare('SELECT 1 FROM Books WHERE BookID = ?');
$exists->execute([$bookId]);
if (!$exists->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'unknown book']);
    exit;
}

if (!empty($_SESSION['customer_id'])) {
    // logged in: save in the BookLike table
    $customerId = $_SESSION['customer_id'];
    $has = $pdo->prepare('SELECT 1 FROM BookLike WHERE CustomerID = ? AND BookID = ?');
    $has->execute([$customerId, $bookId]);

    if ($has->fetch()) {
        $pdo->prepare('DELETE FROM BookLike WHERE CustomerID = ? AND BookID = ?')
            ->execute([$customerId, $bookId]);
        $liked = false;
    } else {
        $pdo->prepare('INSERT INTO BookLike (CustomerID, BookID) VALUES (?, ?)')
            ->execute([$customerId, $bookId]);
        $liked = true;
    }
} else {
    // visitor: keep the list in the session
    $likes = $_SESSION['guest_likes'] ?? [];
    $pos = array_search($bookId, $likes, true);

    if ($pos !== false) {
        unset($likes[$pos]);
        $liked = false;
    } else {
        $likes[] = $bookId;
        $liked = true;
    }
    $_SESSION['guest_likes'] = array_values($likes);
}

echo json_encode(['liked' => $liked]);