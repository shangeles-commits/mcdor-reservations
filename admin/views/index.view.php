<section class="panel">
    <div class="page-heading">
        <div>
            <h1>Reservations</h1>
            <p>Staff tools for reservations, packages, tables, and side extras.</p>
        </div>
        <div class="actions">
            <a class="button" href="<?= e(app_url('admin/packages.php')) ?>">Manage packages</a>
            <a class="button secondary" href="<?= e(app_url('admin/tables.php')) ?>">Manage tables</a>
            <a class="button secondary" href="<?= e(app_url('admin/extras.php')) ?>">Manage extras</a>
        </div>
    </div>

    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="stat-card" style="padding: 1rem; border: 1px solid #ddd; border-radius: 6px;">
            <small>All Active Reservations</small>
            <h2 style="margin: 0.25rem 0 0;"><?= (int)$stats['active_count'] ?></h2>
        </div>
        <div class="stat-card" style="padding: 1rem; border: 1px solid #ddd; border-radius: 6px;">
            <small>Today's Active Guests</small>
            <h2 style="margin: 0.25rem 0 0;"><?= (int)$stats['today_guests'] ?> pax</h2>
        </div>
        <div class="stat-card" style="padding: 1rem; border: 1px solid #ddd; border-radius: 6px;">
            <small>Simulated Booking Total</small>
            <h2 style="margin: 0.25rem 0 0;"><?= money($stats['simulated_booking_total']) ?></h2>
            <small>Simulated amount only; no real payment was collected.</small><br>
            <small>Excludes cancelled reservations; includes active and no-show simulated payments.</small>
        </div>
    </div>

    <?php if ($error): ?>
        <p class="notice error"><?= e($error) ?></p>
    <?php endif; ?>

    <form class="search-form" method="get" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; margin-bottom: 1.25rem;">
        <label>
            Search ID or Phone
            <input name="search" value="<?= e($search) ?>" placeholder="ID or phone">
        </label>

        <label>
            Status
            <select name="status">
                <option value="">All statuses</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>><?= e(reservation_status_label('active')) ?></option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>><?= e(reservation_status_label('cancelled')) ?></option>
                <option value="no_show" <?= $statusFilter === 'no_show' ? 'selected' : '' ?>><?= e(reservation_status_label('no_show')) ?></option>
            </select>
        </label>

        <label>
            Date
            <select name="date">
                <option value="">All dates</option>
                <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today only</option>
            </select>
        </label>

        <button type="submit">Filter</button>
        <?php if ($search !== '' || $statusFilter !== '' || $dateFilter !== ''): ?>
            <a class="button secondary" href="<?= e(app_url('admin/index.php')) ?>">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Date / Time</th>
                    <th>Table / Pax</th>
                    <th>Package & Extras</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Staff Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $row): ?>
                    <tr>
                        <td>#<?= (int)$row['id'] ?></td>
                        <td>
                            <strong><?= e($row['customer_name']) ?></strong><br>
                            <small><?= e($row['phone']) ?></small>
                        </td>
                        <td>
                            <?= e(reservation_datetime_label($row['reservation_date'], $row['reservation_time'])) ?>
                        </td>
                        <td>
                            <?= e($row['table_number']) ?> (<?= (int)$row['guest_count'] ?> pax)
                        </td>
                        <td>
                            <?= e($row['package_name']) ?>
                            <?php if (!empty($row['extras_list'])): ?>
                                <br><small>+ <?= e($row['extras_list']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= money($row['total_price']) ?></td>
                        <td><?= e(payment_status_label($row['payment_status'])) ?></td>
                        <td><?= e(reservation_status_label($row['reservation_status'])) ?></td>
                        <td>
                            <form method="post" class="inline-actions">
                                <?= csrf_field() ?>
                                <input type="hidden" name="reservation_id" value="<?= (int)$row['id'] ?>">
                                <select name="action" aria-label="Staff action">
                                    <option value="payment_unpaid">Set unpaid</option>
                                    <option value="payment_simulated_paid">Set paid</option>
                                    <?php if ($row['reservation_status'] === 'active' && reservation_datetime_has_passed($row['reservation_date'], $row['reservation_time'])): ?>
                                        <option value="no_show">Mark no-show</option>
                                    <?php endif; ?>
                                </select>
                                <button type="submit">Apply</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reservations): ?>
                    <tr><td colspan="9">No reservations found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
