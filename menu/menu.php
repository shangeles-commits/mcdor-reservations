<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
$draft = $_SESSION['booking_draft'] ?? null;
if (!$draft || !valid_reservation_datetime($draft['date'], $draft['time'])) {
    flash('Choose a valid reservation time first.', 'error');
    redirect('reservation/booking.php');
}
$packages = db()->query('SELECT id, name, description, price_per_guest FROM menu_packages WHERE active = 1 ORDER BY name')->fetchAll();
$extras = db()->query('SELECT id, name, price_per_guest FROM extras WHERE active = 1 ORDER BY name')->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        $selection = calculateTotal(db(), (int)($_POST['package_id'] ?? 0), (array)($_POST['extra_ids'] ?? []), (int)$draft['guests']);
        $_SESSION['booking_draft']['package_id'] = (int)$selection['package']['id'];
        $_SESSION['booking_draft']['extra_ids'] = array_map(fn($extra) => (int)$extra['id'], $selection['extras']);
        redirect('review-payment/review.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    }
}
$pageTitle = 'Choose a menu package';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel">
    <h1>Choose your package and sides</h1>
    <p><?= e($draft['date']) ?> at <?= e($draft['time']) ?> · <?= (int)$draft['guests'] ?> guest(s)</p>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <fieldset><legend>Menu package (required)</legend>
            <?php foreach ($packages as $package): ?>
                <label class="choice"><input type="radio" name="package_id" value="<?= (int)$package['id'] ?>" required <?= (int)($_SESSION['booking_draft']['package_id'] ?? 0) === (int)$package['id'] ? 'checked' : '' ?>>
                    <span><strong><?= e($package['name']) ?></strong><br><?= e($package['description']) ?><br><span class="muted"><?= money($package['price_per_guest']) ?> per guest</span></span></label>
            <?php endforeach; ?>
        </fieldset>
        <fieldset><legend>Optional sides (per guest)</legend>
            <?php foreach ($extras as $extra): ?>
                <label class="choice"><input type="checkbox" name="extra_ids[]" value="<?= (int)$extra['id'] ?>" <?= in_array((int)$extra['id'], $_SESSION['booking_draft']['extra_ids'] ?? [], true) ? 'checked' : '' ?>>
                    <span><?= e($extra['name']) ?> — <?= money($extra['price_per_guest']) ?></span></label>
            <?php endforeach; ?>
        </fieldset>
        <button type="submit">Review reservation</button>
    </form>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
