<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

$pdo = Database::connection();
$sport = trim((string) ($_GET['sport'] ?? ''));
$from = (string) ($_GET['from'] ?? '');
$to = (string) ($_GET['to'] ?? '');
$where = [];
$params = [];
if ($sport !== '') { $where[] = 'at.sport = ?'; $params[] = $sport; }
if ($from !== '') { $where[] = 'at.test_date >= ?'; $params[] = $from; }
if ($to !== '') { $where[] = 'at.test_date <= ?'; $params[] = $to; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$testsStatement = $pdo->prepare('SELECT at.* FROM athlete_tests at' . $whereSql . ' ORDER BY at.sport, at.athlete_name, at.test_date DESC, at.id DESC');
$testsStatement->execute($params);
$tests = $testsStatement->fetchAll();
$resultRows = $pdo->query('SELECT athlete_test_id, test_code, result_value, unit FROM test_results')->fetchAll();
$resultsByTest = [];
foreach ($resultRows as $result) $resultsByTest[$result['athlete_test_id']][$result['test_code']] = $result;

$bleepWhere = [];
$bleepParams = [];
if ($sport !== '') { $bleepWhere[] = 'sport = ?'; $bleepParams[] = $sport; }
if ($from !== '') { $bleepWhere[] = 'test_date >= ?'; $bleepParams[] = $from; }
if ($to !== '') { $bleepWhere[] = 'test_date <= ?'; $bleepParams[] = $to; }
$bleepStatement = $pdo->prepare('SELECT * FROM bleep_tests' . ($bleepWhere ? ' WHERE ' . implode(' AND ', $bleepWhere) : '') . ' ORDER BY sport, athlete_name, test_date DESC, id DESC');
$bleepStatement->execute($bleepParams);
$bleepTests = $bleepStatement->fetchAll();

$items = physical_test_items();
$headers = ['No', 'Nama', 'Jenis Kelamin', 'Tempat/Tanggal Lahir', 'Cabang Olahraga', 'Tanggal Tes'];
foreach ($items as $definition) {
    $headers[] = $definition['method'] . ' - Hasil';
    $headers[] = $definition['method'] . ' - Indikator 80%';
    $headers[] = $definition['method'] . ' - Status';
}
$physicalRows = [$headers];
foreach ($tests as $index => $test) {
    $birth = trim((string) ($test['birth_place'] ?: '-')) . ' / ' . format_date($test['birth_date']);
    $row = [$index + 1, $test['athlete_name'], $test['gender'] === 'L' ? 'Laki-Laki' : 'Perempuan', $birth, $test['sport'], format_date($test['test_date'])];
    foreach ($items as $code => $definition) {
        $result = $resultsByTest[$test['id']][$code] ?? null;
        $indicator = physical_test_indicator($test['sport'], $test['gender'], $code, $result['result_value'] ?? null);
        $row[] = $result ? $result['result_value'] . ' ' . $result['unit'] : '';
        $row[] = $indicator['available'] ? $indicator['operator'] . ' ' . $indicator['threshold'] . ' ' . $definition['unit'] : 'Belum tersedia';
        $row[] = $indicator['label'];
    }
    $physicalRows[] = $row;
}

$bleepHeaders = ['No', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Cabang Olahraga', 'Tanggal Tes', 'Level', 'Shuttle', 'Total Shuttle', 'Jarak (m)', 'Kecepatan (km/jam)', 'VO2max', 'Indikator 80%', 'Status'];
$bleepRows = [$bleepHeaders];
foreach ($bleepTests as $index => $test) {
    $indicator = physical_test_indicator($test['sport'], $test['gender'], 'bleep_test', $test['vo2max']);
    $bleepRows[] = [
        $index + 1,
        $test['athlete_name'],
        $test['gender'] === 'L' ? 'Laki-Laki' : 'Perempuan',
        format_date($test['birth_date']),
        $test['sport'],
        format_date($test['test_date']),
        $test['level'],
        $test['shuttle'],
        $test['completed_shuttles'],
        $test['distance_m'],
        $test['speed_kmh'],
        $test['vo2max'],
        $indicator['available'] ? $indicator['operator'] . ' ' . $indicator['threshold'] . ' ml/kg/menit' : 'Belum tersedia',
        $indicator['label'],
    ];
}

function excel_xml(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function excel_cell(mixed $value, bool $header = false): string
{
    $type = is_int($value) || is_float($value) ? 'Number' : 'String';
    $style = $header ? ' ss:StyleID="Header"' : '';
    return '<Cell' . $style . '><Data ss:Type="' . $type . '">' . excel_xml($value) . '</Data></Cell>';
}

function excel_worksheet(string $name, array $rows): string
{
    $xml = '<Worksheet ss:Name="' . excel_xml($name) . '"><Table>';
    foreach ($rows as $rowIndex => $row) {
        $xml .= '<Row>';
        foreach (array_values($row) as $value) {
            $xml .= excel_cell($value, $rowIndex === 0);
        }
        $xml .= '</Row>';
    }
    return $xml . '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>1</SplitHorizontal><TopRowBottomPane>1</TopRowBottomPane><ActivePane>2</ActivePane><ProtectObjects>False</ProtectObjects><ProtectScenarios>False</ProtectScenarios></WorksheetOptions></Worksheet>';
}

$workbook = '<?xml version="1.0" encoding="UTF-8"?>';
$workbook .= '<?mso-application progid="Excel.Sheet"?>';
$workbook .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
$workbook .= '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Author>Bidang Digitalisasi KONI Sumbar</Author><Created>' . gmdate('Y-m-d\TH:i:s\Z') . '</Created></DocumentProperties>';
$workbook .= '<Styles><Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Bottom"/><Font ss:FontName="Arial" ss:Size="10"/></Style><Style ss:ID="Header"><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#101D32" ss:Pattern="Solid"/></Style></Styles>';
$workbook .= excel_worksheet('Hasil Tes Fisik', $physicalRows);
$workbook .= excel_worksheet('Hasil VO2max', $bleepRows);
$workbook .= '</Workbook>';

$filename = 'hasil-tes-fisik-koni-sumbar-' . date('Ymd-His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0, no-store');
echo $workbook;
