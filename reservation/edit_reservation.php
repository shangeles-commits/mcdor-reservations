<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_login();
$id = filter_var($_GET['id'] ?? $_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT);
$reservation = $id ? find_owned_reservation(db(), $id, (int)$user['id']) : null;
if (!$reservation) {
    http_response_code(404);
    exit('Reservation not found.');
}
if ($reservation['reservation_status'] !== 'active') {
    flash('Only active reservations can be changed.', 'error');
    redirect('reservation/my_reservation.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $date = trim($_POST['reservation_date'] ?? '');
    $time = trim($_POST['reservation_time'] ?? '');
    $connection = db();
    try {
        // Release and claim slots inside one transaction so a failed move keeps the old slot.
        $connection->beginTransaction();
        $owned = $connection->prepare("SELECT id, guest_count FROM reservations WHERE id = ? AND user_id = ? AND reservation_status = 'active'");
        $owned->execute([$id, $user['id']]);
        $current = $owned->fetch();
        if (!$current) {
            throw new DomainException('Reservation is no longer active.');
        }
        if (!valid_reservation_datetime($date, $time)) {
            throw new DomainException('Choose a valid slot at least two hours ahead and within 30 days.');
        }
        $connection->prepare('DELETE FROM occupied_slots WHERE reservation_id = ?')->execute([$id]);
        $table = checkAvailability($connection, $date, $time, (int)$current['guest_count']);
        if (!$table) {
            throw new DomainException('No table is available for that time. Your original booking is unchanged.');
        }
        $connection->prepare('UPDATE reservations SET table_id = ?, reservation_date = ?, reservation_time = ? WHERE id = ?')
            ->execute([$table['id'], $date, $time, $id]);
        $connection->prepare('INSERT INTO occupied_slots (reservation_id, table_id, reservation_date, reservation_time) VALUES (?, ?, ?, ?)')
            ->execute([$id, $table['id'], $date, $time]);
        $connection->commit();
        $_SESSION['reservation_lookup_id'] = $id;
        flash('Reservation time updated.', 'success');
        redirect('reservation/my_reservation.php');
    } catch (DomainException $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        if ($exception->getCode() === '23000') {
            $error = 'That slot was booked moments ago. Your original booking is unchanged.';
        } else {
            throw $exception;
        }
    }
}
$today = (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
$maxDate = (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->modify('+30 days')->format('Y-m-d');
$pageTitle = 'Change reservation time';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel narrow">
    <h1>Change reservation time</h1>
    <p>Reservation #<?= (int)$reservation['id'] ?> · <?= (int)$reservation['guest_count'] ?> guest(s)</p>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="reservation_id" value="<?= (int)$reservation['id'] ?>">
        <label>Date <input type="date" name="reservation_date" min="<?= e($today) ?>" max="<?= e($maxDate) ?>" required value="<?= e($_POST['reservation_date'] ?? $reservation['reservation_date']) ?>"></label>
        <label>Time
            <select name="reservation_time" required>
                <?php foreach (allowed_slots() as $slot): ?><option value="<?= e($slot) ?>" <?= substr((string)$reservation['reservation_time'], 0, 5) === $slot ? 'selected' : '' ?>><?= e($slot) ?></option><?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Save new time</button>
    </form>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
