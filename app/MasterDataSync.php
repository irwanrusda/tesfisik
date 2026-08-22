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

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $sportStatement = $pdo->prepare('INSERT INTO sports (name, is_active) VALUES (?, 1) ON DUPLICATE KEY UPDATE is_active = 1, id = LAST_INSERT_ID(id)');
            $sportStatement->execute([$sport]);
            $sportId = (int) $pdo->lastInsertId();
            $sourceKey = hash('sha256', strtolower($name . '|Atlet|' . $sport));
            $hasSourceColumn = self::hasColumn($pdo, 'master_people', 'source');
            $personStatement = $pdo->prepare(
                $hasSourceColumn
                    ? "INSERT INTO master_people (source_key, source, name, person_type, gender, sport_id, achievement, development_status, description, is_active, synced_at)
                       VALUES (?, 'website', ?, 'Atlet', ?, ?, ?, ?, ?, 1, NULL)
                       ON DUPLICATE KEY UPDATE name = VALUES(name), source = IF(source = 'spreadsheet', source, VALUES(source)), gender = VALUES(gender), sport_id = VALUES(sport_id), achievement = VALUES(achievement), development_status = VALUES(development_status), description = VALUES(description), is_active = 1, id = LAST_INSERT_ID(id)"
                    : "INSERT INTO master_people (source_key, name, person_type, gender, sport_id, achievement, development_status, description, is_active, synced_at)
                       VALUES (?, ?, 'Atlet', ?, ?, ?, ?, ?, 1, NULL)
                       ON DUPLICATE KEY UPDATE name = VALUES(name), gender = VALUES(gender), sport_id = VALUES(sport_id), achievement = VALUES(achievement), development_status = VALUES(development_status), description = VALUES(description), is_active = 1, id = LAST_INSERT_ID(id)"
            );
            $personStatement->execute([$sourceKey, $name, $gender, $sportId, $achievement ?: null, $status ?: null, $description ?: null]);
            $personId = (int) $pdo->lastInsertId();
            $pdo->commit();
            return ['id' => $personId, 'name' => $name, 'sport' => $sport];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
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
            $hasSourceColumn = self::hasColumn($pdo, 'master_people', 'source');
            $pdo->exec($hasSourceColumn ? "UPDATE master_people SET is_active = 0 WHERE source = 'spreadsheet'" : 'UPDATE master_people SET is_active = 0');
            $sportStatement = $pdo->prepare('INSERT INTO sports (name, is_active) VALUES (?, 1) ON DUPLICATE KEY UPDATE is_active = 1, id = LAST_INSERT_ID(id)');
            $personStatement = $pdo->prepare(
                $hasSourceColumn
                    ? "INSERT INTO master_people (source_key, source, name, person_type, gender, sport_id, achievement, development_status, description, is_active, synced_at)
                       VALUES (?, 'spreadsheet', ?, ?, ?, ?, ?, ?, ?, 1, NOW())
                       ON DUPLICATE KEY UPDATE name = VALUES(name), person_type = VALUES(person_type), gender = VALUES(gender), sport_id = VALUES(sport_id), achievement = VALUES(achievement), development_status = VALUES(development_status), description = VALUES(description), is_active = 1, synced_at = NOW()"
                    : 'INSERT INTO master_people (source_key, name, person_type, gender, sport_id, achievement, development_status, description, is_active, synced_at)
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

    private static function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $statement = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $statement->execute([$column]);
        return (bool) $statement->fetch();
    }
}
