<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

function redirectNotification(string $message, string $type = 'success'): never
{
    $_SESSION['notification_flash'] = ['message' => $message, 'type' => $type];
    header("Location: ../../notifications.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $id = (int) ($_POST['id_notification'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $type = trim((string) ($_POST['type'] ?? 'info'));
    $isRead = (int) ($_POST['is_read'] ?? 0);
    $isRead = in_array($isRead, [0, 1], true) ? $isRead : 0;

    if ($title === '') {
        redirectNotification('Le titre est obligatoire.', 'error');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE notifications
                SET title = ?, message = ?, type = ?, is_read = ?
                WHERE id_notification = ?
            ");
            $stmt->execute([$title, $message, $type, $isRead, $id]);
            redirectNotification('Notification modifiee avec succes.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO notifications (title, message, type, is_read)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$title, $message, $type, $isRead]);
        redirectNotification('Notification enregistree avec succes.');
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        redirectNotification("La notification n'a pas pu etre enregistree.", 'error');
    }
}

$id = (int) ($_GET['id'] ?? 0);

if ($id < 1) {
    header("Location: ../../notifications.php");
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id_notification = ?");
    $stmt->execute([$id]);
    redirectNotification('Notification marquee comme lue.');
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    redirectNotification("La notification n'a pas pu etre modifiee.", 'error');
}
