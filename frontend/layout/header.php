<?php
$pageTitle = $pageTitle ?? 'McDor Reservations';
$signedInUser = current_user();
$notice = take_flash();
$stylesheetVersion = filemtime(__DIR__ . '/../css/style.css');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | McDor Reservations</title>
    <link rel="stylesheet" href="<?= e(app_url('frontend/css/style.css?v=' . $stylesheetVersion)) ?>">
</head>
<body class="<?= !empty($isHomePage) ? 'home-page' : 'interior-page' ?>">
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?= e(app_url('index.php')) ?>">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span class="brand-copy"><strong>McDor</strong><small>RESTAURANT</small></span>
        </a>
        <nav class="primary-nav" aria-label="Main navigation">
            <?php if (!empty($isHomePage)): ?>
                <a href="#story">Our story</a>
                <a href="#experience">Experience</a>
                <a href="#contact">Contact</a>
            <?php else: ?>
                <a href="<?= e(app_url('index.php')) ?>">Home</a>
                <?php if ($signedInUser): ?>
                    <a href="<?= e(app_url('reservation/booking.php')) ?>">Book a table</a>
                    <a href="<?= e(app_url('reservation/my_reservation.php')) ?>">My reservations</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
        <div class="header-actions">
            <?php if ($signedInUser): ?>
                <span class="nav-user"><?= e($signedInUser['name']) ?></span>
                <?php if ($signedInUser['role'] === 'admin'): ?><a href="<?= e(app_url('admin/index.php')) ?>">Admin</a><?php endif; ?>
                <form class="nav-logout" method="post" action="<?= e(app_url('registration/logout.php')) ?>"><?= csrf_field() ?><button type="submit" class="link-button">Log out</button></form>
            <?php else: ?>
                <a class="nav-login" href="<?= e(app_url('registration/login.php')) ?>">Log in</a>
                <a class="nav-signup" href="<?= e(app_url('registration/signup.php')) ?>">Sign up</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="container">
    <?php if ($notice): ?><div class="notice <?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endif; ?>
