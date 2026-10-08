<?php

require_once __DIR__ . '/../core/ProjectWorkflowMailService.php';

class ProjectController extends Controller
{
    protected $db;

    protected $locationModel;
    protected $communityModel;
    protected $projectModel;
    protected $projectCategoryModel;

    public function __construct($db)
    {
        $this->db = $db;

        $this->locationModel         = new Location($this->db);
        $this->communityModel        = new Community($this->db);
        $this->projectModel          = new Project($this->db);
        $this->projectCategoryModel  = new ProjectCategory($this->db);
    }

    /* =========================
       Project List
    ========================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $projects = $this->projectModel->getAll();

        $this->view("sales-developments/projects/project-lists", [
            "current_route" => "project-lists",
            "page_title"    => "Project List",
            "projects"      => $projects,
        ]);
    }

    /* =========================
       Create Form
    ========================== */
    public function create()
    {
        $locations   = $this->locationModel->getAll();
        $communities = $this->communityModel->getAll();
        $teamUsers   = $this->getTeamUsersByRole();
        $categories  = $this->projectCategoryModel->getAll();

        $this->view("sales-developments/projects/create-project", [
            "current_route"   => "create-project",
            "active"          => "create-project",
            "page_title"      => "Add Project",
            "show_breadcrumb" => true,
            "locations"       => $locations,
            "communities"     => $communities,
            "teamUsers"       => $teamUsers,
            "categories"      => $categories,
        ]);
    }

    /* =========================
       Store Project
    ========================== */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ======================
        // Collect Form Data
        // ======================
        // Helper unique slug generate 
        $slug = generateUniqueSlug($this->db, $_POST['name'], 'projects', 'slug');

        $data = [
            'name'              => $_POST['name'] ?? null,
            'slug'              => $slug,
            'location_id'       => $_POST['location_id'] ?? null,
            'community_id'      => $_POST['community_id'] ?? null,
            'category_id'       => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'incentive_percentage' => !empty($_POST['incentive_percentage']) ? (float)$_POST['incentive_percentage'] : null,
            'building_type'     => $_POST['building_type'] ?? null,
            'total_share'       => $_POST['total_share'] ?? null,
            'unit_per_floor'    => $_POST['unit_per_floor'] ?? null,
            'front_road'        => $_POST['front_road'] ?? null,
            'types'             => $_POST['types'] ?? null,
            'price_range' => isset($_POST['price_range']) && trim($_POST['price_range']) !== ''
                ? trim($_POST['price_range'])
                : null,
            'project_sale_status'       => $_POST['project_sale_status'] ?? null,
            'project_status'           => $_POST['project_status'] ?? null,
            'apartment_count'   => $_POST['apartment_count'] ?? null,
            'hover_text'        => $_POST['hover_text'] ?? null,
            'plot_size'         => $_POST['plot_size'] ?? null,
            'plot_no'           => $_POST['plot_no'] ?? null,
            'block_no'          => $_POST['block_no'] ?? null,
            'brochure_link'     => $_POST['brochure_link'] ?? null,
            'passenger_lift'    => $_POST['passenger_lift'] ?? null,
            'cargo_lift'        => $_POST['cargo_lift'] ?? null,
            // Facilities (checkbox -> default 0)
            'rooftop_gardening' => $_POST['rooftop_gardening'] ?? 0,
            'car_parking'       => $_POST['car_parking'] ?? 0,
            'convention'        => $_POST['convention'] ?? 0,
            'electricity_backup' => $_POST['electricity_backup'] ?? 0,
            'under_construction' => isset($_POST['under_construction']) ? 1 : 0,
            'is_common'          => isset($_POST['is_common']) ? 1 : 0,
            'amenities_details' => $_POST['amenities_details'] ?? null,
            'map_link'          => $_POST['map_link'] ?? null,
            'description'       => $_POST['description'] ?? null,
            'project_map_location' => $_POST['project_map_location'] ?? null,
            'service_charge_per_emi' => !empty($_POST['service_charge_per_emi']) ? (float)$_POST['service_charge_per_emi'] : null,
            'created_by'        => $_SESSION['user']['id'] ?? null,
            // Project Team Assignment
            'pm_id'               => !empty($_POST['pm_id']) ? (int)$_POST['pm_id'] : null,
            'site_engineer_id'    => !empty($_POST['site_engineer_id']) ? (int)$_POST['site_engineer_id'] : null,
            'landman_id'          => !empty($_POST['landman_id']) ? (int)$_POST['landman_id'] : null,
            'struct_engineer_id'  => !empty($_POST['struct_engineer_id']) ? (int)$_POST['struct_engineer_id'] : null,
            'draftsman_id'        => !empty($_POST['draftsman_id']) ? (int)$_POST['draftsman_id'] : null,
            'architect_id'        => !empty($_POST['architect_id']) ? (int)$_POST['architect_id'] : null,
            'estimator_id'        => !empty($_POST['estimator_id']) ? (int)$_POST['estimator_id'] : null,
            'qc_id'               => !empty($_POST['qc_id']) ? (int)$_POST['qc_id'] : null,
            'operator_id'         => !empty($_POST['operator_id']) ? (int)$_POST['operator_id'] : null,
            'purchase_user_id'    => !empty($_POST['purchase_user_id']) ? (int)$_POST['purchase_user_id'] : null,
        ];

        // ======================
        // Floor Wise Unit Meta
        // ======================
        $floorWiseUnitMeta = null;
        if (!empty($_POST['units']) && is_array($_POST['units'])) {
            $cleanUnits = [];
            foreach ($_POST['units'] as $unit) {
                if (!empty($unit['unit_name']) && !empty($unit['sqft'])) {
                    $cleanUnits[] = [
                        'unit_name' => trim($unit['unit_name']),
                        'sqft'      => (int) $unit['sqft']
                    ];
                }
            }
            if (!empty($cleanUnits)) {
                $floorWiseUnitMeta = json_encode([
                    'typical_floor' => $cleanUnits
                ]);
            }
        }

        $data['floor_wise_unit_meta'] = $floorWiseUnitMeta;

        // ======================
        // Basic Validation
        // ======================
        if (empty($data['name'])) {
            setError("Project name is required");
            header("Location: {$GLOBALS['base_url']}/create-project");
            exit;
        }

        // ======================
        // Store via Model
        // ======================
        if ($this->projectModel->create($data)) {
            // ── Batch 1: Send assignment emails to all team members ──
            $newProjectId = $this->db->insert_id;
            if ($newProjectId > 0) {
                try {
                    $wfMail = new ProjectWorkflowMailService($this->db);
                    $wfMail->sendProjectAssignmentEmails($newProjectId, $data['name']);
                } catch (\Throwable $e) {
                    error_log('[ProjectController] Assignment email error: ' . $e->getMessage());
                }
            }
            setSuccess("Project created successfully!");
            header("Location: {$GLOBALS['base_url']}/project-lists");
            exit;
        }

        // Model already called setError() with the real DB message if failed
        header("Location: {$GLOBALS['base_url']}/create-project");
        exit;
    }


    public function show($id)
    {
        $project = $this->projectModel->find($id);

        $this->view("sales-developments/projects/show-project", [
            'current_route' => 'project-details',
            'page_title' => 'project-details',
            'project' => $project,
        ]);
    }

    // Edit Employee Page
    public function edit($id)
    {
        $project     = $this->projectModel->find($id);
        $locations   = $this->locationModel->getAll();
        $communities = $this->communityModel->getAll();
        $teamUsers   = $this->getTeamUsersByRole();
        $categories  = $this->projectCategoryModel->getAll();

        $this->view("sales-developments/projects/edit-project", [
            "current_route"   => "project-lists",
            "page_title"      => "Edit Project",
            "locations"       => $locations,
            "communities"     => $communities,
            "project"         => $project,
            "show_breadcrumb" => true,
            "teamUsers"       => $teamUsers,
            "categories"      => $categories,
        ]);
    }

    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        // Fetch existing project BEFORE update (to compare team changes)
        $oldProject = $this->projectModel->find($id);
        $data = [
            'name' => !empty($_POST['name']) ? trim($_POST['name']) : null,
            'location_id' => !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null,
            'community_id' => !empty($_POST['community_id']) ? (int)$_POST['community_id'] : null,
            'category_id'  => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'incentive_percentage' => (isset($_POST['incentive_percentage']) && $_POST['incentive_percentage'] !== '') ? (float)$_POST['incentive_percentage'] : null,
            'building_type' => $_POST['building_type'] ?? null,
            'total_share' => !empty($_POST['total_share']) ? (int)$_POST['total_share'] : null,
            'unit_per_floor' => !empty($_POST['unit_per_floor']) ? (int)$_POST['unit_per_floor'] : null,
            'types' => $_POST['types'] ?? null,
            'price_range' => $_POST['price_range'] ?? null,
            'project_sale_status' => $_POST['project_sale_status'] ?? null,
            'apartment_count' => !empty($_POST['apartment_count']) ? (int)$_POST['apartment_count'] : null,
            'front_road' => $_POST['front_road'] ?? null,
            'hover_text' => $_POST['hover_text'] ?? null,
            'plot_size' => (isset($_POST['plot_size']) && $_POST['plot_size'] !== '') ? (float)$_POST['plot_size'] : null,
            'plot_no' => $_POST['plot_no'] ?? null,
            'block_no' => $_POST['block_no'] ?? null,
            'brochure_link' => $_POST['brochure_link'] ?? null,
            'passenger_lift' => !empty($_POST['passenger_lift']) ? (int)$_POST['passenger_lift'] : null,
            'cargo_lift' => !empty($_POST['cargo_lift']) ? (int)$_POST['cargo_lift'] : null,
            'rooftop_gardening' => isset($_POST['rooftop_gardening']) ? 1 : 0,
            'swimming_pool' => isset($_POST['swimming_pool']) ? 1 : 0,
            'car_parking' => isset($_POST['car_parking']) ? 1 : 0,
            'convention' => isset($_POST['convention']) ? 1 : 0,
            'electricity_backup' => isset($_POST['electricity_backup']) ? 1 : 0,
            'under_construction' => isset($_POST['under_construction']) ? 1 : 0,
            'is_common'          => isset($_POST['is_common']) ? 1 : 0,
            'amenities_details' => $_POST['amenities_details'] ?? null,
            'map_link' => $_POST['map_link'] ?? null,
            'description' => $_POST['description'] ?? null,
            'project_map_location'   => $_POST['project_map_location'] ?? null,
            'service_charge_per_emi' => (isset($_POST['service_charge_per_emi']) && $_POST['service_charge_per_emi'] !== '') ? (float)$_POST['service_charge_per_emi'] : null,
            'created_by'             => $_SESSION['user']['id'] ?? null,
            // Project Team Assignment
            'pm_id'               => !empty($_POST['pm_id']) ? (int)$_POST['pm_id'] : null,
            'site_engineer_id'    => !empty($_POST['site_engineer_id']) ? (int)$_POST['site_engineer_id'] : null,
            'landman_id'          => !empty($_POST['landman_id']) ? (int)$_POST['landman_id'] : null,
            'struct_engineer_id'  => !empty($_POST['struct_engineer_id']) ? (int)$_POST['struct_engineer_id'] : null,
            'draftsman_id'        => !empty($_POST['draftsman_id']) ? (int)$_POST['draftsman_id'] : null,
            'architect_id'        => !empty($_POST['architect_id']) ? (int)$_POST['architect_id'] : null,
            'estimator_id'        => !empty($_POST['estimator_id']) ? (int)$_POST['estimator_id'] : null,
            'qc_id'               => !empty($_POST['qc_id']) ? (int)$_POST['qc_id'] : null,
            'operator_id'         => !empty($_POST['operator_id']) ? (int)$_POST['operator_id'] : null,
            'purchase_user_id'    => !empty($_POST['purchase_user_id']) ? (int)$_POST['purchase_user_id'] : null,
        ];

        // ===================================
        // Update slug if name changed or slug is empty
        // ===================================
        if (empty($oldProject['slug']) || trim($data['name']) !== trim($oldProject['name'] ?? '')) {
            $data['slug'] = generateUniqueSlug($this->db, $data['name'], 'projects', 'slug', $id);
        } else {
            $data['slug'] = $oldProject['slug'] ?? null;
        }

        // ======================
        // Floor Units
        // ======================
        $floorWiseUnitMeta = null;
        if (!empty($_POST['units']) && is_array($_POST['units'])) {
            $cleanUnits = [];
            foreach ($_POST['units'] as $unit) {
                if (!empty($unit['unit_name']) && !empty($unit['sqft'])) {
                    $cleanUnits[] = [
                        'unit_name' => trim($unit['unit_name']),
                        'sqft' => (float)$unit['sqft']
                    ];
                }
            }
            if (!empty($cleanUnits)) {
                $floorWiseUnitMeta = json_encode(['typical_floor' => $cleanUnits]);
            }
        }
        $data['floor_wise_unit_meta'] = $floorWiseUnitMeta;


        // ══════════════════════════════════════════════════════
        // DEBUG MODE — ?debug=1 URL parameter দিলে activate হবে
        // Project update না করে শুধু email check করবে
        // ══════════════════════════════════════════════════════
        if (!empty($_GET['debug']) && $_GET['debug'] === '1') {
            header('Content-Type: text/html; charset=utf-8');
            echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5}
                  .card{background:#fff;padding:16px;border-radius:8px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
                  .ok{color:#198754;font-weight:700} .warn{color:#f59e0b;font-weight:700}
                  .err{color:#dc3545;font-weight:700} table{width:100%;border-collapse:collapse}
                  th,td{padding:8px 10px;border:1px solid #ddd;font-size:13px;text-align:left}
                  th{background:#f0f0f0}</style>";

            echo "<div class='card'><h2 style='color:#198754;margin-top:0'>🔍 Project Update Email Debug</h2>";
            echo "<p>Project ID: <b>{$id}</b> | Name: <b>" . htmlspecialchars($data['name'] ?? '') . "</b></p></div>";

            // Show OLD vs NEW team comparison
            echo "<div class='card'><h3>Team Assignment Comparison</h3>";
            echo "<table><tr><th>Role</th><th>Column</th><th>OLD ID</th><th>NEW ID</th><th>Changed?</th><th>Will Email?</th></tr>";

            $roleMap = [
                'pm_id'              => 'Project Manager',
                'site_engineer_id'   => 'Site Engineer',
                'landman_id'         => 'Land Manager',
                'struct_engineer_id' => 'Structural Engineer',
                'draftsman_id'       => 'Draftsman',
                'architect_id'       => 'Architect',
                'estimator_id'       => 'Estimator',
                'qc_id'              => 'Quality Controller',
                'operator_id'        => 'Site Operator',
            ];

            $willEmail = [];
            foreach ($roleMap as $col => $label) {
                $oldId = (int)($oldProject[$col] ?? 0);
                $newId = (int)($data[$col] ?? 0);
                $changed = $newId > 0 && $newId !== $oldId;
                $color = $changed ? 'background:#d1e7dd' : '';
                echo "<tr style='{$color}'>";
                echo "<td>{$label}</td><td><code>{$col}</code></td>";
                echo "<td>" . ($oldId ?: '<span class="warn">NULL</span>') . "</td>";
                echo "<td>" . ($newId ?: '<span class="warn">NULL</span>') . "</td>";
                echo "<td>" . ($changed ? '<span class="ok">✅ YES</span>' : 'No') . "</td>";

                if ($changed) {
                    $uRow = $this->db->query("SELECT full_name, email FROM users WHERE id = {$newId} LIMIT 1")->fetch_assoc();
                    $emailStr = $uRow['email'] ?? '—';
                    $willEmail[] = ['name' => $uRow['full_name'] ?? '?', 'email' => $emailStr, 'role' => $label];
                    echo "<td class='ok'>" . htmlspecialchars($emailStr) . "</td>";
                } else {
                    echo "<td style='color:#888'>—</td>";
                }
                echo "</tr>";
            }
            echo "</table></div>";

            // Actually send emails now
            if (!empty($willEmail)) {
                echo "<div class='card'><h3>📧 Sending Emails Now...</h3>";
                try {
                    $wfMail = new ProjectWorkflowMailService($this->db);
                    $results = [];
                    foreach ($willEmail as $m) {
                        // Use reflection to call private send method — or just use sendProjectReassignmentEmails
                        echo "<p>Attempting: <b>" . htmlspecialchars($m['name']) . "</b> → " . htmlspecialchars($m['email']) . " (" . $m['role'] . ")</p>";
                    }
                    $wfMail->sendProjectReassignmentEmails($oldProject ?? [], $data, $data['name']);
                    echo "<p class='ok'>✅ sendProjectReassignmentEmails() called — check inboxes!</p>";
                } catch (\Throwable $e) {
                    echo "<p class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                echo "</div>";
            } else {
                echo "<div class='card'><p class='warn'>⚠️ No team changes detected — no emails will be sent.</p></div>";
            }

            echo "<div class='card' style='background:#fff3cd'><b>⚠️ NOTE:</b> Project was NOT updated (debug mode). Remove <code>?debug=1</code> to actually save.</div>";
            echo "<a href='" . $GLOBALS['base_url'] . "/project-lists' style='display:inline-block;margin-top:10px;padding:10px 20px;background:#198754;color:#fff;border-radius:6px;text-decoration:none'>← Back to Projects</a>";
            exit;
        }
        // ══════════════════════════════════════════════════════
        // END DEBUG — Normal update below
        // ══════════════════════════════════════════════════════

        if ($this->projectModel->update($id, $data)) {
            // ── Batch 1 (UPDATE): Smart reassignment — only email newly assigned/changed members ──
            try {
                $wfMail = new ProjectWorkflowMailService($this->db);
                $wfMail->sendProjectReassignmentEmails(
                    $oldProject ?? [],
                    $data,
                    $data['name']
                );
            } catch (\Throwable $e) {
                error_log('[ProjectController] Reassignment email error: ' . $e->getMessage());
            }
            setSuccess("Project updated successfully!");
            header("Location: {$GLOBALS['base_url']}/project-lists");
            exit;
        }

        if (empty($_SESSION['error'])) {
            setError("Project update failed");
        }
        header("Location: {$GLOBALS['base_url']}/edit-project/$id");
        exit;
    }

    public function delete()
    {
        $id = $_POST['id'] ?? 0;
        if ($this->projectModel->softDelete($id)) {
            setSuccess("Project Deleted Temporaly");
            header("Location:{$GLOBALS['base_url']}/project-lists");
        } else {
            setSuccess("Failed To  Deleted Temporaly Project");
            header("Location:{$GLOBALS['base_url']}/project-lists");
        };
    }

    public function importCsvBulkProjects()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
            setError("CSV upload failed");
            header("Location: {$GLOBALS['base_url']}/project-lists");
            exit;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            setError("Only CSV files allowed");
            header("Location: {$GLOBALS['base_url']}/project-lists");
            exit;
        }

        if (($handle = fopen($fileTmp, "r")) !== false) {
            $header = fgetcsv($handle);
            
            // Clean BOM from first header if present
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
            $header = array_map(function ($value) {
                return trim($value);
            }, $header);

            $successCount = 0;
            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                if (count(array_filter($row)) === 0) continue; // skip empty rows
                if (count($header) !== count($row)) continue;

                $data = array_combine($header, $row);
                
                // Set default created_by
                $data['created_by'] = $_SESSION['user']['id'] ?? null;
                
                try {
                    $this->projectModel->createFromCsvBulk($data);
                    $successCount++;
                } catch (Exception $e) {
                    error_log("CSV Import Error: " . $e->getMessage());
                    continue;
                }
            }
            fclose($handle);

            setSuccess("$successCount Projects imported successfully from CSV");
            header("Location: {$GLOBALS['base_url']}/project-lists");
            exit;
        } else {
            setError("Unable to read CSV file");
            header("Location: {$GLOBALS['base_url']}/project-lists");
            exit;
        }
    }

    /**
     * Fetch users grouped by construction roles
     * Returns array keyed by role slug
     */
    private function getTeamUsersByRole()
    {
        // role_id => key mapping
        $roleMap = [
            15 => 'project_managers',    // Project Manager
            12 => 'site_engineers',      // Site Engineer
            14 => 'land_managers',       // Land Manager
            16 => 'draftsmen',           // Draftsman
            18 => 'architects',          // Project Architecture
            19 => 'estimators',          // Estimator
            13 => 'qc_controllers',      // Quality Controller
            20 => 'site_operators',      // Site Operator
            21 => 'struct_engineers',    // Structure designer
            17 => 'purchase_executives', // Purchase Executive
        ];

        $result = [
            'project_managers'   => [],
            'site_engineers'     => [],
            'land_managers'      => [],
            'struct_engineers'   => [],
            'draftsmen'          => [],
            'architects'         => [],
            'estimators'         => [],
            'qc_controllers'     => [],
            'site_operators'     => [],
            'purchase_executives'=> [],
        ];

        $roleIds = implode(',', array_keys($roleMap));
        $stmt = $this->db->prepare("
            SELECT id, full_name, role_id
            FROM users
            WHERE role_id IN ($roleIds)
            ORDER BY full_name ASC
        ");
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as $user) {
            $key = $roleMap[$user['role_id']] ?? null;
            if ($key) {
                $result[$key][] = $user;
            }
        }



        return $result;
    }
}
