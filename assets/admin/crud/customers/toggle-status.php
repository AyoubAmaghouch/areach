<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../../customers.php");
    exit;
}

$id = (int) $_GET['id'];

// Récupérer le statut actuel
$stmt = $pdo->prepare("
SELECT status
FROM customers
WHERE id_customer = ?
");

$stmt->execute([$id]);

$customer = $stmt->fetch();

if (!$customer) {
    die("Client introuvable.");
}

// Inverser le statut
$newStatus = $customer['status'] ? 0 : 1;

$stmt = $pdo->prepare("
UPDATE customers
SET status = ?
WHERE id_customer = ?
");

$stmt->execute([$newStatus, $id]);

header("Location: ../../customers.php");
exit;