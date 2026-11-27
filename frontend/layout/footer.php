</main>
<?php if (!empty($isHomePage)): ?>
<footer class="site-footer">
    <div class="footer-inner">
        <a class="brand footer-brand" href="<?= e(app_url('index.php')) ?>">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span class="brand-copy"><strong>McDor</strong><small>RESTAURANT</small></span>
        </a>
        <p id="contact">Nicanor Reyes Street, Quiapo, Manila, 1008 Metro Manila, Philippines</p>
        <p><?= e(reservation_slots_label()) ?></p>
    </div>
</footer>
<?php endif; ?>
</body>
</html>
