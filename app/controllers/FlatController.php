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
        $search          = $_GET['q'] ?? '';
        $selectedProject = $_GET['project_id'] ?? '';
        $selectedStatus  = $_GET['status'] ?? '';

        //  Data load
        $projects   = $this->projectModel->getAll();
        $flatCounts = $this->flatModel->getFlatStatusCount();

        $bookedFlats    = $flatCounts['booked'] ?? 0;
        $availableFlats = $flatCounts['available'] ?? 0;

        // Data loaded via AJAX now
        $flats = []; 
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
            "selectedStatus"    => $selectedStatus,
            "totalFlatCount"    => $totalFlatCount,
        ]);
    }

    public function ajaxFlatList()
    {
        $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
        $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

        // Capture our custom filters if they are sent in the URL (e.g. DataTables ajax.url)
        // or through DataTables ajax data function
        $projectId = $_GET['project_id'] ?? '';
        $status = $_GET['status'] ?? '';
        
        // Also fallback to the main search box if they use our custom search input
        if (empty($search) && !empty($_GET['q'])) {
            $search = $_GET['q'];
        }

        $orderCol = $_GET['order'][0]['column'] ?? 0;
        $orderDir = $_GET['order'][0]['dir'] ?? 'desc';

        $totalRecords = $this->flatModel->getTotalFlats();
        
        if (empty($search) && empty($projectId) && empty($status)) {
            $totalFiltered = $totalRecords;
        } else {
            $totalFiltered = $this->flatModel->getFlatsAjaxCount($search, $projectId, $status);
        }

        $flats = $this->flatModel->getFlatsAjax($start, $length, $search, $projectId, $status, $orderCol, $orderDir);

        $data = [];
        $base_url = $GLOBALS['base_url'];
        $hasPermission = hasPermission("view lead");

        foreach ($flats as $index => $flat) {
            $serial = $start + $index + 1;
            
            // Format type badge
            $unitType = $flat['unit_type'] ?? 'residential';
            if ($unitType === 'commercial') {
                $typeBadge = '<span class="bd-badge bg-warning "><i class="bi bi-shop me-1"></i> Commercial (Shop)</span>';
            } elseif ($unitType === 'residential') {
                $typeBadge = '<span class="bd-badge bg-primary"><i class="bi bi-building me-1"></i> Residential (Flat)</span>';
            } else {
                $typeBadge = '<span class="bd-badge bg-info"><i class="bi bi-building me-1"></i> ' . htmlspecialchars(ucfirst($unitType)) . '</span>';
            }

            // Format service charge
            if (($flat['unit_type'] ?? 'residential') === 'commercial' && !empty($flat['shop_service_charge'])) {
                $serviceCharge = '<span class="bd-badge bg-info ">' . (float)$flat['shop_service_charge'] . '%</span>';
            } else {
                $serviceCharge = '<span class="text-muted">—</span>';
            }

            // Format status badge
            $statusVal = $flat['status'] ?? 'available';
            if ($statusVal === 'sold') {
                $statusBadge = '<span class="bd-badge bg-danger">Sold</span>';
            } elseif ($statusVal === 'booked') {
                $statusBadge = '<span class="bd-badge bg-warning ">Booked</span>';
            } else {
                $statusBadge = '<span class="bd-badge bg-success">Available</span>';
            }

            // Format actions
            $actions = '<div class="d-flex align-items-center justify-content-center gap-10">
                <a href="' . $base_url . '/edit-flat/' . $flat['id'] . '" class="table__icon edit">
                    <i class="fa-sharp fa-light fa-pen"></i>
                </a>
                <a class="table__icon download" href="' . $base_url . '/flat-details/' . $flat['id'] . '" title="View Transaction History">
                    <i class="fa-regular fa-eye"></i>
                </a>';
            
            if ($hasPermission) {
                $actions .= '<button class="d-none table__icon delete removeBtnCust" data-name="' . htmlspecialchars($flat['flat_name'] ?? '') . '" data-action="delete-flat" data-id="' . $flat['id'] . '"><i class="fa-regular fa-trash"></i></button>';
            } else {
                $actions .= '<button class="table__icon delete removeBtnCust" data-name="' . htmlspecialchars($flat['flat_name'] ?? '') . '" data-action="delete-flat" data-id="' . $flat['id'] . '"><i class="fa-regular fa-trash"></i></button>';
            }
            $actions .= '</div>';

            $uploadFromText = htmlspecialchars($flat['upload_from'] ?? 'N/A');
            $creator = trim($flat['creator_name'] ?? '');
            if (!empty($creator)) {
                $uploadFromText .= ' by ' . htmlspecialchars($creator);
            }

            $data[] = [
                $serial,
                htmlspecialchars($flat['project_name'] ?? ''),
                htmlspecialchars($flat['flat_name'] ?? 'N/A'),
                htmlspecialchars($flat['suite_id'] ?? 'N/A'),
                format_bdt($flat['per_sqft_price'] ?? 0),
                format_bdt($flat['booking_money'] ?? 0),
                format_bdt($flat['registration_money'] ?? 0),
                $typeBadge,
                $uploadFromText,
                htmlspecialchars($flat['facing_site'] ?? 'N/A'),
                htmlspecialchars($flat['sqft'] ?? 'N/A'),
                format_bdt($flat['land_share_value'] ?? 0),
                !empty($flat['development_charges']) ? format_bdt($flat['development_charges'] ?? 0) : 'N/A',
                format_bdt($flat['price_value'] ?? 0),
                $serviceCharge,
                $statusBadge,
                $actions
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            "draw"            => $draw,
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalFiltered,
            "data"            => $data
        ]);
        exit;
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
            'is_shop'                => (!empty($_POST['is_shop']) || ($_POST['unit_type'] ?? '') === 'commercial') ? 1 : 0,
            'unit_type'              => (!empty($_POST['is_shop']) && ($_POST['unit_type'] ?? '') === 'residential') ? 'commercial' : ($_POST['unit_type'] ?? 'residential'),
            'price_value'            => isset($_POST['price_value']) && $_POST['price_value'] !== '' ? str_replace(',', '', $_POST['price_value']) : null,
            'shop_service_charge'    => isset($_POST['shop_service_charge']) && $_POST['shop_service_charge'] !== '' ? str_replace(',', '', $_POST['shop_service_charge']) : null,
            'land_share_value'       => isset($_POST['land_share_value']) && $_POST['land_share_value'] !== '' ? str_replace(',', '', $_POST['land_share_value']) : null,
            'development_charges'    => isset($_POST['development_charges']) && $_POST['development_charges'] !== '' ? str_replace(',', '', $_POST['development_charges']) : null,
            'facilities'             => $_POST['facilities'] ?? null,
            'is_booked'              => $is_booked,
            'status'                 => $_POST['status'] ?? null,
            'created_by'             => $_SESSION['user']['id'] ?? null,
            'suite_id'               => $_POST['suite_id'] ?? null,
            'ownership_type'         => !empty($_POST['ownership_type']) ? $_POST['ownership_type'] : null,
            'per_sqft_price'         => isset($_POST['per_sqft_price']) && $_POST['per_sqft_price'] !== '' ? str_replace(',', '', $_POST['per_sqft_price']) : null,
            'booking_money'          => isset($_POST['booking_money']) && $_POST['booking_money'] !== '' ? str_replace(',', '', $_POST['booking_money']) : null,
            'registration_money'     => isset($_POST['registration_money']) && $_POST['registration_money'] !== '' ? str_replace(',', '', $_POST['registration_money']) : null,
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
            'is_shop'                => (!empty($_POST['is_shop']) || ($_POST['unit_type'] ?? '') === 'commercial') ? 1 : 0,
            'unit_type'              => (!empty($_POST['is_shop']) && ($_POST['unit_type'] ?? '') === 'residential') ? 'commercial' : ($_POST['unit_type'] ?? 'residential'),
            'price_value'            => isset($_POST['price_value']) && $_POST['price_value'] !== '' ? str_replace(',', '', $_POST['price_value']) : null,
            'shop_service_charge'    => isset($_POST['shop_service_charge']) && $_POST['shop_service_charge'] !== '' ? str_replace(',', '', $_POST['shop_service_charge']) : null,
            'land_share_value'       => isset($_POST['land_share_value']) && $_POST['land_share_value'] !== '' ? str_replace(',', '', $_POST['land_share_value']) : null,
            'development_charges'    => isset($_POST['development_charges']) && $_POST['development_charges'] !== '' ? str_replace(',', '', $_POST['development_charges']) : null,
            'facilities'             => $_POST['facilities'] ?? null,
            'created_by'             => $_SESSION['user']['id'] ?? null,
            'suite_id'               => $_POST['suite_id'] ?? null,
            'ownership_type'         => !empty($_POST['ownership_type']) ? $_POST['ownership_type'] : null,
            'per_sqft_price'         => isset($_POST['per_sqft_price']) && $_POST['per_sqft_price'] !== '' ? str_replace(',', '', $_POST['per_sqft_price']) : null,
            'booking_money'          => isset($_POST['booking_money']) && $_POST['booking_money'] !== '' ? str_replace(',', '', $_POST['booking_money']) : null,
            'registration_money'     => isset($_POST['registration_money']) && $_POST['registration_money'] !== '' ? str_replace(',', '', $_POST['registration_money']) : null,
        ];

        // Update lead
        try {
            $this->flatModel->update($id, $data);
            setSuccess("Flat updated successfully!");
            $redirectUrl = $_POST['return_url'] ?? "{$GLOBALS['base_url']}/flat-lists";
            header("Location: {$redirectUrl}");
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
                'nar'                    => '_ignore',        // second project col in some templates
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
                'ownership_type'     => 'ownership_type',
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

            // ── Step 3: De-duplicate headers so array_combine never gets duplicate keys.
            //   Repeated headers (e.g. two columns both aliased to project_name) get
            //   renamed _dup1, _dup2 … and will simply be skipped during insert.
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
            $errors        = [];
            $skippedRows   = []; // detailed list: [rowNum, project, flat, reason]
            $rowNum        = 1;
            $projectsCache = [];

            while (($row = fgetcsv($handle, 2000, ",")) !== false) {
                $rowNum++;
                if (count(array_filter($row)) === 0) continue;

                // Partial data for nicer error messages even on column-count mismatch
                $partialData = (count($header) === count($row))
                    ? array_combine($header, $row)
                    : array_combine(
                        array_slice($header, 0, count($row)),
                        $row
                    );
                $errProject = trim($partialData['project_name'] ?? '—');
                $errFlat    = trim($partialData['flat_name']    ?? '—');

                if (count($header) !== count($row)) {
                    $reason = "Column count mismatch (expected " . count($header) . ", got " . count($row) . ")";
                    $errors[]      = "Row $rowNum [{$errProject} / {$errFlat}]: $reason — skipped";
                    $skippedRows[] = ['row' => $rowNum, 'project' => $errProject, 'flat' => $errFlat, 'reason' => $reason];
                    continue;
                }

                $data = $partialData;

                // ── Resolve project_id from project name ──────────────────
                $projectName = trim($data['project_name'] ?? '');
                if (empty($projectName)) {
                    $reason = "project_name is empty";
                    $errors[]      = "Row $rowNum [/ {$errFlat}]: $reason — skipped";
                    $skippedRows[] = ['row' => $rowNum, 'project' => '(empty)', 'flat' => $errFlat, 'reason' => $reason];
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
                    $reason = "Project not found in system";
                    $errors[]      = "Row $rowNum [{$projectName} / {$errFlat}]: $reason — skipped";
                    $skippedRows[] = ['row' => $rowNum, 'project' => $projectName, 'flat' => $errFlat, 'reason' => $reason];
                    continue;
                }

                // ── flat_name required ────────────────────────────────────
                $flatName = trim($data['flat_name'] ?? '');
                if (empty($flatName)) {
                    $reason = "flat_name is empty";
                    $errors[]      = "Row $rowNum [{$projectName} / (empty)]: $reason — skipped";
                    $skippedRows[] = ['row' => $rowNum, 'project' => $projectName, 'flat' => '(empty)', 'reason' => $reason];
                    continue;
                }

                $data['project_id'] = $projectId;
                $data['created_by'] = $_SESSION['user']['id'] ?? null;
                $data['upload_from'] = 'From company csv'; // CSV bulk import tag

                // ── Normalize status column ────────────────────────────
                $rawStatus = strtolower(trim($data['status'] ?? ''));
                $statusMap = [
                    'available'  => 'available',
                    'unsold'     => 'available',
                    'on sale'    => 'available',
                    'on_sale'    => 'available',
                    'for sale'   => 'available',
                    'for_sale'   => 'available',
                    'open'       => 'available',
                    'booked'     => 'booked',
                    'reserved'   => 'booked',
                    'sold'       => 'sold',
                    'sold out'   => 'sold',
                    'sold_out'   => 'sold',
                ];
                $data['status']    = $statusMap[$rawStatus] ?? 'available';
                $data['is_booked'] = in_array($data['status'], ['booked', 'sold']) ? 1 : 0;

                try {
                    // DEBUG: log price_value raw value — remove after fix
                    error_log("Row $rowNum price_value raw=[" . ($data['price_value'] ?? 'KEY_NOT_FOUND') . "] land_share=[" . ($data['land_share_value'] ?? 'KEY_NOT_FOUND') . "]");
                    $this->flatModel->createFromCsvBulk($data);
                    $successCount++;
                } catch (Exception $e) {
                    $reason = "DB Error — " . $e->getMessage();
                    $errors[]      = "Row $rowNum [{$projectName} / {$flatName}]: $reason";
                    $skippedRows[] = ['row' => $rowNum, 'project' => $projectName, 'flat' => $flatName, 'reason' => $reason];
                    error_log("Flat CSV Import Error (row $rowNum): " . $e->getMessage());
                }
            }

            // Store full skipped-row details in session for the view to render
            $_SESSION['csv_import_skipped'] = $skippedRows;
            fclose($handle);

            $skippedCount = count($skippedRows);

            if ($successCount > 0) {
                $msg = "{$successCount} flat(s) imported successfully.";
                if ($skippedCount > 0) {
                    $msg .= " {$skippedCount} row(s) were skipped — see the details below.";
                }
                setSuccess($msg);
            } else {
                $msg = "No flats were imported.";
                if ($skippedCount > 0) {
                    $msg .= " {$skippedCount} row(s) were skipped — see the details below.";
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
        // ──────────────────────────────────────────────────────────────────────
    //  Suite CSV Bulk Import
    // ──────────────────────────────────────────────────────────────────────
    public function importCsvBulkSuite()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $projectId = $_POST['project_id'] ?? null;
        if (empty($projectId)) {
            setError("Project is required");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        }

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
            $rawHeader[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $rawHeader[0]);
            
            $header = array_map(function ($col) {
                return strtolower(trim(preg_replace('/\s+/', '_', $col)));
            }, $rawHeader);

            $aliasMap = [
                'unit_name'          => 'flat_name',
                'floor'              => 'floor_no',
                'suite_no.'          => 'suite_id',
                'suite_no'           => 'suite_id',
                'unit_type'          => 'unit_type',
                'size_(sq_ft)'       => 'sqft',
                'size_sq_ft'         => 'sqft',
                'size'               => 'sqft',
                'rate_per_sft_(bdt)' => 'per_sqft_price',
                'rate_per_sft_bdt'   => 'per_sqft_price',
                'booking_20%'        => 'booking_money',
                'booking_20'         => 'booking_money',
                'registration_20%'   => 'registration_money',
                'registration_20'    => 'registration_money',
                'development_10%'    => 'development_charges',
                'development_10'     => 'development_charges',
                'total_price'        => 'price_value',
                'facing_site'        => 'facing_site',
                'ownership_type'     => 'ownership_type',
                'ownership_type'     => 'ownership_type',
                'ownership_type'     => 'ownership_type',
                'facilities'         => 'facilities',
                'status'             => 'status'
            ];

            $normalizedHeader = [];
            foreach ($header as $col) {
                $cleanCol = str_replace(['(', ')'], '', $col);
                $normalizedHeader[] = $aliasMap[$col] ?? $aliasMap[$cleanCol] ?? $col;
            }

            $successCount = 0;
            $skippedCount = 0;
            $skippedRows = [];
            $rowNum = 1;

            while (($data = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (array_filter($data) === []) continue;

                $row = array_combine($normalizedHeader, $data);
                
                $flatName = trim($row['flat_name'] ?? '');
                if (empty($flatName)) {
                    $skippedRows[] = ['row' => $rowNum, 'project' => 'ID: '.$projectId, 'flat' => '', 'reason' => 'Flat/Unit name is empty'];
                    $skippedCount++;
                    continue;
                }

                if ($this->flatModel->checkIfExists($projectId, $flatName)) {
                    $skippedRows[] = ['row' => $rowNum, 'project' => 'ID: '.$projectId, 'flat' => $flatName, 'reason' => 'Suite already exists'];
                    $skippedCount++;
                    continue;
                }

                $statusRaw = strtolower(trim($row['status'] ?? ''));
                $statusMap = [
                    'available' => 'available',
                    'booked'    => 'booked',
                    'sold'      => 'sold'
                ];
                $status = $statusMap[$statusRaw] ?? 'available';
                $isBooked = ($status === 'booked' || $status === 'sold') ? 1 : 0;
                
                $bookingMoney = !empty($row['booking_money']) ? str_replace(',', '', $row['booking_money']) : 0;
                $registrationMoney = !empty($row['registration_money']) ? str_replace(',', '', $row['registration_money']) : 0;
                $landShareValue = $bookingMoney + $registrationMoney;
                
                $perSqftPrice = !empty($row['per_sqft_price']) ? str_replace(',', '', $row['per_sqft_price']) : 0;
                $sqft = !empty($row['sqft']) ? str_replace(',', '', $row['sqft']) : 0;
                $priceValue = !empty($row['price_value']) ? str_replace(',', '', $row['price_value']) : ($perSqftPrice * $sqft);

                $insertData = [
                    'project_id'             => $projectId,
                    'flat_name'              => $flatName,
                    'sqft'                   => (float)$sqft,
                    'bhk'                    => 0,
                    'bedroom'                => 0,
                    'bathroom'               => 0,
                    'balcony'                => 0,
                    'drawing_dining_kitchen' => 0,
                    'common_dining'          => 0,
                    'floor_no'               => trim($row['floor_no'] ?? ''),
                    'facing_site'            => trim($row['facing_site'] ?? ''),
                    'is_shop'                => 0,
                    'unit_type'              => trim($row['unit_type'] ?? 'residential'),
                    'price_value'            => (float)$priceValue,
                    'shop_service_charge'    => 0,
                    'facilities'             => trim($row['facilities'] ?? ''),
                    'land_share_value'       => (float)$landShareValue,
                    'development_charges'    => !empty($row['development_charges']) ? (float)str_replace(',', '', $row['development_charges']) : 0,
                    'created_by'             => $_SESSION['user']['id'] ?? null,
                    'status'                 => $status,
                    'is_booked'              => $isBooked,
                    'upload_from'            => 'From company csv',
                    'suite_id'               => trim($row['suite_id'] ?? ''),
                    'ownership_type'         => !empty(trim($row['ownership_type'] ?? '')) ? trim($row['ownership_type'] ?? '') : null,
                    'per_sqft_price'         => (float)$perSqftPrice,
                    'booking_money'          => (float)$bookingMoney,
                    'registration_money'     => (float)$registrationMoney
                ];

                if ($this->flatModel->createFromCsvBulkSuite($insertData)) {
                    $successCount++;
                } else {
                    $skippedRows[] = ['row' => $rowNum, 'project' => 'ID: '.$projectId, 'flat' => $flatName, 'reason' => 'DB insert failed'];
                    $skippedCount++;
                }
            }

            fclose($handle);
            $_SESSION['csv_import_skipped'] = $skippedRows;
            
            if ($successCount > 0) {
                $msg = "{$successCount} suite(s) imported successfully.";
                if ($skippedCount > 0) $msg .= " {$skippedCount} row(s) skipped.";
                setSuccess($msg);
            } else {
                $msg = "No suites were imported.";
                if ($skippedCount > 0) $msg .= " {$skippedCount} row(s) skipped.";
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
                'ownership_type'     => 'ownership_type',
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

                // shop_service_charge: decimal % (fractions allowed, e.g. 7.5)
                $data['shop_service_charge'] = !empty($data['shop_service_charge'])
                    ? (float)str_replace([',', '%'], '', $data['shop_service_charge'])
                    : null;

                // ── Normalize status column ────────────────────────────
                // Maps various CSV status values → DB enum('available','booked','sold')
                $rawStatus = strtolower(trim($data['status'] ?? ''));
                $statusMap = [
                    'available'  => 'available',
                    'unsold'     => 'available',   // unsold = available
                    'on sale'    => 'available',   // on sale = available
                    'on_sale'    => 'available',
                    'for sale'   => 'available',
                    'for_sale'   => 'available',
                    'open'       => 'available',
                    'booked'     => 'booked',
                    'reserved'   => 'booked',
                    'sold'       => 'sold',
                    'sold out'   => 'sold',
                    'sold_out'   => 'sold',
                ];
                $data['status']    = $statusMap[$rawStatus] ?? 'available';
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
                $msg = " <b>$successCount</b> / <b>$totalRows</b> Shops imported successfully.";
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
