<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$connection = db();
$error = '';
$editing = null;

if (isset($_GET['edit'])) {
    $find = $connection->prepare('SELECT id, table_number, capacity, active FROM dining_tables WHERE id = ?');
    $find->execute([(int)$_GET['edit']]);
    $editing = $find->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'deactivate') {
            $exists = $connection->prepare('SELECT id FROM dining_tables WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                throw new InvalidArgumentException('Table no longer exists.');
            }
            $connection->prepare('UPDATE dining_tables SET active = 0 WHERE id = ?')->execute([$id]);
            flash('Table deactivated; historical reservations remain intact.', 'success');
            redirect('admin/tables.php');
        }

        $number = trim($_POST['table_number'] ?? '');
        $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($number === '' || strlen($number) > 20 || $capacity === false || $capacity < 1 || $capacity > 8) {
            throw new InvalidArgumentException('Enter a table number and capacity from 1 to 8.');
        }

        if ($id > 0) {
            $exists = $connection->prepare('SELECT id FROM dining_tables WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                throw new InvalidArgumentException('Table no longer exists.');
            }
            $connection->prepare('UPDATE dining_tables SET table_number = ?, capacity = ?, active = ? WHERE id = ?')->execute([$number, $capacity, $active, $id]);
        } else {
            $connection->prepare('INSERT INTO dining_tables (table_number, capacity, active) VALUES (?, ?, ?)')->execute([$number, $capacity, $active]);
        }

        flash('Table saved.', 'success');
        redirect('admin/tables.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            $error = 'A table with that number already exists.';
        } else {
            throw $exception;
        }
    }

    $editing = ['id' => $id, 'table_number' => $number ?? '', 'capacity' => $capacity ?: '', 'active' => $active ?? 1];
}

$tables = $connection->query('SELECT id, table_number, capacity, active FROM dining_tables ORDER BY table_number')->fetchAll();

$pageTitle = 'Manage tables';
require dirname(__DIR__) . '/frontend/layout/header.php';
require __DIR__ . '/views/tables.view.php';
require dirname(__DIR__) . '/frontend/layout/footer.php';
