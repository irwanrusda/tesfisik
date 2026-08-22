<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

if (!class_exists(ZipArchive::class)) {
    http_response_code(503);
    exit('Ekstensi PHP ZipArchive belum aktif di server.');
}

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

function xlsx_column(int $number): string
{
    $column = '';
    while ($number > 0) {
        $number--;
        $column = chr(65 + ($number % 26)) . $column;
        $number = intdiv($number, 26);
    }
    return $column;
}

function xlsx_xml(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsx_sheet(array $rows): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetData>';
    foreach ($rows as $rowIndex => $row) {
        $excelRow = $rowIndex + 1;
        $xml .= '<row r="' . $excelRow . '">';
        foreach (array_values($row) as $columnIndex => $value) {
            $reference = xlsx_column($columnIndex + 1) . $excelRow;
            $style = $excelRow === 1 ? ' s="1"' : '';
            if (is_int($value) || is_float($value)) {
                $xml .= '<c r="' . $reference . '"' . $style . '><v>' . $value . '</v></c>';
            } else {
                $xml .= '<c r="' . $reference . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . xlsx_xml($value) . '</t></is></c>';
            }
        }
        $xml .= '</row>';
    }
    $lastColumn = xlsx_column(max(1, count($rows[0] ?? [])));
    return $xml . '</sheetData><autoFilter ref="A1:' . $lastColumn . '1"/></worksheet>';
}

$temporaryFile = tempnam(sys_get_temp_dir(), 'koni-xlsx-');
if ($temporaryFile === false) throw new RuntimeException('File sementara Excel gagal dibuat.');
$zip = new ZipArchive();
if ($zip->open($temporaryFile, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Arsip Excel gagal dibuat.');
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Hasil Tes Fisik" sheetId="1" r:id="rId1"/><sheet name="Hasil VO2max" sheetId="2" r:id="rId2"/></sheets></workbook>');
$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
$zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font/><font><b/><color rgb="FFFFFFFF"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF101D32"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>');
$zip->addFromString('xl/worksheets/sheet1.xml', xlsx_sheet($physicalRows));
$zip->addFromString('xl/worksheets/sheet2.xml', xlsx_sheet($bleepRows));
$zip->close();

$filename = 'hasil-tes-fisik-koni-sumbar-' . date('Ymd-His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0, no-store');
readfile($temporaryFile);
unlink($temporaryFile);
