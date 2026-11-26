<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$draft = $_SESSION['booking_draft'] ?? null;
if (!$draft || !valid_reservation_datetime($draft['date'], $draft['time'])) {
    flash('Choose a valid reservation time first.', 'error');
    redirect('reservation/booking.php');
}

$connection = db();
$packages = $connection->query('SELECT id, name, description, price_per_guest FROM menu_packages WHERE active = 1 ORDER BY name')->fetchAll();
$extras = $connection->query('SELECT id, name, price_per_guest FROM extras WHERE active = 1 ORDER BY name')->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        $selection = calculateTotal(
            $connection,
            (int)($_POST['package_id'] ?? 0),
            (array)($_POST['extra_ids'] ?? []),
            (int)$draft['guests']
        );
        $_SESSION['booking_draft']['package_id'] = (int)$selection['package']['id'];
        $_SESSION['booking_draft']['extra_ids'] = array_map(fn($extra) => (int)$extra['id'], $selection['extras']);
        redirect('review-payment/review.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    }
}

$guestCount = (int)$draft['guests'];
$selectedPackageId = (int)($_SESSION['booking_draft']['package_id'] ?? 0);
$selectedExtraIds = $_SESSION['booking_draft']['extra_ids'] ?? [];

$pageTitle = 'Choose a menu package';
require dirname(__DIR__) . '/frontend/layout/header.php';
require __DIR__ . '/views/select.view.php';
require dirname(__DIR__) . '/frontend/layout/footer.php';
