<?php

class Slider extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all Departments with paginate

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM sliders");

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }


    // Create a new role

    public function create($data, $photoInputName = "")
    {
        $image = null;

        if (!empty($_FILES[$photoInputName]['name'])) {
            $image = uploadImage($photoInputName, 'sliders');
        }
        if (!$image) {
            $image = '';
        }

        $stmt = $this->db->prepare(
            "INSERT INTO sliders (title, subtitle, image, status, link,created_by) VALUES (?,?,?,?,?,?)"
        );

        $stmt->bind_param(
            "sssisi",
            $data['title'],
            $data['subtitle'],
            $image,
            $data['status'],
            $data['link'],
            $data['created_by']
        );

        if ($stmt->execute()) {
            return true;
        } else {
            echo "ERROR: " . $stmt->error;
            return false;
        }
    }

    public function find($id)
    {
        $sql = $this->db->prepare("SELECT * FROM sliders WHERE id = ? ");
        if (!$sql) {
            echo "SQL Prepare Error: " . $this->db->error;
            return false;
        }

        $sql->bind_param('i', $id);

        if ($sql->execute()) {
            $result = $sql->get_result();
            return $result->fetch_assoc(); // single row as associative array
        } else {
            echo "SQL Execute Error: " . $sql->error;
            return false;
        }
    }



    // Update role name + permissions
    public function update($data, $id, $photoInputName = null)
    {
        // Handle image
        $image = null;
        if ($photoInputName && !empty($_FILES[$photoInputName]['name'])) {
            $image = uploadImage($photoInputName, 'sliders');
        } else {
            $existing = $this->find($id);
            $image = $existing['image'] ?? '';
        }

        // Prepare update statement
        $stmt = $this->db->prepare(
            "UPDATE sliders SET title = ?, subtitle = ?, image = ?, status = ?, link = ?, created_by = ? WHERE id = ?"
        );
        if (!$stmt) {
            echo "Prepare failed: " . $this->db->error;
            return false;
        }

        $stmt->bind_param(
            "sssisis",
            $data['title'],
            $data['subtitle'],
            $image,
            $data['status'],
            $data['link'],
            $data['created_by'],
            $id
        );

        if (!$stmt->execute()) {
            echo "Execute failed: " . $stmt->error;
            return false;
        }

        $stmt->close();
        return true;
    }


    public function delete($id)
    {
        // 1 Delete all slider linked to this department
        $stmt1 = $this->db->prepare("DELETE FROM sliders WHERE id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();

      
        return true;
    }
}
