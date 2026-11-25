<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
if (current_user()) {
    redirect('index.php');
}
$error = '';
$phone = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $phoneInput = $_POST['phone'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $phone = is_string($phoneInput) ? trim($phoneInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';
    $query = db()->prepare('SELECT id, name, phone, password_hash, role FROM users WHERE phone = ?');
    $query->execute([$phone]);
    $user = $query->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
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
    <p class="muted">Enter the phone number and password you used when you signed up.</p>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Phone number <input type="tel" name="phone" autocomplete="tel" inputmode="tel" required value="<?= e($phone) ?>"></label>
        <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
        <button type="submit">Log in</button>
    </form>
    <p class="muted">New here? <a href="<?= e(app_url('registration/signup.php')) ?>">Create an account</a>.</p>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
