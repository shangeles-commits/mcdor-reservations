<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Welcome';
require __DIR__ . '/frontend/layout/header.php';
?>
<section class="hero">
    <p class="eyebrow">Good food, good company</p>
    <h1>Reserve your table at McDor</h1>
    <p>Choose a time, pick a meal package, and plan your visit. Reservations are available in two-hour slots up to 30 days ahead.</p>
    <?php if (current_user()): ?>
        <a class="button" href="<?= e(app_url('reservation/booking.php')) ?>">Start a reservation</a>
    <?php else: ?>
        <a class="button" href="<?= e(app_url('registration/signup.php')) ?>">Create an account</a>
        <a class="button secondary" href="<?= e(app_url('registration/login.php')) ?>">Log in</a>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/frontend/layout/footer.php'; ?>
