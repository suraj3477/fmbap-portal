<?php

namespace App\Services;

use App\Models\Scheme;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SchemeExcelService
{
    /**
     * Generate an official FMBAP Excel template (.xlsx)
     * matching the scanned office document format.
     */
    public function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('FMBAP Schemes');

        // Main Title Header
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'ASSAM - STATUS OF FMBAP SCHEMES TAKEN UP UNDER FMBAP DURING XI PLAN, XII PLAN AND BEYOND XII PLAN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E3A8A'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Subtitle Header
        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', '(Figures in Rs. Lakh | Central Share: 90%, State Share: 10%)');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF4B5563'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table Column Headers (Row 4)
        $headers = [
            'A4' => 'Sl. No.',
            'B4' => 'Scheme Code No.',
            'C4' => 'Name of Division',
            'D4' => 'Name of scheme',
            'E4' => 'Plan Period',
            'F4' => 'Estimated amount (Rs. in Lakh)',
            'G4' => 'Physical status',
            'H4' => 'Physical progress (%)',
            'I4' => 'Total Fund utilised - CS (Lakh)',
            'J4' => 'Total Fund utilised - SS (Lakh)',
            'K4' => 'Total Fund utilised - Total (Lakh)',
            'L4' => 'Fund requirement - CS (Lakh)',
            'M4' => 'Fund requirement - SS (Lakh)',
            'N4' => 'Fund requirement - Total (Lakh)',
            'O4' => 'State',
            'P4' => 'River Basin',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Style the header row
        $headerRange = 'A4:P4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E40AF');
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension(4)->setRowHeight(36);

        // Sample Data Rows directly from the official Assam document
        $sampleRows = [
            [
                1, 'AS-1', 'North Lakhimpur',
                'R/S to Kakoi R/B embankment from Lilabari T.G. to Kadam including breach closing work with A/E measures.',
                'XI Plan', 299.93, 'Foreclosed / Ongoing', 60.00,
                269.94, 30.00, 299.94,
                0.00, 0.00, 0.00,
                'Assam', 'Brahmaputra',
            ],
            [
                2, 'AS-2', 'Nagaon',
                'R/S to Hatimura dyke from Kukurakata Hill to Hatimura Hill (0 M to 3595 M).',
                'XI Plan', 337.40, 'Completed', 100.00,
                303.61, 33.79, 337.40,
                0.00, 0.00, 0.00,
                'Assam', 'Brahmaputra',
            ],
            [
                3, 'AS-3', 'Mangaldoi',
                'R/S of Saktola embankment (B/B) from MPK Road to R.K. Embankment (Ch. 0 to 6.6 Km B/B).',
                'XI Plan', 326.51, 'Completed', 100.00,
                293.85, 32.66, 326.51,
                0.00, 0.00, 0.00,
                'Assam', 'Brahmaputra',
            ],
            [
                4, 'AS-10', 'Nagaon',
                'R/S to flood embankment along R/B of Kolong river from Phulaguri to Mulankata and Raha to Chaparmukh including A/E measures.',
                'XI Plan', 603.10, 'Completed', 100.00,
                521.07, 66.99, 588.06,
                16.48, 0.00, 16.48,
                'Assam', 'Brahmaputra',
            ],
        ];

        $rowNum = 5;
        foreach ($sampleRows as $row) {
            $sheet->fromArray($row, null, 'A' . $rowNum);
            $rowNum++;
        }

        // Apply borders and zebra striping
        $dataRange = 'A5:P' . ($rowNum - 1);
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFE5E7EB'));

        // Auto-fit column widths
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(45);

        // Write to temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'fmbap_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Normalize a scheme name for reliable duplicate comparison.
     */
    public static function normalizeName(?string $name): string
    {
        if ($name === null) return '';
        $name = strtolower(trim($name));
        return preg_replace('/\s+/', ' ', $name);
    }

    /**
     * Strip all non-alphanumeric characters for fuzzy exact-token matching.
     */
    public static function cleanKey(?string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', self::normalizeName($name));
    }

    /**
     * Parse an uploaded Excel or CSV file and return structured preview data.
     */
    public function parseExcelFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Find the header row (scan first 10 rows for keywords like "Scheme Code" or "Division")
        $headerRowIndex = null;
        $columnMap = [];

        for ($r = 1; $r <= min(10, $highestRow); $r++) {
            $rowValues = [];
            for ($c = 1; $c <= $highestColIndex; $c++) {
                $val = trim((string) $sheet->getCell([$c, $r])->getValue());
                $rowValues[$c] = $val;
            }

            // Check if this row looks like the header
            $hasCode = false;
            $hasName = false;
            foreach ($rowValues as $colIdx => $text) {
                $clean = strtolower($text);
                if (str_contains($clean, 'code') || str_contains($clean, 'sl. no') || str_contains($clean, 'sl no')) {
                    $hasCode = true;
                }
                if (str_contains($clean, 'scheme') || str_contains($clean, 'division') || str_contains($clean, 'estimated')) {
                    $hasName = true;
                }
            }

            if ($hasCode && $hasName) {
                $headerRowIndex = $r;
                // Build column map
                foreach ($rowValues as $colIdx => $text) {
                    $clean = preg_replace('/[^a-z0-9]/', '', strtolower($text));
                    if (empty($clean)) continue;

                    if (str_contains($clean, 'code')) {
                        $columnMap['scheme_code'] = $colIdx;
                    } elseif (str_contains($clean, 'division')) {
                        $columnMap['division'] = $colIdx;
                    } elseif (str_contains($clean, 'namescheme') || (str_contains($clean, 'scheme') && !str_contains($clean, 'code'))) {
                        $columnMap['scheme_name'] = $colIdx;
                    } elseif (str_contains($clean, 'plan')) {
                        $columnMap['plan_period'] = $colIdx;
                    } elseif (str_contains($clean, 'estimated') || str_contains($clean, 'amount') || str_contains($clean, 'sanctioned')) {
                        $columnMap['estimated_cost_lakh'] = $colIdx;
                    } elseif (str_contains($clean, 'status')) {
                        $columnMap['physical_status'] = $colIdx;
                    } elseif (str_contains($clean, 'progress')) {
                        $columnMap['physical_progress_pct'] = $colIdx;
                    } elseif ((str_contains($clean, 'utilised') || str_contains($clean, 'utilized')) && str_contains($clean, 'cs')) {
                        $columnMap['fund_utilised_cs_lakh'] = $colIdx;
                    } elseif ((str_contains($clean, 'utilised') || str_contains($clean, 'utilized')) && str_contains($clean, 'ss')) {
                        $columnMap['fund_utilised_ss_lakh'] = $colIdx;
                    } elseif ((str_contains($clean, 'utilised') || str_contains($clean, 'utilized')) && str_contains($clean, 'total')) {
                        $columnMap['fund_utilised_total_lakh'] = $colIdx;
                    } elseif ((str_contains($clean, 'requirement') || str_contains($clean, 'req')) && str_contains($clean, 'cs')) {
                        $columnMap['fund_req_cs_lakh'] = $colIdx;
                    } elseif ((str_contains($clean, 'requirement') || str_contains($clean, 'req')) && str_contains($clean, 'ss')) {
                        $columnMap['fund_req_ss_lakh'] = $colIdx;
                    } elseif ((str_contains($clean, 'requirement') || str_contains($clean, 'req')) && str_contains($clean, 'total')) {
                        $columnMap['fund_req_total_lakh'] = $colIdx;
                    } elseif (str_contains($clean, 'state')) {
                        $columnMap['state'] = $colIdx;
                    } elseif (str_contains($clean, 'basin') || str_contains($clean, 'river')) {
                        $columnMap['river_basin'] = $colIdx;
                    }
                }
                break;
            }
        }

        if (!$headerRowIndex) {
            // Fallback default index mapping if header wasn't auto-matched
            $headerRowIndex = 4;
            $columnMap = [
                'scheme_code'              => 2,
                'division'                 => 3,
                'scheme_name'              => 4,
                'plan_period'              => 5,
                'estimated_cost_lakh'      => 6,
                'physical_status'          => 7,
                'physical_progress_pct'    => 8,
                'fund_utilised_cs_lakh'    => 9,
                'fund_utilised_ss_lakh'    => 10,
                'fund_utilised_total_lakh' => 11,
                'fund_req_cs_lakh'         => 12,
                'fund_req_ss_lakh'         => 13,
                'fund_req_total_lakh'      => 14,
                'state'                    => 15,
                'river_basin'              => 16,
            ];
        }

        // Preload existing schemes from the portal to detect duplicates
        $existingSchemes = Scheme::all(['id', 'scheme_code', 'scheme_name']);
        $existingByCode = [];
        $existingByCleanName = [];

        foreach ($existingSchemes as $es) {
            $cKey = strtolower(trim($es->scheme_code));
            $nKey = self::cleanKey($es->scheme_name);
            if (!empty($cKey)) $existingByCode[$cKey] = $es;
            if (!empty($nKey)) $existingByCleanName[$nKey] = $es;
        }

        $seenInSheetCleanNames = [];
        $seenInSheetCodes = [];
        $parsedRows = [];

        for ($r = $headerRowIndex + 1; $r <= $highestRow; $r++) {
            $rawCode = trim((string) $sheet->getCell([$columnMap['scheme_code'] ?? 2, $r])->getValue());
            $rawName = trim((string) $sheet->getCell([$columnMap['scheme_name'] ?? 4, $r])->getValue());

            // Skip completely empty rows
            if (empty($rawCode) && empty($rawName)) {
                continue;
            }

            // If code is missing, auto-generate temporary code
            $schemeCode = !empty($rawCode) ? $rawCode : ('SCH-' . str_pad($r, 3, '0', STR_PAD_LEFT));
            $schemeName = $rawName ?: ('Untitled Scheme (' . $schemeCode . ')');

            $cleanName = self::cleanKey($schemeName);
            $cleanCode = strtolower(trim($schemeCode));

            // Duplicate detection: Primary key is the scheme code
            $isDuplicate = false;
            $duplicateReason = null;
            $matchedCode = null;

            if (!empty($rawCode)) {
                // If scheme code is provided in the file, check if that code already exists
                if (isset($existingByCode[$cleanCode])) {
                    $isDuplicate = true;
                    $matched = $existingByCode[$cleanCode];
                    $matchedCode = $matched->scheme_code;
                    $duplicateReason = "Already in portal ({$matchedCode})";
                } elseif (in_array($cleanCode, $seenInSheetCodes, true)) {
                    $isDuplicate = true;
                    $duplicateReason = "Repeated code in spreadsheet ({$schemeCode})";
                }
            } else {
                // Fallback: only if scheme code was NOT provided in the file, check by name
                if (!empty($cleanName) && isset($existingByCleanName[$cleanName])) {
                    $isDuplicate = true;
                    $matched = $existingByCleanName[$cleanName];
                    $matchedCode = $matched->scheme_code;
                    $duplicateReason = "Already in portal ({$matchedCode})";
                } elseif (!empty($cleanName) && in_array($cleanName, $seenInSheetCleanNames, true)) {
                    $isDuplicate = true;
                    $duplicateReason = "Repeated in this spreadsheet";
                }
            }

            if (!empty($cleanName)) {
                $seenInSheetCleanNames[] = $cleanName;
            }
            if (!empty($cleanCode)) {
                $seenInSheetCodes[] = $cleanCode;
            }

            $division = isset($columnMap['division']) ? trim((string) $sheet->getCell([$columnMap['division'], $r])->getValue()) : null;
            $planPeriod = isset($columnMap['plan_period']) ? trim((string) $sheet->getCell([$columnMap['plan_period'], $r])->getValue()) : 'XI Plan';
            $estimatedLakh = isset($columnMap['estimated_cost_lakh']) ? (float) $sheet->getCell([$columnMap['estimated_cost_lakh'], $r])->getValue() : 0.0;
            $status = isset($columnMap['physical_status']) ? trim((string) $sheet->getCell([$columnMap['physical_status'], $r])->getValue()) : 'Ongoing';
            $progressPct = isset($columnMap['physical_progress_pct']) ? (float) $sheet->getCell([$columnMap['physical_progress_pct'], $r])->getValue() : null;

            if ($progressPct === null || $progressPct === 0.0) {
                $progressPct = (strcasecmp($status, 'Completed') === 0) ? 100.00 : 0.00;
            }

            $fundUtilCs = isset($columnMap['fund_utilised_cs_lakh']) ? (float) $sheet->getCell([$columnMap['fund_utilised_cs_lakh'], $r])->getValue() : null;
            $fundUtilSs = isset($columnMap['fund_utilised_ss_lakh']) ? (float) $sheet->getCell([$columnMap['fund_utilised_ss_lakh'], $r])->getValue() : null;
            $fundUtilTot = isset($columnMap['fund_utilised_total_lakh']) ? (float) $sheet->getCell([$columnMap['fund_utilised_total_lakh'], $r])->getValue() : null;

            $fundReqCs = isset($columnMap['fund_req_cs_lakh']) ? (float) $sheet->getCell([$columnMap['fund_req_cs_lakh'], $r])->getValue() : null;
            $fundReqSs = isset($columnMap['fund_req_ss_lakh']) ? (float) $sheet->getCell([$columnMap['fund_req_ss_lakh'], $r])->getValue() : null;
            $fundReqTot = isset($columnMap['fund_req_total_lakh']) ? (float) $sheet->getCell([$columnMap['fund_req_total_lakh'], $r])->getValue() : null;

            $state = isset($columnMap['state']) ? trim((string) $sheet->getCell([$columnMap['state'], $r])->getValue()) : 'Assam';
            if (empty($state)) $state = 'Assam';

            $riverBasin = isset($columnMap['river_basin']) ? trim((string) $sheet->getCell([$columnMap['river_basin'], $r])->getValue()) : 'Brahmaputra';
            if (empty($riverBasin)) $riverBasin = 'Brahmaputra';

            // Convert Lakh to Crores for the core system
            $sanctionedCr = round($estimatedLakh / 100, 2);

            $parsedRows[] = [
                'scheme_code'              => $schemeCode,
                'scheme_name'              => $schemeName,
                'division'                 => $division,
                'district'                 => $division,
                'plan_period'              => $planPeriod ?: 'XI Plan',
                'estimated_cost_lakh'      => round($estimatedLakh, 2),
                'sanctioned_amount_cr'     => $sanctionedCr,
                'physical_status'          => $status ?: 'Ongoing',
                'physical_progress_pct'    => round($progressPct, 2),
                'fund_utilised_cs_lakh'    => $fundUtilCs !== null ? round($fundUtilCs, 2) : null,
                'fund_utilised_ss_lakh'    => $fundUtilSs !== null ? round($fundUtilSs, 2) : null,
                'fund_utilised_total_lakh' => $fundUtilTot !== null ? round($fundUtilTot, 2) : null,
                'fund_req_cs_lakh'         => $fundReqCs !== null ? round($fundReqCs, 2) : null,
                'fund_req_ss_lakh'         => $fundReqSs !== null ? round($fundReqSs, 2) : null,
                'fund_req_total_lakh'      => $fundReqTot !== null ? round($fundReqTot, 2) : null,
                'central_share_pct'        => 90,
                'state_share_pct'          => 10,
                'project_type'             => 'Flood Management',
                'state'                    => $state,
                'river_basin'              => $riverBasin,
                'is_active'                => true,
                'is_duplicate'             => $isDuplicate,
                'duplicate_reason'         => $duplicateReason,
                'matched_code'             => $matchedCode,
            ];
        }

        return $parsedRows;
    }

    /**
     * Import or update parsed scheme rows into the database.
     * Guarantees that schemes with existing names or codes are NEVER re-inserted as duplicates.
     */
    public function importRows(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $processedCleanNames = [];
        $processedCodes = [];

        // Load all existing schemes to avoid duplicate queries
        $allSchemes = Scheme::all();

        foreach ($rows as $item) {
            $rawName = trim($item['scheme_name'] ?? '');
            $rawCode = trim($item['scheme_code'] ?? '');

            if (empty($rawName) && empty($rawCode)) {
                continue;
            }

            $cleanName = self::cleanKey($rawName);
            $cleanCode = strtolower($rawCode);

            // 1. Check if scheme already exists in database: Primary unique key is scheme_code
            $existingScheme = null;

            if (!empty($rawCode)) {
                // Look up by scheme_code first
                $existingScheme = $allSchemes->first(function ($s) use ($cleanCode) {
                    return strtolower(trim($s->scheme_code)) === $cleanCode;
                });
            } elseif (!empty($cleanName)) {
                // Fallback only if code was not provided
                $existingScheme = $allSchemes->first(function ($s) use ($cleanName) {
                    return SchemeExcelService::cleanKey($s->scheme_name) === $cleanName;
                });
            }

            // 2. Check if this is a repeated row in the same uploaded Excel file
            $isBatchDuplicate = false;
            if (!empty($cleanCode)) {
                if (in_array($cleanCode, $processedCodes, true)) {
                    $isBatchDuplicate = true;
                }
            } elseif (!empty($cleanName) && in_array($cleanName, $processedCleanNames, true)) {
                $isBatchDuplicate = true;
            }

            $data = [
                'scheme_code'              => $rawCode ?: ($existingScheme ? $existingScheme->scheme_code : ('SCH-' . strtoupper(uniqid()))),
                'scheme_name'              => $rawName ?: ($existingScheme ? $existingScheme->scheme_name : 'Untitled Scheme'),
                'division'                 => $item['division'] ?? null,
                'district'                 => $item['district'] ?? $item['division'] ?? null,
                'state'                    => $item['state'] ?? 'Assam',
                'river_basin'              => $item['river_basin'] ?? 'Brahmaputra',
                'plan_period'              => $item['plan_period'] ?? 'XI Plan',
                'estimated_cost_lakh'      => $item['estimated_cost_lakh'] ?? 0,
                'sanctioned_amount_cr'     => $item['sanctioned_amount_cr'] ?? round(($item['estimated_cost_lakh'] ?? 0) / 100, 2),
                'fund_utilised_cs_lakh'    => $item['fund_utilised_cs_lakh'] ?? null,
                'fund_utilised_ss_lakh'    => $item['fund_utilised_ss_lakh'] ?? null,
                'fund_utilised_total_lakh' => $item['fund_utilised_total_lakh'] ?? null,
                'fund_req_cs_lakh'         => $item['fund_req_cs_lakh'] ?? null,
                'fund_req_ss_lakh'         => $item['fund_req_ss_lakh'] ?? null,
                'fund_req_total_lakh'      => $item['fund_req_total_lakh'] ?? null,
                'physical_status'          => $item['physical_status'] ?? 'Ongoing',
                'physical_progress_pct'    => $item['physical_progress_pct'] ?? 0,
                'central_share_pct'        => $item['central_share_pct'] ?? 90,
                'state_share_pct'          => $item['state_share_pct'] ?? 10,
                'project_type'             => $item['project_type'] ?? 'Flood Management',
                'is_active'                => true,
            ];

            if ($existingScheme) {
                // DO NOT INSERT AGAIN. Update the existing scheme in-place to keep data fresh without duplicate rows.
                $existingScheme->update($data);
                $updated++;

                if (!empty($cleanName)) $processedCleanNames[] = $cleanName;
                if (!empty($cleanCode)) $processedCodes[] = $cleanCode;
                if (!empty($existingScheme->scheme_code)) {
                    $processedCodes[] = strtolower(trim($existingScheme->scheme_code));
                }
            } elseif ($isBatchDuplicate) {
                // Repeated row inside this same uploaded file -> skip insert to avoid duplicacy
                $skipped++;
            } else {
                // Truly new scheme -> Enter as a fresh scheme into the portal
                $data['scheme_code'] = !empty($rawCode) ? $rawCode : ('SCH-' . strtoupper(uniqid()));
                $newScheme = Scheme::create($data);
                $allSchemes->push($newScheme);
                $created++;

                if (!empty($cleanName)) $processedCleanNames[] = $cleanName;
                if (!empty($cleanCode)) $processedCodes[] = $cleanCode;
                if (!empty($newScheme->scheme_code)) {
                    $processedCodes[] = strtolower(trim($newScheme->scheme_code));
                }
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total'   => count($rows),
        ];
    }
}
