<dl class="receipt">
    <dt>Status</dt><dd><?= e(reservation_status_label($reservation['reservation_status'])) ?></dd>
    <dt>Date and time</dt><dd><?= e(reservation_datetime_label($reservation['reservation_date'], $reservation['reservation_time'])) ?></dd>
    <dt>Table</dt><dd><?= e($reservation['table_number']) ?></dd>
    <dt>Guests</dt><dd><?= (int)$reservation['guest_count'] ?></dd>
    <dt>Package</dt><dd><?= e($reservation['package_name']) ?> — <?= money($reservation['package_price_snapshot']) ?> per guest</dd>
    <dt>Extras</dt><dd><?php if ($reservation['extras']): ?><?= implode(', ', array_map(fn($extra) => e($extra['name']) . ' (' . money($extra['extra_price_snapshot']) . ')', $reservation['extras'])) ?><?php else: ?>None<?php endif; ?></dd>
    <dt>Total</dt><dd><strong><?= money($reservation['total_price']) ?></strong></dd>
    <dt>Payment</dt><dd><?= e(payment_method_label($reservation['payment_method'])) ?> · <?= e(payment_status_label($reservation['payment_status'])) ?></dd>
</dl>
