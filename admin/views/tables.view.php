<section class="panel">
    <h1>Dining tables</h1>
    <p><a href="<?= e(app_url('admin/index.php')) ?>">← Reservations</a></p>

    <?php if ($error): ?>
        <p class="notice error"><?= e($error) ?></p>
    <?php endif; ?>

    <form class="edit-form" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <h2><?= $editing ? 'Edit table' : 'Add table' ?></h2>

        <label>
            Table number
            <input name="table_number" maxlength="20" required value="<?= e($editing['table_number'] ?? '') ?>">
        </label>

        <label>
            Capacity
            <input type="number" name="capacity" min="1" max="8" required value="<?= e($editing['capacity'] ?? '') ?>">
        </label>

        <label class="check-label">
            <input type="checkbox" name="active" value="1" <?= !isset($editing['active']) || (int)$editing['active'] === 1 ? 'checked' : '' ?>>
            Active for bookings
        </label>

        <button type="submit">Save table</button>
        <?php if ($editing): ?>
            <a href="<?= e(app_url('admin/tables.php')) ?>">Cancel edit</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $table): ?>
                    <tr>
                        <td><?= e($table['table_number']) ?></td>
                        <td><?= (int)$table['capacity'] ?></td>
                        <td><?= (int)$table['active'] ? 'Active' : 'Inactive' ?></td>
                        <td>
                            <a href="<?= e(app_url('admin/tables.php?edit=' . (int)$table['id'])) ?>">Edit</a>
                            <?php if ((int)$table['active']): ?>
                                <form class="inline-form" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$table['id'] ?>">
                                    <input type="hidden" name="action" value="deactivate">
                                    <button type="submit" class="link-button">Deactivate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
