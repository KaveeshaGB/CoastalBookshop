<?php
$pageTitle = 'Register - Coastal Bookshop';
require 'config.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']    = trim($_POST['name'] ?? '');
    $old['email']   = trim($_POST['email'] ?? '');
    $old['phone']   = trim($_POST['phone'] ?? '');
    $old['address'] = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $member   = isset($_POST['member']);

    if ($old['name'] === '') $errors[] = 'Name is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if ($old['phone'] !== '' && !preg_match('/^[0-9+ \-]{7,20}$/', $old['phone'])) $errors[] = 'Enter a valid phone number.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT 1 FROM Customer WHERE Email = ?');
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $errors[] = 'That email is already registered.';
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO Customer (Name, Email, Phone, Address, PasswordHash) VALUES (?,?,?,?,?)');
            $ins->execute([
                $old['name'], $old['email'],
                $old['phone'] ?: null, $old['address'] ?: null,
                password_hash($password, PASSWORD_DEFAULT),
            ]);
            $id = (int)$pdo->lastInsertId();
            if ($member) {
                $pdo->prepare('INSERT INTO Member (CustomerID) VALUES (?)')->execute([$id]);
            }
            $pdo->commit();
            $success = true;
        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = 'Could not create the account. Please try again.';
        }
    }
}

require 'header.php';
?>
            <h2>Sign-in (Register)</h2>

            <?php if ($success): ?>
                <p class="message">Account created! You can now log in using the Log-in button.</p>
            <?php else: ?>
                <?php foreach ($errors as $err): ?>
                    <p class="error"><?= e($err) ?></p>
                <?php endforeach; ?>

                <form id="register-form" method="post" action="register.php">
                    <label class="form-label" for="name">Full Name:</label>
                    <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
                    <br>
                    <label class="form-label" for="reg-email">Email:</label>
                    <input type="email" id="reg-email" name="email" value="<?= e($old['email']) ?>" required>
                    <br>
                    <label class="form-label" for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>">
                    <br>
                    <label class="form-label" for="address">Address:</label>
                    <input type="text" id="address" name="address" value="<?= e($old['address']) ?>">
                    <br>
                    <label class="form-label" for="password">Password:</label>
                    <input type="password" id="password" name="password" minlength="6" required>
                    <br>
                    <label class="form-label" for="confirm">Confirm Password:</label>
                    <input type="password" id="confirm" name="confirm" minlength="6" required>
                    <br>
                    <label class="form-label" for="member">Join as member:</label>
                    <input type="checkbox" id="member" name="member">
                    <br><br>
                    <button type="submit" class="btn">Register</button>
                </form>
            <?php endif; ?>
<?php require 'footer.php'; ?>
