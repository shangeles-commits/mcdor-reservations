<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
$draft = $_SESSION['booking_draft'] ?? [];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $date = trim($_POST['reservation_date'] ?? '');
    $time = trim($_POST['reservation_time'] ?? '');
    $guests = filter_var($_POST['guest_count'] ?? null, FILTER_VALIDATE_INT);
    if ($guests === false || $guests === null || $guests < 1 || $guests > 8 || !valid_reservation_datetime($date, $time)) {
        $error = 'Choose a valid date, one of the listed times, and 1–8 guests. Book at least two hours ahead and within 30 days.';
    } elseif (!checkAvailability(db(), $date, $time, $guests)) {
        $error = 'No active table is available for that time and party size. Choose another slot.';
    } else {
        $_SESSION['booking_draft'] = ['date' => $date, 'time' => $time, 'guests' => $guests];
        redirect('menu/menu.php');
    }
    $draft = ['date' => $date, 'time' => $time, 'guests' => $guests ?: 1];
}
$dateMin = (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
$dateMax = (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->modify('+30 days')->format('Y-m-d');
$defaultDate = $dateMin;
$todayHasBookableTime = false;
foreach (allowed_slots() as $slot) {
    if (valid_reservation_datetime($dateMin, $slot)) {
        $todayHasBookableTime = true;
        break;
    }
}
if (!$todayHasBookableTime) {
    $defaultDate = (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->modify('+1 day')->format('Y-m-d');
}
$pageTitle = 'Choose a reservation time';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel">
    <h1>Choose a date and time</h1>
    <p>Choose one of our two-hour reservation slots. The smallest suitable available table is assigned automatically.</p>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Date <input type="date" name="reservation_date" min="<?= e($dateMin) ?>" max="<?= e($dateMax) ?>" required value="<?= e($draft['date'] ?? $dateMin) ?>"></label>
            <label>Time
                <select name="reservation_time" required>
                    <?php foreach (allowed_slots() as $slot): ?>
                        <option value="<?= e($slot) ?>" <?= ($draft['time'] ?? '') === $slot ? 'selected' : '' ?>><?= e($slot) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Guests
                <select name="guest_count" required>
                    <?php for ($count = 1; $count <= 8; $count++): ?><option value="<?= $count ?>" <?= (int)($draft['guests'] ?? 2) === $count ? 'selected' : '' ?>><?= $count ?></option><?php endfor; ?>
                </select>
            </label>
        </div>
        <p class="muted">Availability is checked for your selected date when you continue. If that slot is full, choose another time.</p>
        <button type="submit">Continue to menu</button>
    </form>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
