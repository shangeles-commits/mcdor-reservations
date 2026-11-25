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
            redirect('admin/index.php');
        } else {
            $connection->rollBack();
            $error = 'Only active reservations can be marked no-show.';
        }
    }
}

$today = date('Y-m-d');
$statsQuery = $connection->prepare("
    SELECT 
        COUNT(CASE WHEN reservation_status = 'active' THEN 1 END) AS active_count,
        COALESCE(SUM(CASE WHEN reservation_date = ? AND reservation_status = 'active' THEN guest_count ELSE 0 END), 0) AS today_guests,
        COALESCE(SUM(CASE WHEN payment_status = 'simulated_paid' AND reservation_status != 'cancelled' THEN total_price ELSE 0 END), 0) AS total_revenue
    FROM reservations
");
$statsQuery->execute([$today]);
$stats = $statsQuery->fetch();

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    if (ctype_digit($search)) {
        $where[] = '(r.id = ? OR u.phone LIKE ?)';
        $params[] = (int)$search;
        $params[] = '%' . $search . '%';
    } else {
        $where[] = 'u.phone LIKE ?';
        $params[] = '%' . $search . '%';
    }
}

if (in_array($statusFilter, ['active', 'cancelled', 'no_show'], true)) {
    $where[] = 'r.reservation_status = ?';
    $params[] = $statusFilter;
}

if ($dateFilter === 'today') {
    $where[] = 'r.reservation_date = ?';
    $params[] = $today;
}

$sql = 'SELECT r.id, u.name AS customer_name, u.phone, r.reservation_date, r.reservation_time,
               r.guest_count, t.table_number, mp.name AS package_name,
               GROUP_CONCAT(e.name SEPARATOR ", ") AS extras_list,
               r.total_price, r.payment_status, r.reservation_status
        FROM reservations r 
        JOIN users u ON u.id = r.user_id 
        JOIN dining_tables t ON t.id = r.table_id
        JOIN menu_packages mp ON mp.id = r.package_id
        LEFT JOIN reservation_extras re ON re.reservation_id = r.id
        LEFT JOIN extras e ON e.id = re.extra_id';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' GROUP BY r.id ORDER BY r.reservation_date DESC, r.reservation_time DESC';

$query = $connection->prepare($sql);
$query->execute($params);
$reservations = $query->fetchAll();

$pageTitle = 'Admin reservations';
require dirname(__DIR__) . '/frontend/layout/header.php';
require __DIR__ . '/views/index.view.php';
require dirname(__DIR__) . '/frontend/layout/footer.php';
