<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$connection = db();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';
    if (!$id || !in_array($action, ['payment_unpaid', 'payment_simulated_paid', 'no_show'], true)) {
        $error = 'Invalid reservation action.';
    } elseif (str_starts_with($action, 'payment_')) {
        $exists = $connection->prepare('SELECT id FROM reservations WHERE id = ?');
        $exists->execute([$id]);
        if (!$exists->fetchColumn()) {
            $error = 'Reservation not found.';
        } else {
            $status = $action === 'payment_unpaid' ? 'unpaid' : 'simulated_paid';
            $update = $connection->prepare('UPDATE reservations SET payment_status = ? WHERE id = ?');
            $update->execute([$status, $id]);
            flash('Payment status updated.', 'success');
            redirect('admin/index.php');
        }
    } else {
        $connection->beginTransaction();
        $update = $connection->prepare("UPDATE reservations SET reservation_status = 'no_show' WHERE id = ? AND reservation_status = 'active'");
        $update->execute([$id]);
        if ($update->rowCount() > 0) {
            $connection->prepare('DELETE FROM occupied_slots WHERE reservation_id = ?')->execute([$id]);
            $connection->commit();
            flash('Reservation marked no-show and its slot released.', 'success');
        } else {
            $connection->rollBack();
            $error = 'Only active reservations can be marked no-show.';
        }
    }
}
$search = trim($_GET['search'] ?? '');
$sql = 'SELECT r.id, u.name AS customer_name, u.phone, r.reservation_date, r.reservation_time,
               t.table_number, r.total_price, r.payment_status, r.reservation_status
        FROM reservations r JOIN users u ON u.id = r.user_id JOIN dining_tables t ON t.id = r.table_id';
$params = [];
if ($search !== '') {
    if (ctype_digit($search)) {
        $sql .= ' WHERE r.id = ? OR u.phone LIKE ?';
        $params = [(int)$search, '%' . $search . '%'];
    } else {
        $sql .= ' WHERE u.phone LIKE ?';
        $params = ['%' . $search . '%'];
    }
}
$sql .= ' ORDER BY r.reservation_date DESC, r.reservation_time DESC';
$query = $connection->prepare($sql);
$query->execute($params);
$reservations = $query->fetchAll();
$pageTitle = 'Admin reservations';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel">
    <div class="page-heading"><div><h1>Reservations</h1><p>Staff tools for reservations, packages, and dining tables.</p></div>
        <div class="actions"><a class="button" href="<?= e(app_url('admin/packages.php')) ?>">Manage packages</a><a class="button secondary" href="<?= e(app_url('admin/tables.php')) ?>">Manage tables</a></div>
    </div>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <form class="search-form" method="get">
        <label>Reservation ID or customer phone <input name="search" value="<?= e($search) ?>" placeholder="Search ID or phone"></label>
        <button type="submit">Search</button>
        <?php if ($search !== ''): ?><a href="<?= e(app_url('admin/index.php')) ?>">Clear</a><?php endif; ?>
    </form>
    <div class="table-wrap"><table>
        <thead><tr><th>ID</th><th>Customer</th><th>Phone</th><th>Date / time</th><th>Table</th><th>Total</th><th>Payment</th><th>Reservation</th><th>Staff actions</th></tr></thead>
        <tbody>
        <?php foreach ($reservations as $row): ?>
            <tr>
                <td>#<?= (int)$row['id'] ?></td><td><?= e($row['customer_name']) ?></td><td><?= e($row['phone']) ?></td>
                <td><?= e($row['reservation_date']) ?> <?= e(substr((string)$row['reservation_time'], 0, 5)) ?></td>
                <td><?= e($row['table_number']) ?></td><td><?= money($row['total_price']) ?></td>
                <td><?= e($row['payment_status']) ?></td><td><?= e($row['reservation_status']) ?></td>
                <td><form method="post" class="inline-actions"><?= csrf_field() ?><input type="hidden" name="reservation_id" value="<?= (int)$row['id'] ?>">
                    <select name="action" aria-label="Staff action">
                        <option value="payment_unpaid">Set unpaid</option><option value="payment_simulated_paid">Set simulated paid</option>
                        <?php if ($row['reservation_status'] === 'active'): ?><option value="no_show">Mark no-show</option><?php endif; ?>
                    </select><button type="submit">Apply</button>
                </form></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$reservations): ?><tr><td colspan="9">No reservations found.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
