<?php
$file = 'C:/xampp/htdocs/TPHL-ERP/app/controllers/Phase2/Con_VendorPenaltyController.php';
$content = file_get_contents($file);

$insert = <<<EOD
    // =========================================================================
    // UPDATE STATUS (Enforce / Waive)
    // =========================================================================

    public function updateStatus()
    {
        if (in_array(\$_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . \$GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        \$id = (int)(\$_POST['id'] ?? 0);
        \$status = \$_POST['penalty_status'] ?? '';
        \$amount = (float)(\$_POST['penalty_amount'] ?? 0);
        
        if (!\$id || !in_array(\$status, ['Approved', 'Rejected'])) {
            setError('Invalid penalty status update request.');
            header("Location: {\$GLOBALS['base_url']}/con-vendor-penalties");
            exit;
        }

        \$stmt = \$this->db->prepare("UPDATE vendor_penalties SET penalty_status = ?, penalty_amount = ? WHERE id = ?");
        \$stmt->bind_param('sdi', \$status, \$amount, \$id);
        
        if (\$stmt->execute()) {
            if (\$status === 'Approved') {
                setSuccess("Penalty enforced successfully with amount: ৳ " . number_format(\$amount, 2));
            } else {
                setSuccess('Penalty waived/cancelled.');
            }
            // Recalculate vendor rating
            \$pen = \$this->penaltyModel->findById(\$id);
            if (\$pen && !empty(\$pen['vendor_id'])) {
                \$this->vendorModel->recalculateRating(\$pen['vendor_id']);
            }
        } else {
            setError('Failed to update penalty status.');
        }
        
        header("Location: {\$GLOBALS['base_url']}/con-vendor-penalties");
        exit;
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function delete()
EOD;

$content = str_replace(
    "    // =========================================================================\r\n    // DELETE\r\n    // =========================================================================\r\n\r\n    public function delete()", 
    $insert, 
    $content
);
$content = str_replace(
    "    // =========================================================================\n    // DELETE\n    // =========================================================================\n\n    public function delete()", 
    $insert, 
    $content
);

file_put_contents($file, $content);
echo "Done";
