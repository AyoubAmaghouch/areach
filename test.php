<?php

$root = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/products';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        echo $file->getFilename() . "<br>";
        echo $file->getPathname() . "<br><br>";
        break;
    }
}