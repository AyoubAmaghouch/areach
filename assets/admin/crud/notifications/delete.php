<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($id < 1) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Notification introuvable.']);
        exit;
    }
    header("Location: ../../notifications.php");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id_notification = ?");
    $stmt->execute([$id]);
    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => 'Notification supprimée avec succès.']);
        exit;
    }
    $_SESSION['notification_flash'] = ['type' => 'success', 'message' => 'Notification supprimee avec succes.'];
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
        exit;
    }
    $_SESSION['notification_flash'] = ['type' => 'error', 'message' => 'Erreur lors de la suppression.'];
}

header("Location: ../../notifications.php");
exit;
