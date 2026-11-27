<?php

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    // Feature pages are one folder below the project root, so remove that folder from links.
    $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('~/(registration|reservation|menu|review-payment|admin)$~', $directory)) {
        $directory = substr($directory, 0, (int)strrpos($directory, '/'));
    }
    $base = rtrim($directory, '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path)
{
    header('Location: ' . app_url($path));
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    if (!current_user()) {
        redirect('registration/login.php');
    }
    return current_user();
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Admin access required.');
    }
    return $user;
}

function csrf_token(): string
{
    // Reuse a random session token so every form and its POST can be matched.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function require_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    // Reject POST requests that did not come from a form rendered for this session.
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Your form session expired. Go back, reload the page, and try again.');
    }
}

function flash(string $message, string $type = 'info'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function allowed_slots(): array
{
    return ['11:00', '13:00', '15:00', '17:00', '19:00'];
}

function reservation_slots_label(): string
{
    $slotLabels = array_map(
        static fn(string $slot): string => (new DateTimeImmutable($slot))->format('g A'),
        allowed_slots()
    );
    $lastSlot = array_pop($slotLabels);
    return 'Reservation slots: ' . implode(', ', $slotLabels) . ', and ' . $lastSlot . ', Manila time.';
}

function reservation_datetime_label(string $date, string $time): string
{
    return (new DateTimeImmutable($date . ' ' . $time, new DateTimeZone('Asia/Manila')))
        ->format('F j, Y \a\t g:i A');
}

function reservation_datetime_has_passed(string $date, string $time): bool
{
    $reservationTimestamp = strtotime($date . ' ' . $time);
    return $reservationTimestamp !== false && $reservationTimestamp < time();
}

function reservation_status_label(string $status): string
{
    $labels = [
        'active' => 'Active',
        'cancelled' => 'Cancelled',
        'no_show' => 'No-show',
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function payment_status_label(string $status): string
{
    $labels = [
        'unpaid' => 'Unpaid',
        'simulated_paid' => 'Simulated paid',
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function payment_method_label(string $method): string
{
    $labels = [
        'pay_at_restaurant' => 'Pay at the restaurant',
        'demo_simulated' => 'Demo payment',
    ];
    return $labels[$method] ?? ucfirst(str_replace('_', ' ', $method));
}

function valid_reservation_datetime(string $date, string $time): bool
{
    if (!in_array($time, allowed_slots(), true)) {
        return false;
    }
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('Asia/Manila'));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$day || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) || $day->format('Y-m-d') !== $date) {
        return false;
    }

    // Apply the booking window and two-hour lead time in the restaurant's timezone.
    $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));
    if ($day < $today || $day > $today->modify('+30 days')) {
        return false;
    }
    $slot = new DateTimeImmutable($date . ' ' . $time, new DateTimeZone('Asia/Manila'));
    return $slot >= (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->modify('+2 hours');
}

/**
 * Returns the smallest suitable free active table, or null when no table can fit.
 * The optional reservation ID lets an existing booking retain/reclaim its own slot.
 */
function checkAvailability(PDO $connection, string $date, string $time, int $guestCount, ?int $excludeReservationId = null): ?array
{
    if (!valid_reservation_datetime($date, $time) || $guestCount < 1 || $guestCount > 8) {
        return null;
    }
    $sql = 'SELECT t.id, t.table_number, t.capacity
            FROM dining_tables t
            WHERE t.active = 1 AND t.capacity >= ?
              AND NOT EXISTS (
                  SELECT 1 FROM occupied_slots s
                  WHERE s.table_id = t.id AND s.reservation_date = ? AND s.reservation_time = ?';
    if ($excludeReservationId !== null) {
        $sql .= ' AND s.reservation_id <> ?';
    }
    $sql .= ') ORDER BY t.capacity, t.table_number LIMIT 1';
    $statement = $connection->prepare($sql);
    $parameters = [$guestCount, $date, $time];
    if ($excludeReservationId !== null) {
        $parameters[] = $excludeReservationId;
    }
    $statement->execute($parameters);
    $table = $statement->fetch();
    return $table ?: null;
}

/**
 * Reads current active menu prices and returns the package, extras, and total.
 * Checkout stores these database prices as snapshots on the reservation.
 * Returns ['package' => row, 'extras' => rows, 'total' => float].
 */
function calculateTotal(PDO $connection, int $packageId, array $extraIds, int $guestCount): array
{
    if ($guestCount < 1 || $guestCount > 8) {
        throw new InvalidArgumentException('Guest count must be between 1 and 8.');
    }
    $packageQuery = $connection->prepare('SELECT id, name, description, price_per_guest FROM menu_packages WHERE id = ? AND active = 1');
    $packageQuery->execute([$packageId]);
    $package = $packageQuery->fetch();
    if (!$package) {
        throw new InvalidArgumentException('Choose an active menu package.');
    }

    // Validate submitted IDs, then load prices from the database instead of trusting the browser.
    $cleanExtraIds = [];
    foreach ($extraIds as $extraId) {
        if (!is_scalar($extraId) || !preg_match('/^\d+$/D', (string)$extraId) || (int)$extraId < 1) {
            throw new InvalidArgumentException('One or more selected sides are invalid.');
        }
        $cleanExtraIds[] = (int)$extraId;
    }
    $extraIds = array_values(array_unique($cleanExtraIds));
    $extras = [];
    if ($extraIds) {
        $marks = implode(',', array_fill(0, count($extraIds), '?'));
        $extraQuery = $connection->prepare("SELECT id, name, price_per_guest FROM extras WHERE active = 1 AND id IN ($marks) ORDER BY name");
        $extraQuery->execute($extraIds);
        $extras = $extraQuery->fetchAll();
        if (count($extras) !== count($extraIds)) {
            throw new InvalidArgumentException('One or more selected sides are no longer available.');
        }
    }
    $perGuest = (float)$package['price_per_guest'];
    foreach ($extras as $extra) {
        $perGuest += (float)$extra['price_per_guest'];
    }
    return ['package' => $package, 'extras' => $extras, 'total' => round($perGuest * $guestCount, 2)];
}

function money(mixed $amount): string
{
    return '₱' . number_format((float)$amount, 2);
}

function find_owned_reservation(PDO $connection, int $reservationId, int $userId): ?array
{
    // Match both IDs so a customer cannot look up another account's reservation.
    $query = $connection->prepare(
        'SELECT r.*, COALESCE(r.table_number_snapshot, t.table_number) AS table_number,
                COALESCE(r.package_name_snapshot, p.name) AS package_name, u.name AS customer_name
         FROM reservations r
         JOIN dining_tables t ON t.id = r.table_id
         JOIN menu_packages p ON p.id = r.package_id
         JOIN users u ON u.id = r.user_id
         WHERE r.id = ? AND r.user_id = ?'
    );
    $query->execute([$reservationId, $userId]);
    $row = $query->fetch();
    if (!$row) {
        return null;
    }
    $extras = $connection->prepare(
        'SELECT COALESCE(re.extra_name_snapshot, e.name) AS name, re.extra_price_snapshot
         FROM reservation_extras re
        JOIN extras e ON e.id = re.extra_id WHERE re.reservation_id = ? ORDER BY re.extra_name_snapshot'
    );
    $extras->execute([$reservationId]);
    $row['extras'] = $extras->fetchAll();
    return $row;
}
