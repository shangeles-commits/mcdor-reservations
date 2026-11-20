<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        $error = 'Enter a valid reservation ID.';
        unset($_SESSION['reservation_lookup_id']);
    } else {
        $_SESSION['reservation_lookup_id'] = $id;
    }
}
$reservation = null;
if (!empty($_SESSION['reservation_lookup_id'])) {
    $reservation = find_owned_reservation(db(), (int)$_SESSION['reservation_lookup_id'], (int)$user['id']);
    if (!$reservation && !$error) {
        $error = 'No reservation was found for that ID on your account.';
    }
}
$pageTitle = 'My reservation';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel narrow">
    <h1>Look up a reservation</h1>
    <form method="post">
        <?= csrf_field() ?>
        <label>Reservation ID <input type="number" min="1" name="reservation_id" required value="<?= e($_SESSION['reservation_lookup_id'] ?? '') ?>"></label>
        <button type="submit">Find reservation</button>
    </form>
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
