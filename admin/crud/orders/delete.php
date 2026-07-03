<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../orders.php');
    exit;
}

$idOrder = filter_input(INPUT_POST, 'id_order', FILTER_VALIDATE_INT);
if ($idOrder === false || $idOrder === null || $idOrder <= 0) {
    header('Location: ../../orders.php?error=1');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtItems = $pdo->prepare('DELETE FROM order_items WHERE id_order = ?');
    $stmtItems->execute([$idOrder]);

    $stmtOrder = $pdo->prepare('DELETE FROM orders WHERE id_order = ?');
    $stmtOrder->execute([$idOrder]);

    if ($stmtOrder->rowCount() === 0) {
        $pdo->rollBack();
        header('Location: ../../orders.php?error=1');
        exit;
    }

    $pdo->commit();
    header('Location: ../../orders.php?deleted=1');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: ../../orders.php?error=1');
    exit;
}
