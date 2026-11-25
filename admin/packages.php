<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$connection = db();
$error = '';
$editing = null;

if (isset($_GET['edit'])) {
    $find = $connection->prepare('SELECT id, name, description, price_per_guest, active FROM menu_packages WHERE id = ?');
    $find->execute([(int)$_GET['edit']]);
    $editing = $find->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'deactivate') {
            $exists = $connection->prepare('SELECT id FROM menu_packages WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                throw new InvalidArgumentException('Package no longer exists.');
            }
            $connection->prepare('UPDATE menu_packages SET active = 0 WHERE id = ?')->execute([$id]);
            flash('Package deactivated; existing receipts remain unchanged.', 'success');
            redirect('admin/packages.php');
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = filter_var($_POST['price_per_guest'] ?? null, FILTER_VALIDATE_FLOAT);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name === '' || strlen($name) > 120 || $description === '' || strlen($description) > 500 || $price === false || $price < 0) {
            throw new InvalidArgumentException('Enter a name, description, and non-negative price.');
        }

        if ($id > 0) {
            $exists = $connection->prepare('SELECT id FROM menu_packages WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                throw new InvalidArgumentException('Package no longer exists.');
            }
            $connection->prepare('UPDATE menu_packages SET name = ?, description = ?, price_per_guest = ?, active = ? WHERE id = ?')->execute([$name, $description, $price, $active, $id]);
        } else {
            $connection->prepare('INSERT INTO menu_packages (name, description, price_per_guest, active) VALUES (?, ?, ?, ?)')->execute([$name, $description, $price, $active]);
        }

        flash('Package saved.', 'success');
        redirect('admin/packages.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            $error = 'A package with that name already exists.';
        } else {
            throw $exception;
        }
    }

    $editing = ['id' => $id, 'name' => $name ?? '', 'description' => $description ?? '', 'price_per_guest' => $price ?: '', 'active' => $active ?? 1];
}

$packages = $connection->query('SELECT id, name, description, price_per_guest, active FROM menu_packages ORDER BY name')->fetchAll();

$pageTitle = 'Manage packages';
require dirname(__DIR__) . '/frontend/layout/header.php';
require __DIR__ . '/views/packages.view.php';
require dirname(__DIR__) . '/frontend/layout/footer.php';
