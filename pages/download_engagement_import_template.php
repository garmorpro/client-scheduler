<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_clients_engagements')) {
    http_response_code(401);
    echo 'Unauthorized';
    exit();
}

if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "The PhpSpreadsheet library isn't installed on this server yet.\n"
        . "Run: composer update phpoffice/phpspreadsheet\n"
        . "(composer install alone won't pick it up if composer.lock predates it being added to composer.json.)";
    exit();
}

// Fall back to a readable error instead of a blank page for anything else
// that goes wrong below (e.g. the server's PHP is missing ext-zip, which
// PhpSpreadsheet's .xlsx writer needs) - display_errors is off in
// production, so an uncaught error here would otherwise just be silent.
set_exception_handler(function (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "Template generation failed: " . $e->getMessage();
});

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$auditTypeNames = [];
$res = $conn->query("SELECT name FROM audit_types WHERE is_active = 1 ORDER BY name ASC");
while ($row = $res->fetch_assoc()) $auditTypeNames[] = $row['name'];

$managerNames = [];
$res = $conn->query("SELECT full_name FROM users WHERE status = 'active' AND role = 'manager' ORDER BY full_name ASC");
while ($row = $res->fetch_assoc()) $managerNames[] = $row['full_name'];

function styleHeaderRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $colCount): void {
    $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
    $range = "A1:{$lastCol}1";
    $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('003F47');
    $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->freezePane('A2');
}

function addDropdown(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $colLetter, int $firstRow, int $lastRow, array $options): void {
    $list = '"' . implode(',', $options) . '"';
    for ($r = $firstRow; $r <= $lastRow; $r++) {
        $validation = $sheet->getCell("{$colLetter}{$r}")->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_WARNING);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1($list);
    }
}

$spreadsheet = new Spreadsheet();

// ---------------------------------------------------------------
// Instructions
// ---------------------------------------------------------------
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Instructions');
$lines = [
    ['How this import works', true],
    ['Fill in the three tabs, then upload this file from Settings > Import Engagements.', false],
    ['', false],
    ['Clients tab', true],
    ['Only add a row here for a client that does NOT already exist in the app. If a name matches an existing client, that row is skipped automatically - it will not create a duplicate.', false],
    ['', false],
    ['Engagements tab', true],
    ['Only add a row here for an engagement that does NOT already exist. An engagement is matched by client_name + year, so an existing client can still get a brand-new engagement row here.', false],
    ['', false],
    ['Weekly Hours tab', true],
    ['One row per employee, per week, per engagement. client_name + year must match a row in the Engagements tab (or an engagement that already exists). employee_full_name must match an active employee\'s name exactly. week_start must be a Monday.', false],
    ['', false],
    ['Before anything is created', true],
    ['Uploading shows a full report first - what will be created, plus any errors that need fixing - nothing is saved until you review it and confirm.', false],
];
$r = 1;
foreach ($lines as [$text, $bold]) {
    $sheet->setCellValue("A{$r}", $text);
    if ($bold) $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(13);
    $r++;
}
$sheet->getColumnDimension('A')->setWidth(110);
foreach (range(1, $r) as $row) $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);

// ---------------------------------------------------------------
// Clients
// ---------------------------------------------------------------
$sheet = $spreadsheet->createSheet();
$sheet->setTitle('Clients');
$headers = ['client_name', 'onboarded_date', 'status', 'notes'];
$sheet->fromArray($headers, null, 'A1');
styleHeaderRow($sheet, count($headers));
$sheet->fromArray(['Example Client Inc.', '2026-01-15', 'Active', 'Optional'], null, 'A2');
$sheet->getStyle('A2:D2')->getFont()->setItalic(true)->getColor()->setRGB('9AA39D');
addDropdown($sheet, 'C', 2, 200, ['Active', 'Inactive']);
foreach (['A' => 32, 'B' => 16, 'C' => 12, 'D' => 40] as $col => $w) $sheet->getColumnDimension($col)->setWidth($w);
$sheet->getStyle('B2:B200')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

// ---------------------------------------------------------------
// Engagements
// ---------------------------------------------------------------
$sheet = $spreadsheet->createSheet();
$sheet->setTitle('Engagements');
$headers = ['client_name', 'year', 'status', 'budgeted_hours', 'manager', 'audit_types', 'tsc', 'soc_type', 'as_of_date', 'review_period_start', 'review_period_end', 'location', 'poc', 'scope', 'repeat', 'notes'];
$sheet->fromArray($headers, null, 'A1');
styleHeaderRow($sheet, count($headers));
$sheet->fromArray(['Example Client Inc.', 2026, 'Pending', 200, $managerNames[0] ?? '', 'SOC 2', 'Security, Availability', 'Type 2', '', '2026-01-01', '2026-12-31', 'Remote', 'Jane Doe', 'Full scope audit', 'No', 'Optional'], null, 'A2');
$sheet->getStyle('A2:P2')->getFont()->setItalic(true)->getColor()->setRGB('9AA39D');
addDropdown($sheet, 'C', 2, 200, ['Confirmed', 'Pending', 'Not Confirmed']);
if (!empty($auditTypeNames)) addDropdown($sheet, 'F', 2, 200, $auditTypeNames);
addDropdown($sheet, 'H', 2, 200, ['Type 1', 'Type 2']);
addDropdown($sheet, 'O', 2, 200, ['Yes', 'No']);
foreach (['I', 'J', 'K'] as $col) $sheet->getStyle("{$col}2:{$col}200")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
foreach (['A' => 28, 'B' => 8, 'C' => 14, 'D' => 15, 'E' => 20, 'F' => 22, 'G' => 26, 'H' => 10, 'I' => 13, 'J' => 16, 'K' => 16, 'L' => 16, 'M' => 16, 'N' => 24, 'O' => 9, 'P' => 24] as $col => $w) $sheet->getColumnDimension($col)->setWidth($w);

// ---------------------------------------------------------------
// Weekly Hours
// ---------------------------------------------------------------
$sheet = $spreadsheet->createSheet();
$sheet->setTitle('Weekly Hours');
$headers = ['client_name', 'year', 'employee_full_name', 'week_start', 'hours', 'audit_type'];
$sheet->fromArray($headers, null, 'A1');
styleHeaderRow($sheet, count($headers));
$sheet->fromArray(['Example Client Inc.', 2026, 'Jane Doe', '2026-01-05', 8, 'SOC 2'], null, 'A2');
$sheet->getStyle('A2:F2')->getFont()->setItalic(true)->getColor()->setRGB('9AA39D');
if (!empty($auditTypeNames)) addDropdown($sheet, 'F', 2, 2000, $auditTypeNames);
$sheet->getStyle('D2:D2000')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
foreach (['A' => 28, 'B' => 8, 'C' => 24, 'D' => 14, 'E' => 9, 'F' => 22] as $col => $w) $sheet->getColumnDimension($col)->setWidth($w);

$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="engagement_import_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
