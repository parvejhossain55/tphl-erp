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
            c.name AS community_name
        FROM projects AS p
        LEFT JOIN locations AS l ON p.location_id = l.id
        LEFT JOIN communities AS c ON p.community_id = c.id
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
        $image = !empty($_FILES['image']['name']) ? uploadImage('image', 'projects', 'fixed') : '';
        $image2 = !empty($_FILES['image2']['name']) ? uploadImage('image2', 'projects', 'fixed') : '';
        $image3 = !empty($_FILES['image3']['name']) ? uploadImage('image3', 'projects', 'fixed') : '';
        $map_image = !empty($_FILES['map_image']['name']) ? uploadImage('map_image', 'projects', 'fixed') : '';
        $amenities_image = !empty($_FILES['amenities_image']['name']) ? uploadImage('amenities_image', 'projects', 'fixed') : '';

        // ======================
        // Insert Query
        // ======================
        $stmt = $this->db->prepare("
        INSERT INTO projects (
            name,slug, location_id, community_id,plot_no,block_no, building_type,
            total_share, unit_per_floor, types, front_road,
            hover_text, price_range, plot_size, brochure_link,
            description, apartment_count, amenities_details, amenities_image,
            map_image, map_link, passenger_lift, cargo_lift,
            rooftop_gardening, swimming_pool, convention, electricity_backup,
            image, image2, image3,car_parking,created_by,floor_wise_unit_meta,project_map_location,project_sale_status
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }

        $stmt->bind_param(
            "sssssssssssssssssssssssssssssssisss",
            $data['name'],
            $data['slug'],
            $data['location_id'],
            $data['community_id'],
            $data['plot_no'],
            $data['block_no'],
            $data['building_type'],
            $data['total_share'],
            $data['unit_per_floor'],
            $data['types'],
            $data['front_road'],
            $data['hover_text'],
            $data['price_range'],
            $data['plot_size'],
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
            $data['swimming_pool'],
            $data['convention'],
            $data['electricity_backup'],
            $image,
            $image2,
            $image3,
            $data['car_parking'],
            $data['created_by'],
            $data['floor_wise_unit_meta'],
            $data['project_map_location'],
            $data['project_sale_status']
        );

        return $stmt->execute();
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
            c.name AS community_name
        FROM projects AS p
        LEFT JOIN locations AS l ON p.location_id = l.id
        LEFT JOIN communities AS c ON p.community_id = c.id
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
        $image = !empty($_FILES['image']['name'])
            ? uploadImage('image', 'projects', 'fixed')
            : $old['image'];

        $image2 = !empty($_FILES['image2']['name'])
            ? uploadImage('image2', 'projects', 'fixed')
            : $old['image2'];

        $image3 = !empty($_FILES['image3']['name'])
            ? uploadImage('image3', 'projects', 'fixed')
            : $old['image3'];

        $map_image = !empty($_FILES['map_image']['name'])
            ? uploadImage('map_image', 'projects', 'fixed')
            : $old['map_image'];

        $amenities_image = !empty($_FILES['amenities_image']['name'])
            ? uploadImage('amenities_image', 'projects', 'fixed')
            : $old['amenities_image'];

        // ======================
        // Update Query
        // ======================
        $stmt = $this->db->prepare("
    UPDATE projects SET
        name = ?, 
        slug = ?, 
        location_id = ?, 
        community_id = ?, 
        plot_no = ?, 
        block_no = ?, 
        building_type = ?,
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
        car_parking = ?,
        created_by = ?,
        floor_wise_unit_meta = ?,
        project_map_location = ?,
        project_sale_status = ?
    WHERE id = ?
");


        if (!$stmt) {
            setError($this->db->error);
            return false;
        }
        $stmt->bind_param(
            "sssssssssssssssssssssssssssssssisssi",
            $data['name'],
            $data['slug'],
            $data['location_id'],
            $data['community_id'],
            $data['plot_no'],
            $data['block_no'],
            $data['building_type'],
            $data['total_share'],
            $data['unit_per_floor'],
            $data['types'],
            $data['front_road'],
            $data['hover_text'],
            $data['price_range'],
            $data['plot_size'],
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
            $data['swimming_pool'],
            $data['convention'],
            $data['electricity_backup'],
            $image,
            $image2,
            $image3,
            $data['car_parking'],
            $data['created_by'],
            $data['floor_wise_unit_meta'],
            $data['project_map_location'],
            $data['project_sale_status'],
            $id
        );


        return $stmt->execute();
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
            SUM(f.is_booked = 0) AS available_flats,
            SUM(f.is_booked = 1 AND f.status = 'booked') AS booked_flats,
            SUM(f.status = 'sold') AS sold_flats,
            SUM(f.status = 'cancelled') AS cancelled_flats,
            SUM(f.price_value) AS total_flat_value
        FROM projects p
        LEFT JOIN flats f ON f.project_id = p.id
        WHERE p.deleted_at IS NULL
        AND f.deleted_at IS NULL 
        GROUP BY p.id
    ");

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $projects[$row['project_id']] = $row;
        }

        // 2. Fetch all flats grouped by project
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
            THEN lb.agreement_value
            ELSE NULL
        END AS agreement_value
    FROM flats f
    LEFT JOIN lead_bookings lb 
        ON lb.flat_id = f.id
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
}
