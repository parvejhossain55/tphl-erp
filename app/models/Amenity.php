<?php

class Amenity extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all locations that have amenities, with a count of amenities
    public function getAllGroups()
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.id as location_id,
                l.name as location_name,
                COUNT(a.id) as total_amenities,
                MAX(u.full_name) as created_by_name
            FROM locations l
            JOIN amenities a ON l.id = a.location_id
            LEFT JOIN users u ON a.created_by = u.id
            GROUP BY l.id, l.name
            ORDER BY l.name ASC
        ");

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    // Get all individual amenities for a specific location
    public function getByLocation($location_id)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM amenities
            WHERE location_id = ?
            ORDER BY id ASC
        ");
        
        $stmt->bind_param("i", $location_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    // Create a new amenity
    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO amenities (location_id, name, image, created_by) VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issi",
            $data['location_id'],
            $data['name'],
            $data['image'],
            $data['created_by']
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        } else {
            echo "ERROR: " . $stmt->error;
            return false;
        }
    }

    // Update an existing amenity
    public function update($data)
    {
        $stmt = $this->db->prepare("UPDATE amenities SET name = ?, image = ? WHERE id = ?");
        
        $stmt->bind_param(
            "ssi",
            $data['name'],     
            $data['image'],     
            $data['id']       
        );

        if (!$stmt->execute()) {
            echo "Execute failed: " . $stmt->error;
            return false;
        }

        $stmt->close();
        return true;
    }

    // Delete a specific amenity by ID
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM amenities WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    // Delete all amenities for a location
    public function deleteByLocation($location_id)
    {
        $stmt = $this->db->prepare("DELETE FROM amenities WHERE location_id = ?");
        $stmt->bind_param("i", $location_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
