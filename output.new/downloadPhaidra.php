<?php
$file = htmlspecialchars($_GET['filename'] ?? '');
$url  = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);

if (!empty($file) && !empty($url)) {
    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    readfile($url);
}
