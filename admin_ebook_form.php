<?php
require __DIR__ . '/includes/admin_check.php';

$id     = $_GET['id'] ?? $_POST['id'] ?? '';
$errors = [];
$ebook  = ['Title' => '', 'Author' => '', 'Price' => '', 'DiscountPercent' => 0, 'OfferEnds' => '', 'CoverImage' => '', 'FileName' => ''];

// Editing: load the existing row
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM Ebook WHERE EbookID = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        header('Location: admin_books.php');
        exit;
    }
    $ebook = $found;
}

// The ebooks folder must exist and must not be openable straight from the browser
$ebookDir = __DIR__ . '/ebooks';
if (!is_dir($ebookDir)) mkdir($ebookDir, 0755, true);
if (!is_file($ebookDir . '/.htaccess')) file_put_contents($ebookDir . '/.htaccess', "Require all denied\n");

// Form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldFile = $ebook['FileName'];

    $ebook['Title']  = trim($_POST['title'] ?? '');
    $ebook['Author'] = trim($_POST['author'] ?? '');
    $ebook['Price']  = trim($_POST['price'] ?? '');
    $ebook['DiscountPercent'] = trim($_POST['discount'] ?? '0');
    $ebook['OfferEnds']       = trim($_POST['offer_ends'] ?? '');

    if ($ebook['Title'] === '')  $errors[] = 'Title is required.';
    if ($ebook['Author'] === '') $errors[] = 'Author is required.';
    if (!is_numeric($ebook['Price']) || $ebook['Price'] < 0) $errors[] = 'Enter a valid price.';
    if ($ebook['DiscountPercent'] === '') $ebook['DiscountPercent'] = '0';
    if (!ctype_digit((string)$ebook['DiscountPercent']) || (int)$ebook['DiscountPercent'] > 100) {
        $errors[] = 'Discount must be a whole number from 0 to 100.';
    }
    if ($ebook['OfferEnds'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $ebook['OfferEnds']);
        if (!$d || $d->format('Y-m-d') !== $ebook['OfferEnds']) $errors[] = 'Enter a valid offer end date.';
    }

    $coverUpload = null;   // [temporary path, extension]
    $fileUpload  = null;

    if (!$errors) {
        // Optional cover image
        if (!empty($_FILES['cover']['name'])) {
            $f   = $_FILES['cover'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Cover image upload failed.';
            } elseif (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) || !getimagesize($f['tmp_name'])) {
                $errors[] = 'Cover must be a JPG, PNG, GIF or WEBP image.';
            } elseif ($f['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Cover image must be smaller than 2 MB.';
            } else {
                $coverUpload = [$f['tmp_name'], $ext];
            }
        }

        // The ebook file: required when adding, optional when editing
        if (!empty($_FILES['ebook_file']['name'])) {
            $f    = $_FILES['ebook_file'];
            $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $head = ($f['error'] === UPLOAD_ERR_OK) ? (string)file_get_contents($f['tmp_name'], false, null, 0, 5) : '';
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Ebook file upload failed. The file may be too large for the server settings.';
            } elseif ($ext === 'pdf' ? strncmp($head, '%PDF', 4) !== 0
                                     : ($ext !== 'epub' || strncmp($head, 'PK', 2) !== 0)) {
                $errors[] = 'The ebook must be a real PDF or EPUB file.';
            } elseif ($f['size'] > 20 * 1024 * 1024) {
                $errors[] = 'The ebook file must be smaller than 20 MB.';
            } else {
                $fileUpload = [$f['tmp_name'], $ext];
            }
        } elseif (!$id) {
            $errors[] = 'Choose the ebook file (PDF or EPUB).';
        }
    }

    // Save the uploaded files
    if (!$errors && $coverUpload) {
        $name = uniqid('cover_') . '.' . $coverUpload[1];
        if (move_uploaded_file($coverUpload[0], __DIR__ . '/images/covers/' . $name)) {
            $ebook['CoverImage'] = $name;
        } else {
            $errors[] = 'Could not save the cover image.';
        }
    }
    if (!$errors && $fileUpload) {
        $name = uniqid('ebook_') . '.' . $fileUpload[1];
        if (move_uploaded_file($fileUpload[0], $ebookDir . '/' . $name)) {
            $ebook['FileName'] = $name;
        } else {
            $errors[] = 'Could not save the ebook file.';
        }
    }

    if (!$errors) {
        if ($id) {
            // UPDATE
            $stmt = $pdo->prepare('UPDATE Ebook SET Title=?, Author=?, Price=?, DiscountPercent=?, OfferEnds=?, CoverImage=?, FileName=? WHERE EbookID=?');
            $stmt->execute([$ebook['Title'], $ebook['Author'], $ebook['Price'], (int)$ebook['DiscountPercent'], $ebook['OfferEnds'] ?: null,
                            $ebook['CoverImage'] ?: null, $ebook['FileName'], $id]);

            // A new file replaced the old one, so remove the old one
            if ($fileUpload && $oldFile && $oldFile !== $ebook['FileName']) {
                $old = $ebookDir . '/' . basename($oldFile);
                if (is_file($old)) unlink($old);
            }
            $_SESSION['admin_msg'] = 'Ebook updated.';
        } else {
            // CREATE: keep the same ID letters the existing ebooks use (EB001, E001 ...), "EB" if there are none yet
            $first  = $pdo->query('SELECT EbookID FROM Ebook ORDER BY EbookID LIMIT 1')->fetchColumn();
            $prefix = ($first && preg_match('/^[A-Za-z]+/', $first, $m)) ? $m[0] : 'EB';
            $newId  = nextId($pdo, 'Ebook', 'EbookID', $prefix);

            $stmt = $pdo->prepare('INSERT INTO Ebook (EbookID, Title, Author, Price, DiscountPercent, OfferEnds, CoverImage, FileName) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$newId, $ebook['Title'], $ebook['Author'], $ebook['Price'], (int)$ebook['DiscountPercent'], $ebook['OfferEnds'] ?: null,
                            $ebook['CoverImage'] ?: null, $ebook['FileName']]);
            $_SESSION['admin_msg'] = 'Ebook added.';
        }

        header('Location: admin_books.php');
        exit;
    }
}

$pageTitle = ($id ? 'Edit Ebook' : 'Add Ebook') . ' - Coastal Bookshop';
require __DIR__ . '/includes/header.php';
?>
            <h2><?= $id ? 'Edit Ebook' : 'Add New Ebook' ?></h2>

            <?php foreach ($errors as $err): ?>
                <p class="error"><?= e($err) ?></p>
            <?php endforeach; ?>

            <form method="post" action="admin_ebooks_form.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= e($id) ?>">

                <label class="form-label" for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?= e($ebook['Title']) ?>" required>
                <br>

                <label class="form-label" for="author">Author:</label>
                <input type="text" id="author" name="author" value="<?= e($ebook['Author']) ?>" required>
                <br>

                <label class="form-label" for="price">Price (Rs.):</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?= e($ebook['Price']) ?>" required>
                <br>

                <label class="form-label" for="discount">Discount (%):</label>
                <input type="number" id="discount" name="discount" min="0" max="100" step="1" value="<?= e($ebook['DiscountPercent']) ?>">
                <br>

                <label class="form-label" for="offer_ends">Offer ends on:</label>
                <input type="date" id="offer_ends" name="offer_ends" value="<?= e($ebook['OfferEnds']) ?>">
                <br>

                <label class="form-label" for="ebook_file">Ebook file (PDF or EPUB):</label>
                <input type="file" id="ebook_file" name="ebook_file" accept=".pdf,.epub" <?= $id ? '' : 'required' ?>>
                <?php if ($id && $ebook['FileName']): ?>
                    <br>
                    <label class="form-label">Current file:</label>
                    <span><?= e($ebook['FileName']) ?> (choose a new file only to replace it)</span>
                <?php endif; ?>
                <br>

                <label class="form-label" for="cover">Cover Image:</label>
                <input type="file" id="cover" name="cover" accept="image/*">
                <?php if ($ebook['CoverImage']): ?>
                    <br>
                    <label class="form-label">Current cover:</label>
                    <img src="images/covers/<?= e($ebook['CoverImage']) ?>" alt="" width="60">
                <?php endif; ?>
                <br><br>

                <button type="submit" class="btn"><?= $id ? 'Save Changes' : 'Add Ebook' ?></button>
                <a class="order-link" href="admin_books.php">Cancel</a>
            </form>
<?php require __DIR__ . '/includes/footer.php'; ?>