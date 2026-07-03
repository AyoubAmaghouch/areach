<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$orderId = $orderId !== false && $orderId > 0 ? $orderId : 0;
$token = (string) ($_POST['token'] ?? '');
$sessionToken = (string) ($_SESSION['checkout_confirm_token'] ?? '');
$sessionOrderId = (int) ($_SESSION['checkout_order_id'] ?? 0);

if ($orderId <= 0 || $sessionOrderId !== $orderId || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$stmt = $pdo->prepare('SELECT id_order, COALESCE(NULLIF(status, ""), "En attente") AS status FROM orders WHERE id_order = ? LIMIT 1');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$status = (string) ($order['status'] ?? 'En attente');
if ($status === 'Annulée') {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Order is not eligible for confirmation']);
    exit;
}

if ($status === 'En attente') {
    $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id_order = ?');
    $stmt->execute(['Confirmée', $orderId]);
    $status = 'Confirmée';
} elseif (!in_array($status, ['Confirmée', 'En préparation', 'Expédiée', 'Livrée'], true)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Order is not eligible for confirmation']);
    exit;
}

$_SESSION['order_id'] = $orderId;
$_SESSION['checkout_order_id'] = $orderId;

echo json_encode([
    'success' => true,
    'order_id' => $orderId,
    'status' => $status,
]);
