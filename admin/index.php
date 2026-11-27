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
        $connection->beginTransaction();
        $find = $connection->prepare('SELECT id FROM reservations WHERE id = ? FOR UPDATE');
        $find->execute([$id]);
        if (!$find->fetch()) {
            $connection->rollBack();
            $error = 'Reservation not found.';
        } else {
            $status = $action === 'payment_unpaid' ? 'unpaid' : 'simulated_paid';
            $update = $connection->prepare('UPDATE reservations SET payment_status = ? WHERE id = ?');
            $update->execute([$status, $id]);
            $connection->commit();
            flash('Payment status updated.', 'success');
            redirect('admin/index.php');
        }
    } else {
        $connection->beginTransaction();
        $find = $connection->prepare('SELECT reservation_date, reservation_time, reservation_status FROM reservations WHERE id = ? FOR UPDATE');
        $find->execute([$id]);
        $reservation = $find->fetch();
        if (!$reservation || $reservation['reservation_status'] !== 'active') {
            $connection->rollBack();
            $error = 'Only active reservations can be marked no-show.';
        } elseif (!reservation_datetime_has_passed($reservation['reservation_date'], $reservation['reservation_time'])) {
            $connection->rollBack();
            $error = 'A reservation can be marked no-show only after its scheduled date and time.';
        } else {
            $connection->prepare("UPDATE reservations SET reservation_status = 'no_show' WHERE id = ?")
                ->execute([$id]);
            $connection->prepare('DELETE FROM occupied_slots WHERE reservation_id = ?')->execute([$id]);
            $connection->commit();
            flash('Reservation marked no-show and its slot released.', 'success');
            redirect('admin/index.php');
        }
    }
}

$today = (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
$statsQuery = $connection->prepare("
    SELECT 
        COUNT(CASE WHEN reservation_status = 'active' THEN 1 END) AS active_count,
        COALESCE(SUM(CASE WHEN reservation_date = ? AND reservation_status = 'active' THEN guest_count ELSE 0 END), 0) AS today_guests,
        COALESCE(SUM(CASE WHEN payment_status = 'simulated_paid' AND reservation_status != 'cancelled' THEN total_price ELSE 0 END), 0) AS simulated_booking_total
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
               r.guest_count, COALESCE(r.table_number_snapshot, t.table_number) AS table_number,
               COALESCE(r.package_name_snapshot, mp.name) AS package_name,
               (SELECT GROUP_CONCAT(COALESCE(re.extra_name_snapshot, e.name)
                                    ORDER BY COALESCE(re.extra_name_snapshot, e.name) SEPARATOR ", ")
                FROM reservation_extras re
                JOIN extras e ON e.id = re.extra_id
                WHERE re.reservation_id = r.id) AS extras_list,
               r.total_price, r.payment_status, r.reservation_status
        FROM reservations r 
        JOIN users u ON u.id = r.user_id 
        JOIN dining_tables t ON t.id = r.table_id
        JOIN menu_packages mp ON mp.id = r.package_id';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY r.reservation_date DESC, r.reservation_time DESC';

$query = $connection->prepare($sql);
$query->execute($params);
$reservations = $query->fetchAll();

$pageTitle = 'Admin reservations';
require dirname(__DIR__) . '/frontend/layout/header.php';
require __DIR__ . '/views/index.view.php';
require dirname(__DIR__) . '/frontend/layout/footer.php';
