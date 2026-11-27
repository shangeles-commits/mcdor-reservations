<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Welcome';
$isHomePage = true;
require __DIR__ . '/frontend/layout/header.php';
?>
<div class="home-main">
    <section class="hero" id="home">
        <div class="hero-content">
            <p class="eyebrow">Burgers · Chicken meals · Packages</p>
            <h1>A good meal<br><em>made simple.</em></h1>
            <p class="hero-description">Choose from burger meals, chicken meals, and meal packages for your visit.</p>
            <?php if (current_user()): ?>
                <a class="hero-cta" href="<?= e(app_url('reservation/booking.php')) ?>">Reserve a table <span aria-hidden="true">→</span></a>
            <?php else: ?>
                <a class="hero-cta" href="<?= e(app_url('registration/signup.php')) ?>">Create an account <span aria-hidden="true">→</span></a>
            <?php endif; ?>
        </div>
        <a class="scroll-cue" href="#experience">Scroll to discover <span aria-hidden="true">↓</span></a>
    </section>

    <section class="reservation-section" id="experience">
        <div class="reservation-card">
            <div class="reservation-copy">
                <p class="eyebrow">Good food, good company</p>
                <h2>Reserve your table at McDor</h2>
                <p>Choose a time, pick a dining package, and plan your visit. We’ll take care of the details.</p>
            </div>
            <div class="reservation-action">
                <span class="reservation-mark" aria-hidden="true">M</span>
                <?php if (current_user()): ?>
                    <p>We look forward to welcoming you.</p>
                    <a class="button" href="<?= e(app_url('reservation/booking.php')) ?>">Start a reservation <span aria-hidden="true">→</span></a>
                <?php else: ?>
                    <p>Please log in or create an account to make a reservation.</p>
                    <div class="reservation-buttons">
                        <a class="button" href="<?= e(app_url('registration/signup.php')) ?>">Create an account <span aria-hidden="true">→</span></a>
                        <a class="text-link" href="<?= e(app_url('registration/login.php')) ?>">Log in</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="story-section" id="story">
        <div class="story-inner">
            <p class="eyebrow">A neighborhood table</p>
            <h2>Burgers and chicken meals<br>for your table.</h2>
            <p class="story-description">Choose a meal package and optional sides when you make a reservation.</p>
            <div class="story-details">
                <div><small>Reservation times</small><strong><?= e(reservation_slots_label()) ?></strong></div>
                <div><small>Address</small><strong>Nicanor Reyes Street, Quiapo, Manila, 1008 Metro Manila, Philippines</strong></div>
            </div>
        </div>
    </section>
</div>
<?php require __DIR__ . '/frontend/layout/footer.php'; ?>
