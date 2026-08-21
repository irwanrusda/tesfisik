<?php

declare(strict_types=1);

final class MasterDataSync
{
    public static function addAthlete(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $gender = (string) ($data['gender'] ?? '');
        $sport = strtoupper(trim((string) ($data['sport'] ?? '')));
        $achievement = trim((string) ($data['achievement'] ?? ''));
        $status = trim((string) ($data['development_status'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        if ($name === '' || !in_array($gender, ['L', 'P'], true) || $sport === '') {
            throw new RuntimeException('Nama, jenis kelamin, dan cabang olahraga wajib diisi.');
        }

        $duplicate = self::sheetContainsAthlete($name, $sport);
        if (!$duplicate) {
            self::appendSheetRow([$name, 'Atlet', $gender === 'P' ? 'Pi' : 'Pa', $sport, $achievement, $status, $description]);
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $sportStatement = $pdo->prepare('INSERT INTO sports (name, is_active) VALUES (?, 1) ON DUPLICATE KEY UPDATE is_active = 1, id = LAST_INSERT_ID(id)');
            $sportStatement->execute([$sport]);
            $sportId = (int) $pdo->lastInsertId();
            $sourceKey = hash('sha256', strtolower($name . '|Atlet|' . $sport));
            $personStatement = $pdo->prepare(
                "INSERT INTO master_people (source_key, name, person_type, gender, sport_id, achievement, development_status, description, is_active, synced_at)
                 VALUES (?, ?, 'Atlet', ?, ?, ?, ?, ?, 1, NOW())
                 ON DUPLICATE KEY UPDATE name = VALUES(name), gender = VALUES(gender), sport_id = VALUES(sport_id), achievement = VALUES(achievement), development_status = VALUES(development_status), description = VALUES(description), is_active = 1, synced_at = NOW(), id = LAST_INSERT_ID(id)"
            );
            $personStatement->execute([$sourceKey, $name, $gender, $sportId, $achievement ?: null, $status ?: null, $description ?: null]);
            $personId = (int) $pdo->lastInsertId();
            $pdo->commit();
            return ['id' => $personId, 'name' => $name, 'sport' => $sport, 'duplicate' => $duplicate];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw new RuntimeException('Atlet sudah ditulis ke Google Sheet, tetapi database lokal gagal diperbarui. Jalankan sinkronisasi. ' . $exception->getMessage(), 0, $exception);
        }
    }

    private static function sheetContainsAthlete(string $name, string $sport): bool
    {
        $sheetId = (string) config('app.google_sheet_id');
        $gid = (string) config('app.google_sheet_gid');
        $url = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
        $handle = @fopen($url, 'rb', false, stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'KONI-Sumbar-Master-Writer/1.0']]));
        if ($handle === false) throw new RuntimeException('Google Sheet tidak dapat dibaca untuk pemeriksaan duplikasi.');
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $row = array_pad($row, 4, '');
            if (strcasecmp(trim((string) $row[0]), $name) === 0 && strcasecmp(trim((string) $row[1]), 'Atlet') === 0 && strcasecmp(trim((string) $row[3]), $sport) === 0) {
                fclose($handle);
                return true;
            }
        }
        fclose($handle);
        return false;
    }

    private static function appendSheetRow(array $row): void
    {
        $token = self::googleAccessToken();
        $sheetId = rawurlencode((string) config('app.google_sheet_id'));
        $sheetName = str_replace("'", "''", (string) config('app.google_sheet_name'));
        $range = rawurlencode("'{$sheetName}'!A:G");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/{$range}:append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS";
        $response = self::httpJsonRequest($url, 'POST', ['Authorization: Bearer ' . $token, 'Content-Type: application/json'], json_encode(['values' => [$row]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ((int) ($response['updates']['updatedRows'] ?? 0) !== 1) throw new RuntimeException('Google Sheets API tidak mengonfirmasi penambahan baris.');
    }

    private static function googleAccessToken(): string
    {
        $path = (string) config('app.google_service_account_json');
        if ($path === '' || !is_file($path) || !is_readable($path)) throw new RuntimeException('File kredensial Google service account belum tersedia atau tidak dapat dibaca.');
        $credentials = json_decode((string) file_get_contents($path), true);
        if (!is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key']) || empty($credentials['token_uri'])) throw new RuntimeException('Format file kredensial Google service account tidak valid.');
        if (!function_exists('openssl_sign')) throw new RuntimeException('Ekstensi PHP OpenSSL wajib aktif untuk Google Sheets API.');

        $now = time();
        $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::base64UrlEncode(json_encode(['iss' => $credentials['client_email'], 'scope' => 'https://www.googleapis.com/auth/spreadsheets', 'aud' => $credentials['token_uri'], 'iat' => $now, 'exp' => $now + 3600]));
        $unsigned = $header . '.' . $claims;
        if (!openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) throw new RuntimeException('JWT Google service account gagal ditandatangani.');
        $assertion = $unsigned . '.' . self::base64UrlEncode($signature);
        $response = self::httpJsonRequest((string) $credentials['token_uri'], 'POST', ['Content-Type: application/x-www-form-urlencoded'], http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $assertion]));
        if (empty($response['access_token'])) throw new RuntimeException('Token akses Google gagal diperoleh.');
        return (string) $response['access_token'];
    }

    private static function httpJsonRequest(string $url, string $method, array $headers, string $body): array
    {
        $context = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers) . "\r\nUser-Agent: KONI-Sumbar-Google-Sheets/1.0\r\n", 'content' => $body, 'timeout' => 30, 'ignore_errors' => true]]);
        $responseBody = @file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $status = (int) ($matches[1] ?? 0);
        $response = json_decode((string) $responseBody, true);
        if ($responseBody === false || $status < 200 || $status >= 300) {
            $message = is_array($response) ? ($response['error']['message'] ?? $response['error_description'] ?? null) : null;
            throw new RuntimeException('Google API gagal' . ($message ? ': ' . $message : ". HTTP {$status}."));
        }
        return is_array($response) ? $response : [];
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public static function run(): array
    {
        $sheetId = (string) config('app.google_sheet_id');
        $gid = (string) config('app.google_sheet_gid');
        if ($sheetId === '' || $gid === '') {
            throw new RuntimeException('Konfigurasi Google Sheet belum tersedia.');
        }

        $url = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
        $context = stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'KONI-Sumbar-Master-Sync/1.0']]);
        $handle = @fopen($url, 'rb', false, $context);
        if ($handle === false) {
            throw new RuntimeException('Google Sheet tidak dapat diakses. Pastikan sheet dapat dilihat melalui tautan.');
        }

        $header = fgetcsv($handle);
        if (!$header || count($header) < 7) {
            fclose($handle);
            throw new RuntimeException('Format kolom Google Sheet tidak sesuai.');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $row = array_pad($row, 7, '');
            if (trim((string) $row[0]) === '') {
                continue;
            }
            $rows[] = array_map(static fn($value) => trim((string) $value), array_slice($row, 0, 7));
        }
        fclose($handle);

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->exec('UPDATE master_people SET is_active = 0');
            $sportStatement = $pdo->prepare('INSERT INTO sports (name, is_active) VALUES (?, 1) ON DUPLICATE KEY UPDATE is_active = 1, id = LAST_INSERT_ID(id)');
            $personStatement = $pdo->prepare(
                'INSERT INTO master_people (source_key, name, person_type, gender, sport_id, achievement, development_status, description, is_active, synced_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
                 ON DUPLICATE KEY UPDATE name = VALUES(name), person_type = VALUES(person_type), gender = VALUES(gender), sport_id = VALUES(sport_id), achievement = VALUES(achievement), development_status = VALUES(development_status), description = VALUES(description), is_active = 1, synced_at = NOW()'
            );

            $athletes = 0;
            $coaches = 0;
            $sportNames = [];
            foreach ($rows as [$name, $type, $gender, $sport, $achievement, $status, $description]) {
                $type = strcasecmp($type, 'Pelatih') === 0 ? 'Pelatih' : 'Atlet';
                $gender = strcasecmp($gender, 'Pi') === 0 ? 'P' : 'L';
                $sport = strtoupper($sport);
                if ($sport === '') {
                    continue;
                }

                $sportStatement->execute([$sport]);
                $sportId = (int) $pdo->lastInsertId();
                $sourceKey = hash('sha256', strtolower($name . '|' . $type . '|' . $sport));
                $personStatement->execute([$sourceKey, $name, $type, $gender, $sportId, $achievement ?: null, $status ?: null, $description ?: null]);
                $sportNames[$sport] = true;
                $type === 'Atlet' ? $athletes++ : $coaches++;
            }

            $pdo->exec('UPDATE sports SET is_active = EXISTS (SELECT 1 FROM master_people WHERE master_people.sport_id = sports.id AND master_people.is_active = 1)');
            $pdo->commit();
            return ['athletes' => $athletes, 'coaches' => $coaches, 'sports' => count($sportNames), 'total' => $athletes + $coaches];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
