<?php
$file = filter_input(INPUT_GET, 'filename', FILTER_SANITIZE_STRING);
$url  = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);

header('Content-Type: image/jpeg');
header('Content-Disposition: attachment; filename="' . $file . '"');
readfile($url);