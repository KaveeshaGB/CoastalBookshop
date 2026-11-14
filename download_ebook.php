<?php
// Sends an ebook file to a member, but only if they bought that book.
// The files live in /ebooks (blocked from direct access by ebooks/.htaccess),
// so this script is the only way to get one.
require __DIR__.'/includes/member_check.php';   // members only ($me = the member's ID)

function backToShelf(string $message): void {
    $_SESSION['shelf_error'] = $message;
    header('Location: bookclub.php#bookshelf');
    exit;
}

$bookId = trim($_GET['id'] ?? '');

// Same rule as the bookshelf: the book has an ebook file AND is in one of this member's orders.
$stmt = $pdo->prepare(
    "SELECT b.Title, b.EbookFile
       FROM Books b
      WHERE b.BookID = ?
        AND b.EbookFile IS NOT NULL AND b.EbookFile <> ''
        AND EXISTS (SELECT 1
                      FROM Orders o
                      JOIN Order_Books ob ON ob.OrderID = o.OrderID
                     WHERE o.CustomerID = ? AND ob.BookID = b.BookID)"
);
$stmt->execute([$bookId, $me]);
$book = $stmt->fetch();
if (!$book) backToShelf('You can only download ebooks you have bought.');

// basename() stops anyone from reaching files outside /ebooks
$path = EBOOK_DIR . '/' . basename($file);
if (!is_file($path)) backToShelf('Sorry, that ebook file is not available right now.');

// Remember the first download (this is what puts the book on the shelf)
$pdo->prepare('INSERT IGNORE INTO EbookDownload (CustomerID, BookID) VALUES (?,?)')
    ->execute([$me, $bookId]);

$ext   = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = ['pdf' => 'application/pdf', 'epub' => 'application/epub+zip'];
$name  = trim(preg_replace('/[^A-Za-z0-9 _-]+/', '', $book['Title'])) ?: 'ebook';

session_write_close();   // do not keep the session locked while the file streams
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $name . '.' . $ext . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;