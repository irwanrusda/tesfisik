<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

$id = (int) ($_GET['id'] ?? 0);
$expires = (int) ($_GET['expires'] ?? 0);
$signature = (string) ($_GET['signature'] ?? '');
$secret = (string) env('PHOTO_URL_SECRET', env('MIGRATION_KEY', ''));
if ($id < 1 || $expires < time() || $secret === '') {
    http_response_code(403);
    exit('Tautan foto tidak valid atau sudah kedaluwarsa.');
}
$expectedSignature = hash_hmac('sha256', $id . '|' . $expires, $secret);
if ($signature === '' || !hash_equals($expectedSignature, $signature)) {
    http_response_code(403);
    exit('Signature tautan foto tidak valid.');
}
$statement = Database::connection()->prepare('SELECT file_name, original_name, mime_type, file_size FROM test_photos WHERE id = ?');
$statement->execute([$id]);
$photo = $statement->fetch();
if (!$photo) {
    http_response_code(404);
    exit('Foto tidak ditemukan.');
}

$path = test_photo_path($photo['file_name']);
if (!is_file($path)) {
    http_response_code(404);
    exit('File foto tidak ditemukan.');
}

header('Content-Type: ' . $photo['mime_type']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . str_replace(['"', "\r", "\n"], '', $photo['original_name']) . '"');
header('Cache-Control: private, max-age=' . max(0, min(900, $expires - time())));
header('X-Content-Type-Options: nosniff');
readfile($path);
