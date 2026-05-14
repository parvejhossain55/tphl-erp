<?php
namespace Phase2;

use Con_ProjectLegalSheet;
use Controller;
use Project;

class Con_ProjectLegalSheetController extends Controller {
    protected $db;
    private $sheetModel;
    private $projectModel;

    public function __construct($db = null) {
        $this->db = $db;

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $this->sheetModel = new Con_ProjectLegalSheet($db);
        
        // Also need project model for dropdowns
        $this->projectModel = new Project($db);
    }

    public function index() {
        $sheets = $this->sheetModel->getAll();
        
        $this->view('phase2/project_legal_sheets/legal-sheet-lists', [
            'current_route' => 'legal-sheet-lists',
            'page_title' => 'Project Legal Sheets',
            'sheets' => $sheets
        ]);
    }

    private function getProjects() {
        $uid = (int)($_SESSION['user']['id'] ?? 0);
        $rid = (int)($_SESSION['user']['role_id'] ?? 0);

        if (in_array($rid, [1, 3, 22])) {
            // Admin / super user — see all projects
            $sql    = "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name";
            $result = $this->db->query($sql);
        } else {
            // Show projects where this user is assigned in ANY role
            $sql = "SELECT id, name FROM projects
                    WHERE deleted_at IS NULL
                      AND (
                          pm_id              = ? OR
                          site_engineer_id   = ? OR
                          landman_id         = ? OR
                          struct_engineer_id = ? OR
                          operation_head_id  = ? OR
                          draftsman_id       = ? OR
                          architect_id       = ? OR
                          estimator_id       = ? OR
                          qc_id              = ? OR
                          operator_id        = ? OR
                          created_by         = ?
                      )
                    ORDER BY name";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                    "iiiiiiiiiii",
                    $uid, $uid, $uid, $uid, $uid,
                    $uid, $uid, $uid, $uid, $uid, $uid
                );
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                // Fallback: show all if prepare fails
                $result = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name");
            }
        }

        if (!$result) {
            return []; // DB error — return empty array safely
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 16) {
            setError('Only Draftsman can add Legal Sheets.');
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }

        $projects = $this->getProjects();
        
        $this->view('phase2/project_legal_sheets/create-legal-sheet', [
            'current_route' => 'create-legal-sheet',
            'page_title' => 'Add New Legal Sheet',
            'projects' => $projects
        ]);
    }

    public function store() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 16) {
            setError('Only Draftsman can add Legal Sheets.');
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'project_id' => $_POST['project_id'] ?? 0,
                'sheet_type' => $_POST['sheet_type'] ?? '',
                'title' => $_POST['title'] ?? '',
                'version' => $_POST['version'] ?? 'V1',
                'link' => $_POST['link'] ?? '',
                'remark' => $_POST['remark'] ?? '',
                'submitted_by' => $_SESSION['user']['id']
            ];

            if (empty($data['project_id']) || empty($data['sheet_type']) || empty($data['title']) || empty($data['link'])) {
                setError('Please fill in all required fields.');
                header("Location: {$GLOBALS['base_url']}/create-legal-sheet");
                exit;
            }

            if ($this->sheetModel->create($data)) {
                setSuccess('Legal sheet added successfully.');
                header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            } else {
                setError('Failed to add legal sheet.');
                header("Location: {$GLOBALS['base_url']}/create-legal-sheet");
            }
            exit;
        }
    }

    public function edit($id) {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 16) {
            setError('Only Draftsman can edit Legal Sheets.');
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }

        $sheet = $this->sheetModel->getById($id);
        if (!$sheet) {
            setError('Sheet not found.');
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }
        
        $projects = $this->getProjects();

        $this->view('phase2/project_legal_sheets/edit-legal-sheet', [
            'current_route' => 'legal-sheet-lists',
            'page_title' => 'Edit Legal Sheet',
            'sheet' => $sheet,
            'projects' => $projects
        ]);
    }

    public function update($id) {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 16) {
            setError('Only Draftsman can edit Legal Sheets.');
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'project_id' => $_POST['project_id'] ?? 0,
                'sheet_type' => $_POST['sheet_type'] ?? '',
                'title' => $_POST['title'] ?? '',
                'version' => $_POST['version'] ?? 'V1',
                'link' => $_POST['link'] ?? '',
                'remark' => $_POST['remark'] ?? ''
            ];

            if ($this->sheetModel->update($id, $data)) {
                setSuccess('Legal sheet updated successfully.');
            } else {
                setError('Failed to update legal sheet.');
            }
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }
    }

    public function delete($id) {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 16) {
            setError('Only Draftsman can delete Legal Sheets.');
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }

        if ($this->sheetModel->delete($id)) {
            setSuccess('Legal sheet deleted successfully.');
        } else {
            setError('Failed to delete legal sheet.');
        }
        header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
        exit;
    }

    // ─── CSV Import ────────────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            setError('Only CSV files are allowed.');
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }

        if (($handle = fopen($fileTmp, "r")) === false) {
            setError('Unable to read CSV file.');
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }

        // ── Read & normalize header ────────────────────────────────────────
        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            setError('CSV file is empty or invalid.');
            fclose($handle);
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }

        // BOM strip + lowercase + spaces→underscores
        $rawHeader[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $rawHeader[0]);
        $header = array_map(function ($col) {
            return strtolower(trim(preg_replace('/\s+/', '_', $col)));
        }, $rawHeader);

        // ── Alias map for common column name variations ────────────────────
        $aliasMap = [
            'project_name'  => 'project_name',
            'project'       => 'project_name',
            'sheet_type'    => 'sheet_type',
            'type'          => 'sheet_type',
            'title'         => 'title',
            'version'       => 'version',
            'link'          => 'link',
            'url'           => 'link',
            'document_link' => 'link',
            'remark'        => 'remark',
            'remarks'       => 'remark',
            'note'          => 'remark',
            'notes'         => 'remark',
        ];

        $header = array_map(function ($col) use ($aliasMap) {
            return $aliasMap[$col] ?? $col;
        }, $header);

        // ── De-duplicate headers ───────────────────────────────────────────
        $seenHeaders = [];
        $dupCount    = [];
        $header = array_map(function ($col) use (&$seenHeaders, &$dupCount) {
            if (in_array($col, $seenHeaders, true)) {
                $dupCount[$col] = ($dupCount[$col] ?? 0) + 1;
                return $col . '_dup' . $dupCount[$col];
            }
            $seenHeaders[] = $col;
            return $col;
        }, $header);

        $successCount  = 0;
        $skippedRows   = [];
        $rowNum        = 1;  // header is row 1
        $projectsCache = [];
        $sessionUserId = $_SESSION['user']['id'] ?? 0;

        while (($row = fgetcsv($handle, 2000, ",")) !== false) {
            $rowNum++;

            // Skip fully empty rows
            if (count(array_filter($row)) === 0) continue;

            // Build partial data for error messages even on column-count mismatch
            $partialData = (count($header) === count($row))
                ? array_combine($header, $row)
                : array_combine(
                    array_slice($header, 0, count($row)),
                    $row
                );
            $errProject = trim($partialData['project_name'] ?? '—');
            $errTitle   = trim($partialData['title'] ?? '—');

            // Column count mismatch
            if (count($header) !== count($row)) {
                $reason = "Column count mismatch (expected " . count($header) . ", got " . count($row) . ")";
                $skippedRows[] = ['row' => $rowNum, 'project' => $errProject, 'title' => $errTitle, 'reason' => $reason];
                continue;
            }

            $data = $partialData;

            // ── Resolve project_id from project_name ───────────────────────
            $projectName = trim($data['project_name'] ?? '');
            if (empty($projectName)) {
                $skippedRows[] = [
                    'row'     => $rowNum,
                    'project' => '(empty)',
                    'title'   => $errTitle,
                    'reason'  => 'project_name is empty'
                ];
                continue;
            }

            $projectId = null;
            if (isset($projectsCache[$projectName])) {
                $projectId = $projectsCache[$projectName];
            } else {
                $stmt = $this->db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
                $stmt->bind_param("s", $projectName);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();

                if ($res) {
                    $projectId = $res['id'];
                    $projectsCache[$projectName] = $projectId;
                }
            }

            if (!$projectId) {
                $skippedRows[] = [
                    'row'     => $rowNum,
                    'project' => $projectName,
                    'title'   => $errTitle,
                    'reason'  => "Project not found in system"
                ];
                continue;
            }

            // ── Validate required fields ───────────────────────────────────
            $sheetType = trim($data['sheet_type'] ?? '');
            $title     = trim($data['title'] ?? '');
            $link      = trim($data['link'] ?? '');

            if (empty($sheetType)) {
                $skippedRows[] = ['row' => $rowNum, 'project' => $projectName, 'title' => $errTitle, 'reason' => 'sheet_type is empty'];
                continue;
            }
            if (empty($title)) {
                $skippedRows[] = ['row' => $rowNum, 'project' => $projectName, 'title' => '(empty)', 'reason' => 'title is empty'];
                continue;
            }
            if (empty($link)) {
                $skippedRows[] = ['row' => $rowNum, 'project' => $projectName, 'title' => $title, 'reason' => 'link is empty'];
                continue;
            }

            // ── Insert via model ───────────────────────────────────────────
            $insertData = [
                'project_id'   => $projectId,
                'sheet_type'   => $sheetType,
                'title'        => $title,
                'version'      => !empty(trim($data['version'] ?? '')) ? trim($data['version']) : 'V1',
                'link'         => $link,
                'submitted_by' => $sessionUserId,
                'remark'       => trim($data['remark'] ?? ''),
            ];

            try {
                if ($this->sheetModel->create($insertData)) {
                    $successCount++;
                } else {
                    $skippedRows[] = ['row' => $rowNum, 'project' => $projectName, 'title' => $title, 'reason' => 'DB insert failed'];
                }
            } catch (\Exception $e) {
                $skippedRows[] = ['row' => $rowNum, 'project' => $projectName, 'title' => $title, 'reason' => 'DB Error — ' . $e->getMessage()];
                error_log("Legal Sheet CSV Import Error (row $rowNum): " . $e->getMessage());
            }
        }

        fclose($handle);

        // Store skipped-row details in session for the view to render
        $_SESSION['csv_legal_sheet_skipped'] = $skippedRows;
        $skippedCount = count($skippedRows);

        if ($successCount > 0) {
            $msg = "{$successCount} legal sheet(s) imported successfully.";
            if ($skippedCount > 0) {
                $msg .= " {$skippedCount} row(s) were skipped — see the details below.";
            }
            setSuccess($msg);
        } else {
            $msg = "No legal sheets were imported.";
            if ($skippedCount > 0) {
                $msg .= " {$skippedCount} row(s) were skipped — see the details below.";
            }
            setError($msg);
        }

        header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
        exit;
    }
}