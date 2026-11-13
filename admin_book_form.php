<?php
require __DIR__ . '/includes/admin_check.php';

$categories = ['Fiction', 'Novel', 'Short Story', 'Kids Story'];

$id = $_GET['id'] ?? $_POST['id'] ?? '';
$errors = [];
$book   = ['Title' => '', 'Author' => '', 'Price' => '', 'Category' => $categories[0], 'ImageFile' => '',
           'Stock' => 10, 'DiscountPercent' => 0, 'OfferEnds' => ''];

// Editing: load the existing row
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM Books WHERE BookID = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        header('Location: admin_books.php');
        exit;
    }
    $book = $found;
}

// Form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book['Title']    = trim($_POST['title'] ?? '');
    $book['Author']   = trim($_POST['author'] ?? '');
    $book['Price']    = trim($_POST['price'] ?? '');
    $book['Category'] = $_POST['category'] ?? '';
    $book['Stock']    = trim($_POST['stock'] ?? '');
    $book['DiscountPercent'] = trim($_POST['discount'] ?? '') === '' ? '0' : trim($_POST['discount']);
    $book['OfferEnds'] = trim($_POST['offer_ends'] ?? '');

    if ($book['Title'] === '')  $errors[] = 'Title is required.';
    if ($book['Author'] === '') $errors[] = 'Author is required.';
    if (!is_numeric($book['Price']) || $book['Price'] < 0) $errors[] = 'Enter a valid price.';
    if (!in_array($book['Category'], $categories, true))   $errors[] = 'Choose a valid category.';
    if (filter_var($book['Stock'], FILTER_VALIDATE_INT) === false || (int)$book['Stock'] < 0) $errors[] = 'Stock must be a whole number, 0 or more.';
    if (filter_var($book['DiscountPercent'], FILTER_VALIDATE_INT) === false || (int)$book['DiscountPercent'] < 0 || (int)$book['DiscountPercent'] > 100) $errors[] = 'Discount must be a whole number from 0 to 100.';
    if ($book['OfferEnds'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $book['OfferEnds']);
        if (!$d || $d->format('Y-m-d') !== $book['OfferEnds']) $errors[] = 'Enter a valid offer end date.';
    }

    // Optional image upload
    if (!$errors && !empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed.';
        } elseif (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)
                  || !getimagesize($_FILES['image']['tmp_name'])) {
            $errors[] = 'Image must be a JPG, PNG, GIF or WEBP file.';
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image must be smaller than 2 MB.';
        } else {
            $newName = uniqid('book_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/images/covers/' . $newName)) {
                $book['ImageFile'] = $newName;
            } else {
                $errors[] = 'Could not save the image.';
            }
        }
    }

    if (!$errors) {
        if ($id) {
            // UPDATE
            $stmt = $pdo->prepare('UPDATE Books SET Title=?, Author=?, Price=?, Category=?, ImageFile=?, Stock=?, DiscountPercent=?, OfferEnds=? WHERE BookID=?');
            $stmt->execute([$book['Title'], $book['Author'], $book['Price'], $book['Category'], $book['ImageFile'] ?: null,
                            (int)$book['Stock'], (int)$book['DiscountPercent'], $book['OfferEnds'] ?: null, $id]);
            $_SESSION['admin_msg'] = 'Book updated.';
        
        } else {
        // CREATE
        $newId = nextId($pdo, 'Books', 'BookID', 'b');
        $stmt = $pdo->prepare('INSERT INTO Books (BookID, Title, Author, Price, Category, ImageFile, Stock, DiscountPercent, OfferEnds) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$newId, $book['Title'], $book['Author'], $book['Price'], $book['Category'], $book['ImageFile'] ?: null,
                        (int)$book['Stock'], (int)$book['DiscountPercent'], $book['OfferEnds'] ?: null]);
        $_SESSION['admin_msg'] = 'Book added.';
        }
        
        header('Location: admin_books.php');
        exit;
    }
}

$pageTitle = ($id ? 'Edit Book' : 'Add Book') . ' - Coastal Bookshop';
require __DIR__.'/includes/header.php';
?>
            <h2><?= $id ? 'Edit Book' : 'Add New Book' ?></h2>

            <?php foreach ($errors as $err): ?>
                <p class="error"><?= e($err) ?></p>
            <?php endforeach; ?>

            <form method="post" action="admin_book_form.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= e($id) ?>">

                <label class="form-label" for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?= e($book['Title']) ?>" required>
                <br>

                <label class="form-label" for="author">Author:</label>
                <input type="text" id="author" name="author" value="<?= e($book['Author']) ?>" required>
                <br>

                <label class="form-label" for="price">Price (Rs.):</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?= e($book['Price']) ?>" required>
                <br>

                <label class="form-label" for="stock">Stock (copies):</label>
                <input type="number" id="stock" name="stock" step="1" min="0" value="<?= e($book['Stock']) ?>" required>
                <br>

                <label class="form-label" for="discount">Discount (%):</label>
                <input type="number" id="discount" name="discount" step="1" min="0" max="100" value="<?= e($book['DiscountPercent']) ?>">
                <br>

                <label class="form-label" for="offer-ends">Offer ends on:</label>
                <input type="date" id="offer-ends" name="offer_ends" value="<?= e($book['OfferEnds']) ?>">
                <br>

                <label class="form-label" for="category">Category:</label>
                <select id="category" name="category">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= e($c) ?>" <?= $book['Category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
                <br>

                <label class="form-label" for="image">Cover Image:</label>
                <input type="file" id="image" name="image" accept="image/*">
                <?php if ($book['ImageFile']): ?>
                    <br>
                    <label class="form-label">Current image:</label>
                    <img src="images/covers/<?= e($book['ImageFile']) ?>" alt="" width="60">
                <?php endif; ?>
                <br><br>

                <button type="submit" class="btn"><?= $id ? 'Save Changes' : 'Add Book' ?></button>
                <a class="order-link" href="admin_books.php">Cancel</a>
            </form>
<?php require __DIR__.'/includes/footer.php'; ?>