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
            ORDER BY p.id DESC
        ");

        if (!$sql->execute()) {
            return [];
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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
            name, location_id, community_id,plot_no,block_no, building_type,
            total_share, unit_per_floor, types, front_road,
            hover_text, price_range, plot_size, brochure_link,
            description, apartment_count, amenities_details, amenities_image,
            map_image, map_link, passenger_lift, cargo_lift,
            rooftop_gardening, swimming_pool, convention, electricity_backup,
            image, image2, image3
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    if (!$stmt) {
        setError($this->db->error);
        return false;
    }

$stmt->bind_param(
    "sssssssssssssssssssssssssssss",
    $data['name'],
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
    $image3
);

    return $stmt->execute();
}

    /* =========================
       Find Project (Edit)
    ========================== */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT p.*,l.name AS location_name, c.name AS community_name 
          FROM projects  AS p 
          JOIN locations AS l ON p.location_id = l.id
          JOIN communities AS c ON p.community_id = c.id
          WHERE p.id = ?");
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
            image3 = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        setError($this->db->error);
        return false;
    }

    $stmt->bind_param(
        "sssssssssssssssssssssssssssssi",
        $data['name'],
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
        $id
    );

    return $stmt->execute();
}

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
