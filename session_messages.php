<?php
// Returns only the message list (no page around it). club.js uses it to refresh the messages.
require __DIR__.'/includes/member_check.php';   // members only ($me = the member's ID)

$ev = getSession($pdo, $_GET['id'] ?? '');
if (!$ev) {
    http_response_code(404);
    exit;
}

renderMessages($pdo, $ev, $me);
