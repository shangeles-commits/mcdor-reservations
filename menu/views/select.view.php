<section class="panel">
    <h1>Choose your package and sides</h1>
    <p>
        <?= e($draft['date']) ?> at <?= e($draft['time']) ?> · <?= $guestCount ?> guest(s) ·
        <a href="<?= e(app_url('reservation/booking.php')) ?>">Change slot</a>
    </p>

    <?php if ($error): ?>
        <p class="notice error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" id="menu-selection-form" data-guests="<?= $guestCount ?>">
        <?= csrf_field() ?>

        <fieldset class="package-options">
            <legend>Menu package (required)</legend>
            <div class="package-options-grid">
                <?php foreach ($packages as $package): ?>
                    <?php $packageImage = $packageImages[strtolower(trim($package['name']))] ?? ''; ?>
                    <label class="package-card">
                        <input
                            type="radio"
                            name="package_id"
                            value="<?= (int)$package['id'] ?>"
                            data-price="<?= (float)$package['price_per_guest'] ?>"
                            required
                            <?= $selectedPackageId === (int)$package['id'] ? 'checked' : '' ?>
                        >
                        <?php if (is_array($packageImage)): ?>
                            <span class="package-card-image-pair">
                                <?php foreach ($packageImage as $image): ?>
                                    <img src="<?= e(app_url('frontend/images/' . $image)) ?>" alt="" loading="lazy" decoding="async">
                                <?php endforeach; ?>
                            </span>
                        <?php elseif ($packageImage !== ''): ?>
                            <img src="<?= e(app_url('frontend/images/' . $packageImage)) ?>" alt="" loading="lazy" decoding="async">
                        <?php else: ?>
                            <span class="package-card-placeholder" aria-hidden="true">McDor</span>
                        <?php endif; ?>
                        <span class="package-card-content">
                            <strong><?= e($package['name']) ?></strong>
                            <span class="package-card-description"><?= e($package['description']) ?></span>
                            <span class="package-card-price"><?= money($package['price_per_guest']) ?> <small>per guest</small></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="side-options">
            <legend>Optional sides (per guest)</legend>
            <div class="side-options-grid">
                <?php foreach ($extras as $extra): ?>
                    <?php $extraImage = $extraImages[strtolower(trim($extra['name']))] ?? ''; ?>
                    <label class="side-card">
                        <input
                            type="checkbox"
                            name="extra_ids[]"
                            value="<?= (int)$extra['id'] ?>"
                            data-price="<?= (float)$extra['price_per_guest'] ?>"
                            <?= in_array((int)$extra['id'], $selectedExtraIds, true) ? 'checked' : '' ?>
                        >
                        <?php if ($extraImage !== ''): ?>
                            <img src="<?= e(app_url('frontend/images/extras/' . $extraImage)) ?>" alt="" loading="lazy" decoding="async">
                        <?php else: ?>
                            <span class="side-card-placeholder" aria-hidden="true">McDor</span>
                        <?php endif; ?>
                        <span class="side-card-content">
                            <strong><?= e($extra['name']) ?></strong>
                            <span class="side-card-price"><?= money($extra['price_per_guest']) ?> <small>per guest</small></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

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
