<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
if (current_user()) {
    redirect('index.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name === '' || strlen($name) > 100 || $phone === '' || strlen($phone) > 30 || strlen($password) < 8) {
        $error = 'Enter a name and phone number; password must be at least 8 characters.';
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
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Name <input name="name" maxlength="100" required value="<?= e($_POST['name'] ?? '') ?>"></label>
        <label>Phone number <input name="phone" maxlength="30" autocomplete="tel" required value="<?= e($_POST['phone'] ?? '') ?>"></label>
        <label>Password <input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
        <button type="submit">Sign up</button>
    </form>
</section>
<?php require dirname(__DIR__) . '/frontend/layout/footer.php'; ?>
