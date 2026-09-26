<?php

require_once __DIR__ . '/../services/TrackerCalculationService.php';

class SalesActivityReportController extends Controller
{
    protected $trackerService;
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (!isset($_SESSION['user'])) {
            header('Location: /');
            exit;
        }
        $this->trackerService = new TrackerCalculationService($this->db);
    }

    public function index()
    {
        $loggedInUser = $_SESSION['user'];

        // Default sales cycle: day >= 26 advances to the next month's cycle
        $defaultYear = (int)date('Y');
        $defaultMonth = (int)date('m');
        if ((int)date('d') >= 26) {
            $defaultMonth++;
            if ($defaultMonth > 12) {
                $defaultMonth = 1;
                $defaultYear++;
            }
        }

        // Get filter inputs
        $year = isset($_GET['year']) ? (int)$_GET['year'] : $defaultYear;
        $month = isset($_GET['month']) ? (int)$_GET['month'] : $defaultMonth;
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $teamId = isset($_GET['team_id']) && $_GET['team_id'] !== '' && $_GET['team_id'] !== 'all' ? (int)$_GET['team_id'] : null;
        
        $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

        // Monthly Data (pass teamId for filtering)
        $monthlyData = $this->trackerService->getTrackerReport($year, $month, $loggedInUser, null, null, $teamId);
        
        // Custom Sorting for Tables: Branch -> Team Name -> Rank
        $sortForTables = function($a, $b) {
            $getBranchPriority = function($bName) {
                $bName = strtolower($bName);
                if (str_contains($bName, 'ashulia')) return 1;
                if (str_contains($bName, 'gulshan')) return 2;
                if (str_contains($bName, 'ati')) return 3;
                return 4;
            };
            
            $pA = $getBranchPriority($a['branch_name'] ?? '');
            $pB = $getBranchPriority($b['branch_name'] ?? '');
            if ($pA !== $pB) return $pA <=> $pB;
            
            $tA = $a['team_name'] ?? '';
            $tB = $b['team_name'] ?? '';
            if ($tA !== $tB) {
                if (strcasecmp($tA, 'General Team') === 0) return 1;
                if (strcasecmp($tB, 'General Team') === 0) return -1;
                return strcmp($tA, $tB);
            }
            
            return ($a['rank'] ?? 999) <=> ($b['rank'] ?? 999);
        };

        // Table specific sorted arrays for Monthly
        $tableInds = $monthlyData['individuals'] ?? [];
        usort($tableInds, $sortForTables);
        $monthlyData['table_individuals'] = $tableInds;

        // Daily Data (only query when daily tab is active to optimize speed and clean up requests)
        $dailyData = [];
        if ($activeTab === 'daily') {
            $dailyData = $this->trackerService->getDailyReport($date, $loggedInUser, $teamId);
            usort($dailyData, $sortForTables);
        }

        // Sort Teams array by branch then team name
        $teamsArray = $monthlyData['teams'] ?? [];
        usort($teamsArray, function($a, $b) use ($sortForTables) {
            // We use the same priority logic for teams, assuming branch_name is set for teams in service
            $getBranchPriority = function($bName) {
                $bName = strtolower($bName);
                if (str_contains($bName, 'ashulia')) return 1;
                if (str_contains($bName, 'gulshan')) return 2;
                if (str_contains($bName, 'ati')) return 3;
                return 4;
            };
            
            $pA = $getBranchPriority($a['branch_name'] ?? '');
            $pB = $getBranchPriority($b['branch_name'] ?? '');
            if ($pA !== $pB) return $pA <=> $pB;
            
            $tA = $a['team_name'] ?? '';
            $tB = $b['team_name'] ?? '';
            if ($tA !== $tB) {
                if (strcasecmp($tA, 'General Team') === 0) return 1;
                if (strcasecmp($tB, 'General Team') === 0) return -1;
                return strcmp($tA, $tB);
            }
            return 0;
        });
        $monthlyData['teams'] = $teamsArray;

        // Fetch all active teams for dropdown filter
        $teamsRes = $this->db->query("SELECT id, name FROM teams ORDER BY name ASC");
        $teams = $teamsRes ? $teamsRes->fetch_all(MYSQLI_ASSOC) : [];

        // Check if Export requested
        if (isset($_GET['export'])) {
            $exportType = strtolower(trim($_GET['export']));
            if ($exportType === 'excel') {
                $this->exportExcel($activeTab, $monthlyData, $dailyData, $year, $month, $date);
            } elseif ($exportType === 'pdf') {
                $this->exportPdf($activeTab, $monthlyData, $dailyData, $year, $month, $date);
            }
        }

        $this->view('sales-activity-report/index', [
            'monthlyData'    => $monthlyData,
            'dailyData'      => $dailyData,
            'year'           => $year,
            'month'          => $month,
            'date'           => $date,
            'selectedTeamId' => $teamId,
            'teams'          => $teams,
            'activeTab'      => $activeTab
        ]);
    }

    /**
     * Export report data to Excel (.xlsx)
     */
    private function exportExcel($tab, $monthlyData, $dailyData, $year, $month, $date)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $periodText = !empty($monthlyData['period']) 
            ? date('d M, Y', strtotime($monthlyData['period']['start'])) . ' to ' . date('d M, Y', strtotime($monthlyData['period']['end']))
            : "Month: {$month}, Year: {$year}";
            
        $dailyDateText = date('d M, Y', strtotime($date));

        // Company Title
        $sheet->setCellValue('A1', 'THE PREMIUM HOMES LTD');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('1F4E79');

        $headers = [];
        $dataRows = [];
        $reportTitle = '';
        $subTitle = '';
        $filenamePrefix = '';

        if ($tab === 'target-vs-achievement') {
            $reportTitle = 'Sales Activity Report - Target Vs Achievement (8-Pillar Scorecard)';
            $subTitle = 'Period: ' . $periodText;
            $filenamePrefix = "Sales_Target_Vs_Achievement_{$year}_{$month}";
            $headers = [
                'Rank', 'Member Name', 'Designation', 'Team Name',
                'Vol Target (Tk)', 'Vol Achieved (Tk)', 'Vol Ach %', 'Rev Score (/30)',
                'Min 20% Met', 'Val Score (/20)',
                'Unit Target', 'Units Sold', 'Unit Ach %', 'Unit Score (/10)',
                'Visit Target', 'Visits Done', 'Visit Ach %', 'Visit Score (/10)',
                'Assigned Leads', 'CVR %', 'CVR Score (/10)',
                'Contacted %', 'Followed %', 'Conn Score (/10)',
                'Quality Leads', 'Quality %', 'Quality Score (/5)',
                'HR Score (/5)',
                'Total Score (/100)', 'Grade'
            ];
            
            $inds = $monthlyData['scorecard_individuals'] ?? ($monthlyData['individuals'] ?? []);
            foreach ($inds as $emp) {
                $sc = $emp['scorecard'] ?? [];
                $dataRows[] = [
                    '#' . ($sc['rank'] ?? ($emp['rank'] ?? '-')),
                    $emp['member_name'] ?? '',
                    $emp['level_designation'] ?? '',
                    $emp['team_name'] ?? '',
                    number_format((float)($emp['volume_target'] ?? 0), 2),
                    number_format((float)($emp['total_volume_achieved'] ?? 0), 2),
                    ($emp['volume_ach_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar1_score'] ?? 0), 2) . ' / 30',
                    !empty($sc['min_20_met']) ? 'Yes' : 'No',
                    number_format((float)($sc['pillar2_score'] ?? 0), 2) . ' / 20',
                    $emp['unit_target'] ?? 0,
                    $emp['units_sold'] ?? 0,
                    ($emp['unit_ach_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar3_score'] ?? 0), 2) . ' / 10',
                    $emp['visit_target'] ?? 0,
                    $emp['visited'] ?? 0,
                    ($emp['visit_ach_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar4_score'] ?? 0), 2) . ' / 10',
                    $emp['assigned_leads'] ?? 0,
                    ($sc['cvr_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar5_score'] ?? 0), 2) . ' / 10',
                    ($sc['contacted_ratio_pct'] ?? 0) . '%',
                    ($sc['followed_ratio_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar6_score'] ?? 0), 2) . ' / 10',
                    $sc['quality_leads'] ?? 0,
                    ($sc['quality_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar7_score'] ?? 0), 2) . ' / 5',
                    number_format((float)($sc['pillar8_score'] ?? 0), 2) . ' / 5',
                    number_format((float)($sc['total_score'] ?? 0), 2) . ' %',
                    $sc['grade'] ?? 'N/A'
                ];
            }
        } elseif ($tab === 'dashboard') {
            $reportTitle = 'Sales Activity Report - Ranking Dashboard (Top Performers)';
            $subTitle = 'Period: ' . $periodText;
            $filenamePrefix = "Sales_Ranking_Dashboard_{$year}_{$month}";
            $headers = ['Rank', 'Member Name', 'Designation', 'Team Name', 'Total Score', 'Achieved Volume (Tk)', 'Follow Up Rate (%)', 'Assigned Leads', 'Total Follow Up', 'Total Contacted', 'Site Visits', 'Units Sold'];
            
            $inds = $monthlyData['individuals'] ?? [];
            foreach ($inds as $emp) {
                $dataRows[] = [
                    '#' . ($emp['rank'] ?? '-'),
                    $emp['member_name'] ?? '',
                    $emp['level_designation'] ?? '',
                    $emp['team_name'] ?? '',
                    number_format((float)($emp['performance_score'] ?? 0), 2),
                    number_format((float)($emp['total_volume_achieved'] ?? 0), 2),
                    ($emp['follow_up_pct'] ?? 0) . '%',
                    $emp['assigned_leads'] ?? 0,
                    $emp['total_follow_up'] ?? 0,
                    $emp['total_contacted'] ?? 0,
                    $emp['visited'] ?? 0,
                    $emp['units_sold'] ?? 0
                ];
            }
        } elseif ($tab === 'monthly') {
            $reportTitle = 'Sales Activity Report - Monthly Team Summary';
            $subTitle = 'Period: ' . $periodText;
            $filenamePrefix = "Sales_Monthly_Team_Summary_{$year}_{$month}";
            $headers = [
                'Rank', 'Branch', 'Team Name', 'Member Name', 'Designation', 
                'Unit Target', 'Volume Target (Tk)', 'Visit Target', 
                'Assigned Leads', 'Follow Up', 'Follow Up %', 'Contacted', 'Contacted %', 'Interested', 
                'Visits Done', 'Visit Ach %', 
                'Units Sold', 'Unit Ach %', 'Volume Assigned (Tk)', 'Volume Unassigned (Tk)', 'Total Volume Achieved (Tk)', 'Perf. Score'
            ];
            
            $inds = $monthlyData['table_individuals'] ?? ($monthlyData['individuals'] ?? []);
            foreach ($inds as $emp) {
                $dataRows[] = [
                    '#' . ($emp['rank'] ?? '-'),
                    $emp['branch_name'] ?? 'Other',
                    $emp['team_name'] ?? '',
                    $emp['member_name'] ?? '',
                    $emp['level_designation'] ?? '',
                    $emp['unit_target'] ?? 0,
                    number_format((float)($emp['volume_target'] ?? 0), 2),
                    $emp['visit_target'] ?? 0,
                    $emp['assigned_leads'] ?? 0,
                    $emp['total_follow_up'] ?? 0,
                    ($emp['follow_up_pct'] ?? 0) . '%',
                    $emp['total_contacted'] ?? 0,
                    ($emp['contacted_pct'] ?? 0) . '%',
                    $emp['interested'] ?? 0,
                    $emp['visited'] ?? 0,
                    ($emp['visit_ach_pct'] ?? 0) . '%',
                    $emp['units_sold'] ?? 0,
                    ($emp['unit_ach_pct'] ?? 0) . '%',
                    number_format((float)($emp['volume_assigned'] ?? 0), 2),
                    number_format((float)($emp['volume_unassigned'] ?? 0), 2),
                    number_format((float)($emp['total_volume_achieved'] ?? 0), 2),
                    number_format((float)($emp['performance_score'] ?? 0), 2)
                ];
            }
        } elseif ($tab === 'all-team') {
            $reportTitle = 'Sales Activity Report - All Team Report';
            $subTitle = 'Period: ' . $periodText;
            $filenamePrefix = "Sales_All_Team_Report_{$year}_{$month}";
            $headers = [
                'Rank', 'Branch', 'Team Name', 'Total Leads', 'Follow Up', 'Follow Up %', 
                'Contacted', 'Contacted %', 'Not Contacted', 'Interested', 'Not Interested', 
                'Visits Done', 'Visit Target', 'Visit Ach %', 
                'Units Sold', 'Volume Target (Tk)', 'Volume Achieved (Tk)', 'Volume Ach %', 'Team Score'
            ];
            
            $teams = $monthlyData['teams'] ?? [];
            foreach ($teams as $t) {
                $dataRows[] = [
                    '#' . ($t['rank'] ?? '-'),
                    $t['branch_name'] ?? 'Other',
                    $t['team_name'] ?? '',
                    $t['total_leads'] ?? 0,
                    $t['total_follow_up'] ?? 0,
                    ($t['follow_up_pct'] ?? 0) . '%',
                    $t['total_contacted'] ?? 0,
                    ($t['contacted_pct'] ?? 0) . '%',
                    $t['total_not_contacted'] ?? 0,
                    $t['interested'] ?? 0,
                    $t['not_interested'] ?? 0,
                    $t['visited'] ?? 0,
                    $t['visit_target'] ?? 0,
                    ($t['visit_ach_pct'] ?? 0) . '%',
                    $t['units_sold'] ?? 0,
                    number_format((float)($t['volume_target'] ?? 0), 2),
                    number_format((float)($t['total_volume_achieved'] ?? 0), 2),
                    ($t['volume_ach_pct'] ?? 0) . '%',
                    number_format((float)($t['performance_score'] ?? 0), 2)
                ];
            }
        } else { // daily
            $reportTitle = 'Sales Activity Report - Daily Activity Report';
            $subTitle = 'Specific Date: ' . $dailyDateText;
            $filenamePrefix = "Sales_Daily_Report_{$date}";
            $headers = [
                'SL', 'Branch', 'Member Name', 'Designation', 'Team Name', 
                'Leads Assigned Today', 'Followed Up Today', 'Contacted Today', 
                'Interested', 'Not Interested', 'Will Visit Today', 'Visited Today', 'Units Sold Today', 'Volume Achieved Today (Tk)'
            ];
            
            $daily = $dailyData ?? [];
            $sl = 1;
            foreach ($daily as $emp) {
                $dataRows[] = [
                    $sl++,
                    $emp['branch_name'] ?? 'Other',
                    $emp['member_name'] ?? '',
                    $emp['level_designation'] ?? '',
                    $emp['team_name'] ?? '',
                    $emp['assigned_leads'] ?? 0,
                    $emp['total_follow_up'] ?? 0,
                    $emp['total_contacted'] ?? 0,
                    $emp['interested'] ?? 0,
                    $emp['not_interested'] ?? 0,
                    $emp['will_visit'] ?? 0,
                    $emp['visited'] ?? 0,
                    $emp['units_sold'] ?? 0,
                    number_format((float)($emp['total_volume_achieved'] ?? 0), 2)
                ];
            }
        }

        $sheet->setCellValue('A2', $reportTitle);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A3', $subTitle . ' | Exported on: ' . date('d M Y, h:i A'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('555555');

        // Headers row (Row 5)
        $headerRow = 5;
        $totalCols = count($headers);
        for ($i = 0; $i < $totalCols; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($colLetter . $headerRow, $headers[$i]);
        }

        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);
        $headerRange = 'A5:' . $lastColLetter . '5';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(25);

        // Data rows
        $currentRow = 6;
        foreach ($dataRows as $row) {
            $colIdx = 1;
            foreach ($row as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue($colLetter . $currentRow, $val);
                $colIdx++;
            }
            if ($currentRow % 2 == 0) {
                $rowRange = 'A' . $currentRow . ':' . $lastColLetter . $currentRow;
                $sheet->getStyle($rowRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }
            $currentRow++;
        }

        $lastDataRow = $currentRow - 1;
        if (!empty($dataRows)) {
            $fullTableRange = 'A5:' . $lastColLetter . $lastDataRow;
            $sheet->getStyle($fullTableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('D9D9D9');
        }

        for ($c = 1; $c <= $totalCols; $c++) {
            $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colString)->setAutoSize(true);
        }

        $filename = $filenamePrefix . '_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export report data to PDF
     */
    private function exportPdf($tab, $monthlyData, $dailyData, $year, $month, $date)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $periodText = !empty($monthlyData['period']) 
            ? date('d M, Y', strtotime($monthlyData['period']['start'])) . ' to ' . date('d M, Y', strtotime($monthlyData['period']['end']))
            : "Month: {$month}, Year: {$year}";
            
        $dailyDateText = date('d M, Y', strtotime($date));

        $headers = [];
        $dataRows = [];
        $reportTitle = '';
        $subTitle = '';
        $filenamePrefix = '';

        if ($tab === 'target-vs-achievement') {
            $reportTitle = 'Sales Activity Report - Target Vs Achievement (8-Pillar Scorecard)';
            $subTitle = 'Period: ' . $periodText;
            $filenamePrefix = "Sales_Target_Vs_Achievement_{$year}_{$month}";
            $headers = [
                'Rank', 'Member Name', 'Designation', 'Team Name',
                'Vol Tgt (Tk)', 'Vol Ach (Tk)', 'Vol %', 'Rev (/30)',
                'Min 20%', 'Val (/20)',
                'Unit Tgt', 'Units', 'Unit %', 'Unit (/10)',
                'Visit Tgt', 'Visits', 'Visit %', 'Visit (/10)',
                'Leads', 'CVR %', 'CVR (/10)',
                'Cont %', 'Foll %', 'Conn (/10)',
                'Qual Leads', 'Qual (/5)', 'HR (/5)',
                'Total (/100)', 'Grade'
            ];
            
            $inds = $monthlyData['scorecard_individuals'] ?? ($monthlyData['individuals'] ?? []);
            foreach ($inds as $emp) {
                $sc = $emp['scorecard'] ?? [];
                $dataRows[] = [
                    '#' . ($sc['rank'] ?? ($emp['rank'] ?? '-')),
                    $emp['member_name'] ?? '',
                    $emp['level_designation'] ?? '',
                    $emp['team_name'] ?? '',
                    number_format((float)($emp['volume_target'] ?? 0), 0),
                    number_format((float)($emp['total_volume_achieved'] ?? 0), 0),
                    ($emp['volume_ach_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar1_score'] ?? 0), 1),
                    !empty($sc['min_20_met']) ? 'Yes' : 'No',
                    number_format((float)($sc['pillar2_score'] ?? 0), 1),
                    $emp['unit_target'] ?? 0,
                    $emp['units_sold'] ?? 0,
                    ($emp['unit_ach_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar3_score'] ?? 0), 1),
                    $emp['visit_target'] ?? 0,
                    $emp['visited'] ?? 0,
                    ($emp['visit_ach_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar4_score'] ?? 0), 1),
                    $emp['assigned_leads'] ?? 0,
                    ($sc['cvr_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar5_score'] ?? 0), 1),
                    ($sc['contacted_ratio_pct'] ?? 0) . '%',
                    ($sc['followed_ratio_pct'] ?? 0) . '%',
                    number_format((float)($sc['pillar6_score'] ?? 0), 1),
                    $sc['quality_leads'] ?? 0,
                    number_format((float)($sc['pillar7_score'] ?? 0), 1),
                    number_format((float)($sc['pillar8_score'] ?? 0), 1),
                    number_format((float)($sc['total_score'] ?? 0), 1) . '%',
                    $sc['grade'] ?? 'N/A'
                ];
            }
        } elseif ($tab === 'dashboard') {
            $reportTitle = 'Sales Activity Report - Ranking Dashboard (Top Performers)';
            $subTitle = 'Period: ' . $periodText;
            $filenamePrefix = "Sales_Ranking_Dashboard_{$year}_{$month}";
            $headers = ['Rank', 'Member Name', 'Designation', 'Team Name', 'Total Score', 'Achieved Volume (Tk)', 'Follow Up Rate (%)', 'Assigned Leads', 'Total Follow Up', 'Contacted', 'Visits', 'Units Sold'];
            
            $inds = $monthlyData['individuals'] ?? [];
            foreach ($inds as $emp) {
                $dataRows[] = [
                    '#' . ($emp['rank'] ?? '-'),
                    $emp['member_name'] ?? '',
                    $emp['level_designation'] ?? '',
                    $emp['team_name'] ?? '',
                    number_format((float)($emp['performance_score'] ?? 0), 2),
                    number_format((float)($emp['total_volume_achieved'] ?? 0), 2),
                    ($emp['follow_up_pct'] ?? 0) . '%',
                    $emp['assigned_leads'] ?? 0,
                    $emp['total_follow_up'] ?? 0,
                    $emp['total_contacted'] ?? 0,
                    $emp['visited'] ?? 0,
                    $emp['units_sold'] ?? 0
                ];
            }
        } elseif ($tab === 'monthly') {
            $reportTitle = 'Sales Activity Report - Monthly Team Summary';
            $subTitle = 'Period: ' . $periodText;
            $filenamePrefix = "Sales_Monthly_Team_Summary_{$year}_{$month}";
            $headers = [
                'Rank', 'Branch', 'Team', 'Member Name', 'Designation', 
                'Unit Tgt', 'Vol Tgt', 'Visit Tgt', 
                'Assigned', 'F.Up', 'F.Up%', 'Contact', 'Contact%', 'Interest', 
                'Visits', 'Visit%', 
                'Sold', 'Sold%', 'Assigned Vol', 'Unassigned Vol', 'Total Vol Achieved', 'Perf Score'
            ];
            
            $inds = $monthlyData['table_individuals'] ?? ($monthlyData['individuals'] ?? []);
            foreach ($inds as $emp) {
                $dataRows[] = [
                    '#' . ($emp['rank'] ?? '-'),
                    $emp['branch_name'] ?? 'Other',
                    $emp['team_name'] ?? '',
                    $emp['member_name'] ?? '',
                    $emp['level_designation'] ?? '',
                    $emp['unit_target'] ?? 0,
                    number_format((float)($emp['volume_target'] ?? 0)),
                    $emp['visit_target'] ?? 0,
                    $emp['assigned_leads'] ?? 0,
                    $emp['total_follow_up'] ?? 0,
                    ($emp['follow_up_pct'] ?? 0) . '%',
                    $emp['total_contacted'] ?? 0,
                    ($emp['contacted_pct'] ?? 0) . '%',
                    $emp['interested'] ?? 0,
                    $emp['visited'] ?? 0,
                    ($emp['visit_ach_pct'] ?? 0) . '%',
                    $emp['units_sold'] ?? 0,
                    ($emp['unit_ach_pct'] ?? 0) . '%',
                    number_format((float)($emp['volume_assigned'] ?? 0)),
                    number_format((float)($emp['volume_unassigned'] ?? 0)),
                    number_format((float)($emp['total_volume_achieved'] ?? 0)),
                    number_format((float)($emp['performance_score'] ?? 0), 2)
                ];
            }
        } elseif ($tab === 'all-team') {
            $reportTitle = 'Sales Activity Report - All Team Report';
            $subTitle = 'Period: ' . $periodText;
            $filenamePrefix = "Sales_All_Team_Report_{$year}_{$month}";
            $headers = [
                'Rank', 'Branch', 'Team Name', 'Total Leads', 'Follow Up', 'Follow Up %', 
                'Contacted', 'Contacted %', 'Not Contacted', 'Interested', 'Not Interested', 
                'Visits (Ach/Tgt)', 'Sold Units', 'Volume Target (Tk)', 'Volume Achieved (Tk)', 'Vol Ach %', 'Score'
            ];
            
            $teams = $monthlyData['teams'] ?? [];
            foreach ($teams as $t) {
                $dataRows[] = [
                    '#' . ($t['rank'] ?? '-'),
                    $t['branch_name'] ?? 'Other',
                    $t['team_name'] ?? '',
                    $t['total_leads'] ?? 0,
                    $t['total_follow_up'] ?? 0,
                    ($t['follow_up_pct'] ?? 0) . '%',
                    $t['total_contacted'] ?? 0,
                    ($t['contacted_pct'] ?? 0) . '%',
                    $t['total_not_contacted'] ?? 0,
                    $t['interested'] ?? 0,
                    $t['not_interested'] ?? 0,
                    ($t['visited'] ?? 0) . ' / ' . ($t['visit_target'] ?? 0) . ' (' . ($t['visit_ach_pct'] ?? 0) . '%)',
                    $t['units_sold'] ?? 0,
                    number_format((float)($t['volume_target'] ?? 0)),
                    number_format((float)($t['total_volume_achieved'] ?? 0)),
                    ($t['volume_ach_pct'] ?? 0) . '%',
                    number_format((float)($t['performance_score'] ?? 0), 2)
                ];
            }
        } else { // daily
            $reportTitle = 'Sales Activity Report - Daily Activity Report';
            $subTitle = 'Specific Date: ' . $dailyDateText;
            $filenamePrefix = "Sales_Daily_Report_{$date}";
            $headers = [
                'SL', 'Branch', 'Member Name', 'Designation', 'Team Name', 
                'Assigned Today', 'Followed Up', 'Contacted', 
                'Interested', 'Not Interested', 'Will Visit', 'Visited', 'Sold Units', 'Achieved Volume (Tk)'
            ];
            
            $daily = $dailyData ?? [];
            $sl = 1;
            foreach ($daily as $emp) {
                $dataRows[] = [
                    $sl++,
                    $emp['branch_name'] ?? 'Other',
                    $emp['member_name'] ?? '',
                    $emp['level_designation'] ?? '',
                    $emp['team_name'] ?? '',
                    $emp['assigned_leads'] ?? 0,
                    $emp['total_follow_up'] ?? 0,
                    $emp['total_contacted'] ?? 0,
                    $emp['interested'] ?? 0,
                    $emp['not_interested'] ?? 0,
                    $emp['will_visit'] ?? 0,
                    $emp['visited'] ?? 0,
                    $emp['units_sold'] ?? 0,
                    number_format((float)($emp['total_volume_achieved'] ?? 0), 2)
                ];
            }
        }

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($reportTitle) . '</title>
            <style>
                @page { margin: 16px 18px; size: A4 landscape; }
                body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; font-size: 8.5px; color: #2d3748; line-height: 1.3; }
                .report-header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #1F4E79; padding-bottom: 6px; }
                .company-name { font-size: 15px; font-weight: bold; color: #1F4E79; margin: 0 0 2px 0; letter-spacing: 0.5px; text-transform: uppercase; }
                .report-title { font-size: 11px; font-weight: bold; color: #2d3748; margin: 0 0 3px 0; }
                .meta-info { font-size: 8.5px; color: #718096; margin: 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 7.5px; }
                th, td { border: 1px solid #cbd5e0; padding: 3.5px 4.5px; text-align: center; word-break: break-word; }
                th { background-color: #1F4E79; color: #ffffff; font-weight: bold; text-transform: uppercase; font-size: 7.5px; }
                tr:nth-child(even) { background-color: #f8fafc; }
                .text-start { text-align: left !important; }
                .text-end { text-align: right !important; }
                .fw-bold { font-weight: bold; }
                .footer { margin-top: 10px; font-size: 7.5px; color: #a0aec0; text-align: right; }
            </style>
        </head>
        <body>
            <div class="report-header">
                <div class="company-name">THE PREMIUM HOMES LTD</div>
                <div class="report-title">' . htmlspecialchars($reportTitle) . '</div>
                <div class="meta-info">' . htmlspecialchars($subTitle) . ' &nbsp;|&nbsp; Generated on: ' . date('d M Y, h:i A') . '</div>
            </div>
            <table>
                <thead>
                    <tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . htmlspecialchars($h) . '</th>';
        }
        $html .= '  </tr>
                </thead>
                <tbody>';

        if (!empty($dataRows)) {
            foreach ($dataRows as $row) {
                $html .= '<tr>';
                $colIdx = 0;
                foreach ($row as $val) {
                    $class = '';
                    if ($colIdx === 1 || ($tab === 'monthly' && ($colIdx === 1 || $colIdx === 2))) {
                        $class = ' class="text-start"';
                    }
                    $html .= '<td' . $class . '>' . htmlspecialchars($val) . '</td>';
                    $colIdx++;
                }
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="' . count($headers) . '" style="text-align: center; padding: 15px; color: #718096;">No records found for the selected period.</td></tr>';
        }

        $html .= '  </tbody>
            </table>
            <div class="footer">
                Printed from TPHL ERP System &copy; ' . date('Y') . ' The Premium Homes Ltd. All rights reserved.
            </div>
        </body>
        </html>';

        $dompdf = new \Dompdf\Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = $filenamePrefix . '_' . date('Ymd_His') . '.pdf';
        $dompdf->stream($filename, ["Attachment" => true]);
        exit;
    }

    /**
     * Dedicated Team Performance Action
     */
    public function teamPerformance()
    {
        $loggedInUser = $_SESSION['user'];

        // Default sales cycle: day >= 26 advances to the next month's cycle
        $defaultYear = (int)date('Y');
        $defaultMonth = (int)date('m');
        if ((int)date('d') >= 26) {
            $defaultMonth++;
            if ($defaultMonth > 12) {
                $defaultMonth = 1;
                $defaultYear++;
            }
        }

        // Get filter inputs
        $year     = isset($_GET['year']) ? (int)$_GET['year'] : $defaultYear;
        $month    = isset($_GET['month']) ? (int)$_GET['month'] : $defaultMonth;
        $dateFrom = !empty($_GET['date_from']) ? trim($_GET['date_from']) : null;
        $dateTo   = !empty($_GET['date_to']) ? trim($_GET['date_to']) : null;
        $teamId   = isset($_GET['team_id']) && $_GET['team_id'] !== '' && $_GET['team_id'] !== 'all' ? (int)$_GET['team_id'] : null;

        // Check if export requested
        if (isset($_GET['export']) && strtolower(trim($_GET['export'])) === 'excel') {
            $this->exportTeamPerformanceExcel($year, $month, $loggedInUser, $dateFrom, $dateTo, $teamId);
            return;
        }

        // Fetch Team Performance Data (with teamId filter)
        $reportData = $this->trackerService->getTeamPerformanceReport($year, $month, $loggedInUser, $dateFrom, $dateTo, $teamId);

        $teamsRes = $this->db->query("SELECT id, name FROM teams ORDER BY name ASC");
        $teamsList = $teamsRes ? $teamsRes->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('sales-activity-report/team-performance', [
            'current_route' => 'team-performance',
            'reportData'    => $reportData,
            'teams'         => $reportData['teams'] ?? [],
            'allTeams'      => $teamsList,
            'selectedTeamId'=> $teamId,
            'kpis'          => $reportData['kpis'] ?? [],
            'period'        => $reportData['period'] ?? [],
            'year'          => $year,
            'month'         => $month,
            'dateFrom'      => $dateFrom,
            'dateTo'        => $dateTo,
            'loggedInUser'  => $loggedInUser
        ]);
    }

    /**
     * Export Team Performance to Excel
     */
    public function exportTeamPerformanceExcel($year, $month, $loggedInUser, $dateFrom = null, $dateTo = null, $teamId = null)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $reportData = $this->trackerService->getTeamPerformanceReport($year, $month, $loggedInUser, $dateFrom, $dateTo, $teamId);
        $teams = $reportData['teams'] ?? [];
        $period = $reportData['period'] ?? [];

        $periodText = !empty($period['start']) && !empty($period['end']) 
            ? "{$period['start']} to {$period['end']}"
            : "Month: {$month}, Year: {$year}";

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Company Title
        $sheet->setCellValue('A1', 'THE PREMIUM HOMES LTD');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('166534');

        $sheet->setCellValue('A2', 'Sales Department - Team Performance Summary Report');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A3', 'Period: ' . $periodText . ' | Exported on: ' . date('d M Y, h:i A'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('555555');

        $headers = [
            'Rank',
            'Team Name',
            'Members',
            'Assigned Leads',
            'Total Calls',
            'Contacted',
            'Not Contacted',
            'Contacted %',
            'Visit Target',
            'Visited Ach.',
            'Visit Ach %',
            'Unit Target',
            'Units Booked',
            'Unit Ach %',
            'Volume Target (Tk)',
            'Volume Achieved (Tk)',
            'Volume Ach %',
            'Performance Score'
        ];

        $headerRow = 5;
        $totalCols = count($headers);
        for ($i = 0; $i < $totalCols; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($colLetter . $headerRow, $headers[$i]);
        }

        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);
        $headerRange = 'A5:' . $lastColLetter . '5';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '16A34A']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(26);

        $currentRow = 6;
        foreach ($teams as $t) {
            $rowVals = [
                '#' . ($t['rank'] ?? '-'),
                $t['team_name'] ?? '',
                $t['member_count'] ?? 0,
                $t['total_leads'] ?? 0,
                $t['total_follow_up_calls'] ?? 0,
                $t['total_contacted'] ?? 0,
                $t['total_not_contacted'] ?? 0,
                ($t['contacted_pct'] ?? 0) . '%',
                $t['visit_target'] ?? 0,
                $t['visited'] ?? 0,
                ($t['visit_ach_pct'] ?? 0) . '%',
                $t['unit_target'] ?? 0,
                $t['units_sold'] ?? 0,
                ($t['unit_ach_pct'] ?? 0) . '%',
                number_format((float)($t['volume_target'] ?? 0), 2),
                number_format((float)($t['total_volume_achieved'] ?? 0), 2),
                ($t['volume_ach_pct'] ?? 0) . '%',
                number_format((float)($t['performance_score'] ?? 0), 2)
            ];

            $colIdx = 1;
            foreach ($rowVals as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue($colLetter . $currentRow, $val);
                $colIdx++;
            }

            if ($currentRow % 2 == 0) {
                $rowRange = 'A' . $currentRow . ':' . $lastColLetter . $currentRow;
                $sheet->getStyle($rowRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F0FDF4');
            }
            $currentRow++;
        }

        $lastDataRow = $currentRow - 1;
        if (!empty($teams)) {
            $fullTableRange = 'A5:' . $lastColLetter . $lastDataRow;
            $sheet->getStyle($fullTableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('D9D9D9');
        }

        for ($c = 1; $c <= $totalCols; $c++) {
            $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colString)->setAutoSize(true);
        }

        $filename = "Team_Performance_Report_{$year}_{$month}_" . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
