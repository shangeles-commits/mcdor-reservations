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
