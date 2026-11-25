<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
if (current_user()) {
    redirect('index.php');
}
$error = '';
$name = '';
$phone = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $nameInput = $_POST['name'] ?? '';
    $phoneInput = $_POST['phone'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $name = is_string($nameInput) ? trim($nameInput) : '';
    $phone = is_string($phoneInput) ? trim($phoneInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';
    if ($name === '') {
        $error = 'Enter your name.';
    } elseif (strlen($name) > 100) {
        $error = 'Your name must be 100 characters or fewer.';
    } elseif ($phone === '') {
        $error = 'Enter a phone number you can use to log in.';
    } elseif (strlen($phone) > 30) {
        $error = 'Your phone number must be 30 characters or fewer.';
    } elseif (strlen($password) < 8) {
        $error = 'Choose a password with at least 8 characters.';
    } else {
        try {
            $insert = db()->prepare("INSERT INTO users (name, phone, password_hash, role) VALUES (?, ?, ?, 'customer')");
            $insert->execute([$name, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            flash('Account created. Please log in.', 'success');
            redirect('registration/login.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'That phone number is already registered.';
            } else {
                throw $exception;
            }
        }
    }
}
$pageTitle = 'Sign up';
require dirname(__DIR__) . '/frontend/layout/header.php';
?>
<section class="panel narrow">
    <h1>Create an account</h1>
    <p class="muted">Use a phone number you can access. You’ll use the same number to log in.</p>
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Full name <input name="name" maxlength="100" autocomplete="name" required value="<?= e($name) ?>"></label>
        <label>Phone number <input type="tel" name="phone" maxlength="30" autocomplete="tel" inputmode="tel" required value="<?= e($phone) ?>"></label>
        <label>Password <input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
        <p class="muted">Use at least 8 characters. Your password is not shown when the form is reloaded.</p>
        <button type="submit">Sign up</button>
    </form>
    <p class="muted">Already have an account? <a href="<?= e(app_url('registration/login.php')) ?>">Log in</a>.</p>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
