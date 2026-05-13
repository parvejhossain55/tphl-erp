<?php

class FlatController extends Controller
{
    protected $db;

    protected $projectModel;
    protected $flatModel;

    public function __construct($db)
    {
        $this->db = $db;

        $this->projectModel  = new Project($this->db);
        $this->flatModel = new FlatModel($this->db);
    }

    /* =========================
       Project List
    ========================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        //  GET filters
        $search        = $_GET['q'] ?? '';
        $selectedProject = $_GET['project_id'] ?? '';

        //  Data load
        $projects   = $this->projectModel->getAll();
        $flatCounts = $this->flatModel->getFlatStatusCount();

        $bookedFlats    = $flatCounts['booked'] ?? 0;
        $availableFlats = $flatCounts['available'] ?? 0;

        //  Filtered flat list
        $flats = $this->flatModel->getAll($search, $selectedProject);
        $totalFlatCount = $this->flatModel->getTotalFlats();
        //  View
        $this->view("sales-developments/flats/flat-lists", [
            "current_route"     => "flat-lists",
            "page_title"        => "Flat List",

            "flats"             => $flats,
            "projects"          => $projects,

            "bookedFlats"       => $bookedFlats,
            "availableFlats"    => $availableFlats,

            //  IMPORTANT for select & search retain
            "searchTerm"        => $search,
            "selectedProject"   => $selectedProject,
            "totalFlatCount"   => $totalFlatCount,
        ]);
    }

    /* =========================
       Create Form
    ========================== */

    public function create()
    {
        $projects   = $this->projectModel->getAll();

        //         $flatCounts = $this->flatModel->getFlatStatusCount();

        $this->view("sales-developments/flats/create-flat", [
            "current_route"   => "create-flat",
            "active"          => "create-flat",
            "page_title"      => "Add Flat",
            "show_breadcrumb" => true,
            "projects"       => $projects,
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


        $is_booked = 0;
        if (in_array($_POST['status'], ['sold', 'booked'])) {
            $is_booked = 1;
        }
        // ======================
        // Collect Form Data
        // ======================
        $data = [
            'project_id'             => $_POST['project_id'] ?? null,
            'flat_name'              => $_POST['flat_name'] ?? null,
            'sqft'                   => $_POST['sqft'] ?? null,
            'bhk'                    => $_POST['bhk'] ?? null,
            'bedroom'                => $_POST['bedroom'] ?? null,
            'bathroom'               => $_POST['bathroom'] ?? null,
            'balcony'                => $_POST['balcony'] ?? null,
            'drawing_dining_kitchen' => isset($_POST['drawing_dining_kitchen']) ? 1 : 0,
            'common_dining'          => isset($_POST['common_dining']) ? 1 : 0,
            'floor_no'               => $_POST['floor_no'] ?? null,
            'facing_site'            => $_POST['facing_site'] ?? null,
            'is_shop'                => isset($_POST['is_shop']) ? 1 : 0,
            'unit_type'              => $_POST['unit_type'] ?? 'residential',
            'price_value'            => $_POST['price_value'] ?? null,
            'shop_service_charge'    => !empty($_POST['shop_service_charge']) ? (int)$_POST['shop_service_charge'] : null,
            'land_share_value'       => $_POST['land_share_value'] ?? null,
            'development_charges'    => $_POST['development_charges'] ?? null,
            'facilities'             => $_POST['facilities'] ?? null,
            'is_booked'              => $is_booked,
            'status'                 => $_POST['status'] ?? null,
            'created_by'             => $_SESSION['user']['id'] ?? null,
        ];

        // ======================
        // Basic Validation
        // ======================
        if (empty($data['project_id'])) {
            setError("Project is required");
            header("Location: {$GLOBALS['base_url']}/create-flat");
            exit;
        }

        // ======================
        // Store via Model
        // ======================
        if ($this->flatModel->create($data)) {
            setSuccess("Flat created successfully!");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        }

        setError("Flat creation failed");
        header("Location: {$GLOBALS['base_url']}/create-flat");
        exit;
    }

    // Edit Employee Page
    public function edit($id)
    {
        $projects   = $this->projectModel->getAll();
        $flat = $this->flatModel->find($id);
        $this->view("sales-developments/flats/edit-flat", [
            "current_route"   => "edit-flat",
            "page_title"      => "Edit Flat",
            "projects"       => $projects,
            "flat"     => $flat,
            "show_breadcrumb" => true,
        ]);
    }

    public function update($id)
    {

        if (session_status() === PHP_SESSION_NONE) session_start();

        // Collect form data
        $data = [
            'project_id'             => $_POST['project_id'] ?? null,
            'flat_name'              => $_POST['flat_name'] ?? null,
            'sqft'                   => $_POST['sqft'] ?? null,
            'bhk'                    => $_POST['bhk'] ?? null,
            'bedroom'                => $_POST['bedroom'] ?? null,
            'bathroom'               => $_POST['bathroom'] ?? null,
            'balcony'                => $_POST['balcony'] ?? null,
            'drawing_dining_kitchen' => isset($_POST['drawing_dining_kitchen']) ? 1 : 0,
            'common_dining'          => isset($_POST['common_dining']) ? 1 : 0,
            'floor_no'               => $_POST['floor_no'] ?? null,
            'facing_site'            => $_POST['facing_site'] ?? null,
            'is_shop'                => isset($_POST['is_shop']) ? 1 : 0,
            'unit_type'              => $_POST['unit_type'] ?? 'residential',
            'price_value'            => $_POST['price_value'] ?? null,
            'shop_service_charge'    => !empty($_POST['shop_service_charge']) ? (int)$_POST['shop_service_charge'] : null,
            'land_share_value'       => $_POST['land_share_value'] ?? null,
            'development_charges'    => $_POST['development_charges'] ?? null,
            'facilities'             => $_POST['facilities'] ?? null,
            'created_by'             => $_SESSION['user']['id'] ?? null,
        ];

        // Update lead
        try {
            $this->flatModel->update($id, $data);
            setSuccess("Flat updated successfully!");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        } catch (Exception $e) {
            setError($e->getMessage());
            $_SESSION['old'] = $_POST;
            header("Location: {$GLOBALS['base_url']}/edit-flat/{$id}");
            exit;
        }
    }


    public function show($id)
    {
        $flat = $this->flatModel->find($id);
        $this->view('sales-developments/flats/flat-details', [
            'current_route' => 'flat-details',
            'page_title' => 'flat-details',
            'flat' => $flat,
        ]);
    }



    // Delete Employee
    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = $_POST['id'] ?? 0;

        if (!$id) {
            setError("Invalid Flat ID");
            header("Location:{$GLOBALS['base_url']}/flat-lists");
            exit;
        }

        try {
            $deleted = $this->flatModel->softDelete($id);

            if ($deleted) {
                setSuccess("Flat Deleted Successfully");
            } else {
                setError("Something went wrong. Please try again.");
            }
        } catch (Exception $e) {
            // Catch DB exceptions
            setError("Error: " . $e->getMessage());
        }

        header("Location:{$GLOBALS['base_url']}/flat-lists");
        exit;
    }

    public function importCsvBulkFlats()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
            setError("CSV upload failed");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            setError("Only CSV files allowed");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        }

        if (($handle = fopen($fileTmp, "r")) !== false) {
            $rawHeader = fgetcsv($handle);

            // ── Step 1: BOM strip + normalize header (lowercase, spaces→underscores) ──
            $rawHeader[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $rawHeader[0]);
            $header = array_map(function ($col) {
                return strtolower(trim(preg_replace('/\s+/', '_', $col)));
            }, $rawHeader);

            // ── Step 2: Alias map for common CSV column name variations ──────────────
            $aliasMap = [
                'project_name'           => 'project_name',
                'project_nar'            => 'project_name',  // Excel truncation
                'project'                => 'project_name',
                'sft'                    => 'sqft',
                'sqft'                   => 'sqft',
                'size'                   => 'sqft',
                'flat_name'              => 'flat_name',
                'flat_no'                => 'flat_name',
                'unit'                   => 'flat_name',
                'unit_name'              => 'flat_name',
                'bhk'                    => 'bhk',
                'bedroom'                => 'bedroom',
                'bedrooms'               => 'bedroom',
                'bathroom'               => 'bathroom',
                'bathrooms'              => 'bathroom',
                'balcony'                => 'balcony',
                'drawing'                => 'drawing_dining_kitchen',
                'drawing_dining_kitchen' => 'drawing_dining_kitchen',
                'common'                 => 'common_dining',
                'common_dining'          => 'common_dining',
                'floor_no'               => 'floor_no',
                'floor'                  => 'floor_no',
                'facing'                 => 'facing_site',
                'facing_site'            => 'facing_site',
                'price_value'            => 'price_value',
                'price'                  => 'price_value',
                'land_share'             => 'land_share_value',
                'land_share_value'       => 'land_share_value',
                'developme'              => 'development_charges', // Excel truncation
                'development'            => 'development_charges',
                'development_charges'    => 'development_charges',
                'facilities'             => 'facilities',
                'facility'               => 'facilities',
                'status'                 => 'status',
            ];

            $header = array_map(function ($col) use ($aliasMap) {
                return $aliasMap[$col] ?? $col;
            }, $header);

            $successCount  = 0;
            $errors        = [];
            $rowNum        = 1;
            $projectsCache = [];

            while (($row = fgetcsv($handle, 2000, ",")) !== false) {
                $rowNum++;
                if (count(array_filter($row)) === 0) continue;
                if (count($header) !== count($row)) {
                    $errors[] = "Row $rowNum: Column count mismatch — skipped";
                    continue;
                }

                $data = array_combine($header, $row);

                // ── Resolve project_id from project name ──────────────────
                $projectName = trim($data['project_name'] ?? '');
                if (empty($projectName)) {
                    $errors[] = "Row $rowNum: project_name is empty — skipped";
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
                    $errors[] = "Row $rowNum: Project not found: '$projectName'";
                    continue;
                }

                // ── flat_name required ────────────────────────────────────
                $flatName = trim($data['flat_name'] ?? '');
                if (empty($flatName)) {
                    $errors[] = "Row $rowNum: flat_name is empty — skipped";
                    continue;
                }

                $data['project_id'] = $projectId;
                $data['created_by'] = $_SESSION['user']['id'] ?? null;
                $data['upload_from'] = 'From company csv'; // CSV bulk import tag

                try {
                    $this->flatModel->createFromCsvBulk($data);
                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "Row $rowNum: DB Error — " . $e->getMessage();
                    error_log("Flat CSV Import Error (row $rowNum): " . $e->getMessage());
                }
            }
            fclose($handle);

            if ($successCount > 0) {
                $msg = "$successCount Flats imported successfully.";
                if (!empty($errors)) {
                    $msg .= " Skipped " . count($errors) . " row(s): " . implode("; ", array_slice($errors, 0, 3));
                    if (count($errors) > 3) $msg .= " and " . (count($errors) - 3) . " more.";
                }
                setSuccess($msg);
            } else {
                $msg = "0 Flats imported.";
                if (!empty($errors)) {
                    $msg .= " Errors: " . implode("; ", array_slice($errors, 0, 5));
                    if (count($errors) > 5) $msg .= " and " . (count($errors) - 5) . " more.";
                }
                setError($msg);
            }

            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        } else {
            
            setError("Unable to read CSV file");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Shop / Commercial Unit CSV Bulk Import
    // ──────────────────────────────────────────────────────────────────────
    public function importCsvBulkShops()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
            setError("CSV upload failed");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            setError("Only CSV files allowed");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        }

        if (($handle = fopen($fileTmp, "r")) !== false) {
            $rawHeader = fgetcsv($handle);

            // BOM strip + normalize header
            $rawHeader[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $rawHeader[0]);
            $header = array_map(function ($col) {
                return strtolower(trim(preg_replace('/\s+/', '_', $col)));
            }, $rawHeader);

            // Alias map — shop_name → flat_name, and other variations
            $aliasMap = [
                'project_name'        => 'project_name',
                'project'             => 'project_name',
                'shop_name'           => 'flat_name',   // primary alias
                'shop_no'             => 'flat_name',
                'unit_name'           => 'flat_name',
                'flat_name'           => 'flat_name',
                'sqft'                => 'sqft',
                'sft'                 => 'sqft',
                'size'                => 'sqft',
                'floor_no'            => 'floor_no',
                'floor'               => 'floor_no',
                'facing_site'         => 'facing_site',
                'facing'              => 'facing_site',
                'price_value'         => 'price_value',
                'price'               => 'price_value',
                'shop_service_charge' => 'shop_service_charge',
                'service_charge'      => 'shop_service_charge',
                'service_charge_%'    => 'shop_service_charge',
                // land_share_value: "15+15% booking+registration"
                'land_share_value(15%+15%)' => 'land_share_value',
                'land_share_value'    => 'land_share_value',
                'land_share'          => 'land_share_value',
                'booking_registration'=> 'land_share_value',
                'status'              => 'status',
            ];

            $header = array_map(function ($col) use ($aliasMap) {
                return $aliasMap[$col] ?? $col;
            }, $header);

            $successCount  = 0;
            $errors        = [];
            $rowNum        = 1;
            $projectsCache = [];

            while (($row = fgetcsv($handle, 2000, ",")) !== false) {
                $rowNum++;
                if (count(array_filter($row)) === 0) continue;
                if (count($header) !== count($row)) {
                    $errors[] = "Row $rowNum: Column count mismatch — skipped";
                    continue;
                }

                $data = array_combine($header, $row);
                $rowIssues = [];

                // ── Resolve project_id ──────────────────────────────────
                $projectName = trim($data['project_name'] ?? '');
                if (empty($projectName)) {
                    $rowIssues[] = "project_name is empty";
                } else {
                    if (isset($projectsCache[$projectName])) {
                        $projectId = $projectsCache[$projectName];
                    } else {
                        $stmt = $this->db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
                        $stmt->bind_param("s", $projectName);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $projectId = $res['id'];
                            $projectsCache[$projectName] = $projectId;
                        } else {
                            $rowIssues[] = "Project not found: '$projectName'";
                            $projectId = null;
                        }
                    }
                }

                // ── shop_name / flat_name required ───────────────────────
                $flatName = trim($data['flat_name'] ?? '');
                if (empty($flatName)) {
                    $rowIssues[] = "shop_name is empty";
                }

                if (!empty($rowIssues)) {
                    $errors[] = "Row $rowNum: " . implode(' | ', $rowIssues);
                    continue;
                }

                // ── Build data for createFromCsvBulk ───────────────────
                $data['project_id']         = $projectId;
                $data['flat_name']          = $flatName;
                $data['unit_type']          = 'commercial'; // forced
                $data['is_shop']            = 1;            // forced
                $data['upload_from']        = 'From company csv';
                $data['created_by']         = $_SESSION['user']['id'] ?? null;

                // Defaults for unused residential fields
                $data['bhk']                    = $data['bhk']                    ?? null;
                $data['bedroom']                = $data['bedroom']                ?? null;
                $data['bathroom']               = $data['bathroom']               ?? null;
                $data['balcony']                = $data['balcony']                ?? null;
                $data['drawing_dining_kitchen'] = 0;
                $data['common_dining']          = 0;
                $data['development_charges']    = null;
                $data['facilities']             = null;

                // shop_service_charge: integer %
                $data['shop_service_charge'] = !empty($data['shop_service_charge'])
                    ? (int)$data['shop_service_charge']
                    : null;

                // Status
                $status = strtolower(trim($data['status'] ?? 'available'));
                $data['status']    = in_array($status, ['available', 'booked', 'sold']) ? $status : 'available';
                $data['is_booked'] = in_array($data['status'], ['booked', 'sold']) ? 1 : 0;

                try {
                    $this->flatModel->createFromCsvBulk($data);
                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "Row $rowNum: DB Error — " . $e->getMessage();
                    error_log("Shop CSV Import Error (row $rowNum): " . $e->getMessage());
                }
            }
            fclose($handle);

            $totalRows   = $rowNum - 1;
            $failedCount = count($errors);

            if ($successCount > 0) {
                $msg = "✅ $successCount / $totalRows Shops imported successfully.";
                if ($failedCount > 0) {
                    $msg .= " ⚠️ $failedCount skipped: " . implode(" | ", array_slice($errors, 0, 5));
                    if ($failedCount > 5) $msg .= " ... and " . ($failedCount - 5) . " more.";
                }
                setSuccess($msg);
            } else {
                $msg = "❌ 0 / $totalRows Shops imported. Errors: " . implode(" | ", array_slice($errors, 0, 5));
                if ($failedCount > 5) $msg .= " ... and " . ($failedCount - 5) . " more.";
                setError($msg);
            }

            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        } else {
            setError("Unable to read CSV file");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        }
    }
}
