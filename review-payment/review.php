<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
$draft = $_SESSION['booking_draft'] ?? null;
if (!$draft || empty($draft['package_id']) || !valid_reservation_datetime($draft['date'], $draft['time'])) {
    flash('Complete the date, time, and menu selections first.', 'error');
    redirect('reservation/booking.php');
}
$table = checkAvailability(db(), $draft['date'], $draft['time'], (int)$draft['guests']);
try {
    $prices = calculateTotal(db(), (int)$draft['package_id'], $draft['extra_ids'] ?? [], (int)$draft['guests']);
} catch (InvalidArgumentException $exception) {
    flash($exception->getMessage(), 'error');
    redirect('menu/menu.php');
}
if (!$table) {
    flash('That slot is no longer available. Please choose another time.', 'error');
    redirect('reservation/booking.php');
}
$pageTitle = 'Review reservation';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel narrow">
    <h1>Review your reservation</h1>
    <dl class="receipt">
        <dt>Date and time</dt><dd><?= e($draft['date']) ?> at <?= e($draft['time']) ?></dd>
        <dt>Guests</dt><dd><?= (int)$draft['guests'] ?></dd>
        <dt>Assigned table</dt><dd><?= e($table['table_number']) ?> (capacity <?= (int)$table['capacity'] ?>)</dd>
        <dt>Package</dt><dd><?= e($prices['package']['name']) ?> — <?= money($prices['package']['price_per_guest']) ?> per guest</dd>
        <dt>Optional sides</dt><dd><?php if ($prices['extras']): ?><?= implode(', ', array_map(fn($x) => e($x['name']) . ' (' . money($x['price_per_guest']) . ')', $prices['extras'])) ?><?php else: ?>None<?php endif; ?></dd>
        <dt>Total</dt><dd><strong><?= money($prices['total']) ?></strong> (per guest × <?= (int)$draft['guests'] ?> guests)</dd>
    </dl>
    <p class="muted">The table and total are verified again when you confirm.</p>
    <a class="button" href="<?= e(app_url('review-payment/checkout.php')) ?>">Continue to payment simulation</a>
    <a class="button secondary" href="<?= e(app_url('menu/menu.php')) ?>">Change menu</a>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
