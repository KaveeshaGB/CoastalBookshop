<?php
// Called by the log-in pop-up with fetch(); replies with JSON.
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request.']);
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare('SELECT CustomerID, Name, PasswordHash FROM Customer WHERE Email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && $user['PasswordHash'] !== '' && password_verify($password, $user['PasswordHash'])) {
    session_regenerate_id(true);
    $_SESSION['customer_id']   = (int)$user['CustomerID'];
    $_SESSION['customer_name'] = $user['Name'];
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'message' => 'Incorrect email or password.']);
}
