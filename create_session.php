<?php
require __DIR__.'/includes/member_check.php';   // only members can start a session ($me = the member's ID)

$errors = [];
// A new form starts with "now" and one hour later
$f = [
    'name'   => '',
    'book'   => '',
    'start'  => date('Y-m-d\TH:i'),
    'end'    => date('Y-m-d\TH:i', strtotime('+1 hour')),
    'public' => true,
];

$books = $pdo->query('SELECT BookID, Title, Author FROM Books ORDER BY Title')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['name']   = trim($_POST['name'] ?? '');
    $f['book']   = $_POST['book'] ?? '';
    $f['start']  = $_POST['start'] ?? '';
    $f['end']    = $_POST['end'] ?? '';
    $f['public'] = isset($_POST['public']);

    $start = DateTime::createFromFormat('Y-m-d\TH:i', $f['start']);
    $end   = DateTime::createFromFormat('Y-m-d\TH:i', $f['end']);
    $startOk = $start && $start->format('Y-m-d\TH:i') === $f['start'];
    $endOk   = $end   && $end->format('Y-m-d\TH:i')   === $f['end'];

    if ($f['name'] === '' || mb_strlen($f['name']) > 150) $errors[] = 'Enter a session name (up to 150 characters).';
    if (!in_array($f['book'], array_column($books, 'BookID'), true)) $errors[] = 'Choose the book for this session.';
    if (!$startOk) $errors[] = 'Enter a valid start date and time.';
    if (!$endOk)   $errors[] = 'Enter a valid end date and time.';
    if ($startOk && $endOk) {
        if ($end <= $start)            $errors[] = 'The session must end after it starts.';
        elseif ($end->getTimestamp() <= time()) $errors[] = 'The end time must be in the future. The server time is now ' . date('d M Y, h:i A') . '.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $eventId = nextId($pdo, 'Event', 'EventID', 'E');

            $pdo->prepare('INSERT INTO Event (EventID, EventName, EventDate, BookID, HostID, StartTime, EndTime, IsPublic)
                           VALUES (?,?,?,?,?,?,?,?)')
                ->execute([
                    $eventId, $f['name'], $start->format('Y-m-d'), $f['book'], $me,
                    $start->format('Y-m-d H:i:00'), $end->format('Y-m-d H:i:00'),
                    $f['public'] ? 1 : 0,
                ]);

            // The host is the first member in the session
            $pdo->prepare('INSERT INTO Member_Event (CustomerID, EventID) VALUES (?,?)')
                ->execute([$me, $eventId]);

            $pdo->commit();

            $_SESSION['club_msg'] = 'Session ' . $eventId . ' started.';
            header('Location: session.php?id=' . urlencode($eventId));
            exit;
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Could not start the session. Please try again.';
        }
    }
}

$pageTitle = 'Start a Session - Coastal Bookshop';
require __DIR__.'/includes/header.php';
?>
            <h2>Start a Session</h2>

            <?php foreach ($errors as $err): ?>
                <p class="error"><?= e($err) ?></p>
            <?php endforeach; ?>

            <form method="post" action="create_session.php">
                <label class="form-label" for="name">Session name:</label>
                <input type="text" id="name" name="name" maxlength="150" value="<?= e($f['name']) ?>" required>
                <br>

                <label class="form-label" for="book">Book:</label>
                <select id="book" name="book" required>
                    <option value="">Choose a book</option>
                    <?php foreach ($books as $b): ?>
                        <option value="<?= e($b['BookID']) ?>" <?= $f['book'] === $b['BookID'] ? 'selected' : '' ?>>
                            <?= e($b['Title']) ?> - <?= e($b['Author']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <br>

                <label class="form-label" for="start">Starts:</label>
                <input type="datetime-local" id="start" name="start" value="<?= e($f['start']) ?>" required>
                <br>

                <label class="form-label" for="end">Ends:</label>
                <input type="datetime-local" id="end" name="end" value="<?= e($f['end']) ?>" required>
                <br>

                <label class="form-label" for="public">Show to everyone:</label>
                <input type="checkbox" id="public" name="public" <?= $f['public'] ? 'checked' : '' ?>>
                <span>Everyone can see this session is happening. Only members can join it.</span>
                <br><br>

                <button type="submit" class="btn">Start Session</button>
                <a class="order-link" href="bookclub.php">Cancel</a>
            </form>
<?php require __DIR__.'/includes/footer.php'; ?>