<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/includes/db.php';
$phone = $argv[1] ?? '';
if ($phone === '') {
    fwrite(STDERR, "Usage: php scripts/promote_admin.php <registered-phone-number>\n");
    exit(1);
}
$update = db()->prepare("UPDATE users SET role = 'admin' WHERE phone = ?");
$update->execute([$phone]);
if ($update->rowCount() !== 1) {
    fwrite(STDERR, "No matching user found, or the account was already an admin.\n");
    exit(1);
}
fwrite(STDOUT, "Admin role granted to the matching registered user.\n");
