<?php

class Project extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /* =========================
       Get All Projects
    ========================== */
    public function getAll()
    {
        $sql = $this->db->prepare("
        SELECT 
            p.*,
            l.name AS location_name,
            c.name AS community_name,
            pc.name AS category_name
        FROM projects AS p
        LEFT JOIN locations AS l ON p.location_id = l.id
        LEFT JOIN communities AS c ON p.community_id = c.id
        LEFT JOIN project_categories AS pc ON p.category_id = pc.id
        WHERE p.deleted_at IS NULL
        ORDER BY p.id DESC
    ");

        if (!$sql->execute()) {
            return [];
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


    public function countProject()
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) AS total_projects 
        FROM projects
        WHERE deleted_at IS NULL
    ");

        if (!$stmt->execute()) {
            return 0;
        }

        $result = $stmt->get_result()->fetch_assoc();
        return $result['total_projects'] ?? 0;
    }

    /* =========================
       Create Project
    ========================== */
    public function create($data)
    {
        // ======================
        // Image Uploads
        // ======================
        // $image = !empty($_FILES['image']['name']) ? uploadImage('image', 'projects', 'fixed') : null;
        // $image2 = !empty($_FILES['image2']['name']) ? uploadImage('image2', 'projects', 'fixed') : null;
        // $image3 = !empty($_FILES['image3']['name']) ? uploadImage('image3', 'projects', 'fixed') : null;
        // $map_image = !empty($_FILES['map_image']['name']) ? uploadImage('map_image', 'projects', 'fixed') : null;
        // $amenities_image = !empty($_FILES['amenities_image']['name']) ? uploadImage('amenities_image', 'projects', 'fixed') : null;
        $fields = ['image', 'image2', 'image3', 'map_image', 'amenities_image'];

        foreach ($fields as $field) {
            $$field = !empty($_FILES[$field]['name'])
                ? uploadImage($field, 'projects', 'original')
                : null;
        }

        // ======================
        // Data Formatting (যাতে Integer কলামে Empty String না যায়)
        // ======================
        $location_id      = !empty($data['location_id']) ? (int)$data['location_id'] : null;
        $community_id     = !empty($data['community_id']) ? (int)$data['community_id'] : null;
        $total_share      = !empty($data['total_share']) ? (int)$data['total_share'] : null;
        $unit_per_floor   = !empty($data['unit_per_floor']) ? (int)$data['unit_per_floor'] : null;
        $apartment_count  = !empty($data['apartment_count']) ? (int)$data['apartment_count'] : null;
        $passenger_lift   = !empty($data['passenger_lift']) ? (int)$data['passenger_lift'] : 0;
        $cargo_lift       = !empty($data['cargo_lift']) ? (int)$data['cargo_lift'] : 0;
        $created_by       = !empty($data['created_by']) ? (int)$data['created_by'] : null;
        $car_parking      = !empty($data['car_parking']) ? (int)$data['car_parking'] : 0;

        // Checkbox Handling (0 or 1)
        $rooftop_gardening  = isset($data['rooftop_gardening']) ? (int)$data['rooftop_gardening'] : 0;
        $swimming_pool      = isset($data['swimming_pool']) ? (int)$data['swimming_pool'] : 0;
        $convention         = isset($data['convention']) ? (int)$data['convention'] : 0;
        $electricity_backup = isset($data['electricity_backup']) ? (int)$data['electricity_backup'] : 0;
        $under_construction = isset($data['under_construction']) ? (int)$data['under_construction'] : 0;
        $is_common = isset($data['is_common']) ? (int)$data['is_common'] : 0;
        $price_range = !empty($data['price_range']) ? $data['price_range'] : null;
        // plot_size: float column — empty string causes "Data truncated" error
        $plot_size = (isset($data['plot_size']) && $data['plot_size'] !== '' && $data['plot_size'] !== null)
            ? (float)$data['plot_size']
            : null;
        // ======================
        // Insert Query
        $sql = "INSERT INTO projects (
                name, slug, location_id, community_id, category_id, incentive_percentage,
                building_type, building_face, suite_sizes, hotel_operation, construction_start, handover, delivery,
                total_share, unit_per_floor, floor_wise_unit_meta, types, image, 
                image2, image3, gallery_images, front_road, price_range, hover_text, 
                plot_size, plot_no, block_no, brochure_link, description, 
                apartment_count, amenities_details, amenities_image, map_image, map_link, 
                project_map_location, nearby_locations, passenger_lift, cargo_lift, created_by, rooftop_gardening, 
                swimming_pool, convention, electricity_backup, car_parking, project_sale_status,
                service_charge_per_emi,
                pm_id, site_engineer_id, landman_id, struct_engineer_id,
                draftsman_id, architect_id, estimator_id, qc_id, operator_id,
                purchase_user_id, under_construction,is_common
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            error_log('[Project::create] Prepare FAILED: ' . $this->db->error);
            setError('Project creation failed. DB: ' . $this->db->error);
            return false;
        }

        $category_id          = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $incentive_percentage = !empty($data['incentive_percentage']) ? (float)$data['incentive_percentage'] : null;
        $gallery_images       = !empty($data['gallery_images']) ? $data['gallery_images'] : null;
        $nearby_locations     = !empty($data['nearby_locations']) ? $data['nearby_locations'] : null;
        $pm_id                = !empty($data['pm_id']) ? (int)$data['pm_id'] : null;
        $site_engineer_id     = !empty($data['site_engineer_id']) ? (int)$data['site_engineer_id'] : null;
        $landman_id           = !empty($data['landman_id']) ? (int)$data['landman_id'] : null;
        $struct_engineer_id   = !empty($data['struct_engineer_id']) ? (int)$data['struct_engineer_id'] : null;
        $draftsman_id         = !empty($data['draftsman_id']) ? (int)$data['draftsman_id'] : null;
        $architect_id         = !empty($data['architect_id']) ? (int)$data['architect_id'] : null;
        $estimator_id         = !empty($data['estimator_id']) ? (int)$data['estimator_id'] : null;
        $qc_id                = !empty($data['qc_id']) ? (int)$data['qc_id'] : null;
        $operator_id          = !empty($data['operator_id']) ? (int)$data['operator_id'] : null;
        $purchase_user_id     = !empty($data['purchase_user_id']) ? (int)$data['purchase_user_id'] : null;

        $stmt->bind_param(
            "ssiiidsssssssiisssssssssdssssissssssiiiiiiiisdiiiiiiiiiiii",
            $data['name'],                   // 1
            $data['slug'],                   // 2
            $location_id,                    // 3 (i)
            $community_id,                   // 4 (i)
            $category_id,                    // 5 (i)
            $incentive_percentage,           // 6 (d)
            $data['building_type'],          // 7
            $data['building_face'],          // 8
            $data['suite_sizes'],            // 9
            $data['hotel_operation'],        // 10
            $data['construction_start'],     // 11
            $data['handover'],               // 12
            $data['delivery'],               // 13
            $total_share,                    // 14 (i)
            $unit_per_floor,                 // 15 (i)
            $data['floor_wise_unit_meta'],   // 16
            $data['types'],                  // 17
            $image,                          // 18
            $image2,                         // 19
            $image3,                         // 20
            $gallery_images,                 // 21
            $data['front_road'],             // 22
            $price_range,                    // 22
            $data['hover_text'],             // 23
            $plot_size,                      // 24 (d)
            $data['plot_no'],                // 25
            $data['block_no'],               // 26
            $data['brochure_link'],          // 27
            $data['description'],            // 28
            $apartment_count,                // 29 (i)
            $data['amenities_details'],      // 30
            $amenities_image,                // 31
            $map_image,                      // 32
            $data['map_link'],               // 33
            $data['project_map_location'],   // 34
            $nearby_locations,               // 35
            $passenger_lift,                 // 36 (i)
            $cargo_lift,                     // 36 (i)
            $created_by,                     // 37 (i)
            $rooftop_gardening,              // 38 (i)
            $swimming_pool,                  // 39 (i)
            $convention,                     // 40 (i)
            $electricity_backup,             // 41 (i)
            $car_parking,                    // 42 (i)
            $data['project_sale_status'],    // 43 (s)
            $data['service_charge_per_emi'], // 44 (d)
            $pm_id,                          // 45 (i)
            $site_engineer_id,               // 46 (i)
            $landman_id,                     // 47 (i)
            $struct_engineer_id,             // 48 (i)
            $draftsman_id,                   // 49 (i)
            $architect_id,                   // 50 (i)
            $estimator_id,                   // 51 (i)
            $qc_id,                          // 52 (i)
            $operator_id,                    // 53 (i)
            $purchase_user_id,               // 54 (i)
            $under_construction  ,            // 55 (i)
            $is_common              // 56 (i)
        );

        $result = $stmt->execute();
        if (!$result) {
            error_log('[Project::create] Execute FAILED: ' . $stmt->error);
            setError('Project creation failed. SQL Error: ' . $stmt->error);
        }
        return $result;
    }

    /* =========================
       Create Project From CSV
    ========================== */
    public function createFromCsvBulk($data)
    {
        $sql = "INSERT INTO projects (
            name, slug, location_id, community_id, building_type, 
            total_share, unit_per_floor, floor_wise_unit_meta, types, image, 
            image2, image3, front_road, price_range, hover_text, tag,
            plot_size, plot_no, block_no, brochure_link, description, 
            apartment_count, amenities_details, amenities_image, map_image, map_link, 
            project_map_location, passenger_lift, cargo_lift, rooftop_gardening, 
            swimming_pool, convention, electricity_backup, car_parking, project_sale_status,
            service_charge_per_emi, project_status, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?, ?, ?,
            ?, ?, ?
        )";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new Exception($this->db->error);

        // Helper to handle empty strings as null
        $v = function($key) use ($data) {
            return (isset($data[$key]) && trim($data[$key]) !== '') ? trim($data[$key]) : null;
        };

        $createdBy = $data['created_by'] ?? null;
        
        $v_name = $v('name');
        $v_slug = $v('slug');
        if (empty($v_slug) && !empty($v_name)) {
            $v_slug = generateUniqueSlug($this->db, $v_name, 'projects', 'slug');
        }
        $v_location_id = $v('location_id'); $v_community_id = $v('community_id'); $v_building_type = $v('building_type');
        $v_total_share = $v('total_share'); $v_unit_per_floor = $v('unit_per_floor'); $v_floor_wise_unit_meta = $v('floor_wise_unit_meta'); $v_types = $v('types'); $v_image = $v('image');
        $v_image2 = $v('image2'); $v_image3 = $v('image3'); $v_front_road = $v('front_road'); $v_price_range = $v('price_range'); $v_hover_text = $v('hover_text'); $v_tag = $v('tag');
        $v_plot_size = $v('plot_size'); $v_plot_no = $v('plot_no'); $v_block_no = $v('block_no'); $v_brochure_link = $v('brochure_link'); $v_description = $v('description');
        $v_apartment_count = $v('apartment_count'); $v_amenities_details = $v('amenities_details'); $v_amenities_image = $v('amenities_image'); $v_map_image = $v('map_image'); $v_map_link = $v('map_link');
        $v_project_map_location = $v('project_map_location'); $v_passenger_lift = $v('passenger_lift'); $v_cargo_lift = $v('cargo_lift'); $v_rooftop_gardening = $v('rooftop_gardening');
        $v_swimming_pool = $v('swimming_pool'); $v_convention = $v('convention'); $v_electricity_backup = $v('electricity_backup'); $v_car_parking = $v('car_parking'); $v_project_sale_status = $v('project_sale_status');
        $v_service_charge_per_emi = $v('service_charge_per_emi'); $v_project_status = $v('project_status');

        $bindStr = str_repeat('s', 38);
        $stmt->bind_param(
            $bindStr,
            $v_name, $v_slug, $v_location_id, $v_community_id, $v_building_type,
            $v_total_share, $v_unit_per_floor, $v_floor_wise_unit_meta, $v_types, $v_image,
            $v_image2, $v_image3, $v_front_road, $v_price_range, $v_hover_text, $v_tag,
            $v_plot_size, $v_plot_no, $v_block_no, $v_brochure_link, $v_description,
            $v_apartment_count, $v_amenities_details, $v_amenities_image, $v_map_image, $v_map_link,
            $v_project_map_location, $v_passenger_lift, $v_cargo_lift, $v_rooftop_gardening,
            $v_swimming_pool, $v_convention, $v_electricity_backup, $v_car_parking, $v_project_sale_status,
            $v_service_charge_per_emi, $v_project_status, $createdBy
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        return true;
    }


    /* =========================
       Find Project (Edit)
    ========================== */
    public function find($id)
    {
        $stmt = $this->db->prepare("
        SELECT 
            p.*, 
            l.name AS location_name, 
            c.name AS community_name,
            pc.name AS category_name
        FROM projects AS p
        LEFT JOIN locations AS l ON p.location_id = l.id
        LEFT JOIN communities AS c ON p.community_id = c.id
        LEFT JOIN project_categories AS pc ON p.category_id = pc.id
        WHERE p.id = ?
    ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    /**
     * Update lead
     */
    public function update($id, $data)
    {
        // ======================
        // Get Old Images
        // ======================
        $old = $this->find($id);

        if (!$old) {
            return false;
        }

        // ======================
        // Image Upload (fallback old)
        // ======================
        $imageFields = ['image', 'image2', 'image3', 'map_image', 'amenities_image'];
        foreach ($imageFields as $field) {
            $$field = $old[$field]; // default: keep old
            if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $up = uploadImage($field, 'projects', 'original');
                if ($up) $$field = $up;
            }
        }
        $plot_size = ($data['plot_size'] === '' || $data['plot_size'] === null)
            ? null
            : (float)$data['plot_size'];

        // ======================
        // Update Query
        // ======================
        $stmt = $this->db->prepare("
    UPDATE projects SET
        name = ?, 
        slug = ?, 
        location_id = ?, 
        community_id = ?,
        category_id = ?,
        incentive_percentage = ?,
        plot_no = ?, 
        block_no = ?, 
        building_type = ?,
        building_face = ?,
        suite_sizes = ?,
        hotel_operation = ?,
        construction_start = ?,
        handover = ?,
        delivery = ?,
        total_share = ?, 
        unit_per_floor = ?, 
        types = ?, 
        front_road = ?,
        hover_text = ?, 
        price_range = ?, 
        plot_size = ?, 
        brochure_link = ?,
        description = ?, 
        apartment_count = ?, 
        amenities_details = ?, 
        amenities_image = ?,
        map_image = ?, 
        map_link = ?, 
        passenger_lift = ?, 
        cargo_lift = ?,
        rooftop_gardening = ?, 
        swimming_pool = ?, 
        convention = ?, 
        electricity_backup = ?,
        image = ?, 
        image2 = ?, 
        image3 = ?,
        gallery_images = ?,
        car_parking = ?,
        created_by = ?,
        floor_wise_unit_meta = ?,
        project_map_location = ?,
        nearby_locations = ?,
        project_sale_status = ?,
        service_charge_per_emi = ?,
        pm_id = ?,
        site_engineer_id = ?,
        landman_id = ?,
        struct_engineer_id = ?,
        draftsman_id = ?,
        architect_id = ?,
        estimator_id = ?,
        qc_id = ?,
        operator_id = ?,
        purchase_user_id = ?,
        under_construction = ?,
        is_common = ?
    WHERE id = ?
");


        if (!$stmt) {
            setError($this->db->error);
            return false;
        }
        $pm_id             = !empty($data['pm_id']) ? (int)$data['pm_id'] : null;
        $site_engineer_id  = !empty($data['site_engineer_id']) ? (int)$data['site_engineer_id'] : null;
        $landman_id        = !empty($data['landman_id']) ? (int)$data['landman_id'] : null;
        $struct_engineer_id = !empty($data['struct_engineer_id']) ? (int)$data['struct_engineer_id'] : null;
        $draftsman_id      = !empty($data['draftsman_id']) ? (int)$data['draftsman_id'] : null;
        $architect_id      = !empty($data['architect_id']) ? (int)$data['architect_id'] : null;
        $estimator_id      = !empty($data['estimator_id']) ? (int)$data['estimator_id'] : null;
        $qc_id             = !empty($data['qc_id']) ? (int)$data['qc_id'] : null;
        $operator_id       = !empty($data['operator_id']) ? (int)$data['operator_id'] : null;
        $purchase_user_id  = !empty($data['purchase_user_id']) ? (int)$data['purchase_user_id'] : null;
        $under_construction = isset($data['under_construction']) ? (int)$data['under_construction'] : 0;
        $is_common = isset($data['is_common']) ? (int)$data['is_common'] : 0;
        $swimming_pool      = isset($data['swimming_pool']) ? (int)$data['swimming_pool'] : 0;
        $category_id        = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $incentive_percentage = (isset($data['incentive_percentage']) && $data['incentive_percentage'] !== '') ? (float)$data['incentive_percentage'] : null;
        $gallery_images     = isset($data['gallery_images']) ? $data['gallery_images'] : null;
        $nearby_locations   = isset($data['nearby_locations']) ? $data['nearby_locations'] : null;

        $stmt->bind_param(
            "ssiiidsssssssssisssssdssissssiiiissssssssssssdiiiiiiiiiiiii",
            $data['name'],
            $data['slug'],
            $data['location_id'],
            $data['community_id'],
            $category_id,
            $incentive_percentage,
            $data['plot_no'],
            $data['block_no'],
            $data['building_type'],
            $data['building_face'],
            $data['suite_sizes'],
            $data['hotel_operation'],
            $data['construction_start'],
            $data['handover'],
            $data['delivery'],
            $data['total_share'],
            $data['unit_per_floor'],
            $data['types'],
            $data['front_road'],
            $data['hover_text'],
            $data['price_range'],
            $plot_size,
            $data['brochure_link'],
            $data['description'],
            $data['apartment_count'],
            $data['amenities_details'],
            $amenities_image,
            $map_image,
            $data['map_link'],
            $data['passenger_lift'],
            $data['cargo_lift'],
            $data['rooftop_gardening'],
            $swimming_pool,
            $data['convention'],
            $data['electricity_backup'],
            $image,
            $image2,
            $image3,
            $gallery_images,
            $data['car_parking'],
            $data['created_by'],
            $data['floor_wise_unit_meta'],
            $data['project_map_location'],
            $nearby_locations,
            $data['project_sale_status'],
            $data['service_charge_per_emi'],
            $pm_id,
            $site_engineer_id,
            $landman_id,
            $struct_engineer_id,
            $draftsman_id,
            $architect_id,
            $estimator_id,
            $qc_id,
            $operator_id,
            $purchase_user_id,
            $under_construction,
            $is_common,
            $id
        );


        $res = $stmt->execute();
        if (!$res) {
            error_log('[Project::update] Execute FAILED: ' . $stmt->error);
            setError('Project update failed. SQL Error: ' . $stmt->error);
        }
        return $res;
    }

    public function softDelete($projectId)
    {
        $this->db->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');

            $tables = [
                ['projects', 'id'],
                ['flats', 'project_id'],
                ['lead_bookings', 'project_id'],
                ['lead_follow_ups', 'project_id'],
            ];

            foreach ($tables as [$table, $col]) {
                $stmt = $this->db->prepare(
                    "UPDATE $table SET deleted_at=? WHERE $col=?"
                );
                $stmt->bind_param("si", $now, $projectId);
                $stmt->execute();
                $stmt->close();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    public function inventoryGetAll()
    {
        // 1. Fetch all projects summary
        $projects = [];
        $stmt = $this->db->prepare("
        SELECT 
            p.id AS project_id,
            p.name AS project_name,
            COUNT(f.id) AS total_flats,
            SUM(CASE WHEN f.id IS NOT NULL AND f.is_booked = 0 AND f.status != 'booked' AND f.status != 'sold' THEN 1 ELSE 0 END) AS available_flats,
            SUM(CASE WHEN f.id IS NOT NULL AND (f.is_booked = 1 OR f.status = 'booked') AND f.status != 'sold' THEN 1 ELSE 0 END) AS booked_flats,
            SUM(CASE WHEN f.id IS NOT NULL AND f.status = 'sold' THEN 1 ELSE 0 END) AS sold_flats,
            SUM(CASE WHEN f.id IS NOT NULL AND f.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_flats,
            COALESCE(SUM(f.price_value), 0) AS total_flat_value
        FROM projects p
        LEFT JOIN flats f ON f.project_id = p.id AND f.deleted_at IS NULL
        WHERE p.deleted_at IS NULL
        GROUP BY p.id
    ");

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $projects[$row['project_id']] = $row;
        }

        // 2. Fetch all flats grouped by project with safely scoped latest booking agreement value
        $stmt2 = $this->db->prepare("
        SELECT 
            f.id,
            f.project_id,
            f.flat_name,
            f.sqft,
            f.bhk,
            f.price_value,
            f.is_booked,
            f.status,
            CASE 
                WHEN f.is_booked = 1 OR f.status = 'booked' 
                THEN (
                    SELECT lb.agreement_value 
                    FROM lead_bookings lb 
                    WHERE lb.flat_id = f.id 
                      AND lb.deleted_at IS NULL 
                      AND lb.booking_status IN ('booked', 'completed')
                    ORDER BY lb.id DESC 
                    LIMIT 1
                )
                ELSE NULL
            END AS agreement_value
        FROM flats f
        WHERE f.deleted_at IS NULL
        ORDER BY f.project_id, f.flat_name
    ");


        $stmt2->execute();
        $flatsResult = $stmt2->get_result();

        while ($flat = $flatsResult->fetch_assoc()) {
            $projects[$flat['project_id']]['flats'][] = $flat;
        }

        return array_values($projects); // return numeric array
    }

    /* =========================
       Get Projects with Active Bookings (Sold/Booked)
    ========================== */
    public function getProjectsWithActiveBookings()
    {
        $sql = "SELECT DISTINCT p.id, p.name 
                FROM projects p
                INNER JOIN lead_bookings lb ON lb.project_id = p.id
                INNER JOIN lead_payments lp ON lp.booking_id = lb.id
                WHERE lb.deleted_at IS NULL 
                  AND lb.booking_status IN ('booked', 'completed')
                  AND lp.payment_status = 'paid'
                  AND p.deleted_at IS NULL
                ORDER BY p.name ASC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
