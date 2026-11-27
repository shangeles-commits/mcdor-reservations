<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use the reservation form to cancel a booking.');
}
require_csrf();
$id = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid reservation ID.');
}
$connection = db();
// The transaction saves the cancelled status and frees its slot together.
$connection->beginTransaction();
// The reservation row is locked while it is being changed.
$find = $connection->prepare('SELECT reservation_status FROM reservations WHERE id = ? AND user_id = ? FOR UPDATE');
$find->execute([$id, $user['id']]);
$reservation = $find->fetch();
if ($reservation && $reservation['reservation_status'] === 'active') {
    $connection->prepare("UPDATE reservations SET reservation_status = 'cancelled' WHERE id = ?")->execute([$id]);
    $connection->prepare('DELETE FROM occupied_slots WHERE reservation_id = ?')->execute([$id]);
    $connection->commit();
    $_SESSION['reservation_lookup_id'] = $id;
    flash('Reservation cancelled. Its time slot is now available.', 'success');
} else {
    $connection->rollBack();
    flash('Reservation not found or it is no longer active.', 'error');
}
redirect('reservation/my_reservation.php');
