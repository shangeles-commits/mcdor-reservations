<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use the logout form to end your session.');
}
require_csrf();
$_SESSION = [];
session_regenerate_id(true);
flash('You are logged out.', 'success');
redirect('index.php');
