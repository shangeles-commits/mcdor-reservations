<?php
$pageTitle = $pageTitle ?? 'McDor Reservations';
$signedInUser = current_user();
$notice = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | McDor Reservations</title>
    <link rel="stylesheet" href="<?= e(app_url('frontend/css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?= e(app_url('index.php')) ?>">McDor Reservations</a>
    <nav>
        <a href="<?= e(app_url('index.php')) ?>">Home</a>
        <?php if ($signedInUser): ?>
            <a href="<?= e(app_url('reservation/booking.php')) ?>">Book a table</a>
            <a href="<?= e(app_url('reservation/my_reservation.php')) ?>">My reservation</a>
            <?php if ($signedInUser['role'] === 'admin'): ?><a href="<?= e(app_url('admin/index.php')) ?>">Admin</a><?php endif; ?>
            <span class="nav-user"><?= e($signedInUser['name']) ?></span>
            <form class="nav-logout" method="post" action="<?= e(app_url('registration/logout.php')) ?>"><?= csrf_field() ?><button type="submit" class="link-button">Log out</button></form>
        <?php else: ?>
            <a href="<?= e(app_url('registration/login.php')) ?>">Log in</a>
            <a href="<?= e(app_url('registration/signup.php')) ?>">Sign up</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
    <?php if ($notice): ?><div class="notice <?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endif; ?>
