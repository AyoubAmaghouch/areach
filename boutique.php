<?php

declare(strict_types=1);

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = pageUrl('shop.php');

if ($query !== '') {
    $target .= '?' . $query;
}

header('Location: ' . $target, true, 301);
exit;
