<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
$draft = $_SESSION['booking_draft'] ?? [];
if (!is_array($draft)) {
    $draft = [];
}
$timezone = new DateTimeZone('Asia/Manila');
$dateMin = (new DateTimeImmutable('today', $timezone))->format('Y-m-d');
$dateMax = (new DateTimeImmutable('today', $timezone))->modify('+30 days')->format('Y-m-d');
$defaultDate = $dateMin;
$todayHasBookableTime = false;
foreach (allowed_slots() as $slot) {
    if (valid_reservation_datetime($dateMin, $slot)) {
        $todayHasBookableTime = true;
        break;
    }
}
if (!$todayHasBookableTime) {
    $defaultDate = (new DateTimeImmutable('today', $timezone))->modify('+1 day')->format('Y-m-d');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $dateInput = $_POST['reservation_date'] ?? '';
    $timeInput = $_POST['reservation_time'] ?? '';
    $guestInput = $_POST['guest_count'] ?? null;
    $date = is_string($dateInput) ? trim($dateInput) : '';
    $time = is_string($timeInput) ? trim($timeInput) : '';
    $guests = is_scalar($guestInput) ? filter_var($guestInput, FILTER_VALIDATE_INT) : false;
    if ($guests === false || $guests === null || $guests < 1 || $guests > 8) {
        $error = 'Choose a party size between 1 and 8 guests.';
    } elseif (!valid_reservation_datetime($date, $time)) {
        $error = 'Choose a listed time at least two hours from now and within the next 30 days.';
    } elseif (!checkAvailability(db(), $date, $time, $guests)) {
        $error = 'No active table is available for that time and party size. Choose another slot.';
    } else {
        $_SESSION['booking_draft'] = ['date' => $date, 'time' => $time, 'guests' => $guests];
        redirect('menu/menu.php');
    }
    $draft = ['date' => $date, 'time' => $time, 'guests' => $guests === false ? 2 : $guests];
}
$selectedDate = is_string($draft['date'] ?? null) ? $draft['date'] : $defaultDate;
$selectedTime = is_string($draft['time'] ?? null) ? $draft['time'] : '';
$selectedGuests = filter_var($draft['guests'] ?? 2, FILTER_VALIDATE_INT);
if ($selectedGuests === false || $selectedGuests < 1 || $selectedGuests > 8) {
    $selectedGuests = 2;
}
$pageTitle = 'Choose a reservation time';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel">
    <h1>Choose a date and time</h1>
    <p>Pick a date, time, and party size. We’ll assign the smallest available table that fits your group.</p>
    <p class="muted"><?= e(reservation_slots_label()) ?></p>
    <p class="muted">Reservations must be at least 2 hours ahead and within the next 30 days.</p>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Date <input type="date" name="reservation_date" min="<?= e($dateMin) ?>" max="<?= e($dateMax) ?>" required value="<?= e($selectedDate) ?>"></label>
            <label>Time
                <select name="reservation_time" required>
                    <?php foreach (allowed_slots() as $slot): ?>
                        <option value="<?= e($slot) ?>" <?= $selectedTime === $slot ? 'selected' : '' ?>><?= e((new DateTimeImmutable($slot))->format('g:i A')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Guests
                <select name="guest_count" required>
                    <?php for ($count = 1; $count <= 8; $count++): ?><option value="<?= $count ?>" <?= $selectedGuests === $count ? 'selected' : '' ?>><?= $count ?> <?= $count === 1 ? 'guest' : 'guests' ?></option><?php endfor; ?>
                </select>
            </label>
        </div>
        <p class="muted">We’ll check availability when you continue. If your chosen slot is full, you can pick another time.</p>
        <button type="submit">Continue to menu</button>
    </form>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
