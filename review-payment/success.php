<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login();
$reservationId = (int)filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$reservation = find_owned_reservation(db(), $reservationId, (int)$user['id']);
if (!$reservation) {
    flash('Reservation receipt was not found.', 'error');
    redirect('reservation/my_reservation.php');
}
$pageTitle = 'Booking confirmed';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel narrow">
    <p class="eyebrow">Booking confirmed</p>
    <h1>Thank you, <?= e($user['name']) ?>!</h1>
    <p>Your reservation ID is <strong>#<?= (int)$reservation['id'] ?></strong>. Save it to look up your receipt.</p>
    <?php require dirname(__DIR__) . '/frontend/layout/receipt.php'; ?>
    <a class="button" href="<?= e(app_url('reservation/my_reservation.php')) ?>">View or manage reservation</a>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
