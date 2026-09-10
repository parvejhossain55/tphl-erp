<?php

class HrSignature extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db;
    }

    /**
     * Retrieve all active official signatures.
     * Default signature is ordered first.
     */
    public function getAllActive()
    {
        $sql = "SELECT * FROM hr_signatures WHERE status = 'active' ORDER BY is_default DESC, id DESC";
        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Find single signature by ID
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM hr_signatures WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: null;
    }

    /**
     * Get current default signature
     */
    public function getDefault()
    {
        $sql = "SELECT * FROM hr_signatures WHERE is_default = 1 AND status = 'active' LIMIT 1";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        // Fallback to first active signature if none marked as default
        $fallback = $this->db->query("SELECT * FROM hr_signatures WHERE status = 'active' ORDER BY id ASC LIMIT 1");
        if ($fallback && $fallback->num_rows > 0) {
            return $fallback->fetch_assoc();
        }
        return null;
    }

    /**
     * Create a new official HR signature record
     */
    public function create($data)
    {
        $isDefault = !empty($data['is_default']) ? 1 : 0;

        // If newly added signature is marked default, or if it's the very first signature, reset others
        $countRes = $this->db->query("SELECT COUNT(*) AS total FROM hr_signatures WHERE status = 'active'");
        $totalCount = ($countRes) ? (int)$countRes->fetch_assoc()['total'] : 0;
        if ($totalCount === 0) {
            $isDefault = 1;
        } elseif ($isDefault === 1) {
            $this->db->query("UPDATE hr_signatures SET is_default = 0");
        }

        $stmt = $this->db->prepare("
            INSERT INTO hr_signatures (signatory_name, signatory_title, signature_path, is_default, status)
            VALUES (?, ?, ?, ?, 'active')
        ");
        if (!$stmt) {
            return false;
        }

        $name = trim($data['signatory_name']);
        $title = trim($data['signatory_title']);
        $path = trim($data['signature_path']);

        $stmt->bind_param("sssi", $name, $title, $path, $isDefault);
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    /**
     * Set a specific signature as the default
     */
    public function setDefault($id)
    {
        $id = (int)$id;
        $this->db->query("UPDATE hr_signatures SET is_default = 0");
        $stmt = $this->db->prepare("UPDATE hr_signatures SET is_default = 1 WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Delete an official signature by ID and unlink file
     */
    public function delete($id)
    {
        $id = (int)$id;
        $sig = $this->find($id);
        if (!$sig) {
            return false;
        }

        $wasDefault = ($sig['is_default'] == 1);

        // Delete from database
        $stmt = $this->db->prepare("DELETE FROM hr_signatures WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $id);
        $deleted = $stmt->execute();

        if ($deleted) {
            // Unlink file if it exists and is stored in signatures folder
            $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
            $filePath = $baseDir . '/' . ltrim($sig['signature_path'], '/\\');
            if (file_exists($filePath) && is_file($filePath)) {
                // Only delete if it's in the signatures folder (to avoid accidentally deleting system assets)
                if (strpos($sig['signature_path'], 'public/assets/images/hrm/signatures/') !== false) {
                    @unlink($filePath);
                }
            }

            // If we deleted the default, assign a new default if any active signature remains
            if ($wasDefault) {
                $next = $this->db->query("SELECT id FROM hr_signatures WHERE status = 'active' ORDER BY id ASC LIMIT 1");
                if ($next && $row = $next->fetch_assoc()) {
                    $this->setDefault($row['id']);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Check and import legacy hr_signature.png if table is empty
     */
    public function seedLegacyIfEmpty()
    {
        $res = $this->db->query("SELECT COUNT(*) as total FROM hr_signatures");
        if ($res) {
            $row = $res->fetch_assoc();
            if ((int)$row['total'] === 0) {
                $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
                $legacyPath = 'public/assets/images/hrm/hr_signature.png';
                if (file_exists($baseDir . '/' . $legacyPath)) {
                    $this->create([
                        'signatory_name' => 'Md. Mostofa Kamal',
                        'signatory_title' => 'Head of HR & Admin',
                        'signature_path' => $legacyPath,
                        'is_default' => 1
                    ]);
                }
            }
        }
    }
}

