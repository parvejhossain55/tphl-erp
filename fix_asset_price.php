<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/AssetManagement.php';
$content = file_get_contents($file);

$old = <<<'PHP'
                $qty = (int)$req['quantity'];
                for ($i = 0; $i < $qty; $i++) {
                    $code = $this->generateAssetCode();
                    
                    $finalAssignedTo = $assignedTo !== null ? $assignedTo : ($req['assigned_to'] ? (int)$req['assigned_to'] : null);
                    $finalLocation = $location !== null && $location !== '' ? $location : $req['asset_location'];
                    $assetStatus = $finalAssignedTo ? 'in_use' : 'in_store';
                    
                    $insertStmt = $this->db->prepare("INSERT INTO office_assets (asset_code, product_id, item_name, project_id, location, assigned_to, purchase_price, purchase_date, condition_status, asset_status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'new', ?, ?, NOW())");
                    $insertStmt->bind_param('sisisisdi', $code, $productId, $req['item_name'], $req['project_id'], $finalLocation, $finalAssignedTo, $req['unit_price'], $assetStatus, $userId);
                    $insertStmt->execute();
                }
PHP;

$new = <<<'PHP'
                $qty = (int)$req['quantity'];
                
                // Calculate correct unit price factoring in returned amount
                $paidAmt = (float)($req['paid_amount'] > 0 ? $req['paid_amount'] : $req['total_amount']);
                $totalSpent = $paidAmt - (float)$returnAmount;
                $actualUnitPrice = $qty > 0 ? ($totalSpent / $qty) : (float)$req['unit_price'];

                for ($i = 0; $i < $qty; $i++) {
                    $code = $this->generateAssetCode();
                    
                    $finalAssignedTo = $assignedTo !== null ? $assignedTo : ($req['assigned_to'] ? (int)$req['assigned_to'] : null);
                    $finalLocation = $location !== null && $location !== '' ? $location : $req['asset_location'];
                    $assetStatus = $finalAssignedTo ? 'in_use' : 'in_store';
                    
                    $insertStmt = $this->db->prepare("INSERT INTO office_assets (asset_code, product_id, item_name, project_id, location, assigned_to, purchase_price, purchase_date, condition_status, asset_status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'new', ?, ?, NOW())");
                    $insertStmt->bind_param('sisisisdi', $code, $productId, $req['item_name'], $req['project_id'], $finalLocation, $finalAssignedTo, $actualUnitPrice, $assetStatus, $userId);
                    $insertStmt->execute();
                }
PHP;

$content = str_replace($old, $new, $content);
file_put_contents('c:/xampp/htdocs/TPHL-ERP/app/models/AssetManagement.php', $content);
