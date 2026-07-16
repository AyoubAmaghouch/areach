<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

function redirectCampaign(string $message, string $type = 'success'): never
{
    $_SESSION['campaign_flash'] = ['message' => $message, 'type' => $type];
    header("Location: ../../campaigns.php");
    exit;
}

function campaignImageUpload(string $current = ''): string
{
    if (empty($_FILES['image']['name'])) {
        return $current;
    }

    if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException("L'image n'a pas pu etre telechargee.");
    }

    $tmp = $_FILES['image']['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp) || getimagesize($tmp) === false) {
        throw new RuntimeException("Le fichier telecharge n'est pas une image valide.");
    }

    $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('Format image non supporte.');
    }

    $directory = __DIR__ . '/../../../assets/uploads/campaigns';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Le dossier des campagnes est indisponible.');
    }

    $name = time() . '_campaign_' . bin2hex(random_bytes(8)) . '.' . $extension;
    if (!move_uploaded_file($tmp, $directory . '/' . $name)) {
        throw new RuntimeException("L'image n'a pas pu etre enregistree.");
    }

    if ($current !== '') {
        $old = $directory . '/' . basename($current);
        if (is_file($old)) {
            unlink($old);
        }
    }

    return $name;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../campaigns.php");
    exit;
}

$id = (int) ($_POST['id_campaign'] ?? 0);
$subject = trim((string) ($_POST['subject'] ?? ''));
$content = trim((string) ($_POST['content'] ?? ''));
$buttonText = trim((string) ($_POST['button_text'] ?? ''));
$buttonLink = trim((string) ($_POST['button_link'] ?? ''));
$status = (int) ($_POST['status'] ?? 1);
$status = in_array($status, [0, 1], true) ? $status : 1;

if ($subject === '') {
    redirectCampaign('Le sujet est obligatoire.', 'error');
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT image FROM campaigns WHERE id_campaign = ?");
        $stmt->execute([$id]);
        $campaign = $stmt->fetch();

        if (!$campaign) {
            redirectCampaign('Campagne introuvable.', 'error');
        }

        $image = campaignImageUpload((string) ($campaign['image'] ?? ''));
        $stmt = $pdo->prepare("
            UPDATE campaigns
            SET subject = ?, content = ?, image = ?, button_text = ?, button_link = ?, status = ?
            WHERE id_campaign = ?
        ");
        $stmt->execute([$subject, $content, $image, $buttonText, $buttonLink, $status, $id]);
        redirectCampaign('Campagne modifiee avec succes.');
    }

    $image = campaignImageUpload('');
    $stmt = $pdo->prepare("
        INSERT INTO campaigns (subject, content, image, button_text, button_link, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$subject, $content, $image, $buttonText, $buttonLink, $status]);
    redirectCampaign('Campagne enregistree avec succes.');
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    redirectCampaign("La campagne n'a pas pu etre enregistree.", 'error');
}
