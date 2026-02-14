<?php

class Camera extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll(){
        $stmt = $this->db->prepare("SELECT * FROM cameras");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(MYSQLI_ASSOC);
    }
    // Get all Blogs with paginate
 

    // Optional: get camera by id
    public function find($camera_id) {
        $stmt = $this->db->prepare("SELECT * FROM cameras WHERE id = ?");
        $stmt->execute([$camera_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
