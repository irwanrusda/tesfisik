<?php

declare(strict_types=1);

final class MasterDataSync
{
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
                $sourceKey = hash('sha256', mb_strtolower($name . '|' . $type . '|' . $sport));
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
