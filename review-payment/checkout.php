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
            // Save the reservation, occupied slot, and extras together or save none of them.
            $connection->beginTransaction();
            $table = checkAvailability($connection, $draft['date'], $draft['time'], (int)$draft['guests']);
            if (!$table) {
                throw new DomainException('That slot has just become unavailable. Choose another time.');
            }
            $prices = calculateTotal($connection, (int)$draft['package_id'], $draft['extra_ids'] ?? [], (int)$draft['guests']);
            $status = $method === 'demo_simulated' ? 'simulated_paid' : 'unpaid';
            $insert = $connection->prepare(
                'INSERT INTO reservations (user_id, table_id, package_id, reservation_date, reservation_time, guest_count,
                    package_price_snapshot, total_price, payment_method, payment_status, reservation_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $user['id'], $table['id'], $prices['package']['id'], $draft['date'], $draft['time'], $draft['guests'],
                $prices['package']['price_per_guest'], $prices['total'], $method, $status, 'active',
            ]);
            $reservationId = (int)$connection->lastInsertId();
            $slot = $connection->prepare('INSERT INTO occupied_slots (reservation_id, table_id, reservation_date, reservation_time) VALUES (?, ?, ?, ?)');
            $slot->execute([$reservationId, $table['id'], $draft['date'], $draft['time']]);
            $extraInsert = $connection->prepare('INSERT INTO reservation_extras (reservation_id, extra_id, extra_price_snapshot) VALUES (?, ?, ?)');
            foreach ($prices['extras'] as $extra) {
                $extraInsert->execute([$reservationId, $extra['id'], $extra['price_per_guest']]);
            }
            $connection->commit();
            unset($_SESSION['booking_draft']);
            $_SESSION['recent_reservation_id'] = $reservationId;
            redirect('review-payment/success.php');
        } catch (DomainException $exception) {
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
$pageTitle = 'Confirm reservation';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel narrow">
    <h1>Confirm reservation</h1>
    <p><?= e($draft['date']) ?> at <?= e($draft['time']) ?> · <?= e($preview['package']['name']) ?> · <?= money($preview['total']) ?></p>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Payment method
            <select name="payment_method" required>
                <option value="pay_at_restaurant">Pay at the restaurant</option>
                <option value="demo_simulated">Demo simulated payment (no real charge)</option>
            </select>
        </label>
        <p class="muted">The demo option only labels this booking “simulated paid.” No card details are collected and no money moves.</p>
        <button type="submit">Confirm booking</button>
    </form>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
