<?php
/**
 * Batch update: Add COA support to remaining expense module controllers & views
 * Run once: php batch_coa_update.php
 */

$root = __DIR__;

// ============================================================
// 1. CON_VENDORPAYMENT CONTROLLER
// ============================================================
$file = "$root/app/controllers/Phase2/Con_VendorPaymentRequestController.php";
$content = file_get_contents($file);

// Add coaList to create view call
$content = str_replace(
    "\$this->view('phase2/vendor-payment-requests/create-payment', [",
    "\$coaList = getCOAExpenseList(\$this->db);\n        \$this->view('phase2/vendor-payment-requests/create-payment', [",
    $content
);
// Add coaList to array (find the closing ]);  of create view)
$content = preg_replace(
    "/(view\('phase2\/vendor-payment-requests\/create-payment',\s*\[.*?)\]\s*\);/s",
    '$1    \'coaList\' => $coaList,' . "\n        ]);\n",
    $content, 1
);
// Add account_id to POST parsing in store
$content = str_replace(
    "'created_by'       => \$this->userId,\n        ];\n\n        // Handle file upload\n        if (!empty(\$_FILES['attachment']['name']))",
    "'created_by'       => \$this->userId,\n            'account_id'       => !empty(\$_POST['account_id']) ? (int)\$_POST['account_id'] : null,\n        ];\n\n        // Handle file upload\n        if (!empty(\$_FILES['attachment']['name']))",
    $content
);
file_put_contents($file, $content);
echo "Updated: Con_VendorPaymentRequestController.php\n";

// ============================================================
// 2. PROJECT OTHER COSTS CONTROLLER
// ============================================================
$file = "$root/app/controllers/Phase2/Con_DashboardController.php";
echo "Skipped Con_DashboardController (project_other_costs managed from dashboard)\n";

// Find correct controller for project_other_costs
$files = glob("$root/app/controllers/Phase2/*.php");
foreach ($files as $f) {
    $c = file_get_contents($f);
    if (strpos($c, 'project_other_costs') !== false && strpos($c, "INSERT INTO project_other_costs") !== false) {
        echo "Found project_other_costs INSERT in: " . basename($f) . "\n";
        // Add account_id to INSERT
        $c = str_replace(
            "'created_by_id' => \$this->userId",
            "'created_by_id' => \$this->userId,\n            'account_id' => !empty(\$_POST['account_id']) ? (int)\$_POST['account_id'] : null",
            $c
        );
        file_put_contents($f, $c);
    }
}

// ============================================================
// 3. CASHOUT REQUESTS CONTROLLER
// ============================================================
foreach ($files as $f) {
    $c = file_get_contents($f);
    if (strpos($c, 'cashout_requests') !== false && strpos($c, "INSERT INTO cashout_requests") !== false) {
        echo "Found cashout_requests INSERT in: " . basename($f) . "\n";
        if (strpos($c, "'account_id'") === false) {
            $c = str_replace(
                "'requested_by' => \$userId",
                "'requested_by' => \$userId,\n            'account_id' => !empty(\$_POST['account_id']) ? (int)\$_POST['account_id'] : null",
                $c
            );
            file_put_contents($f, $c);
        }
    }
    if (strpos($c, 'cashout_requests') !== false && strpos($c, "->view(") !== false && strpos($c, 'cashout') !== false) {
        if (strpos($c, 'getCOAExpenseList') === false) {
            $c = str_replace(
                "\$this->view('cashout",
                "\$coaList = getCOAExpenseList(\$this->db);\n        \$this->view('cashout",
                $c
            );
            file_put_contents($f, $c);
            echo "Added getCOAExpenseList to cashout controller\n";
        }
    }
}

// ============================================================
// 4. LAND FUND REQUISITIONS CONTROLLER
// ============================================================
foreach ($files as $f) {
    $c = file_get_contents($f);
    if ((strpos($c, 'land_fund_requisitions') !== false && strpos($c, "INSERT INTO land_fund_requisitions") !== false)) {
        echo "Found land_fund_requisitions INSERT in: " . basename($f) . "\n";
        if (strpos($c, "'account_id'") === false) {
            $c = str_replace(
                "'requested_by' => \$userId",
                "'requested_by' => \$userId,\n            'account_id' => !empty(\$_POST['account_id']) ? (int)\$_POST['account_id'] : null",
                $c
            );
            file_put_contents($f, $c);
        }
    }
}

// ============================================================
// 5. PROJECT OTHER COSTS (project-other-costs views)
// ============================================================
$pocFiles = glob("$root/app/controllers/*.php");
foreach ($pocFiles as $f) {
    $c = file_get_contents($f);
    if (strpos($c, 'project_other_costs') !== false && strpos($c, 'project-other-costs') !== false) {
        echo "POC controller: " . basename($f) . "\n";
    }
}

echo "\nBatch update complete!\n";
