<?php
// Opens a bought ebook. The Read link on the shelf has target="_blank", so it opens in a new tab.
// The files live in /ebooks (blocked from direct access by ebooks/.htaccess),
// so this script is the only way to get one. Replaces download_ebook.php.
require_once __DIR__.'/includes/club_functions.php';

$me = $_SESSION['customer_id'] ?? null;
if (!isMember($pdo, $me)) {
    http_response_code(403);
    exit('Members only.');
}

// The member must have bought this ebook
$stmt = $pdo->prepare('SELECT e.Title, e.FileName
                         FROM Ebook e
                         JOIN Member_Ebook m ON m.EbookID = e.EbookID
                        WHERE e.EbookID = ? AND m.CustomerID = ?');
$stmt->execute([trim($_GET['id'] ?? ''), $me]);
$ebook = $stmt->fetch();
if (!$ebook) {
    http_response_code(403);
    exit('You can only open ebooks you have bought.');
}

// basename() stops anyone from reaching files outside /ebooks
$path = __DIR__.'/ebooks/'.basename($ebook['FileName']);
if (!is_file($path)) {
    http_response_code(404);
    exit('Sorry, that ebook file is not available right now.');
}

$ext   = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = ['pdf' => 'application/pdf', 'epub' => 'application/epub+zip'];
$name  = trim(preg_replace('/[^A-Za-z0-9 _-]+/', '', $ebook['Title'])) ?: 'ebook';

session_write_close();   // do not keep the session locked while the file streams
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
// A PDF opens inside the new tab; other formats (epub) are saved instead
header('Content-Disposition: ' . ($ext === 'pdf' ? 'inline' : 'attachment') . '; filename="' . $name . '.' . $ext . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;