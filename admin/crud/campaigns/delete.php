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
        echo json_encode(['success' => false, 'message' => 'Campagne introuvable.']);
        exit;
    }
    header("Location: ../../campaigns.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT image FROM campaigns WHERE id_campaign = ?");
    $stmt->execute([$id]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Campagne introuvable.']);
            exit;
        }
        $_SESSION['campaign_flash'] = ['type' => 'error', 'message' => 'Campagne introuvable.'];
        header("Location: ../../campaigns.php");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id_campaign = ?");
    $stmt->execute([$id]);

    if (!empty($campaign['image'])) {
        $path = __DIR__ . '/../../../assets/uploads/campaigns/' . basename((string) $campaign['image']);
        if (is_file($path)) {
            unlink($path);
        }
    }

    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => 'Campagne supprimée avec succès.']);
        exit;
    }
    $_SESSION['campaign_flash'] = ['type' => 'success', 'message' => 'Campagne supprimee avec succes.'];
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
        exit;
    }
    $_SESSION['campaign_flash'] = ['type' => 'error', 'message' => 'Erreur lors de la suppression.'];
}

header("Location: ../../campaigns.php");
exit;
