<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function webhook_response(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function run_deploy_command(array $command, string $workingDirectory): array
{
    if (!function_exists('proc_open')) {
        throw new RuntimeException('Fungsi proc_open dinonaktifkan oleh hosting.');
    }

    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptorSpec, $pipes, $workingDirectory, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new RuntimeException('Proses deployment tidak dapat dijalankan.');
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return [
        'command' => implode(' ', array_map('escapeshellarg', $command)),
        'exit_code' => $exitCode,
        'output' => trim((string) $stdout),
        'error' => trim((string) $stderr),
    ];
}

if (!request_method('POST')) {
    header('Allow: POST');
    webhook_response(405, ['ok' => false, 'message' => 'Gunakan metode POST.']);
}

$secret = (string) env('GITHUB_WEBHOOK_SECRET', '');
if ($secret === '') {
    webhook_response(503, ['ok' => false, 'message' => 'GITHUB_WEBHOOK_SECRET belum dikonfigurasi.']);
}

$payload = file_get_contents('php://input');
$signature = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
$expectedSignature = 'sha256=' . hash_hmac('sha256', (string) $payload, $secret);
if ($signature === '' || !hash_equals($expectedSignature, $signature)) {
    webhook_response(401, ['ok' => false, 'message' => 'Signature webhook tidak valid.']);
}

$event = (string) ($_SERVER['HTTP_X_GITHUB_EVENT'] ?? '');
if ($event === 'ping') {
    webhook_response(200, ['ok' => true, 'message' => 'Webhook KONI Sumbar aktif.']);
}
if ($event !== 'push') {
    webhook_response(202, ['ok' => true, 'message' => "Event {$event} diabaikan."]);
}

$data = json_decode((string) $payload, true);
if (!is_array($data)) {
    webhook_response(400, ['ok' => false, 'message' => 'Payload JSON tidak valid.']);
}

$branch = (string) env('DEPLOY_BRANCH', 'main');
if (($data['ref'] ?? '') !== "refs/heads/{$branch}") {
    webhook_response(202, ['ok' => true, 'message' => 'Push bukan untuk branch deployment.']);
}

$allowedRepository = (string) env('GITHUB_REPOSITORY', 'irwanrusda/tesfisik');
$payloadRepository = (string) ($data['repository']['full_name'] ?? '');
if ($allowedRepository !== '' && !hash_equals(strtolower($allowedRepository), strtolower($payloadRepository))) {
    webhook_response(403, ['ok' => false, 'message' => 'Repository tidak diizinkan.']);
}

$repositoryPath = (string) env('DEPLOY_REPOSITORY_PATH', BASE_PATH);
$gitBinary = (string) env('DEPLOY_GIT_BINARY', '/usr/bin/git');
$phpBinary = (string) env('DEPLOY_PHP_BINARY', PHP_BINARY);
if (!is_dir($repositoryPath . '/.git')) {
    webhook_response(500, ['ok' => false, 'message' => 'DEPLOY_REPOSITORY_PATH bukan repository Git.']);
}

$lockPath = BASE_PATH . '/storage/deploy-webhook.lock';
$lock = fopen($lockPath, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    webhook_response(409, ['ok' => false, 'message' => 'Deployment lain sedang berjalan.']);
}

$startedAt = date(DATE_ATOM);
$deliveryId = (string) ($_SERVER['HTTP_X_GITHUB_DELIVERY'] ?? 'unknown');
$results = [];

try {
    $results[] = run_deploy_command([$gitBinary, 'pull', '--ff-only', 'origin', $branch], $repositoryPath);
    if ($results[0]['exit_code'] !== 0) {
        throw new RuntimeException('Git pull gagal.');
    }

    $results[] = run_deploy_command([$phpBinary, 'migrate.php'], $repositoryPath);
    if ($results[array_key_last($results)]['exit_code'] !== 0) {
        throw new RuntimeException('Migrasi database gagal.');
    }

    $logEntry = json_encode([
        'delivery_id' => $deliveryId,
        'started_at' => $startedAt,
        'finished_at' => date(DATE_ATOM),
        'status' => 'success',
        'commit' => $data['after'] ?? null,
        'results' => $results,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents(BASE_PATH . '/storage/logs/deploy.log', $logEntry, FILE_APPEND | LOCK_EX);

    webhook_response(200, [
        'ok' => true,
        'message' => 'Deployment dan migrasi berhasil.',
        'delivery_id' => $deliveryId,
        'commit' => $data['after'] ?? null,
    ]);
} catch (Throwable $exception) {
    $logEntry = json_encode([
        'delivery_id' => $deliveryId,
        'started_at' => $startedAt,
        'finished_at' => date(DATE_ATOM),
        'status' => 'failed',
        'message' => $exception->getMessage(),
        'results' => $results,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents(BASE_PATH . '/storage/logs/deploy.log', $logEntry, FILE_APPEND | LOCK_EX);
    webhook_response(500, ['ok' => false, 'message' => $exception->getMessage(), 'delivery_id' => $deliveryId]);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
