<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login();
$draft = $_SESSION['booking_draft'] ?? null;
if (!$draft || empty($draft['package_id'])) {
    flash('Start a reservation before checkout.', 'error');
    redirect('reservation/booking.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $method = $_POST['payment_method'] ?? '';
    if (!in_array($method, ['pay_at_restaurant', 'demo_simulated'], true)) {
        $error = 'Choose one of the listed payment options.';
    } elseif (!valid_reservation_datetime($draft['date'], $draft['time'])) {
        $error = 'This reservation time is no longer valid. Choose another slot.';
    } else {
        $connection = db();
        try {
            // Save the reservation, occupied slot, and extras together, or save none of them.
            $connection->beginTransaction();
            $table = checkAvailability($connection, $draft['date'], $draft['time'], (int)$draft['guests']);
            if (!$table) {
                throw new DomainException('That slot has just become unavailable. Choose another time.');
            }
            $prices = calculateTotal($connection, (int)$draft['package_id'], $draft['extra_ids'] ?? [], (int)$draft['guests']);
            $status = $method === 'demo_simulated' ? 'simulated_paid' : 'unpaid';
            $insert = $connection->prepare(
                'INSERT INTO reservations (user_id, table_id, package_id, package_name_snapshot, table_number_snapshot,
                    reservation_date, reservation_time, guest_count, package_price_snapshot, total_price,
                    payment_method, payment_status, reservation_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $user['id'], $table['id'], $prices['package']['id'], $prices['package']['name'], $table['table_number'],
                $draft['date'], $draft['time'], $draft['guests'], $prices['package']['price_per_guest'],
                $prices['total'], $method, $status, 'active',
            ]);
            $reservationId = (int)$connection->lastInsertId();
            $slot = $connection->prepare('INSERT INTO occupied_slots (reservation_id, table_id, reservation_date, reservation_time) VALUES (?, ?, ?, ?)');
            $slot->execute([$reservationId, $table['id'], $draft['date'], $draft['time']]);
            $extraInsert = $connection->prepare('INSERT INTO reservation_extras (reservation_id, extra_id, extra_name_snapshot, extra_price_snapshot) VALUES (?, ?, ?, ?)');
            foreach ($prices['extras'] as $extra) {
                $extraInsert->execute([$reservationId, $extra['id'], $extra['name'], $extra['price_per_guest']]);
            }
            $connection->commit();
            unset($_SESSION['booking_draft']);
            redirect('review-payment/success.php?id=' . $reservationId);
        } catch (DomainException | InvalidArgumentException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            if ($exception->getCode() === '23000') {
                $error = 'That slot was booked moments ago. Please choose another time.';
            } else {
                throw $exception;
            }
        }
    }
}
try {
    $preview = calculateTotal(db(), (int)$draft['package_id'], $draft['extra_ids'] ?? [], (int)$draft['guests']);
} catch (InvalidArgumentException $exception) {
    flash($exception->getMessage(), 'error');
    redirect('menu/menu.php');
}
$previewTable = checkAvailability(db(), $draft['date'], $draft['time'], (int)$draft['guests']);
$pageTitle = 'Confirm reservation';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel narrow">
    <h1>Confirm reservation</h1>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <dl class="receipt">
        <dt>Date and time</dt><dd><?= e(reservation_datetime_label($draft['date'], $draft['time'])) ?></dd>
        <dt>Guests</dt><dd><?= (int)$draft['guests'] ?></dd>
        <dt>Table</dt><dd><?= $previewTable ? e($previewTable['table_number']) : 'No table free for this slot' ?></dd>
        <dt>Package</dt><dd><?= e($preview['package']['name']) ?></dd>
        <dt>Optional sides</dt><dd><?= $preview['extras'] ? e(implode(', ', array_column($preview['extras'], 'name'))) : 'None' ?></dd>
        <dt>Total</dt><dd><strong><?= money($preview['total']) ?></strong></dd>
    </dl>
    <form method="post">
        <?= csrf_field() ?>
        <label>Payment method
            <select name="payment_method" required>
                <option value="pay_at_restaurant"><?= e(payment_method_label('pay_at_restaurant')) ?></option>
                <option value="demo_simulated"><?= e(payment_method_label('demo_simulated')) ?></option>
            </select>
        </label>
        <p class="muted">The demo option only labels this booking “simulated paid.” No card details are collected and no money moves.</p>
        <button type="submit">Confirm booking</button>
        <a class="button secondary" href="<?= e(app_url('review-payment/review.php')) ?>">Back to review</a>
    </form>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
