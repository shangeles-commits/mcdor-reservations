</main>
<footer class="site-footer">
    <?php if (!empty($isHomePage)): ?>
        <div class="footer-inner">
            <a class="brand footer-brand" href="<?= e(app_url('index.php')) ?>">
                <span class="brand-mark" aria-hidden="true">M</span>
                <span class="brand-copy"><strong>McDor</strong><small>RESTAURANT</small></span>
            </a>
            <p id="contact">Nicanor Reyes Street, Quiapo, Manila, 1008 Metro Manila, Philippines</p>
            <p><?= e(reservation_slots_label()) ?></p>
            <small>Prototype only. Simulated payment is a demo status; no real payment is collected.</small>
        </div>
    <?php else: ?>
        Prototype only. Simulated payment is a demo status; no real payment is collected.
    <?php endif; ?>
</footer>
</body>
</html>
