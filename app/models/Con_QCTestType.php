<?php
class Con_QCTestType extends Model {
    protected $db;

    public function __construct($db = null) {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    public function getAll() {
        $sql = "SELECT * FROM qc_test_types ORDER BY id ASC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM qc_test_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO qc_test_types (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $data['name'], $data['description']);
        return $stmt->execute() ? $this->db->insert_id : false;
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE qc_test_types SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $data['name'], $data['description'], $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM qc_test_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
