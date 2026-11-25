<section class="panel">
    <h1>Side extras</h1>
    <p><a href="<?= e(app_url('admin/index.php')) ?>">← Reservations</a></p>

    <?php if ($error): ?>
        <p class="notice error"><?= e($error) ?></p>
    <?php endif; ?>

    <form class="edit-form" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <h2><?= $editing ? 'Edit extra' : 'Add extra' ?></h2>

        <label>
            Item name
            <input name="name" maxlength="100" required value="<?= e($editing['name'] ?? '') ?>">
        </label>

        <label>
            Price per guest (₱)
            <input type="number" name="price_per_guest" min="0" step="0.01" required value="<?= e($editing['price_per_guest'] ?? '') ?>">
        </label>

        <label class="check-label">
            <input type="checkbox" name="active" value="1" <?= !isset($editing['active']) || (int)$editing['active'] === 1 ? 'checked' : '' ?>>
            Active for new reservations
        </label>

        <button type="submit">Save extra</button>
        <?php if ($editing): ?>
            <a href="<?= e(app_url('admin/extras.php')) ?>">Cancel edit</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Price per guest</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($extras as $extra): ?>
                    <tr>
                        <td><?= e($extra['name']) ?></td>
                        <td><?= money($extra['price_per_guest']) ?></td>
                        <td><?= (int)$extra['active'] ? 'Active' : 'Inactive' ?></td>
                        <td>
                            <a href="<?= e(app_url('admin/extras.php?edit=' . (int)$extra['id'])) ?>">Edit</a>
                            <?php if ((int)$extra['active']): ?>
                                <form class="inline-form" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$extra['id'] ?>">
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
