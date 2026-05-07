<?php
namespace Phase2;

use Controller;
use Con_DPR;

class Con_DPRController extends Controller

{
    protected $db;
    protected $dprModel;

    public function __construct($db)
    {
        $this->db       = $db;
        $this->dprModel = new Con_DPR($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $reports = $this->dprModel->getAll();

        $this->view('phase2/daily-reports/report-lists', [
            'current_route' => 'con-daily-reports',
            'page_title'    => 'Daily Progress Reports',
            'reports'       => $reports,
        ]);
    }

    // =========================================================================
    // CREATE FORM
    // =========================================================================
    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);
        $engineers = $this->db->query("SELECT id, full_name FROM users  ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/daily-reports/create-report', [
            'current_route' => 'con-daily-reports',
            'page_title'    => 'New Daily Progress Report',
            'projects'      => $projects,
            'engineers'     => $engineers,
        ]);
    }

    // =========================================================================
    // STORE (POST)
    // =========================================================================
    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        // Handle progress image upload
        $imagePath = null;
        if (!empty($_FILES['progress_image']['name'])) {
            $uploadDir = __DIR__ . '/../../../public/uploads/dpr-images/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext      = strtolower(pathinfo($_FILES['progress_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $_FILES['progress_image']['error'] === UPLOAD_ERR_OK) {
                $filename  = time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['progress_image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'uploads/dpr-images/' . $filename;
                }
            }
        }

        $data = [
            'project_id'          => $_POST['project_id']          ?? null,
            'site_engineer_id'    => $_POST['site_engineer_id']    ?? ($_SESSION['user']['id'] ?? null),
            'work_done'           => trim($_POST['work_done']      ?? ''),
            'progress_percentage' => $_POST['progress_percentage'] ?? 0.00,
            'report_date'         => $_POST['report_date']         ?? date('Y-m-d'),
            'weather_condition'   => trim($_POST['weather_condition'] ?? ''),
            'manpower_count'      => !empty($_POST['manpower_count']) ? (int)$_POST['manpower_count'] : null,
            'progress_image'      => $imagePath,
            'created_by'          => $_SESSION['user']['id']       ?? null,
        ];

        if (empty($data['project_id']) || empty($data['work_done'])) {
            setError('Project and Work Done are required.');
            header("Location: {$GLOBALS['base_url']}/con-daily-report-create");
            exit;
        }

        $id = $this->dprModel->create($data);

        if ($id) {
            setSuccess('Daily progress report saved!');
            header("Location: {$GLOBALS['base_url']}/con-daily-report/{$id}");
        } else {
            setError('Failed to save report.');
            header("Location: {$GLOBALS['base_url']}/con-daily-report-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW DETAIL
    // =========================================================================
    public function show($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $report = $this->dprModel->find($id);
        if (!$report) {
            setError('Report not found.');
            header("Location: {$GLOBALS['base_url']}/con-daily-reports");
            exit;
        }

        $this->view('phase2/daily-reports/show-report', [
            'current_route' => 'con-daily-reports',
            'page_title'    => 'DPR Detail',
            'report'        => $report,
        ]);
    }
}
