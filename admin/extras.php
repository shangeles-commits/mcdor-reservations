<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$connection = db();
$error = '';
$editing = null;

if (isset($_GET['edit'])) {
    $find = $connection->prepare('SELECT id, name, price_per_guest, active FROM extras WHERE id = ?');
    $find->execute([(int)$_GET['edit']]);
    $editing = $find->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'deactivate') {
            $exists = $connection->prepare('SELECT id FROM extras WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                throw new InvalidArgumentException('Extra item no longer exists.');
            }
            $connection->prepare('UPDATE extras SET active = 0 WHERE id = ?')->execute([$id]);
            flash('Extra deactivated; historical receipts remain unchanged.', 'success');
            redirect('admin/extras.php');
        }

        $name = trim($_POST['name'] ?? '');
        $price = filter_var($_POST['price_per_guest'] ?? null, FILTER_VALIDATE_FLOAT);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name === '' || strlen($name) > 100 || $price === false || $price < 0) {
            throw new InvalidArgumentException('Enter an extra item name and non-negative price.');
        }

        if ($id > 0) {
            $exists = $connection->prepare('SELECT id FROM extras WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                throw new InvalidArgumentException('Extra item no longer exists.');
            }
            $connection->prepare('UPDATE extras SET name = ?, price_per_guest = ?, active = ? WHERE id = ?')->execute([$name, $price, $active, $id]);
        } else {
            $connection->prepare('INSERT INTO extras (name, price_per_guest, active) VALUES (?, ?, ?)')->execute([$name, $price, $active]);
        }

        flash('Extra saved.', 'success');
        redirect('admin/extras.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            $error = 'An extra item with that name already exists.';
        } else {
            throw $exception;
        }
    }

    $editing = ['id' => $id, 'name' => $name ?? '', 'price_per_guest' => $price ?: '', 'active' => $active ?? 1];
}

$extras = $connection->query('SELECT id, name, price_per_guest, active FROM extras ORDER BY name')->fetchAll();

$pageTitle = 'Manage extras';
require dirname(__DIR__) . '/frontend/layout/header.php';
require __DIR__ . '/views/extras.view.php';
require dirname(__DIR__) . '/frontend/layout/footer.php';
