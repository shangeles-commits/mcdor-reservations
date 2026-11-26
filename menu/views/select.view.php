<section class="panel">
    <div class="page-heading">
        <div>
            <h1>Choose your package and sides</h1>
            <p>
                <?= e($draft['date']) ?> at <?= e($draft['time']) ?> · 
                <strong><?= $guestCount ?> guest(s)</strong> · 
                <a href="<?= e(app_url('reservation/booking.php')) ?>">Change slot</a>
            </p>
        </div>
    </div>

    <?php if ($error): ?>
        <p class="notice error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" id="menu-selection-form" data-guests="<?= $guestCount ?>">
        <?= csrf_field() ?>

        <fieldset>
            <legend>Menu package (required)</legend>
            <?php if (empty($packages)): ?>
                <p class="muted">No active menu packages are currently available.</p>
            <?php else: ?>
                <?php foreach ($packages as $package): ?>
                    <label class="choice">
                        <input 
                            type="radio" 
                            name="package_id" 
                            value="<?= (int)$package['id'] ?>" 
                            data-price="<?= (float)$package['price_per_guest'] ?>"
                            required 
                            <?= $selectedPackageId === (int)$package['id'] ? 'checked' : '' ?>
                        >
                        <span>
                            <strong><?= e($package['name']) ?></strong><br>
                            <?= e($package['description']) ?><br>
                            <span class="muted">
                                <?= money($package['price_per_guest']) ?> per guest 
                                (<?= money((float)$package['price_per_guest'] * $guestCount) ?> for <?= $guestCount ?> pax)
                            </span>
                        </span>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </fieldset>

        <?php if (!empty($extras)): ?>
            <fieldset>
                <legend>Optional sides (per guest)</legend>
                <?php foreach ($extras as $extra): ?>
                    <label class="choice">
                        <input 
                            type="checkbox" 
                            name="extra_ids[]" 
                            value="<?= (int)$extra['id'] ?>" 
                            data-price="<?= (float)$extra['price_per_guest'] ?>"
                            <?= in_array((int)$extra['id'], $selectedExtraIds, true) ? 'checked' : '' ?>
                        >
                        <span>
                            <?= e($extra['name']) ?> — <?= money($extra['price_per_guest']) ?> per guest
                            <span class="muted">(+<?= money((float)$extra['price_per_guest'] * $guestCount) ?> total)</span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        <?php endif; ?>

        <div class="live-summary" style="padding: 1rem; margin: 1.25rem 0; border: 1px solid #ddd; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
            <span>Estimated Total (<?= $guestCount ?> pax):</span>
            <strong id="estimated-total" style="font-size: 1.25rem;">₱0.00</strong>
        </div>

        <button type="submit">Review reservation</button>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('menu-selection-form');
    const guests = parseInt(form.dataset.guests, 10) || 1;
    const totalDisplay = document.getElementById('estimated-total');

    function updateEstimate() {
        let perGuestTotal = 0;
        const selectedPkg = form.querySelector('input[name="package_id"]:checked');
        if (selectedPkg) {
            perGuestTotal += parseFloat(selectedPkg.dataset.price) || 0;
        }
        form.querySelectorAll('input[name="extra_ids[]"]:checked').forEach(extra => {
            perGuestTotal += parseFloat(extra.dataset.price) || 0;
        });
        const grandTotal = perGuestTotal * guests;
        totalDisplay.textContent = '₱' + grandTotal.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    form.addEventListener('change', updateEstimate);
    updateEstimate();
});
</script>
