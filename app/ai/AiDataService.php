<?php

class AiDataService
{
    /**
     * Categorizes an expense item intelligently based on original category, item name, and source module.
     */
    private static function categorizeProjectExpense(string $originalCategory, string $itemName, string $sourceModule): string
    {
        $itemLower = strtolower(trim($itemName));
        $catLower = strtolower(trim($originalCategory));

        // 0. Material Expense (approved Purchase Orders)
        if ($sourceModule === 'material_expense') {
            return 'Material Expense';
        }

        // 1. Contractor & Civil Works (both legacy contractor PO items and modern contractor bills)
        if ($sourceModule === 'contractor_bill' || strpos($itemLower, 'contractor') !== false || strpos($itemLower, 'contracter') !== false) {
            return 'Contractor & Civil Works';
        }

        // 2. Labor & Daily Wages (SE labor requisitions or legacy labor items)
        if ($sourceModule === 'labor_bill' || strpos($itemLower, 'labor') !== false || strpos($itemLower, 'labour') !== false) {
            return 'Site Labor & Daily Wages';
        }

        // 3. Utility Bills (Electricity, Internet, Wifi, Water, Site Running)
        if (strpos($itemLower, 'electric') !== false || strpos($itemLower, 'current') !== false || 
            strpos($itemLower, 'internet') !== false || strpos($itemLower, 'wifi') !== false || 
            $catLower === 'utility bill' || strpos($itemLower, 'utility') !== false) {
            return 'Utility Bill (Electricity, Internet & Site Running)';
        }

        // 4. Site Cashouts & Petty Cash
        if ($sourceModule === 'cashout') {
            return 'Site Cashout & Petty Cash';
        }

        // 5. Expense Adjustments & Settlements
        if ($sourceModule === 'expense_adjustment') {
            return 'Expense Adjustments & Settlements';
        }

        // 6. Project Other Costs (Site Overhead & Miscellaneous)
        if ($sourceModule === 'project_other_cost') {
            return 'Project Other Costs & Miscellaneous';
        }

        // 7. Normalize Standard Material Categories
        if (!empty($originalCategory) && $originalCategory !== 'Uncategorized') {
            if (strcasecmp($originalCategory, 'shutter') === 0) {
                return 'Shuttering Material';
            }
            return $originalCategory;
        }

        return 'Others (Site Development & Construction Cost)';
    }

    /**
     * Gets project expense details grouped by category across all project expense modules:
     * 1. Vendor Payments & Material Purchases
     * 2. Contractor Bills
     * 3. Labor Requisitions
     * 4. Project Other Costs
     * 5. Cashout Requests (Project Linked)
     * 6. Expense Adjustments (Project Linked Settlements)
     */
    public static function getProjectExpenseDetails(\mysqli $db, int $projectId): array
    {
        $breakdown = [];
        $moduleTotals = [
            'material_expenses'   => 0.0,
            'vendor_payments'     => 0.0,
            'contractor_bills'    => 0.0,
            'labor_bills'         => 0.0,
            'project_other_costs' => 0.0,
            'cashouts'            => 0.0,
            'expense_adjustments' => 0.0
        ];
        $moduleDues = [
            'material_expenses'   => 0.0,
            'vendor_payments'     => 0.0,
            'contractor_bills'    => 0.0,
            'labor_bills'         => 0.0,
            'project_other_costs' => 0.0,
            'cashouts'            => 0.0
        ];
        $totalExpense = 0.0;
        $totalDue = 0.0;

        // -------------------------------------------------------------
        // 1. MATERIAL EXPENSES (APPROVED PURCHASE ORDERS & PO ITEMS)
        // -------------------------------------------------------------
        $poSql = "
            SELECT 
                po.id as po_id,
                po.status as po_status,
                COALESCE(po.order_date, DATE(po.created_at)) as order_date,
                COALESCE(NULLIF(TRIM(v.company_name), ''), v.name, 'Unknown') as vendor_name,
                poi.id as poi_id,
                poi.material_id,
                poi.quantity as qty,
                poi.unit_price,
                COALESCE(poi.total_price, poi.quantity * poi.unit_price) as item_total,
                m.name as expense_name,
                COALESCE(m.unit, 'Unit') as unit,
                COALESCE(c.name, 'Uncategorized') as category_name
            FROM purchase_orders po
            JOIN purchase_requests pr ON po.request_id = pr.id
            JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
            JOIN con_materials m ON poi.material_id = m.id
            LEFT JOIN con_material_categories c ON m.category_id = c.id
            LEFT JOIN vendors v ON COALESCE(poi.selected_vendor_id, po.vendor_id) = v.id
            WHERE pr.project_id = ?
              AND po.status NOT IN ('Cancelled', 'Draft')
              AND (
                  po.status IN ('Approved', 'Fully Received', 'Partially Received', 'QC Approved', 'Purchase Head Approved')
                  OR po.op_head_approved_by_id IS NOT NULL 
                  OR po.mgmt_approved_by_id IS NOT NULL 
                  OR po.purchase_head_approved_by_id IS NOT NULL 
                  OR po.qc_approved_by_id IS NOT NULL
              )
            ORDER BY order_date DESC, po.id DESC, poi.id ASC
        ";
        $stmt = $db->prepare($poSql);
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $qty = (float)$row['qty'];
                $unitPrice = (float)$row['unit_price'];
                $itemTotal = (float)($row['item_total'] ?: ($qty * $unitPrice));

                if ($itemTotal <= 0 && $qty <= 0) {
                    continue;
                }

                $category = 'Material Expense';
                if (!isset($breakdown[$category])) {
                    $breakdown[$category] = [];
                }

                $breakdown[$category][] = [
                    'expense_name'      => $row['expense_name'],
                    'material_category' => $row['category_name'],
                    'vendor_name'       => $row['vendor_name'],
                    'qty'               => $qty,
                    'unit'              => $row['unit'],
                    'unit_price'        => $unitPrice,
                    'total_amount'      => round($itemTotal, 2),
                    'payment_date'      => $row['order_date'],
                    'source_module'     => 'material_expense',
                    'po_id'             => (int)$row['po_id'],
                    'po_status'         => $row['po_status']
                ];

                $moduleTotals['material_expenses'] += $itemTotal;
                $totalExpense += $itemTotal;
            }
            $stmt->close();
        }

        // Keep vendor_payments in sync as alias for backwards compatibility
        $moduleTotals['vendor_payments'] = $moduleTotals['material_expenses'];

        // -------------------------------------------------------------
        // 2. CONTRACTOR BILLS (STRICT PAID)
        // -------------------------------------------------------------
        $cbSql = "
            SELECT 
                cbr.id,
                COALESCE(c.name, 'Contractor') as contractor_name,
                cbr.bill_type,
                cbr.bill_date,
                cbr.work_description,
                cbr.bill_amount,
                cbr.approved_amount,
                cbr.due_amount,
                cbr.status,
                COALESCE(ft.transaction_date_str, cbr.bill_date, DATE(cbr.updated_at)) as payment_date
            FROM contractor_bill_requests cbr
            LEFT JOIN contractors c ON cbr.operator_id = c.id
            LEFT JOIN con_financial_transactions ft ON ft.ref_type = 'contractor_bill' AND ft.ref_id = cbr.id
            WHERE cbr.project_id = ?
            ORDER BY payment_date DESC
        ";
        $stmt = $db->prepare($cbSql);
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $st = strtolower($row['status']);
                $approvedAmt = (float)($row['approved_amount'] ?: $row['bill_amount']);
                $dueAmt = (float)($row['due_amount'] ?? 0);
                $paidAmount = 0.0;

                if (in_array($st, ['paid', 'fully_paid'])) {
                    $paidAmount = $approvedAmt;
                } elseif ($st === 'partially_paid') {
                    $paidAmount = max(0.0, $approvedAmt - $dueAmt);
                    $moduleDues['contractor_bills'] += $dueAmt;
                } elseif ($st === 'approved') {
                    $moduleDues['contractor_bills'] += ($dueAmt > 0 ? $dueAmt : $approvedAmt);
                }

                if ($paidAmount <= 0) continue;

                $expName = (!empty($row['work_description'])) 
                    ? trim($row['work_description']) . " (Bill Type: " . $row['bill_type'] . ")"
                    : "Contractor Bill #" . $row['id'] . " (" . $row['bill_type'] . ")";

                $category = self::categorizeProjectExpense('', $expName, 'contractor_bill');
                if (!isset($breakdown[$category])) {
                    $breakdown[$category] = [];
                }

                $breakdown[$category][] = [
                    'expense_name' => $expName,
                    'vendor_name'  => $row['contractor_name'],
                    'qty'          => 1.0,
                    'unit'         => 'Bill',
                    'unit_price'   => $paidAmount,
                    'total_amount' => $paidAmount,
                    'payment_date' => $row['payment_date'],
                    'source_module'=> 'contractor_bill'
                ];

                $totalExpense += $paidAmount;
                $moduleTotals['contractor_bills'] += $paidAmount;
            }
            $stmt->close();
        }

        // -------------------------------------------------------------
        // 3. LABOR REQUISITIONS (STRICT PAID)
        // -------------------------------------------------------------
        $lrSql = "
            SELECT 
                lr.id,
                lr.requisition_no,
                lr.labor_type,
                lr.labor_count,
                lr.working_days,
                lr.per_labor_rate,
                lr.total_amount,
                lr.approved_amount,
                lr.paid_amount,
                lr.total_paid,
                lr.purpose,
                lr.status,
                COALESCE(lr.payment_date, DATE(lr.labor_disbursed_at), DATE(lr.created_at)) as payment_date
            FROM con_labor_requisitions lr
            WHERE lr.project_id = ?
            ORDER BY payment_date DESC
        ";
        $stmt = $db->prepare($lrSql);
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $st = strtolower($row['status']);
                $totalPaid = (float)($row['total_paid'] ?: $row['paid_amount']);
                $app = (float)($row['approved_amount'] ?: $row['total_amount']);

                if (in_array($st, ['management_approved', 'partially_paid'])) {
                    $moduleDues['labor_bills'] += max(0.0, $app - $totalPaid);
                }

                if ($totalPaid <= 0) continue;

                $expName = sprintf("[%s] %s Labor - %s", $row['requisition_no'], $row['labor_type'], $row['purpose'] ?: 'Site work');
                $category = self::categorizeProjectExpense('', $expName, 'labor_bill');
                if (!isset($breakdown[$category])) {
                    $breakdown[$category] = [];
                }

                $breakdown[$category][] = [
                    'expense_name' => $expName,
                    'vendor_name'  => 'Site Labor (' . $row['labor_type'] . ')',
                    'qty'          => (float)($row['labor_count'] ?: 1),
                    'unit'         => 'Person(s)',
                    'unit_price'   => (float)($row['per_labor_rate'] ?: $totalPaid),
                    'total_amount' => $totalPaid,
                    'payment_date' => $row['payment_date'],
                    'source_module'=> 'labor_bill'
                ];

                $totalExpense += $totalPaid;
                $moduleTotals['labor_bills'] += $totalPaid;
            }
            $stmt->close();
        }

        // -------------------------------------------------------------
        // 4. PROJECT OTHER COSTS (STRICT PAID)
        // -------------------------------------------------------------
        $pocSql = "
            SELECT 
                poc.id,
                poc.rcost_type,
                poc.purpose,
                poc.request_amount,
                poc.approved_amount,
                poc.payment_amount,
                poc.status,
                COALESCE(DATE(poc.payment_at), DATE(poc.created_at)) as payment_date
            FROM project_other_costs poc
            WHERE poc.project_id = ?
            ORDER BY payment_date DESC
        ";
        $stmt = $db->prepare($pocSql);
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $st = strtolower($row['status']);
                $amt = (float)($row['payment_amount'] ?: ($row['approved_amount'] ?: $row['request_amount']));

                if ($st === 'management_approved') {
                    $moduleDues['project_other_costs'] += $amt;
                }

                if ($st !== 'paid' || $amt <= 0) continue;

                $expName = sprintf("[%s] %s", ucwords(str_replace('_', ' ', $row['rcost_type'])), $row['purpose']);
                $category = self::categorizeProjectExpense('', $expName, 'project_other_cost');
                if (!isset($breakdown[$category])) {
                    $breakdown[$category] = [];
                }

                $breakdown[$category][] = [
                    'expense_name' => $expName,
                    'vendor_name'  => 'Site Office / Miscellaneous',
                    'qty'          => 1.0,
                    'unit'         => 'Item',
                    'unit_price'   => $amt,
                    'total_amount' => $amt,
                    'payment_date' => $row['payment_date'],
                    'source_module'=> 'project_other_cost'
                ];

                $totalExpense += $amt;
                $moduleTotals['project_other_costs'] += $amt;
            }
            $stmt->close();
        }

        // -------------------------------------------------------------
        // 5. CASHOUT REQUESTS (PROJECT WISE CASHOUT ADVANCES - STRICT PAID)
        // -------------------------------------------------------------
        $coSql = "
            SELECT 
                co.id,
                co.project_id,
                co.office,
                co.purpose_paid_to,
                co.amount,
                co.approved_amount,
                co.due_amount,
                co.description,
                co.status,
                DATE(co.created_at) as payment_date
            FROM cashout_requests co
            WHERE (co.project_id = ? OR co.project_id LIKE ?)
            ORDER BY payment_date DESC
        ";
        $stmt = $db->prepare($coSql);
        if ($stmt) {
            $likePid = "%," . $projectId . ",%";
            $stmt->bind_param("is", $projectId, $likePid);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $st = strtolower($row['status']);
                $approvedAmt = (float)($row['approved_amount'] ?: $row['amount']);
                $dueAmt = (float)($row['due_amount'] ?? 0);
                $paidAmount = 0.0;

                if (in_array($st, ['paid', 'fully_paid'])) {
                    $paidAmount = $approvedAmt;
                } elseif ($st === 'partially_paid') {
                    $paidAmount = max(0.0, $approvedAmt - $dueAmt);
                    $moduleDues['cashouts'] += $dueAmt;
                } elseif ($st === 'approved') {
                    $moduleDues['cashouts'] += ($dueAmt > 0 ? $dueAmt : $approvedAmt);
                }

                if ($paidAmount <= 0) continue;

                $expName = sprintf("Cashout #%d: %s (%s)", $row['id'], $row['purpose_paid_to'], $row['description'] ?: 'Site Expense Advance');
                $category = self::categorizeProjectExpense('', $expName, 'cashout');
                if (!isset($breakdown[$category])) {
                    $breakdown[$category] = [];
                }

                $breakdown[$category][] = [
                    'expense_name' => $expName,
                    'vendor_name'  => $row['purpose_paid_to'] ?: 'Site Cash Advance',
                    'qty'          => 1.0,
                    'unit'         => 'Advance',
                    'unit_price'   => $paidAmount,
                    'total_amount' => $paidAmount,
                    'payment_date' => $row['payment_date'],
                    'source_module'=> 'cashout'
                ];

                $totalExpense += $paidAmount;
                $moduleTotals['cashouts'] += $paidAmount;
            }
            $stmt->close();
        }

        // -------------------------------------------------------------
        // 6. EXPENSE ADJUSTMENTS / SETTLEMENTS (STRICT PAID)
        // -------------------------------------------------------------
        $eaSql = "
            SELECT 
                ea.id,
                ea.project_id,
                ea.parent_type,
                ea.adjustment_type,
                ea.title,
                ea.description,
                ea.amount,
                ea.approved_amount,
                ea.due_amount,
                ea.status,
                DATE(ea.created_at) as payment_date
            FROM expense_adjustments ea
            WHERE ea.project_id = ?
              AND ea.status IN ('fully_paid', 'partially_paid')
            ORDER BY payment_date DESC
        ";
        $stmt = $db->prepare($eaSql);
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $approvedAmt = (float)($row['approved_amount'] ?: $row['amount']);
                $dueAmt = (float)($row['due_amount'] ?? 0);
                $netAmt = ($row['status'] === 'partially_paid') ? max(0.0, $approvedAmt - $dueAmt) : $approvedAmt;

                if ($netAmt <= 0) continue;

                $isReturn = ($row['adjustment_type'] === 'return');
                $effectiveAmount = $isReturn ? (-1 * $netAmt) : $netAmt;

                $expName = sprintf("Adjustment #%d [%s]: %s (%s)", 
                    $row['id'], 
                    strtoupper($row['adjustment_type']), 
                    $row['title'], 
                    $isReturn ? 'Refund/Return to Company' : 'Extra Expense'
                );
                $category = self::categorizeProjectExpense('', $expName, 'expense_adjustment');
                if (!isset($breakdown[$category])) {
                    $breakdown[$category] = [];
                }

                $breakdown[$category][] = [
                    'expense_name' => $expName,
                    'vendor_name'  => 'Settlement (' . ucwords($row['parent_type']) . ')',
                    'qty'          => 1.0,
                    'unit'         => 'Adjustment',
                    'unit_price'   => $effectiveAmount,
                    'total_amount' => $effectiveAmount,
                    'payment_date' => $row['payment_date'],
                    'source_module'=> 'expense_adjustment'
                ];

                $totalExpense += $effectiveAmount;
                $moduleTotals['expense_adjustments'] += $effectiveAmount;
            }
            $stmt->close();
        }

        $totalDue = array_sum($moduleDues);

        // -------------------------------------------------------------
        // FORMAT CATEGORY BREAKDOWN
        // -------------------------------------------------------------
        $formattedBreakdown = [];
        foreach ($breakdown as $catName => $items) {
            $catTotal = array_sum(array_column($items, 'total_amount'));
            $formattedBreakdown[] = [
                'category_name' => $catName,
                'category_total'=> round($catTotal, 2),
                'item_count'    => count($items),
                'items'         => $items
            ];
        }

        // -------------------------------------------------------------
        // PROJECT SUMMARY & SQFT METRICS
        // -------------------------------------------------------------
        $pStmt = $db->prepare("SELECT total_share, floor_wise_unit_meta FROM projects WHERE id = ?");
        $totalSqft = 0;
        $totalShare = 0;
        $costPerSqft = 0;

        if ($pStmt) {
            $pStmt->bind_param("i", $projectId);
            $pStmt->execute();
            $pResult = $pStmt->get_result()->fetch_assoc();
            
            if ($pResult) {
                $totalShare = (float)$pResult['total_share'];
                $meta = json_decode($pResult['floor_wise_unit_meta'] ?? '{}', true);
                
                $floorSqft = 0;
                if (isset($meta['typical_floor']) && is_array($meta['typical_floor'])) {
                    foreach ($meta['typical_floor'] as $unit) {
                        $sqftVal = $unit['sqft'] ?? $unit['size'] ?? 0;
                        $floorSqft += (float)$sqftVal;
                    }
                } else {
                    if (is_array($meta)) {
                        foreach ($meta as $unit) {
                            if (is_array($unit)) {
                                $sqftVal = $unit['sqft'] ?? $unit['size'] ?? 0;
                                $floorSqft += (float)$sqftVal;
                            }
                        }
                    }
                }
                
                $totalSqft = $floorSqft * $totalShare;
            }
            $pStmt->close();
        }

        // Fallback: If totalSqft is still 0, calculate from flats table
        if ($totalSqft <= 0) {
            $fStmt = $db->prepare("SELECT SUM(sqft) as total_sqft FROM flats WHERE project_id = ?");
            if ($fStmt) {
                $fStmt->bind_param("i", $projectId);
                $fStmt->execute();
                $fRes = $fStmt->get_result()->fetch_assoc();
                if ($fRes && (float)$fRes['total_sqft'] > 0) {
                    $totalSqft = (float)$fRes['total_sqft'];
                }
                $fStmt->close();
            }
        }

        if ($totalSqft > 0) {
            $costPerSqft = $totalExpense / $totalSqft;
        }

        return [
            'summary' => [
                'total_expense'      => round($totalExpense, 2),
                'total_due_expense'  => round($totalDue, 2),
                'total_sqft'         => round($totalSqft, 2),
                'cost_per_sqft'      => round($costPerSqft, 2),
                'total_share'        => $totalShare,
                'cost_per_share'     => $totalShare > 0 ? round($totalExpense / $totalShare, 2) : 0,
                'module_summary'     => [
                    'material_expenses'   => round($moduleTotals['material_expenses'], 2),
                    'vendor_payments'     => round($moduleTotals['vendor_payments'], 2),
                    'contractor_bills'    => round($moduleTotals['contractor_bills'], 2),
                    'labor_bills'         => round($moduleTotals['labor_bills'], 2),
                    'project_other_costs' => round($moduleTotals['project_other_costs'], 2),
                    'cashouts'            => round($moduleTotals['cashouts'], 2),
                    'expense_adjustments' => round($moduleTotals['expense_adjustments'], 2),
                ],
                'module_due_summary' => [
                    'material_expenses'   => round($moduleDues['material_expenses'], 2),
                    'vendor_payments'     => round($moduleDues['vendor_payments'], 2),
                    'contractor_bills'    => round($moduleDues['contractor_bills'], 2),
                    'labor_bills'         => round($moduleDues['labor_bills'], 2),
                    'project_other_costs' => round($moduleDues['project_other_costs'], 2),
                    'cashouts'            => round($moduleDues['cashouts'], 2),
                ]
            ],
            'breakdown' => $formattedBreakdown
        ];
    }

    /**
     * Gets all pending approvals in the ERP.
     */
    public static function getPendingApprovals(\mysqli $db): array
    {
        $data = [
            'fund_requisitions' => [],
            'contractor_bills' => [],
            'vendor_payments' => [],
            'cashouts' => [],
            'land_funds' => []
        ];

        // 1. Fund Requisitions
        $res = $db->query("SELECT fr.id, p.name AS project_name, fr.amount, fr.purpose, fr.status 
                           FROM fund_requisitions fr 
                           JOIN projects p ON fr.project_id = p.id 
                           WHERE fr.status = 'pending'");
        if ($res) {
            $data['fund_requisitions'] = $res->fetch_all(MYSQLI_ASSOC);
        }

        // 2. Contractor Bills
        $res = $db->query("SELECT cbr.id, p.name AS project_name, c.name AS contractor_name, cbr.bill_amount, cbr.bill_type, cbr.status 
                           FROM contractor_bill_requests cbr 
                           JOIN projects p ON cbr.project_id = p.id 
                           JOIN contractors c ON cbr.operator_id = c.id 
                           WHERE cbr.status = 'submitted'");
        if ($res) {
            $data['contractor_bills'] = $res->fetch_all(MYSQLI_ASSOC);
        }

        // 3. Vendor Payments
        $res = $db->query("SELECT vpr.id, p.name AS project_name, v.name AS vendor_name, vpr.net_payable_amount, vpr.payment_type, vpr.status 
                           FROM vendor_payment_requests vpr 
                           JOIN projects p ON vpr.project_id = p.id 
                           JOIN vendors v ON vpr.vendor_id = v.id 
                           WHERE vpr.status = 'Submitted'");
        if ($res) {
            $data['vendor_payments'] = $res->fetch_all(MYSQLI_ASSOC);
        }

        // 4. Cashout Requests
        $res = $db->query("SELECT cr.id, cr.purpose_paid_to, cr.amount, cr.description, cr.status 
                           FROM cashout_requests cr 
                           WHERE cr.status = 'pending'");
        if ($res) {
            $data['cashouts'] = $res->fetch_all(MYSQLI_ASSOC);
        }

        // 5. Land Funds
        $res = $db->query("SELECT lfr.id, la.land_owner_name, lfr.requisition_type, lfr.requested_amount, lfr.mgmt_status, lfr.accounts_status 
                           FROM land_fund_requisitions lfr 
                           JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id 
                           WHERE lfr.mgmt_status = 'Pending' OR lfr.accounts_status = 'Pending'");
        if ($res) {
            $data['land_funds'] = $res->fetch_all(MYSQLI_ASSOC);
        }

        return $data;
    }

    /**
     * Gets project timeline milestones and calculated progress percentage.
     */
    public static function getProjectTimelineAndProgress(\mysqli $db, int $projectId): array
    {
        $data = [
            'project_name' => '',
            'workflow_version' => '',
            'timeline' => null,
            'progress' => [
                'actual_progress_pct' => 0.0,
                'planned_progress_pct' => 0.0,
                'remaining_progress_pct' => 100.0
            ]
        ];

        // Fetch project workflow
        $stmt = $db->prepare("SELECT name, workflow_version FROM projects WHERE id = ? AND deleted_at IS NULL");
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                $data['project_name'] = $row['name'];
                $data['workflow_version'] = $row['workflow_version'];
            }
            $stmt->close();
        }

        if (empty($data['project_name'])) {
            return ['error' => 'Project not found.'];
        }

        // Fetch Timeline
        $stmt = $db->prepare("SELECT project_code, project_type, project_category, current_status, project_start_date, construction_start_date, construction_end_date, expected_handover_date 
                              FROM project_timelines WHERE project_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $data['timeline'] = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        // Calculate progress
        if ($data['workflow_version'] === 'phase_based') {
            $sql = "SELECT 
                        SUM(COALESCE(LEAST(1.0, COALESCE(act.total_actual_qty, 0) / NULLIF(ppid.total_boq_qty, 0)) * ppid.weight_pct, 0) * (pp.weight_percentage / 100)) AS actual_progress_pct,
                        SUM(COALESCE(LEAST(1.0, COALESCE(plan.total_target_qty, 0) / NULLIF(ppid.total_boq_qty, 0)) * ppid.weight_pct, 0) * (pp.weight_percentage / 100)) AS planned_progress_pct
                    FROM projects p
                    JOIN project_phases pp ON pp.project_id = p.id
                    JOIN project_phase_items ppi ON ppi.phase_id = pp.id
                    JOIN project_phase_item_details ppid ON ppid.phase_item_id = ppi.id
                    LEFT JOIN (
                        SELECT pmt.project_id, pmti.phase_item_id, pmti.work_type_id, SUM(pmti.monthly_target_qty) AS total_target_qty
                        FROM phase_monthly_target_items pmti
                        JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                        GROUP BY pmt.project_id, pmti.phase_item_id, pmti.work_type_id
                    ) plan ON plan.project_id = p.id AND plan.phase_item_id = ppi.id AND plan.work_type_id = ppid.work_type_id
                    LEFT JOIN (
                        SELECT project_id, phase_item_id, work_type_id, SUM(qty) AS total_actual_qty
                        FROM (
                            SELECT pmt.project_id, pmti.phase_item_id, pmti.work_type_id, SUM(pwpi.achieved_qty) AS qty
                            FROM phase_weekly_progress_items pwpi
                            JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                            JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
                            JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                            WHERE pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved'
                            GROUP BY pmt.project_id, pmti.phase_item_id, pmti.work_type_id
                            UNION ALL
                            SELECT dpl.project_id, pmti.phase_item_id, dwil.work_type_id, SUM(dwil.qty_done_today) AS qty
                            FROM daily_work_item_logs dwil
                            JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                            JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                            LEFT JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
                            WHERE dpl.qc_status = 'Approved' AND dpl.pm_status = 'Approved'
                              AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                              AND pmti.phase_item_id IS NOT NULL
                            GROUP BY dpl.project_id, pmti.phase_item_id, dwil.work_type_id
                        ) union_act
                        GROUP BY project_id, phase_item_id, work_type_id
                    ) act ON act.project_id = p.id AND act.phase_item_id = ppi.id AND act.work_type_id = ppid.work_type_id
                    WHERE p.id = ?
                    GROUP BY p.id";

            $stmt = $db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $projectId);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                if ($res) {
                    $data['progress']['actual_progress_pct'] = round((float)$res['actual_progress_pct'], 4);
                    $data['progress']['planned_progress_pct'] = round((float)$res['planned_progress_pct'], 4);
                    $data['progress']['remaining_progress_pct'] = round(100.0 - $data['progress']['actual_progress_pct'], 4);
                }
                $stmt->close();
            }
        } else {
            $stmt = $db->prepare("SELECT SUM((COALESCE(pp.actual_progress, 0) / 100) * COALESCE(pp.weight_percentage, 0)) AS actual_progress_pct
                                  FROM project_phases pp WHERE pp.project_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $projectId);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                if ($res) {
                    $data['progress']['actual_progress_pct'] = round((float)$res['actual_progress_pct'], 2);
                    $data['progress']['planned_progress_pct'] = $data['progress']['actual_progress_pct'];
                    $data['progress']['remaining_progress_pct'] = round(100.0 - $data['progress']['actual_progress_pct'], 2);
                }
                $stmt->close();
            }
        }

        return $data;
    }

    /**
     * Gets performance metrics of site engineers.
     */
    public static function getSiteEngineerPerformance(\mysqli $db): array
    {
        $sql = "SELECT 
                    u.full_name AS engineer_name,
                    COALESCE(report_stats.total_reports, 0) AS total_reports,
                    COALESCE(report_stats.qc_approved, 0) AS qc_approved,
                    COALESCE(report_stats.pm_approved, 0) AS pm_approved,
                    COALESCE(target_stats.total_planned, 0) AS total_planned_qty,
                    COALESCE(target_stats.total_achieved, 0) AS total_achieved_qty,
                    CASE 
                        WHEN target_stats.total_planned IS NULL OR target_stats.total_planned = 0 THEN 0
                        ELSE ROUND((target_stats.total_achieved / target_stats.total_planned) * 100, 2)
                    END AS target_achievement_pct,
                    CASE 
                        WHEN target_stats.total_planned IS NULL OR target_stats.total_planned = 0 THEN 'No Target Set'
                        WHEN (target_stats.total_achieved / target_stats.total_planned) * 100 >= 90 THEN 'Excellence'
                        WHEN (target_stats.total_achieved / target_stats.total_planned) * 100 >= 70 THEN 'Average'
                        ELSE 'Poor'
                    END AS performance_rating
                FROM users u
                JOIN (
                    SELECT DISTINCT site_engineer_id FROM projects WHERE site_engineer_id IS NOT NULL AND deleted_at IS NULL
                ) active_se ON active_se.site_engineer_id = u.id
                LEFT JOIN (
                    SELECT 
                        created_by,
                        COUNT(id) AS total_reports,
                        SUM(CASE WHEN qc_status = 'Approved' THEN 1 ELSE 0 END) AS qc_approved,
                        SUM(CASE WHEN pm_status = 'Approved' THEN 1 ELSE 0 END) AS pm_approved
                    FROM daily_progress_logs
                    GROUP BY created_by
                ) report_stats ON report_stats.created_by = u.id
                LEFT JOIN (
                    SELECT 
                        p.site_engineer_id,
                        SUM(COALESCE(pwp.total_planned_qty, 0)) AS total_planned,
                        SUM(COALESCE(pwp.total_achieved_qty, 0)) AS total_achieved
                    FROM phase_weekly_progress pwp
                    JOIN projects p ON pwp.project_id = p.id
                    WHERE p.deleted_at IS NULL
                    GROUP BY p.site_engineer_id
                ) target_stats ON target_stats.site_engineer_id = u.id
                ORDER BY target_achievement_pct DESC, total_reports DESC";
        $res = $db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Gets material stock levels for a specific project.
     */
    public static function getProjectStockMaterials(\mysqli $db, int $projectId): array
    {
        $stmt = $db->prepare("SELECT 
                                   m.name AS material_name, 
                                   s.current_quantity AS stock_qty, 
                                   m.unit, 
                                   w.name AS warehouse_name
                               FROM con_stocks s
                               JOIN con_materials m ON s.material_id = m.id
                               JOIN con_warehouses w ON s.warehouse_id = w.id
                               WHERE w.project_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $res;
        }
        return [];
    }

    /**
     * Gets sales performance and metrics of sales executives.
     */
    public static function getSalesExecutivePerformance(\mysqli $db): array
    {
        $res = $db->query("SELECT 
                               u.full_name AS sales_executive,
                               COUNT(lb.id) AS total_bookings,
                               SUM(lb.agreement_value) AS total_agreement_value,
                               SUM(lb.discount_amount) AS total_discount,
                               COALESCE(SUM(lp.amount), 0) AS total_payments_collected
                           FROM lead_bookings lb
                           JOIN users u ON lb.sales_by_id = u.id
                           LEFT JOIN lead_payments lp ON lp.booking_id = lb.id
                           WHERE lb.deleted_at IS NULL
                           GROUP BY u.id
                           ORDER BY total_bookings DESC");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Gets sold vs available flats inventory summary.
     */
    public static function getFlatSalesInventorySummary(\mysqli $db, int $projectId = null): array
    {
        if ($projectId !== null) {
            $stmt = $db->prepare("SELECT 
                                       COUNT(*) AS total_flats,
                                       SUM(CASE WHEN is_booked = 1 THEN 1 ELSE 0 END) AS sold_flats,
                                       SUM(CASE WHEN is_booked = 0 THEN 1 ELSE 0 END) AS available_flats,
                                       SUM(CASE WHEN is_booked = 1 THEN price_value ELSE 0 END) AS sold_value,
                                       SUM(CASE WHEN is_booked = 0 THEN price_value ELSE 0 END) AS available_value
                                   FROM flats
                                   WHERE project_id = ? AND deleted_at IS NULL");
            if ($stmt) {
                $stmt->bind_param("i", $projectId);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                return $res ?: [];
            }
        } else {
            $res = $db->query("SELECT 
                                   COUNT(*) AS total_flats,
                                   SUM(CASE WHEN is_booked = 1 THEN 1 ELSE 0 END) AS sold_flats,
                                   SUM(CASE WHEN is_booked = 0 THEN 1 ELSE 0 END) AS available_flats,
                                   SUM(CASE WHEN is_booked = 1 THEN price_value ELSE 0 END) AS sold_value,
                                   SUM(CASE WHEN is_booked = 0 THEN price_value ELSE 0 END) AS available_value
                               FROM flats
                               WHERE deleted_at IS NULL");
            return $res ? $res->fetch_assoc() : [];
        }
        return [];
    }

    /**
     * Gets company-wide total cashflow.
     */
    public static function getCompanyFinancialSummary(\mysqli $db): array
    {
        $data = [
            'total_cash_in' => 0.0,
            'total_cash_out' => 0.0,
            'net_cashflow' => 0.0
        ];

        // Cash In query
        $resIn = $db->query("SELECT (
            (SELECT COALESCE(SUM(CASE WHEN payment_type = 'land_share' THEN land_share_amount ELSE amount END), 0) FROM lead_payments WHERE payment_status IN ('paid','partially_paid') AND verification_status = 'verified') +
            (SELECT COALESCE(SUM(amount), 0) FROM management_cashin WHERE payment_status = 'paid') +
            (SELECT COALESCE(SUM(at.amount), 0) FROM adjustment_transactions at JOIN expense_adjustments ea ON at.adjustment_id = ea.id WHERE at.transaction_type = 'cashin' AND ea.status IN ('approved', 'partially_paid', 'fully_paid'))
        ) AS total_cash_in");
        if ($resIn) {
            $row = $resIn->fetch_assoc();
            $data['total_cash_in'] = (float)($row['total_cash_in'] ?? 0.0);
        }

        // Cash Out query
        $resOut = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total_cash_out
            FROM (
                SELECT amount as total_amount FROM management_cashout WHERE payment_status = 'paid'
                UNION ALL
                SELECT cp.amount as total_amount FROM cashout_payments cp LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                UNION ALL
                SELECT net_salary as total_amount FROM payslips WHERE status = 'paid'
                UNION ALL
                SELECT COALESCE(approved_amount, bill_amount) as total_amount FROM contractor_bill_requests WHERE status IN ('approved', 'paid', 'partially_paid', 'fully_paid')
                UNION ALL
                SELECT COALESCE(approved_amount, net_payable) as total_amount FROM vendor_payment_requests WHERE status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid')
                UNION ALL
                SELECT amount_paid as total_amount FROM con_financial_transactions WHERE ref_type = 'operational_fund'
                UNION ALL
                SELECT at.amount as total_amount FROM adjustment_transactions at JOIN expense_adjustments ea ON at.adjustment_id = ea.id WHERE at.transaction_type = 'cashout' AND ea.status IN ('approved', 'partially_paid', 'fully_paid')
                UNION ALL
                SELECT amount as total_amount FROM fund_requisitions WHERE status = 'paid'
                UNION ALL
                SELECT lfr.requested_amount as total_amount FROM land_fund_requisitions lfr WHERE lfr.mgmt_status = 'Approved' AND lfr.accounts_status = 'Paid'
            ) combined");
        if ($resOut) {
            $row = $resOut->fetch_assoc();
            $data['total_cash_out'] = (float)($row['total_cash_out'] ?? 0.0);
        }

        $data['net_cashflow'] = $data['total_cash_in'] - $data['total_cash_out'];

        return $data;
    }
}

