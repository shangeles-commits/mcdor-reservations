<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
if (current_user()) {
    redirect('index.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $query = db()->prepare('SELECT id, name, phone, password_hash, role FROM users WHERE phone = ?');
    $query->execute([trim($_POST['phone'] ?? '')]);
    $user = $query->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        redirect('index.php');
    }
    $error = 'Phone number or password was not recognized.';
}
$pageTitle = 'Log in';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel narrow">
    <h1>Log in</h1>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Phone number <input name="phone" autocomplete="tel" required value="<?= e($_POST['phone'] ?? '') ?>"></label>
        <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
        <button type="submit">Log in</button>
    </form>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
