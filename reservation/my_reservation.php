<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login();
$reservationQuery = db()->prepare(
    'SELECT id, reservation_date, reservation_time, payment_method, payment_status, reservation_status
     FROM reservations
     WHERE user_id = ?
     ORDER BY reservation_date DESC, reservation_time DESC, id DESC'
);
$reservationQuery->execute([(int)$user['id']]);
$reservations = $reservationQuery->fetchAll();
$reservationIds = array_fill_keys(array_map(static fn($row) => (int)$row['id'], $reservations), true);
$error = '';
$selectedId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $reservationId = $_POST['reservation_id'] ?? null;
    $id = is_scalar($reservationId) ? filter_var($reservationId, FILTER_VALIDATE_INT) : false;
    if ($id === false || $id === null || $id < 1 || !isset($reservationIds[$id])) {
        $error = 'Choose a reservation from your list.';
        unset($_SESSION['reservation_lookup_id']);
    } else {
        $selectedId = $id;
        $_SESSION['reservation_lookup_id'] = $selectedId;
    }
} elseif (isset($_SESSION['reservation_lookup_id'])) {
    $storedId = filter_var($_SESSION['reservation_lookup_id'], FILTER_VALIDATE_INT);
    if ($storedId !== false && isset($reservationIds[$storedId])) {
        $selectedId = $storedId;
    } else {
        unset($_SESSION['reservation_lookup_id']);
    }
}
$reservation = null;
if ($selectedId !== null) {
    $reservation = find_owned_reservation(db(), $selectedId, (int)$user['id']);
    if (!$reservation && !$error) {
        $error = 'That reservation could not be found. Choose another reservation.';
    }
}
$pageTitle = 'My reservation';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel narrow">
    <h1>My reservations</h1>
    <?php if ($reservations): ?>
        <p>Choose one of your reservations to see its details.</p>
        <form class="reservation-selector" method="post">
            <?= csrf_field() ?>
            <label for="reservation_id">Reservation</label>
            <select id="reservation_id" name="reservation_id" required>
                <option value="">Choose a reservation</option>
                <?php foreach ($reservations as $option): ?>
                    <?php
                    $optionId = (int)$option['id'];
                    $dateTimeLabel = reservation_datetime_label($option['reservation_date'], $option['reservation_time']);
                    $methodLabel = payment_method_label($option['payment_method']);
                    $paymentLabel = payment_status_label($option['payment_status']);
                    $statusLabel = reservation_status_label($option['reservation_status']);
                    ?>
                    <option value="<?= $optionId ?>" <?= $selectedId === $optionId ? 'selected' : '' ?>>
                        <?= e('#' . $optionId . ' · ' . $dateTimeLabel . ' · ' . $methodLabel . ' · Payment: ' . $paymentLabel . ' · Reservation: ' . $statusLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">View reservation</button>
        </form>
    <?php else: ?>
        <p>You don’t have any reservations yet. <a href="<?= e(app_url('reservation/booking.php')) ?>">Book a table</a> to get started.</p>
    <?php endif; ?>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($reservation): ?>
        <hr>
        <?php require dirname(__DIR__) . '/frontend/layout/receipt.php'; ?>
        <?php if ($reservation['reservation_status'] === 'active'): ?>
            <div class="actions">
                <a class="button" href="<?= e(app_url('reservation/edit_reservation.php?id=' . (int)$reservation['id'])) ?>">Change time</a>
                <form method="post" action="<?= e(app_url('reservation/cancel_reservation.php')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="reservation_id" value="<?= (int)$reservation['id'] ?>">
                    <button class="danger" type="submit">Cancel reservation</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
