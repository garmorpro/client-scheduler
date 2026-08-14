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
    ['Only add a row here for an engagement that does NOT already exist. An engagement is matched by client_name + year + engagement_name, so an existing client can still get a brand-new engagement row here.', false],
    ['engagement_name is OPTIONAL - leave it blank and the client\'s own name is used everywhere. Only fill it in when one client runs more than one engagement in the same year (e.g. a client named "LivePerson" with separate engagements named "Conversation Cloud", "Tenfold", and "Voicebase") - without it, the 2nd and 3rd engagement for that client/year would look like duplicates of the 1st and get skipped.', false],
    ['', false],
    ['Weekly Hours tab', true],
    ['One row per employee, per engagement - not per week. Every Monday of the current year is its own column to the right; type hours into whichever weeks that person worked on this engagement and leave the rest blank. client_name + year (+ engagement_name, if that client has more than one engagement this year) must match a row in the Engagements tab (or an engagement that already exists). employee_full_name must match an active employee\'s name exactly. audit_type applies to every week in that row - give the same person a second row (with a different audit_type) if their hours on this engagement need to be split across audit types.', false],
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
$headers = ['client_name', 'engagement_name', 'year', 'status', 'budgeted_hours', 'manager', 'audit_types', 'tsc', 'soc_type', 'as_of_date', 'review_period_start', 'review_period_end', 'location', 'poc', 'scope', 'repeat', 'notes'];
$sheet->fromArray($headers, null, 'A1');
styleHeaderRow($sheet, count($headers));
$sheet->fromArray(['Example Client Inc.', '', 2026, 'Pending', 200, $managerNames[0] ?? '', 'SOC 2', 'Security, Availability', 'Type 2', '', '2026-01-01', '2026-12-31', 'Remote', 'Jane Doe', 'Full scope audit', 'No', 'Optional'], null, 'A2');
$sheet->getStyle('A2:Q2')->getFont()->setItalic(true)->getColor()->setRGB('9AA39D');
addDropdown($sheet, 'D', 2, 200, ['Confirmed', 'Pending', 'Not Confirmed']);
if (!empty($auditTypeNames)) addDropdown($sheet, 'G', 2, 200, $auditTypeNames);
addDropdown($sheet, 'I', 2, 200, ['Type 1', 'Type 2', 'RA']);
addDropdown($sheet, 'P', 2, 200, ['Yes', 'No']);
foreach (['J', 'K', 'L'] as $col) $sheet->getStyle("{$col}2:{$col}200")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
foreach (['A' => 28, 'B' => 22, 'C' => 8, 'D' => 14, 'E' => 15, 'F' => 20, 'G' => 22, 'H' => 26, 'I' => 10, 'J' => 13, 'K' => 16, 'L' => 16, 'M' => 16, 'N' => 16, 'O' => 24, 'P' => 9, 'Q' => 24] as $col => $w) $sheet->getColumnDimension($col)->setWidth($w);

// ---------------------------------------------------------------
// Weekly Hours - a grid (one row per person per engagement, weeks as
// columns) instead of one row per person per week. Filling this out by
// hand for a full year of hours across a team would mean retyping
// client_name/employee_full_name on every single week's row otherwise;
// here it's typed once per person+engagement and every week is just a
// number in the matching column (blank = no hours that week).
// ---------------------------------------------------------------
$sheet = $spreadsheet->createSheet();
$sheet->setTitle('Weekly Hours');

$currentYear = (int) date('Y');
$weekStarts = [];
$d = new \DateTime("{$currentYear}-01-01");
while ((int) $d->format('N') !== 1) $d->modify('+1 day'); // first Monday on/after Jan 1
while ((int) $d->format('Y') === $currentYear) {
    $weekStarts[] = $d->format('Y-m-d');
    $d->modify('+7 days');
}

$fixedHeaders = ['client_name', 'engagement_name', 'year', 'employee_full_name', 'audit_type'];
$headers = array_merge($fixedHeaders, $weekStarts);
$sheet->fromArray($headers, null, 'A1');
styleHeaderRow($sheet, count($headers));
$sheet->freezePane('F2'); // client/engagement/year/employee/audit_type stay visible while scrolling through weeks
$sheet->getStyle('A1:E1')->getAlignment()->setWrapText(false);
$lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
$sheet->getStyle("F1:{$lastCol}1")->getAlignment()->setTextRotation(90);

$exampleRow = array_merge(['Example Client Inc.', '', $currentYear, 'Jane Doe', 'SOC 2'], array_fill(0, count($weekStarts), null));
$exampleRow[5] = 8;  // first week
$exampleRow[6] = 6;  // second week
$sheet->fromArray($exampleRow, null, 'A2');
$sheet->getStyle("A2:{$lastCol}2")->getFont()->setItalic(true)->getColor()->setRGB('9AA39D');

if (!empty($auditTypeNames)) addDropdown($sheet, 'E', 2, 500, $auditTypeNames);

$sheet->getColumnDimension('A')->setWidth(28);
$sheet->getColumnDimension('B')->setWidth(22);
$sheet->getColumnDimension('C')->setWidth(8);
$sheet->getColumnDimension('D')->setWidth(24);
$sheet->getColumnDimension('E')->setWidth(16);
foreach ($weekStarts as $i => $ws) {
    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6 + $i);
    $sheet->getColumnDimension($col)->setWidth(7);
}

$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="engagement_import_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
