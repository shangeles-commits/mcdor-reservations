<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Welcome';
$isHomePage = true;
require __DIR__ . '/frontend/layout/header.php';
?>
<div class="home-main">
    <section class="hero" id="home">
        <div class="hero-content">
            <p class="eyebrow">Fine dining · Portland, Oregon</p>
            <h1>An evening<br><em>worth savoring.</em></h1>
            <p class="hero-description">Seasonal plates, thoughtful hospitality, and a room that invites you to stay a little longer.</p>
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
            <h2>Rooted in the season.<br>Made for the moment.</h2>
            <p class="story-description">Our menus follow the farms, waters, and makers of the Pacific Northwest. Every plate begins with a relationship and ends with something memorable.</p>
            <div class="story-details">
                <div><small>Dinner</small><strong>Tue — Sun, from 5pm</strong></div>
                <div><small>Location</small><strong>128 NW Alder Street</strong></div>
                <div><small>Dress</small><strong>Come as you are</strong></div>
            </div>
        </div>
    </section>
</div>
<?php require __DIR__ . '/frontend/layout/footer.php'; ?>
