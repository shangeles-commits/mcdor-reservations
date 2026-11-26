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
$packageImages = [
    'burger with fries and drink' => ['packages/burger-meal.jpg', 'extras/extra-fries.jpg'],
    'chicken sandwich with drink' => 'packages/chicken-sandwich.jpg',
    'fried chicken and spaghetti with drink' => 'packages/spaghetti.jpg',
    'burger steak with rice and drink' => 'packages/burger-steak-rice.jpg',
    'chicken salad with drink' => 'packages/chicken-salad.jpg',
    'fried chicken with fries and drink' => ['packages/fried-chicken.jpg', 'extras/extra-fries.jpg'],
    'fries with sundae' => ['extras/extra-fries.jpg', 'extras/sundae.jpg'],
    'chicken fillet with rice and drink' => 'packages/chicken-fillet-rice.jpg',
    'barbeque pork sandwich with drink' => 'packages/barbeque-pork-sandwich.jpg',
    'mcdor kids meal (chicken fingers and fries)' => ['packages/chicken-fingers.jpg', 'extras/extra-fries.jpg'],
    'mcdor weekly special (currently spicy chicken fillet with sundae and drink)' => 'packages/spicy-chicken.jpg',
];
$extraImages = [
    'extra fries' => 'extra-fries.jpg',
    'side salad' => 'side-salad.jpg',
    'sundae' => 'sundae.jpg',
    'soft drink' => 'soft-drink.jpg',
];
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
        <fieldset class="package-options"><legend>Menu package (required)</legend>
            <div class="package-options-grid">
                <?php foreach ($packages as $package): ?>
                    <?php $packageImage = $packageImages[strtolower(trim($package['name']))] ?? ''; ?>
                    <label class="package-card">
                        <input type="radio" name="package_id" value="<?= (int)$package['id'] ?>" required <?= (int)($_SESSION['booking_draft']['package_id'] ?? 0) === (int)$package['id'] ? 'checked' : '' ?>>
                        <?php if (is_array($packageImage)): ?>
                            <span class="package-card-image-pair">
                                <?php foreach ($packageImage as $image): ?>
                                    <img src="<?= e(app_url('frontend/images/' . $image)) ?>" alt="" loading="lazy" decoding="async">
                                <?php endforeach; ?>
                            </span>
                        <?php elseif ($packageImage !== ''): ?>
                            <img src="<?= e(app_url('frontend/images/' . $packageImage)) ?>" alt="" loading="lazy" decoding="async">
                        <?php else: ?>
                            <span class="package-card-placeholder" aria-hidden="true">McDor</span>
                        <?php endif; ?>
                        <span class="package-card-content">
                            <strong><?= e($package['name']) ?></strong>
                            <span class="package-card-description"><?= e($package['description']) ?></span>
                            <span class="package-card-price"><?= money($package['price_per_guest']) ?> <small>per guest</small></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <fieldset class="side-options"><legend>Optional sides (per guest)</legend>
            <div class="side-options-grid">
                <?php foreach ($extras as $extra): ?>
                    <?php $extraImage = $extraImages[strtolower(trim($extra['name']))] ?? ''; ?>
                    <label class="side-card">
                        <input type="checkbox" name="extra_ids[]" value="<?= (int)$extra['id'] ?>" <?= in_array((int)$extra['id'], $_SESSION['booking_draft']['extra_ids'] ?? [], true) ? 'checked' : '' ?>>
                        <?php if ($extraImage !== ''): ?>
                            <img src="<?= e(app_url('frontend/images/extras/' . $extraImage)) ?>" alt="" loading="lazy" decoding="async">
                        <?php else: ?>
                            <span class="side-card-placeholder" aria-hidden="true">McDor</span>
                        <?php endif; ?>
                        <span class="side-card-content">
                            <strong><?= e($extra['name']) ?></strong>
                            <span class="side-card-price"><?= money($extra['price_per_guest']) ?> <small>per guest</small></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <button type="submit">Review reservation</button>
    </form>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
