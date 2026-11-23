<?php

class AccountClient extends Model
{
    protected $db;
    protected static $columnCache = [];

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    /**
     * Check if a column exists in a table (cached)
     */
    protected function hasColumn($table, $column)
    {
        $key = "{$table}.{$column}";
        if (!isset(self::$columnCache[$key])) {
            $check = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            self::$columnCache[$key] = ($check && $check->num_rows > 0);
        }
        return self::$columnCache[$key];
    }

    /**
     * Get all clients (booked leads) with full details, optionally filtered and paginated
     */
    public function getAllClients($projectId = null, $verificationStatus = null, $search = null, $limit = null, $offset = 0, $hasConflict = false, $dateType = null, $startDate = null, $endDate = null, $paymentType = null)
    {
        $whereParts = ["1=1"];
        $havingParts = [];
        $params = [];
        $types = '';

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereParts[] = "lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= 'i';
                    }
                }
            } else {
                $whereParts[] = "lb.project_id = ?";
                $params[] = (int) $projectId;
                $types .= 'i';
            }
        }

        if (!empty($search)) {
            $searchTerm = '%' . trim($search) . '%';
            $whereParts[] = "(l.full_name LIKE ? OR l.lead_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ? OR l.alt_phone_number LIKE ? OR l.`2nd_owner_phone` LIKE ? OR l.email LIKE ? OR l.company LIKE ? OR p.name LIKE ? OR f.flat_name LIKE ? OR EXISTS (
                SELECT 1 FROM lead_payments lp_s 
                LEFT JOIN users u_s ON lp_s.created_by = u_s.id 
                LEFT JOIN employees e_s ON (u_s.employee_id = e_s.id OR e_s.user_id = u_s.id)
                WHERE lp_s.booking_id = lb.id AND lp_s.is_trashed = 0 AND (u_s.full_name LIKE ? OR e_s.full_name LIKE ? OR u_s.user_name LIKE ?)
            ))";
            for ($i = 0; $i < 13; $i++) {
                $params[] = $searchTerm;
                $types .= 's';
            }
        }

        if ($hasConflict) {
            $whereParts[] = "EXISTS (
                SELECT 1 FROM lead_bookings lb_c 
                JOIN flats f_c ON lb_c.flat_id = f_c.id 
                WHERE lb_c.lead_id = l.id 
                  AND lb_c.deleted_at IS NULL
                  AND lb_c.booking_status NOT IN ('refunded', 'cancelled')
                  AND (lb_c.landshare_status IS NULL OR lb_c.landshare_status != 'cancelled')
                  AND (lb_c.project_id, LOWER(TRIM(f_c.flat_name))) IN (
                      SELECT lb_c2.project_id, LOWER(TRIM(f_c2.flat_name)) 
                      FROM lead_bookings lb_c2 
                      JOIN flats f_c2 ON lb_c2.flat_id = f_c2.id 
                      WHERE lb_c2.deleted_at IS NULL 
                        AND lb_c2.booking_status NOT IN ('refunded', 'cancelled')
                        AND (lb_c2.landshare_status IS NULL OR lb_c2.landshare_status != 'cancelled')
                      GROUP BY lb_c2.project_id, LOWER(TRIM(f_c2.flat_name)) 
                      HAVING COUNT(DISTINCT lb_c2.id) > 1
                  )
            )";
        }

        // Date Range Filter (Booking Date or Last Payment Date)
        if (!empty($startDate) || !empty($endDate)) {
            $targetDateType = !empty($dateType) ? $dateType : 'booking_date';
            if ($targetDateType === 'booking_date') {
                if (!empty($startDate)) {
                    $whereParts[] = "DATE(COALESCE(lb.booking_date, lb.created_at)) >= ?";
                    $params[] = $startDate;
                    $types .= 's';
                }
                if (!empty($endDate)) {
                    $whereParts[] = "DATE(COALESCE(lb.booking_date, lb.created_at)) <= ?";
                    $params[] = $endDate;
                    $types .= 's';
                }
            } elseif ($targetDateType === 'last_payment_date') {
                $dateCol = "p_stats.last_payment_date";
                if (!empty($paymentType) && $paymentType !== 'full_payment') {
                    if ($paymentType === 'construction_fee') {
                        $dateCol = "p_stats.emi_last_payment_date";
                    } elseif ($paymentType === 'land_share') {
                        $dateCol = "p_stats.land_share_last_payment_date";
                    } elseif ($paymentType === 'development_charge') {
                        $dateCol = "p_stats.dev_charge_last_payment_date";
                    } elseif ($paymentType === 'shop_service_charge') {
                        $dateCol = "p_stats.shop_service_charge_last_payment_date";
                    }
                }
                $whereParts[] = "{$dateCol} IS NOT NULL";
                if (!empty($startDate)) {
                    $whereParts[] = "DATE({$dateCol}) >= ?";
                    $params[] = $startDate;
                    $types .= 's';
                }
                if (!empty($endDate)) {
                    $whereParts[] = "DATE({$dateCol}) <= ?";
                    $params[] = $endDate;
                    $types .= 's';
                }
            }
        }
        
        if (!empty($verificationStatus)) {
            if (!empty($paymentType) && $paymentType !== 'full_payment') {
                $targetPending = "total_pending_payments";
                $targetVer = "latest_verification_status";
                if ($paymentType === 'construction_fee') {
                    $targetPending = "construction_pending_payments";
                    $targetVer = "construction_latest_verification_status";
                } elseif ($paymentType === 'land_share') {
                    $targetPending = "land_share_pending_payments";
                    $targetVer = "land_share_latest_verification_status";
                } elseif ($paymentType === 'development_charge') {
                    $targetPending = "dev_charge_pending_payments";
                    $targetVer = "dev_charge_latest_verification_status";
                } elseif ($paymentType === 'shop_service_charge') {
                    $targetPending = "shop_service_charge_pending_payments";
                    $targetVer = "shop_service_charge_latest_verification_status";
                }
                if ($verificationStatus === 'verified') {
                    $havingParts[] = "{$targetVer} = 'verified' AND {$targetPending} = 0";
                } elseif ($verificationStatus === 'pending') {
                    $havingParts[] = "{$targetPending} > 0";
                } elseif ($verificationStatus === 'rejected') {
                    $havingParts[] = "{$targetVer} = 'rejected' AND {$targetPending} = 0";
                }
            } else {
                if ($verificationStatus === 'verified') {
                    $havingParts[] = "latest_verification_status = 'verified' AND total_pending_payments = 0";
                } elseif ($verificationStatus === 'pending') {
                    $havingParts[] = "total_pending_payments > 0";
                } elseif ($verificationStatus === 'rejected') {
                    $havingParts[] = "latest_verification_status = 'rejected' AND total_pending_payments = 0";
                }
            }
        }

        
        if (!empty($paymentType)) {
            if ($paymentType === 'full_payment') {
                $havingParts[] = "(total_price_target > 0 OR total_paid > 0)";
            } elseif ($paymentType === 'land_share') {
                $havingParts[] = "(total_land_share_target > 0 OR land_share_paid > 0)";
            } elseif ($paymentType === 'development_charge') {
                $havingParts[] = "(total_dev_charge_target > 0 OR dev_charge_paid > 0)";
            } elseif ($paymentType === 'shop_service_charge') {
                $havingParts[] = "(total_shop_service_charge_target > 0 OR shop_service_charge_paid > 0)";
            } elseif ($paymentType === 'construction_fee') {
                $havingParts[] = "(total_construction_target > 0 OR construction_paid > 0)";
            }
        }

        $whereClause = "WHERE " . implode(" AND ", $whereParts);
        $havingClause = !empty($havingParts) ? "HAVING " . implode(" AND ", $havingParts) : "";

        if (is_array($projectId)) {
            $pIds = array_values(array_filter(array_map('intval', $projectId)));
            $projectSubFilter = !empty($pIds) ? " AND lb_sub.project_id IN (" . implode(',', $pIds) . ")" : "";
        } else {
            $projectSubFilter = $projectId ? " AND lb_sub.project_id = " . (int)$projectId : "";
        }

        $dataSql ="SELECT
            l.id AS lead_id,
            COALESCE(NULLIF(TRIM(l.full_name), ''), NULLIF(TRIM(l.lead_name), ''), NULLIF(TRIM(l.lead_name_bn), '')) AS client_name,
            COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), NULLIF(TRIM(l.alt_phone_number), ''), NULLIF(TRIM(l.`2nd_owner_phone`), '')) AS phone,
            l.phone_number,
            l.email,
            l.designation,
            l.company,
            agents.agent_name,
            COUNT(DISTINCT lb.id) AS total_bookings,
            SUM(lb.agreement_value) AS total_agreement_value,
            GROUP_CONCAT(DISTINCT CONCAT(p.name, '||', f.flat_name, '||', f.id) SEPARATOR ';;') AS project_flats_combined,
            p_stats.latest_verification_status,
            COALESCE(p_stats.total_pending_payments, 0) AS total_pending_payments,
            COALESCE(p_stats.total_paid_verified, 0) AS total_paid_verified,
            COALESCE(p_stats.total_paid_unverified, 0) AS total_paid_unverified,
            COALESCE(p_stats.total_paid, 0) AS total_paid,
            p_stats.last_payment_date,
            p_stats.last_payment_creator_name,
            p_stats.last_payment_created_by,
            MAX(lb.id) AS booking_id,
            COALESCE(MAX(lb.booking_date), DATE(MAX(lb.created_at))) AS booking_date,
            GROUP_CONCAT(
                DISTINCT CONCAT(
                    COALESCE(lb.booking_date, DATE(lb.created_at)),
                    '||',
                    COALESCE(f.flat_name, ''),
                    '||',
                    COALESCE(p.name, ''),
                    '||',
                    lb.id
                )
                ORDER BY COALESCE(lb.booking_date, DATE(lb.created_at)) DESC
                SEPARATOR ';;'
            ) AS all_booking_dates,
            
            -- Financial Breakdown Targets
            COALESCE(SUM(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0)), 0) AS total_price_target,
            COALESCE(SUM(COALESCE(f.land_share_value, 0)), 0) AS total_land_share_target,
            COALESCE(SUM(COALESCE(f.development_charges, 0)), 0) AS total_dev_charge_target,
            COALESCE(SUM(CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END), 0) AS total_shop_service_charge_target,
            COALESCE(SUM(GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - (COALESCE(f.land_share_value, 0) + COALESCE(f.development_charges, 0) + CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END)))), 0) AS total_construction_target,
            COALESCE(SUM(lb.discount_amount), 0) AS total_discount,
            COALESCE(p_stats.total_bulk_emi_discount, 0) AS total_bulk_emi_discount,

            -- Financial Breakdown Paid
            COALESCE(p_stats.land_share_paid, 0) AS land_share_paid,
            COALESCE(p_stats.land_share_paid_verified, 0) AS land_share_paid_verified,
            COALESCE(p_stats.land_share_paid_unverified, 0) AS land_share_paid_unverified,
            p_stats.land_share_last_payment_date,
            COALESCE(p_stats.land_share_pending_payments, 0) AS land_share_pending_payments,
            p_stats.land_share_latest_verification_status,

            COALESCE(p_stats.dev_charge_paid, 0) AS dev_charge_paid,
            COALESCE(p_stats.dev_charge_paid_verified, 0) AS dev_charge_paid_verified,
            COALESCE(p_stats.dev_charge_paid_unverified, 0) AS dev_charge_paid_unverified,
            p_stats.dev_charge_last_payment_date,
            COALESCE(p_stats.dev_charge_pending_payments, 0) AS dev_charge_pending_payments,
            p_stats.dev_charge_latest_verification_status,

            COALESCE(p_stats.shop_service_charge_paid, 0) AS shop_service_charge_paid,
            COALESCE(p_stats.shop_service_charge_paid_verified, 0) AS shop_service_charge_paid_verified,
            COALESCE(p_stats.shop_service_charge_paid_unverified, 0) AS shop_service_charge_paid_unverified,
            p_stats.shop_service_charge_last_payment_date,
            COALESCE(p_stats.shop_service_charge_pending_payments, 0) AS shop_service_charge_pending_payments,
            p_stats.shop_service_charge_latest_verification_status,

            COALESCE(p_stats.emi_paid, 0) AS construction_paid,
            COALESCE(p_stats.construction_paid_verified, 0) AS construction_paid_verified,
            COALESCE(p_stats.construction_paid_unverified, 0) AS construction_paid_unverified,
            p_stats.emi_last_payment_date AS construction_last_payment_date,
            COALESCE(p_stats.emi_pending_payments, 0) AS construction_pending_payments,
            p_stats.emi_latest_verification_status AS construction_latest_verification_status,

            -- Financial Breakdown Dues
            GREATEST(0, ROUND(COALESCE(SUM(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0)), 0) - COALESCE(SUM(lb.discount_amount), 0) - COALESCE(p_stats.total_paid, 0) - COALESCE(p_stats.total_bulk_emi_discount, 0))) AS overall_due,
            GREATEST(0, ROUND(COALESCE(SUM(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0)), 0) - COALESCE(SUM(lb.discount_amount), 0) - COALESCE(p_stats.total_paid_verified, 0) - COALESCE(p_stats.emi_discount_verified, 0))) AS overall_due_verified,
            GREATEST(0, ROUND(GREATEST(0, COALESCE(SUM(COALESCE(f.land_share_value, 0)), 0) - COALESCE(SUM(lb.discount_amount), 0)) - COALESCE(p_stats.land_share_paid, 0))) AS land_share_due,
            GREATEST(0, ROUND(COALESCE(SUM(COALESCE(f.development_charges, 0)), 0) - COALESCE(p_stats.dev_charge_paid, 0))) AS dev_charge_due,
            GREATEST(0, ROUND(COALESCE(SUM(CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END), 0) - COALESCE(p_stats.shop_service_charge_paid, 0))) AS shop_service_charge_due,
            GREATEST(0, ROUND(GREATEST(0, COALESCE(SUM(GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - (COALESCE(f.land_share_value, 0) + COALESCE(f.development_charges, 0) + CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END)))), 0)) - COALESCE(p_stats.emi_paid, 0) - COALESCE(p_stats.total_bulk_emi_discount, 0))) AS construction_due
        FROM leads l
        INNER JOIN lead_bookings lb ON lb.lead_id = l.id
            AND lb.deleted_at IS NULL
            AND lb.booking_status NOT IN ('refunded', 'cancelled')
            AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
            AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        LEFT JOIN (
            SELECT 
                lb_sub.lead_id,
                SUM(CASE WHEN (lp.payment_status IN ('paid','partially_paid')) 
                              AND lp.verification_status = 'verified' 
                              AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS total_paid_verified,
                SUM(CASE WHEN (lp.payment_status IN ('paid','partially_paid','pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') 
                              AND lp.verification_status != 'rejected' 
                              AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS total_paid_unverified,
                SUM(CASE WHEN (lp.payment_status IN ('paid','partially_paid','pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                              AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS total_paid,
                SUM(CASE WHEN (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1) AND lp.verification_status = 'verified' 
                         THEN CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END ELSE 0 END) AS emi_discount_verified,
                SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) AS land_share_paid,
                SUM(CASE WHEN lp.payment_type = 'development_charge' 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) AS dev_charge_paid,
                SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) AS shop_service_charge_paid,
                SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                              AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS emi_paid,
                SUM(CASE WHEN (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1) AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                         THEN CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END ELSE 0 END) AS total_bulk_emi_discount,
                MAX(CASE WHEN (lp.payment_status IN ('paid','partially_paid','pending')) AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.payment_date ELSE NULL END) AS last_payment_date,
                SUBSTRING_INDEX(GROUP_CONCAT(
                    CASE WHEN (lp.payment_status IN ('paid','partially_paid','pending')) AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                    THEN COALESCE(e_lp.full_name, u_lp.full_name, u_lp.user_name, CONCAT('User #', lp.created_by)) 
                    ELSE NULL END 
                    ORDER BY lp.payment_date DESC, lp.id DESC 
                    SEPARATOR '|||'
                ), '|||', 1) AS last_payment_creator_name,
                SUBSTRING_INDEX(GROUP_CONCAT(
                    CASE WHEN (lp.payment_status IN ('paid','partially_paid','pending')) AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                    THEN lp.created_by 
                    ELSE NULL END 
                    ORDER BY lp.payment_date DESC, lp.id DESC 
                    SEPARATOR '|||'
                ), '|||', 1) AS last_payment_created_by,
                SUM(CASE WHEN lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS total_pending_payments,
                SUBSTRING_INDEX(GROUP_CONCAT(lp.verification_status ORDER BY lp.created_at DESC SEPARATOR ','), ',', 1) AS latest_verification_status,

                -- Land Share specific
                SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                              AND (lp.payment_status IN ('paid', 'partially_paid')) 
                              AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS land_share_paid_verified,
                SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') 
                              AND lp.verification_status != 'rejected' THEN lp.amount ELSE 0 END) AS land_share_paid_unverified,
                MAX(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.payment_date ELSE NULL END) AS land_share_last_payment_date,
                SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                              AND lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS land_share_pending_payments,
                SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') THEN lp.verification_status ELSE NULL END ORDER BY lp.created_at DESC SEPARATOR ','), ',', 1) AS land_share_latest_verification_status,

                -- Dev Charge specific
                SUM(CASE WHEN lp.payment_type = 'development_charge' 
                              AND (lp.payment_status IN ('paid', 'partially_paid')) 
                              AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS dev_charge_paid_verified,
                SUM(CASE WHEN lp.payment_type = 'development_charge' 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') 
                              AND lp.verification_status != 'rejected' THEN lp.amount ELSE 0 END) AS dev_charge_paid_unverified,
                MAX(CASE WHEN lp.payment_type = 'development_charge' 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.payment_date ELSE NULL END) AS dev_charge_last_payment_date,
                SUM(CASE WHEN lp.payment_type = 'development_charge' 
                              AND lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS dev_charge_pending_payments,
                SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_type = 'development_charge' THEN lp.verification_status ELSE NULL END ORDER BY lp.created_at DESC SEPARATOR ','), ',', 1) AS dev_charge_latest_verification_status,

                -- Shop Service Charge specific
                SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                              AND (lp.payment_status IN ('paid', 'partially_paid')) 
                              AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS shop_service_charge_paid_verified,
                SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') 
                              AND lp.verification_status != 'rejected' THEN lp.amount ELSE 0 END) AS shop_service_charge_paid_unverified,
                MAX(CASE WHEN lp.payment_type = 'shop_service_charge' 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.payment_date ELSE NULL END) AS shop_service_charge_last_payment_date,
                SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                              AND lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS shop_service_charge_pending_payments,
                SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_type = 'shop_service_charge' THEN lp.verification_status ELSE NULL END ORDER BY lp.created_at DESC SEPARATOR ','), ',', 1) AS shop_service_charge_latest_verification_status,

                -- Construction Fee / EMI specific
                SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                              AND (lp.payment_status IN ('paid', 'partially_paid')) 
                              AND lp.verification_status = 'verified' 
                              AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS construction_paid_verified,
                SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') 
                              AND lp.verification_status != 'rejected' 
                              AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS construction_paid_unverified,
                MAX(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                              AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                              AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                              AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.payment_date ELSE NULL END) AS emi_last_payment_date,
                SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                              AND lp.verification_status = 'pending' 
                              AND COALESCE(lp.is_emi_discount, 0) = 0 THEN 1 ELSE 0 END) AS emi_pending_payments,
                SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.verification_status ELSE NULL END ORDER BY lp.created_at DESC SEPARATOR ','), ',', 1) AS emi_latest_verification_status
            FROM lead_bookings lb_sub
            INNER JOIN lead_payments lp ON lp.booking_id = lb_sub.id
            LEFT JOIN users u_lp ON lp.created_by = u_lp.id
            LEFT JOIN employees e_lp ON (u_lp.employee_id = e_lp.id OR e_lp.user_id = u_lp.id)
            WHERE lb_sub.deleted_at IS NULL
               AND lb_sub.booking_status NOT IN ('refunded', 'cancelled')
               AND (lb_sub.landshare_status IS NULL OR lb_sub.landshare_status != 'cancelled')
               AND lb_sub.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
               AND lp.is_trashed = 0
               {$projectSubFilter}
            GROUP BY lb_sub.lead_id
        ) AS p_stats ON p_stats.lead_id = l.id
        LEFT JOIN (
            SELECT 
                lb_sub.lead_id, 
                GROUP_CONCAT(
                    DISTINCT u.full_name 
                    ORDER BY lb_sub.id DESC 
                    SEPARATOR ', '
                ) AS agent_name
            FROM lead_bookings lb_sub
            LEFT JOIN leads l_sub ON lb_sub.lead_id = l_sub.id
            LEFT JOIN lead_payments lp_bm ON lp_bm.booking_id = lb_sub.id AND lp_bm.payment_type = 'booking_money' AND lp_bm.is_trashed = 0
            LEFT JOIN users u ON COALESCE(
                NULLIF(lb_sub.sales_by_id, 0), 
                NULLIF(l_sub.assigned_id, 0), 
                NULLIF(lb_sub.created_by, 0),
                NULLIF(lp_bm.created_by, 0)
            ) = u.id
            WHERE lb_sub.deleted_at IS NULL
               AND lb_sub.booking_status NOT IN ('refunded', 'cancelled')
               AND (lb_sub.landshare_status IS NULL OR lb_sub.landshare_status != 'cancelled')
               AND lb_sub.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
               {$projectSubFilter}
            GROUP BY lb_sub.lead_id
        ) AS agents ON agents.lead_id = l.id
        $whereClause
        GROUP BY l.id
        $havingClause
        ORDER BY l.full_name
        ";

        if ($limit !== null) {
            $dataSql .= " LIMIT ? OFFSET ?";
            $params[] = (int) $limit;
            $params[] = (int) ($offset ?? 0);
            $types .= 'ii';
        }

        $sql = $this->db->prepare($dataSql);
        if (!$sql) {
            error_log("getAllClients prepare failed: " . $this->db->error);
            return [];
        }

        if (!empty($params)) {
            $sql->bind_param($types, ...$params);
        }

        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get total count of clients with given filters
     */
    public function getTotalClientsCount($projectId = null, $verificationStatus = null, $search = null, $hasConflict = false, $dateType = null, $startDate = null, $endDate = null, $paymentType = null)
    {
        $whereParts = ["1=1"];
        $havingParts = [];
        $params = [];
        $types = '';

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereParts[] = "lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= 'i';
                    }
                }
            } else {
                $whereParts[] = "lb.project_id = ?";
                $params[] = (int) $projectId;
                $types .= 'i';
            }
        }

        if (!empty($search)) {
            $searchTerm = '%' . trim($search) . '%';
            $whereParts[] = "(l.full_name LIKE ? OR l.lead_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ? OR l.alt_phone_number LIKE ? OR l.`2nd_owner_phone` LIKE ? OR l.email LIKE ? OR l.company LIKE ? OR p.name LIKE ? OR f.flat_name LIKE ? OR EXISTS (
                SELECT 1 FROM lead_payments lp_s 
                LEFT JOIN users u_s ON lp_s.created_by = u_s.id 
                LEFT JOIN employees e_s ON (u_s.employee_id = e_s.id OR e_s.user_id = u_s.id)
                WHERE lp_s.booking_id = lb.id AND lp_s.is_trashed = 0 AND (u_s.full_name LIKE ? OR e_s.full_name LIKE ? OR u_s.user_name LIKE ?)
            ))";
            for ($i = 0; $i < 13; $i++) {
                $params[] = $searchTerm;
                $types .= 's';
            }
        }

        if ($hasConflict) {
            $whereParts[] = "EXISTS (
                SELECT 1 FROM lead_bookings lb_c 
                JOIN flats f_c ON lb_c.flat_id = f_c.id 
                WHERE lb_c.lead_id = l.id 
                  AND lb_c.deleted_at IS NULL
                  AND lb_c.booking_status NOT IN ('refunded', 'cancelled')
                  AND (lb_c.landshare_status IS NULL OR lb_c.landshare_status != 'cancelled')
                  AND (lb_c.project_id, LOWER(TRIM(f_c.flat_name))) IN (
                      SELECT lb_c2.project_id, LOWER(TRIM(f_c2.flat_name)) 
                      FROM lead_bookings lb_c2 
                      JOIN flats f_c2 ON lb_c2.flat_id = f_c2.id 
                      WHERE lb_c2.deleted_at IS NULL 
                        AND lb_c2.booking_status NOT IN ('refunded', 'cancelled')
                        AND (lb_c2.landshare_status IS NULL OR lb_c2.landshare_status != 'cancelled')
                      GROUP BY lb_c2.project_id, LOWER(TRIM(f_c2.flat_name)) 
                      HAVING COUNT(DISTINCT lb_c2.id) > 1
                  )
            )";
        }

        // Date Range Filter (Booking Date or Last Payment Date)
        if (!empty($startDate) || !empty($endDate)) {
            $targetDateType = !empty($dateType) ? $dateType : 'booking_date';
            if ($targetDateType === 'booking_date') {
                if (!empty($startDate)) {
                    $whereParts[] = "DATE(COALESCE(lb.booking_date, lb.created_at)) >= ?";
                    $params[] = $startDate;
                    $types .= 's';
                }
                if (!empty($endDate)) {
                    $whereParts[] = "DATE(COALESCE(lb.booking_date, lb.created_at)) <= ?";
                    $params[] = $endDate;
                    $types .= 's';
                }
            } elseif ($targetDateType === 'last_payment_date') {
                $dateCol = "p_stats.last_payment_date";
                if (!empty($paymentType) && $paymentType !== 'full_payment') {
                    if ($paymentType === 'construction_fee') {
                        $dateCol = "p_stats.emi_last_payment_date";
                    } elseif ($paymentType === 'land_share') {
                        $dateCol = "p_stats.land_share_last_payment_date";
                    } elseif ($paymentType === 'development_charge') {
                        $dateCol = "p_stats.dev_charge_last_payment_date";
                    } elseif ($paymentType === 'shop_service_charge') {
                        $dateCol = "p_stats.shop_service_charge_last_payment_date";
                    }
                }
                $whereParts[] = "{$dateCol} IS NOT NULL";
                if (!empty($startDate)) {
                    $whereParts[] = "DATE({$dateCol}) >= ?";
                    $params[] = $startDate;
                    $types .= 's';
                }
                if (!empty($endDate)) {
                    $whereParts[] = "DATE({$dateCol}) <= ?";
                    $params[] = $endDate;
                    $types .= 's';
                }
            }
        }

        if (!empty($verificationStatus)) {
            if (!empty($paymentType) && $paymentType !== 'full_payment') {
                $targetPending = "total_pending_payments";
                $targetVer = "latest_verification_status";
                if ($paymentType === 'construction_fee') {
                    $targetPending = "construction_pending_payments";
                    $targetVer = "construction_latest_verification_status";
                } elseif ($paymentType === 'land_share') {
                    $targetPending = "land_share_pending_payments";
                    $targetVer = "land_share_latest_verification_status";
                } elseif ($paymentType === 'development_charge') {
                    $targetPending = "dev_charge_pending_payments";
                    $targetVer = "dev_charge_latest_verification_status";
                } elseif ($paymentType === 'shop_service_charge') {
                    $targetPending = "shop_service_charge_pending_payments";
                    $targetVer = "shop_service_charge_latest_verification_status";
                }
                if ($verificationStatus === 'verified') {
                    $havingParts[] = "{$targetVer} = 'verified' AND {$targetPending} = 0";
                } elseif ($verificationStatus === 'pending') {
                    $havingParts[] = "{$targetPending} > 0";
                } elseif ($verificationStatus === 'rejected') {
                    $havingParts[] = "{$targetVer} = 'rejected' AND {$targetPending} = 0";
                }
            } else {
                if ($verificationStatus === 'verified') {
                    $havingParts[] = "latest_verification_status = 'verified' AND total_pending_payments = 0";
                } elseif ($verificationStatus === 'pending') {
                    $havingParts[] = "total_pending_payments > 0";
                } elseif ($verificationStatus === 'rejected') {
                    $havingParts[] = "latest_verification_status = 'rejected' AND total_pending_payments = 0";
                }
            }
        }

        if (!empty($paymentType)) {
            if ($paymentType === 'full_payment') {
                $havingParts[] = "(total_price_target > 0 OR total_paid > 0)";
            } elseif ($paymentType === 'land_share') {
                $havingParts[] = "(total_land_share_target > 0 OR land_share_paid > 0)";
            } elseif ($paymentType === 'development_charge') {
                $havingParts[] = "(total_dev_charge_target > 0 OR dev_charge_paid > 0)";
            } elseif ($paymentType === 'shop_service_charge') {
                $havingParts[] = "(total_shop_service_charge_target > 0 OR shop_service_charge_paid > 0)";
            } elseif ($paymentType === 'construction_fee') {
                $havingParts[] = "(total_construction_target > 0 OR construction_paid > 0)";
            }
        }

        $whereClause = "WHERE " . implode(" AND ", $whereParts);
        $havingClause = !empty($havingParts) ? "HAVING " . implode(" AND ", $havingParts) : "";

        if (is_array($projectId)) {
            $pIds = array_values(array_filter(array_map('intval', $projectId)));
            $projectSubFilter = !empty($pIds) ? " AND lb_sub.project_id IN (" . implode(',', $pIds) . ")" : "";
        } else {
            $projectSubFilter = $projectId ? " AND lb_sub.project_id = " . (int)$projectId : "";
        }

        $countSql = "
            SELECT COUNT(*) as total FROM (
                SELECT 
                    l.id,
                    p_stats.latest_verification_status,
                    COALESCE(p_stats.total_pending_payments, 0) AS total_pending_payments,
                    COALESCE(SUM(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0)), 0) AS total_price_target,
                    COALESCE(SUM(COALESCE(f.land_share_value, 0)), 0) AS total_land_share_target,
                    COALESCE(SUM(COALESCE(f.development_charges, 0)), 0) AS total_dev_charge_target,
                    COALESCE(SUM(CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END), 0) AS total_shop_service_charge_target,
                    COALESCE(SUM(GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - (COALESCE(f.land_share_value, 0) + COALESCE(f.development_charges, 0) + CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END)))), 0) AS total_construction_target,
                    COALESCE(p_stats.total_paid, 0) AS total_paid,
                    COALESCE(p_stats.land_share_paid, 0) AS land_share_paid,
                    COALESCE(p_stats.land_share_pending_payments, 0) AS land_share_pending_payments,
                    p_stats.land_share_latest_verification_status,
                    COALESCE(p_stats.dev_charge_paid, 0) AS dev_charge_paid,
                    COALESCE(p_stats.dev_charge_pending_payments, 0) AS dev_charge_pending_payments,
                    p_stats.dev_charge_latest_verification_status,
                    COALESCE(p_stats.shop_service_charge_paid, 0) AS shop_service_charge_paid,
                    COALESCE(p_stats.shop_service_charge_pending_payments, 0) AS shop_service_charge_pending_payments,
                    p_stats.shop_service_charge_latest_verification_status,
                    COALESCE(p_stats.emi_paid, 0) AS construction_paid,
                    COALESCE(p_stats.emi_pending_payments, 0) AS construction_pending_payments,
                    p_stats.emi_latest_verification_status AS construction_latest_verification_status
                FROM leads l
                INNER JOIN lead_bookings lb ON lb.lead_id = l.id
                    AND lb.deleted_at IS NULL
                    AND lb.booking_status NOT IN ('refunded', 'cancelled')
                    AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                    AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
                LEFT JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN (
                    SELECT 
                        lb_sub.lead_id,
                        SUM(CASE WHEN (lp.payment_status IN ('paid','partially_paid','pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                      AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS total_paid,
                        SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) AS land_share_paid,
                        MAX(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.payment_date ELSE NULL END) AS land_share_last_payment_date,
                        SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                                      AND lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS land_share_pending_payments,
                        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') THEN lp.verification_status ELSE NULL END ORDER BY lp.created_at DESC SEPARATOR ','), ',', 1) AS land_share_latest_verification_status,

                        SUM(CASE WHEN lp.payment_type = 'development_charge' 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) AS dev_charge_paid,
                        MAX(CASE WHEN lp.payment_type = 'development_charge' 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.payment_date ELSE NULL END) AS dev_charge_last_payment_date,
                        SUM(CASE WHEN lp.payment_type = 'development_charge' 
                                      AND lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS dev_charge_pending_payments,
                        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_type = 'development_charge' THEN lp.verification_status ELSE NULL END ORDER BY lp.created_at DESC SEPARATOR ','), ',', 1) AS dev_charge_latest_verification_status,

                        SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) AS shop_service_charge_paid,
                        MAX(CASE WHEN lp.payment_type = 'shop_service_charge' 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.payment_date ELSE NULL END) AS shop_service_charge_last_payment_date,
                        SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                                      AND lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS shop_service_charge_pending_payments,
                        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_type = 'shop_service_charge' THEN lp.verification_status ELSE NULL END ORDER BY lp.created_at DESC SEPARATOR ','), ',', 1) AS shop_service_charge_latest_verification_status,

                        SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                      AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS emi_paid,
                        MAX(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                      AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.payment_date ELSE NULL END) AS emi_last_payment_date,
                        SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                      AND lp.verification_status = 'pending' 
                                      AND COALESCE(lp.is_emi_discount, 0) = 0 THEN 1 ELSE 0 END) AS emi_pending_payments,
                        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.verification_status ELSE NULL END ORDER BY lp.created_at DESC SEPARATOR ','), ',', 1) AS emi_latest_verification_status,

                        MAX(CASE WHEN (lp.payment_status IN ('paid','partially_paid','pending')) AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.payment_date ELSE NULL END) AS last_payment_date,
                        SUM(CASE WHEN lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS total_pending_payments,
                        SUBSTRING_INDEX(GROUP_CONCAT(lp.verification_status ORDER BY lp.created_at DESC SEPARATOR ','), ',', 1) AS latest_verification_status
                    FROM lead_bookings lb_sub
                    INNER JOIN lead_payments lp ON lp.booking_id = lb_sub.id
                    WHERE lb_sub.deleted_at IS NULL
                       AND lb_sub.booking_status NOT IN ('refunded', 'cancelled')
                       AND (lb_sub.landshare_status IS NULL OR lb_sub.landshare_status != 'cancelled')
                       AND lb_sub.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                       AND lp.is_trashed = 0
                       {$projectSubFilter}
                    GROUP BY lb_sub.lead_id
                ) AS p_stats ON p_stats.lead_id = l.id
                $whereClause
                GROUP BY l.id
                $havingClause
            ) AS count_table
        ";

        $stmt = $this->db->prepare($countSql);
        if (!$stmt) {
            error_log("getTotalClientsCount prepare failed: " . $this->db->error);
            return 0;
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? (int)($res->fetch_assoc()['total'] ?? 0) : 0;
    }

    /**
     * Fast summary of due EMIs for the top statistics card
     */
    public function getDueStatsSummary($projectId = null)
    {
        $projectWhere = "";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $projectWhere = " AND lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= "i";
                    }
                }
            } else {
                $projectWhere = " AND lb.project_id = ?";
                $params[] = (int)$projectId;
                $types .= "i";
            }
        }

        $sql = "
            SELECT 
                COALESCE(SUM(lp.due_emi_amount), 0) AS total_due_amount,
                COUNT(DISTINCT lp.id) AS total_due_emis_count,
                COUNT(DISTINCT lb.lead_id) AS total_due_clients_count
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.is_trashed = 0
              AND lp.payment_type = 'emi'
              AND lp.due_emi_amount > 0
              AND lp.verification_status IN ('verified', 'unverified', 'pending')
              AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
              AND lb.deleted_at IS NULL
              AND lb.booking_status NOT IN ('refunded', 'cancelled')
              AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
              AND lb.id NOT IN (
                  SELECT crr.booking_id FROM client_refund_requests crr 
                  WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded')
              )
              $projectWhere
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['total_due_amount' => 0, 'total_due_emis_count' => 0, 'total_due_clients_count' => 0];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;

        return [
            'total_due_amount' => (float)($row['total_due_amount'] ?? 0),
            'total_due_emis_count' => (int)($row['total_due_emis_count'] ?? 0),
            'total_due_clients_count' => (int)($row['total_due_clients_count'] ?? 0)
        ];
    }

    /**
     * Get list of clients with due EMIs for the Due EMIs modal
     */
    public function getDueClientsList($projectId = null, $search = null, $limit = 200)
    {
        $whereParts = [
            "lp.is_trashed = 0",
            "lp.payment_type = 'emi'",
            "lp.due_emi_amount > 0",
            "lp.verification_status IN ('verified', 'unverified', 'pending')",
            "(lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')",
            "lb.deleted_at IS NULL",
            "lb.booking_status NOT IN ('refunded', 'cancelled')",
            "(lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')",
            "lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))"
        ];
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereParts[] = "lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= "i";
                    }
                }
            } else {
                $whereParts[] = "lb.project_id = ?";
                $params[] = (int)$projectId;
                $types .= "i";
            }
        }

        if (!empty($search)) {
            $searchTerm = '%' . trim($search) . '%';
            $whereParts[] = "(l.full_name LIKE ? OR l.lead_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ? OR p.name LIKE ? OR f.flat_name LIKE ?)";
            for ($i = 0; $i < 6; $i++) {
                $params[] = $searchTerm;
                $types .= "s";
            }
        }

        $whereClause = "WHERE " . implode(" AND ", $whereParts);

        $sql = "
            SELECT 
                l.id AS lead_id,
                lb.id AS booking_id,
                COALESCE(NULLIF(TRIM(l.full_name), ''), NULLIF(TRIM(l.lead_name), '')) AS client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), NULLIF(TRIM(l.alt_phone_number), ''), NULLIF(TRIM(l.`2nd_owner_phone`), '')) AS phone,
                l.phone_number,
                l.email,
                p.name AS project_name,
                f.flat_name AS unit_name,
                SUM(lp.due_emi_amount) AS total_due_amount,
                COUNT(lp.id) AS due_count,
                GROUP_CONCAT(CONCAT(COALESCE(es.emi_no, 0), '||', lp.due_emi_amount, '||', COALESCE(lp.payment_date, '')) ORDER BY lp.payment_date ASC SEPARATOR ';;') AS due_emis_raw
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            INNER JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN flats f ON lb.flat_id = f.id
            $whereClause
            GROUP BY lb.id
            ORDER BY total_due_amount DESC
            LIMIT ?
        ";
        $params[] = (int)$limit;
        $types .= "i";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $list = [];
        $today = new \DateTime();

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $dueEmis = [];
                if (!empty($row['due_emis_raw'])) {
                    $items = explode(';;', $row['due_emis_raw']);
                    foreach ($items as $item) {
                        $parts = explode('||', $item);
                        $emiNo = (int)($parts[0] ?? 0);
                        $dueAmt = (float)($parts[1] ?? 0);
                        $dueDateStr = $parts[2] ?? '';
                        $isExpired = false;
                        $daysDiff = 0;

                        if (!empty($dueDateStr)) {
                            $dObj = new \DateTime($dueDateStr);
                            if ($dObj < $today) {
                                $isExpired = true;
                                $daysDiff = $today->diff($dObj)->days;
                            }
                        }

                        $dueEmis[$emiNo] = [
                            'emi_no' => $emiNo,
                            'due_amount' => $dueAmt,
                            'due_date' => $dueDateStr,
                            'is_expired' => $isExpired,
                            'days_expired' => $daysDiff,
                            'badge_class' => $isExpired ? 'bg-danger text-white' : 'bg-warning text-dark',
                            'type_label' => $isExpired ? 'Overdue EMI' : 'Due EMI'
                        ];
                    }
                }

                $list[] = [
                    'lead_id' => $row['lead_id'],
                    'booking_id' => $row['booking_id'],
                    'client_name' => $row['client_name'] ?? 'N/A',
                    'phone' => $row['phone'] ?? 'N/A',
                    'email' => $row['email'] ?? 'N/A',
                    'project_flats' => ($row['project_name'] ?? '') . '||' . ($row['unit_name'] ?? ''),
                    'due_count' => (int)($row['due_count'] ?? 0),
                    'total_due_amount' => (float)($row['total_due_amount'] ?? 0),
                    'due_emis' => $dueEmis
                ];
            }
        }

        return $list;
    }

    /**
     * Get all bookings with client and location info, optionally filtered by project
     */
    public function getAllBookings($projectId = null, $search = null, $limit = null, $offset = 0)
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereClause .= " AND lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= 'i';
                    }
                }
            } else {
                $whereClause .= " AND lb.project_id = ?";
                $params[] = (int) $projectId;
                $types .= 'i';
            }
        }

        if (!empty($search)) {
            $search = '%' . $search . '%';
            $whereClause .= " AND (l.full_name LIKE ? OR l.lead_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ? OR l.email LIKE ? OR p.name LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'ssssss';
        }

        $limitClause = "";
        if ($limit !== null) {
            $limitClause = "LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            $types .= 'ii';
        }

        $sql = $this->db->prepare("
        SELECT
            l.id AS lead_id,
            COALESCE(NULLIF(TRIM(l.full_name), ''), NULLIF(TRIM(l.lead_name), '')) AS client_name,
            COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), NULLIF(TRIM(l.alt_phone_number), ''), NULLIF(TRIM(l.`2nd_owner_phone`), '')) AS phone,
            l.phone_number,
            l.email,
            l.designation,
            lb.id AS booking_id,
            lb.project_id,
            p.name AS project_name,
            f.flat_name AS unit_name,
            f.floor_no AS flat_floor_no,
            lb.agreement_value AS total_price,
            lb.agreement_value AS total_agreement_value,
            COALESCE(
                (SELECT u.full_name FROM users u WHERE u.id = COALESCE(NULLIF(lb.sales_by_id, 0), NULLIF(l.assigned_id, 0), NULLIF(lb.created_by, 0)) LIMIT 1),
                (SELECT GROUP_CONCAT(DISTINCT u_agent.full_name SEPARATOR ', ')
                 FROM lead_payments lp_sub
                 INNER JOIN users u_agent ON u_agent.id = lp_sub.created_by
                 WHERE lp_sub.booking_id = lb.id AND lp_sub.payment_type = 'booking_money'
                )
            ) AS agent_name,
            COALESCE(
                (SELECT SUM(lp.amount)
                 FROM lead_payments lp
                 WHERE lp.is_trashed = 0 AND lp.booking_id = lb.id
                 AND lp.payment_status IN ('paid','partially_paid')),
                0
            ) AS total_paid,
            (
                SELECT MAX(lp.payment_date)
                FROM lead_payments lp
                WHERE lp.is_trashed = 0 AND lp.booking_id = lb.id
                AND lp.payment_status IN ('paid','partially_paid')
            ) AS last_payment_date
        FROM leads l
        INNER JOIN lead_bookings lb ON lb.lead_id = l.id
            AND lb.deleted_at IS NULL
            AND lb.booking_status NOT IN ('refunded', 'cancelled')
            AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
            AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        $whereClause
        ORDER BY l.full_name
        $limitClause
        ");

        if (!$sql) {
            error_log("getAllBookings prepare failed: " . $this->db->error);
            return [];
        }

        if (!empty($params)) {
            $sql->bind_param($types, ...$params);
        }

        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get total bookings count for CED dashboard pagination
     */
    public function getTotalBookingsCount($projectId = null, $search = null)
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereClause .= " AND lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= 'i';
                    }
                }
            } else {
                $whereClause .= " AND lb.project_id = ?";
                $params[] = (int) $projectId;
                $types .= 'i';
            }
        }

        if (!empty($search)) {
            $search = '%' . $search . '%';
            $whereClause .= " AND (l.full_name LIKE ? OR l.lead_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ? OR l.email LIKE ? OR p.name LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'ssssss';
        }

        $sql = $this->db->prepare("
        SELECT COUNT(l.id) AS total
        FROM leads l
        INNER JOIN lead_bookings lb ON lb.lead_id = l.id
            AND lb.deleted_at IS NULL
            AND lb.booking_status NOT IN ('refunded', 'cancelled')
            AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
            AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
        LEFT JOIN projects p ON lb.project_id = p.id
        $whereClause
        ");

        if (!$sql) {
            error_log("getTotalBookingsCount prepare failed: " . $this->db->error);
            return 0;
        }

        if (!empty($params)) {
            $sql->bind_param($types, ...$params);
        }

        $sql->execute();
        $result = $sql->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * Get lands by lead ID
     */
    public function getLandsByLeadId($leadId)
    {
        $sql = $this->db->prepare("
        SELECT 
            lb.id as booking_id,
            lb.booking_date,
            lb.agreement_value,
            lb.booking_status,
            lb.landshare_status,
            lb.service_fee_balance,
            lb.transfer_status,
            
            p.id as project_id,
            p.name as project_name,
            p.types as project_type,
            
            f.id as flat_id,
            f.floor_no as flat_no,
            f.sqft as flat_size,
            f.price_value as total_price,
            f.land_share_value as total_land_share,
            f.flat_name as unit_name,
            
            (SELECT u_agent.full_name
             FROM lead_payments lp_sub
             INNER JOIN users u_agent ON u_agent.id = lp_sub.created_by
             WHERE lp_sub.booking_id = lb.id AND lp_sub.payment_type = 'booking_money'
             LIMIT 1
            ) AS booking_agent_name,
            
            COALESCE(
                (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                 WHERE lp.is_trashed = 0 AND lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                0
            ) as total_paid,
            COALESCE(
                (SELECT SUM(lp.land_share_amount) 
                 FROM lead_payments lp 
                 WHERE lp.is_trashed = 0 AND lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                0
            ) as total_land_share_paid,
            COALESCE(
                (SELECT SUM(lp.service_fee) 
                 FROM lead_payments lp 
                 WHERE lp.is_trashed = 0 AND lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                0
            ) as total_service_fee,
            COALESCE(
                (SELECT MAX(lp.payment_date) 
                 FROM lead_payments lp 
                 WHERE lp.is_trashed = 0 AND lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                NULL
            ) as last_payment_date,
            (SELECT COALESCE(e_lp.full_name, u_lp.full_name, u_lp.user_name, CONCAT('User #', lp.created_by))
             FROM lead_payments lp
             LEFT JOIN users u_lp ON lp.created_by = u_lp.id
             LEFT JOIN employees e_lp ON (u_lp.employee_id = e_lp.id OR e_lp.user_id = u_lp.id)
             WHERE lp.is_trashed = 0 AND lp.booking_id = lb.id 
               AND lp.payment_status IN ('paid','partially_paid','pending')
               AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
             ORDER BY lp.payment_date DESC, lp.id DESC
             LIMIT 1
            ) as last_payment_creator_name
        FROM lead_bookings lb
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        WHERE lb.lead_id = ?
          AND lb.deleted_at IS NULL
          AND lb.booking_status NOT IN ('refunded', 'cancelled')
          AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
          AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
        ORDER BY lb.booking_date DESC
    ");

        $sql->bind_param("i", $leadId);
        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


    /**
     * Get payment statistics
     */
    public function getPaymentStatistics($projectId = null)
    {
        $whereClause = "WHERE lb.deleted_at IS NULL 
            AND lb.booking_status NOT IN ('refunded', 'cancelled') 
            AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled') 
            AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereClause .= " AND lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= 'i';
                    }
                }
            } else {
                $whereClause .= " AND lb.project_id = ?";
                $params[] = (int) $projectId;
                $types .= "i";
            }
        }

        $sql = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT l.id) as total_clients,
                COUNT(DISTINCT lb.id) as total_units_booked,
                COUNT(DISTINCT lb.id) as units_booked,
                COUNT(DISTINCT lb.id) as approved_bookings,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT SUM(lp.amount) 
                          FROM lead_payments lp 
                          WHERE lp.is_trashed = 0 AND lp.booking_id = lb.id 
                          AND lp.payment_status IN ('paid','partially_paid')) >= lb.agreement_value 
                    THEN lb.id 
                END) as full_payment_completed,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT COUNT(*) 
                          FROM lead_payments lp2 
                          WHERE lp2.booking_id = lb.id 
                          AND lp2.payment_status = 'paid') > 0
                    THEN lb.id 
                END) as paid_bookings,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT COUNT(*) 
                          FROM lead_payments lp3 
                          WHERE lp3.booking_id = lb.id 
                          AND lp3.verification_status = 'pending') > 0
                    THEN l.id 
                END) as pending_verifications,
                COALESCE(SUM(lp.service_fee), 0) as total_service_fee_collected
            FROM leads l
            INNER JOIN lead_bookings lb ON l.id = lb.lead_id
            LEFT JOIN lead_payments lp ON lb.id = lp.booking_id AND lp.payment_status IN ('paid','partially_paid') AND lp.is_trashed = 0
            $whereClause
        ");

        if (!$sql) {
            error_log("getPaymentStatistics prepare failed: " . $this->db->error);
            return [];
        }

        if (!empty($params)) {
            $sql->bind_param($types, ...$params);
        }

        $sql->execute();
        $result = $sql->get_result();
        $stats = $result ? $result->fetch_assoc() : [];

        // Get total pending transactions count
        $pendingSql = $this->db->prepare("
            SELECT COUNT(lp.id) as total_pending_transactions
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            $whereClause AND lp.verification_status = 'pending' AND lp.is_trashed = 0
        ");
        if ($pendingSql) {
            if (!empty($params)) {
                $pendingSql->bind_param($types, ...$params);
            }
            $pendingSql->execute();
            $pendingResult = $pendingSql->get_result();
            if ($pendingResult && $row = $pendingResult->fetch_assoc()) {
                $stats['total_pending_transactions'] = $row['total_pending_transactions'];
            } else {
                $stats['total_pending_transactions'] = 0;
            }
        } else {
            $stats['total_pending_transactions'] = 0;
        }

        return $stats;
    }

    /**
     * Get collection stats summary (Total, Verified, Unverified, Land Share, Dev Charge, EMI)
     */
    public function getCollectionStatsSummary($projectId = null)
    {
        $whereClause = "WHERE lp.is_trashed = 0 
            AND COALESCE(lp.is_emi_discount, 0) = 0
            AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
            AND lb.deleted_at IS NULL
            AND lb.booking_status NOT IN ('refunded', 'cancelled')
            AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
            AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereClause .= " AND lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= 'i';
                    }
                }
            } else {
                $whereClause .= " AND lb.project_id = ?";
                $params[] = (int) $projectId;
                $types .= "i";
            }
        }

        $sql = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                       AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                  THEN lp.amount ELSE 0 END), 0) AS total_collected,
                COALESCE(SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid')) 
                                       AND lp.verification_status = 'verified' 
                                  THEN lp.amount ELSE 0 END), 0) AS total_verified_collected,
                COALESCE(SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                       AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
                                       AND NOT (lp.payment_status IN ('paid', 'partially_paid') AND COALESCE(lp.verification_status, '') = 'verified')
                                  THEN lp.amount ELSE 0 END), 0) AS total_unverified_collected,

                COALESCE(SUM(CASE WHEN (lp.payment_type IN ('land_share', 'booking_money') OR IFNULL(lp.land_share_amount, 0) > 0) 
                                       AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                       AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                  THEN lp.amount ELSE 0 END), 0) AS total_land_share_collected,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'development_charge' 
                                       AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                       AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                  THEN lp.amount ELSE 0 END), 0) AS total_dev_charge_collected,
                COALESCE(SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                       AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                       AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                  THEN lp.amount ELSE 0 END), 0) AS total_emi_collected,
                COALESCE(SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                       AND (lp.payment_status IN ('paid', 'partially_paid')) 
                                       AND lp.verification_status = 'verified' 
                                  THEN lp.amount ELSE 0 END), 0) AS total_verified_emi_collected,
                COALESCE(SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                       AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                       AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
                                       AND NOT (lp.payment_status IN ('paid', 'partially_paid') AND COALESCE(lp.verification_status, '') = 'verified')
                                  THEN lp.amount ELSE 0 END), 0) AS total_unverified_emi_collected
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            $whereClause
        ");

        if (!$sql) {
            error_log("getCollectionStatsSummary prepare failed: " . $this->db->error);
            return [
                'total_collected' => 0.00,
                'total_verified_collected' => 0.00,
                'total_unverified_collected' => 0.00,
                'total_land_share_collected' => 0.00,
                'total_dev_charge_collected' => 0.00,
                'total_emi_collected' => 0.00,
                'total_verified_emi_collected' => 0.00,
                'total_unverified_emi_collected' => 0.00
            ];
        }

        if (!empty($params)) {
            $sql->bind_param($types, ...$params);
        }

        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_assoc() : [
            'total_collected' => 0.00,
            'total_verified_collected' => 0.00,
            'total_unverified_collected' => 0.00,
            'total_land_share_collected' => 0.00,
            'total_dev_charge_collected' => 0.00,
            'total_emi_collected' => 0.00,
            'total_verified_emi_collected' => 0.00,
            'total_unverified_emi_collected' => 0.00
        ];
    }

    /**
     * Get project-wise collection breakdown (Total, Land Share, Dev Charge, EMI, Verified EMI, Unverified EMI per project)
     */
    public function getProjectCollectionBreakdown($allowedProjectIds = null)
    {
        $projectFilter = "";
        if (!empty($allowedProjectIds)) {
            if (is_array($allowedProjectIds)) {
                $pIds = array_values(array_filter(array_map('intval', $allowedProjectIds)));
                if (!empty($pIds)) {
                    $projectFilter = " AND p.id IN (" . implode(',', $pIds) . ")";
                }
            } else {
                $projectFilter = " AND p.id = " . (int)$allowedProjectIds;
            }
        }

        $sql = "
            SELECT 
                p.id AS project_id,
                p.name AS project_name,
                COUNT(DISTINCT lb.id) AS total_bookings,
                COALESCE(SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                 THEN lp.amount ELSE 0 END), 0) AS total_collected,
                COALESCE(SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid')) 
                                      AND lp.verification_status = 'verified' 
                                 THEN lp.amount ELSE 0 END), 0) AS verified_collected,
                COALESCE(SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
                                      AND NOT (lp.payment_status IN ('paid', 'partially_paid') AND COALESCE(lp.verification_status, '') = 'verified')
                                 THEN lp.amount ELSE 0 END), 0) AS unverified_collected,
                COALESCE(SUM(CASE WHEN (lp.payment_type IN ('land_share', 'booking_money') OR IFNULL(lp.land_share_amount, 0) > 0) 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                 THEN lp.amount ELSE 0 END), 0) AS land_share_collected,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'development_charge' 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                 THEN lp.amount ELSE 0 END), 0) AS dev_charge_collected,
                COALESCE(SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                 THEN lp.amount ELSE 0 END), 0) AS total_emi_collected,
                COALESCE(SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                      AND (lp.payment_status IN ('paid', 'partially_paid')) 
                                      AND lp.verification_status = 'verified' 
                                 THEN lp.amount ELSE 0 END), 0) AS verified_emi_collected,
                COALESCE(SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                      AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
                                      AND NOT (lp.payment_status IN ('paid', 'partially_paid') AND COALESCE(lp.verification_status, '') = 'verified')
                                 THEN lp.amount ELSE 0 END), 0) AS unverified_emi_collected
            FROM projects p
            LEFT JOIN lead_bookings lb ON lb.project_id = p.id 
                AND lb.deleted_at IS NULL
                AND lb.booking_status NOT IN ('refunded', 'cancelled')
                AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
            LEFT JOIN lead_payments lp ON lp.booking_id = lb.id
                AND lp.is_trashed = 0
                AND COALESCE(lp.is_emi_discount, 0) = 0
                AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
            WHERE p.deleted_at IS NULL {$projectFilter}
            GROUP BY p.id, p.name
            HAVING total_collected > 0
            ORDER BY total_collected DESC, p.name ASC
        ";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get booking money transaction from database
     */
    public function getBookingMoneyTransaction($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                lp.*,
                l.full_name as client_name,
                p.name as project_name,
                f.id as flat_id,
                f.floor_no,
                f.flat_name as unit_name,
                CONCAT(f.flat_name, ' - FLOOR ', f.floor_no) as flat_no,
                u.full_name as created_by_name
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN users u ON u.id = lp.created_by
            WHERE lp.is_trashed = 0 AND lp.booking_id = ? 
            AND lp.payment_type = 'booking_money'
            ORDER BY lp.payment_date ASC
            LIMIT 1
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result ? $result->fetch_assoc() : null;

        if ($transaction) {
            $transaction['installment_label'] = 'BOOKING';
            $transaction['payment_details'] = 'Booking Agreement';
        }

        return $transaction;
    }

    /**
     * Get client details by booking ID with correct payment calculations
     * FIXED: Now correctly calculates service fee from emi_schedules table
     */
    public function getClientByBookingId($bookingId)
    {
        $hasTrashed = $this->hasColumn('lead_payments', 'is_trashed');
        $lpTrash = $hasTrashed ? "lp.is_trashed = 0 AND " : "";

        $hasTransfer = $this->hasColumn('lead_bookings', 'transfer_status');
        $hasTotalEmi = $this->hasColumn('lead_bookings', 'total_emi_no');
        $hasEmiAudit = $this->hasColumn('lead_bookings', 'emi_updated_by');
        $hasApproval = $this->hasColumn('lead_bookings', 'approval_status');
        $hasApprovedAmt = $this->hasColumn('lead_bookings', 'approved_amount');
        $hasPastApp  = $this->hasColumn('lead_bookings', 'past_approval_attachment');
        $hasLandshareStatus = $this->hasColumn('lead_bookings', 'landshare_status');
        $hasServiceFeeBal   = $this->hasColumn('lead_bookings', 'service_fee_balance');

        $approvalCol    = $hasApproval ? "lb.approval_status," : "NULL as approval_status,";
        $approvedAmtCol = $hasApprovedAmt ? "lb.approved_amount," : "NULL as approved_amount,";
        $pastAppCol     = $hasPastApp ? "lb.past_approval_attachment," : "NULL as past_approval_attachment,";
        $lsStatusCol    = $hasLandshareStatus ? "lb.landshare_status," : "NULL as landshare_status,";
        $sfBalCol       = $hasServiceFeeBal ? "lb.service_fee_balance," : "NULL as service_fee_balance,";

        $transferCols   = $hasTransfer ? "lb.transfer_date, lb.transfer_note, lb.transfer_status, transfer_u.full_name as transfer_updated_by_name," : "NULL as transfer_date, NULL as transfer_note, 0 as transfer_status, NULL as transfer_updated_by_name,";
        $transferJoin   = $hasTransfer ? "LEFT JOIN users transfer_u ON lb.transfer_updated_by = transfer_u.id" : "";

        $totalEmiCol    = $hasTotalEmi ? "lb.total_emi_no as booking_total_emi_no," : "NULL as booking_total_emi_no,";
        $emiAuditCols   = $hasEmiAudit ? "lb.emi_updated_by, lb.emi_updated_at, emi_u.full_name as emi_updated_by_name" : "NULL as emi_updated_by, NULL as emi_updated_at, NULL as emi_updated_by_name";
        $emiAuditJoin   = $hasEmiAudit ? "LEFT JOIN users emi_u ON lb.emi_updated_by = emi_u.id" : "";

        $stmt = $this->db->prepare("
            SELECT 
                lb.id as booking_id,
                lb.lead_id,
                lb.booking_date,
                lb.sales_by_id,
                l.assigned_id,
                lb.agreement_value,
                -- booking_money_total: all NON-REJECTED entries (pending + verified) — for remaining balance in dropdown
                COALESCE(
                 (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                WHERE {$lpTrash}lp.booking_id = lb.id 
                 AND lp.payment_type = 'booking_money'
                 AND lp.verification_status != 'rejected'), 
                 0) AS booking_money_total,
                -- booking_money: only VERIFIED entries (for stat cards & land share calc)
                COALESCE(
                 (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                WHERE {$lpTrash}lp.booking_id = lb.id 
                 AND lp.payment_type = 'booking_money'
                 AND lp.verification_status = 'verified'), 
                 0) AS booking_money,
                lb.discount_amount,
                {$approvedAmtCol}
                {$approvalCol}
                lb.booking_status,
                {$lsStatusCol}
                {$sfBalCol}
                {$pastAppCol}
                l.id as lead_id,
                COALESCE(NULLIF(TRIM(l.full_name), ''), NULLIF(TRIM(l.lead_name), '')) as client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), NULLIF(TRIM(l.alt_phone_number), ''), NULLIF(TRIM(l.`2nd_owner_phone`), '')) AS phone,
                l.phone_number,
                l.email,
                l.2nd_lead_owner_name,
                l.2nd_owner_phone,
                l.designation,
                p.id as project_id,
                p.name as project_name,
                p.category_id,
                pc.name as category_name,
                p.types as project_type,
                COALESCE(p.service_charge_per_emi, 0) as service_charge_per_emi,
                f.id as flat_id,
                f.floor_no as flat_no,
                f.sqft as flat_size,
                f.price_value as total_price,
                f.land_share_value as total_land_share,
                COALESCE(f.development_charges, 0) as flat_development_charges,
                f.flat_name as unit_name,
                f.is_shop,
                f.unit_type,
                COALESCE(f.shop_service_charge, 0) as shop_service_charge,
                u.full_name as agent_name,
                COALESCE(NULLIF(e1.employee_id, ''), NULLIF(e2.employee_id, ''), NULLIF(u.employee_id, '')) as agent_employee_id,
                -- Payment calculations
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND COALESCE(lp.is_emi_discount, 0) = 0), 
                    0
                ) as total_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_type = 'others'
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    0
                ) as others_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_type = 'others'
                     AND lp.verification_status != 'rejected'), 
                    0
                ) as others_paid_total,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_type = 'land_share'
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    0
                ) as additional_land_share_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_type = 'land_share'
                     AND lp.verification_status != 'rejected'), 
                    0
                ) as additional_land_share_paid_total,
                -- Service fee: sum from verified EMI/settlement payments only
                COALESCE(
                    (SELECT SUM(lp.service_fee) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_type IN ('emi', 'emi_due_settlement')
                     AND lp.verification_status = 'verified'), 
                    0
                ) as total_service_fee,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'registration'), 
                    0
                ) as registration_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'registration'), 
                    0
                ) as registration_paid_total,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'mutation_cost'), 
                    0
                ) as mutation_cost_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'mutation_cost'), 
                    0
                ) as mutation_cost_paid_total,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_type = 'booking_money'
                     AND lp.verification_status = 'verified'), 
                    0
                ) as booking_money_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND COALESCE(lp.is_emi_discount, 0) = 0
                     AND lp.payment_type IN ('emi', 'emi_due_settlement')), 
                    0
                ) as emi_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'
                     AND COALESCE(lp.is_emi_discount, 0) = 0
                     AND lp.payment_type IN ('emi', 'emi_due_settlement')), 
                    0
                ) as emi_paid_total,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'development_charge'), 
                    0
                ) as development_charge_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'development_charge'), 
                    0
                ) as development_charge_paid_total,
                COALESCE(
                    (SELECT SUM(lp.due_emi_amount)
                     FROM lead_payments lp
                     WHERE {$lpTrash}lp.booking_id = lb.id
                     AND lp.payment_type = 'emi'
                     AND lp.due_emi_amount > 0
                     AND lp.verification_status != 'rejected'
                     AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
                     AND NOT EXISTS (
                         SELECT 1 FROM lead_payments child
                         WHERE child.is_trashed = 0 AND child.parent_payment_id = lp.id
                         AND (child.payment_type = 'emi_due_settlement' OR child.settlement_type = 'emi_due_settlement')
                         AND child.verification_status IN ('pending', 'verified')
                     )),
                    0
                ) as total_emi_due,
                COALESCE(
                    (SELECT SUM(
                        CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END
                    )
                     FROM lead_payments lp
                     WHERE {$lpTrash}lp.booking_id = lb.id
                     AND lp.verification_status = 'verified'
                     AND (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1)),
                    0
                ) as total_bulk_emi_discount,
                COALESCE(
                    (SELECT SUM(
                        CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END
                    )
                     FROM lead_payments lp
                     WHERE {$lpTrash}lp.booking_id = lb.id
                     AND lp.verification_status IN ('pending', 'under_review', 'unverified')
                     AND (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1)),
                    0
                ) as unverified_bulk_emi_discount,
                COALESCE(
                    (SELECT MAX(lp.payment_date) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    NULL
                ) as last_payment_date,
                COALESCE(
                    (SELECT SUM(lp.penalty_paid) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status != 'rejected'), 
                    0
                ) as total_penalty_paid,
                COALESCE(
                    (SELECT SUM(lp.penalty_amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'), 
                    0
                ) as total_penalty_amount,
                COALESCE(
                    (SELECT SUM(lp.penalty_waived) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'), 
                    0
                ) as total_penalty_waived,
                {$transferCols}
                {$totalEmiCol}
                {$emiAuditCols}
            FROM lead_bookings lb
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u ON COALESCE(NULLIF(lb.sales_by_id, 0), NULLIF(l.assigned_id, 0)) = u.id
            LEFT JOIN employees e1 ON e1.user_id = u.id
            LEFT JOIN employees e2 ON u.employee_id = e2.id
            {$transferJoin}
            {$emiAuditJoin}
            WHERE lb.id = ?
        ");

        if (!$stmt) {
            error_log("Prepare statement failed: " . $this->db->error);
            return null;
        }

        $stmt->bind_param("i", $bookingId);

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return null;
        }

        $result = $stmt->get_result();
        $clientDetails = $result ? $result->fetch_assoc() : null;

        if ($clientDetails) {
            // Cast all amounts to float for calculations
            $totalPrice            = (float) $clientDetails['total_price'];
            $totalLandShare        = (float) $clientDetails['total_land_share'];   // flat.land_share_value (fixed target)
            $flatDevCharges        = (float) ($clientDetails['flat_development_charges'] ?? 0); // flat.development_charges (fixed)
            $serviceChargePerEmi   = (float) ($clientDetails['service_charge_per_emi'] ?? 0); // project %
            $bookingMoney          = (float) $clientDetails['booking_money'];      // verified booking money
            $discountAmount        = (float) $clientDetails['discount_amount'];

            // Paid amounts
            $totalPaid             = (float) $clientDetails['total_paid'];
            $othersPaid            = (float) ($clientDetails['others_paid'] ?? 0);
            $othersPaidTotal       = (float) ($clientDetails['others_paid_total'] ?? $othersPaid);
            $bookingMoneyPaid      = (float) $clientDetails['booking_money_paid'];
            $additionalLandSharePaid      = (float) $clientDetails['additional_land_share_paid'];
            $additionalLandSharePaidTotal = (float) ($clientDetails['additional_land_share_paid_total'] ?? $additionalLandSharePaid);
            $registrationPaid             = (float) $clientDetails['registration_paid'];
            $registrationPaidTotal        = (float) ($clientDetails['registration_paid_total'] ?? $registrationPaid);
            $developmentChargePaid        = (float) $clientDetails['development_charge_paid'];
            $developmentChargePaidTotal   = (float) ($clientDetails['development_charge_paid_total'] ?? $developmentChargePaid);
            $emiPaid                      = (float) $clientDetails['emi_paid'];
            $emiPaidTotal                 = (float) ($clientDetails['emi_paid_total'] ?? $emiPaid);
            $totalServiceFee              = (float) $clientDetails['total_service_fee'];
            $mutationCostPaid             = (float) ($clientDetails['mutation_cost_paid'] ?? 0);
            $mutationCostPaidTotal        = (float) ($clientDetails['mutation_cost_paid_total'] ?? $mutationCostPaid);

            // booking_money_total: all recorded entries (used for remaining in dropdown)
            $bookingMoneyTotal = (float) ($clientDetails['booking_money_total'] ?? 0);

            // NEW: Landshare Paid = booking money + land share payments + registration payments + mutation cost
            // (registration and mutation cost are treated as part of the landshare component)
            $totalLandSharePaid      = $bookingMoney + $additionalLandSharePaid + $registrationPaid + $mutationCostPaid;
            $totalLandSharePaidTotal = $bookingMoneyTotal + $additionalLandSharePaidTotal + $registrationPaidTotal + $mutationCostPaidTotal;

            // Remaining land share = effective target - all land share group payments
            // Land share group = booking money + additional land share payments + registration + mutation cost
            // Discount from sales reduces the effective land share target
            $landSharePaymentsOnly    = round($bookingMoney + $additionalLandSharePaid + $registrationPaid + $mutationCostPaid);
            $effectiveLandShareTarget = max(0, round($totalLandShare - $discountAmount));
            $remainingLandShare       = max(0, round($effectiveLandShareTarget - $landSharePaymentsOnly));
            $remainingLandShareTotal  = max(0, round($effectiveLandShareTarget - $totalLandSharePaidTotal));
            $isLandShareCompleted     = ($remainingLandShareTotal <= 0 || $remainingLandShare <= 0);

            // Total Construction Fee = Total Price - (Land Share + Dev Charges)
            // Discount only affects land share remaining, NOT the construction fee
            $constructionFee = max(0, round($totalPrice - ($totalLandShare + $flatDevCharges)));

            // Dev charge: completed when paid amount (or unrejected total) >= flat fixed development_charges
            $isDevChargeCompleted = ($flatDevCharges > 0 && ($developmentChargePaidTotal >= $flatDevCharges || $developmentChargePaid >= $flatDevCharges));
            // If there's no dev charge defined on the flat, treat it as complete (not blocking EMI)
            if ($flatDevCharges <= 0) {
                $isDevChargeCompleted = true;
            }

            // Construction fee paid = total EMI amounts paid (amount IS the construction portion;
            // service fee is charged ON TOP as company revenue, not deducted from construction)
            $constructionFeePaid         = $emiPaid;
            $totalBulkEmiDiscount        = (float) ($clientDetails['total_bulk_emi_discount'] ?? 0);
            $remainingConstructionFee    = max(0, round($constructionFee - $constructionFeePaid - $totalBulkEmiDiscount));

            // Total due and net received
            // Booking discount + Bulk EMI discount both reduce the client's total obligation
            // Note: Payments under 'others' are outside flat/unit price and DO NOT reduce flat due
            $unitPaid     = max(0, $totalPaid - $othersPaid);
            $totalDue     = max(0, round($totalPrice - $discountAmount - $unitPaid - $totalBulkEmiDiscount));
            $netReceived  = round($totalPaid - $totalServiceFee);
            $totalEmiDue  = (float) ($clientDetails['total_emi_due'] ?? 0);

            // Safety net: if construction fee is fully paid, there can be NO outstanding EMI due
            // This handles legacy data where due_emi_amount was set on the last installment
            if ($remainingConstructionFee <= 0) {
                $totalEmiDue = 0;
            }

            // Fetch EMI statistics
            $emiStats = $this->getEMIStatistics($bookingId);
            $totalEmiCount = $emiStats['total_emi'];
            $completedEmiCount = $emiStats['completed_emi'];
            $remainingEmiCount = max(0, $totalEmiCount - $completedEmiCount);

            // Fixed Per-EMI Calculation: Total Construction Fee ÷ Total EMI Count
            $perEmiAmount = ($totalEmiCount > 0) ? round($constructionFee / $totalEmiCount) : 0;

            // Registration paid status
            $isRegistrationPaid = ($registrationPaid > 0);

            // Add calculated fields to response
            $agreementValForTarget = (float) ($clientDetails['agreement_value'] ?? 0);
            $discountValForTarget  = (float) ($clientDetails['discount_amount'] ?? 0);
            $clientDetails['effective_booking_target']             = ($agreementValForTarget > 0) 
                ? max(0, $agreementValForTarget - $discountValForTarget) 
                : 300000;
            $clientDetails['total_land_share_paid']                = $totalLandSharePaid;
            $clientDetails['landshare_paid']                       = $totalLandSharePaid;
            $clientDetails['total_land_share_paid_total']          = $totalLandSharePaidTotal;
            $clientDetails['mutation_cost_paid']                   = $mutationCostPaid;
            $clientDetails['flat_development_charges']             = $flatDevCharges;
            $clientDetails['development_charge_paid']              = $developmentChargePaid;
            $clientDetails['development_charge_paid_total']        = $developmentChargePaidTotal;
            $clientDetails['remaining_land_share']                 = $remainingLandShare;
            $clientDetails['remaining_land_share_total']           = $remainingLandShareTotal;
            $clientDetails['is_land_share_completed']              = $isLandShareCompleted;
            $clientDetails['is_dev_charge_completed']              = $isDevChargeCompleted;
            $clientDetails['is_registration_paid']                 = $isRegistrationPaid;
            $clientDetails['construction_fee']                     = $constructionFee;
            $clientDetails['per_emi_amount']                       = $perEmiAmount;
            $clientDetails['service_charge_per_emi']               = $serviceChargePerEmi;
            $clientDetails['construction_fee_paid']                = $constructionFeePaid;
            $clientDetails['emi_paid_total']                       = $emiPaidTotal;
            $clientDetails['construction_fee_paid_after_service']  = $constructionFeePaid;
            $clientDetails['remaining_construction_fee']           = $remainingConstructionFee;
            $clientDetails['total_due']                            = $totalDue;
            $clientDetails['total_penalty_paid']                   = (float) ($clientDetails['total_penalty_paid'] ?? 0);
            $clientDetails['total_penalty_amount']                 = (float) ($clientDetails['total_penalty_amount'] ?? 0);
            $clientDetails['total_penalty_waived']                 = (float) ($clientDetails['total_penalty_waived'] ?? 0);
            $clientDetails['others_paid']                          = $othersPaid;
            $clientDetails['others_paid_total']                    = $othersPaidTotal;
            $clientDetails['net_received']                         = $netReceived;
            $clientDetails['total_emi_due']                        = $totalEmiDue;
            $clientDetails['total_bulk_emi_discount']              = $totalBulkEmiDiscount;

            // Add EMI statistics & audit metadata
            $clientDetails['total_emi']              = $emiStats['total_emi'];
            $clientDetails['completed_emi']          = $emiStats['completed_emi'];
            $clientDetails['unverified_emi_count']   = $emiStats['unverified_emi_count'];
            $clientDetails['unverified_emi_amount']  = $emiStats['unverified_emi_amount'];
            $clientDetails['pending_emi']            = $emiStats['pending_emi'];
            $clientDetails['paid_emi_numbers']       = $emiStats['paid_emi_numbers'];
            $clientDetails['unverified_emi_numbers'] = $emiStats['unverified_emi_numbers'];
            $clientDetails['pending_emi_numbers']    = $emiStats['pending_emi_numbers'];
            $clientDetails['emi_updated_by']         = $emiStats['emi_updated_by'];
            $clientDetails['emi_updated_at']         = $emiStats['emi_updated_at'];
            $clientDetails['next_emi_no']            = $emiStats['next_emi_no'] ?? $this->getNextAvailableEmiNo($bookingId);
            $clientDetails['emi_updated_by_name']    = $emiStats['emi_updated_by_name'];
        }

        return $clientDetails;
    }

    /**
     * Get EMI statistics for a booking
     */
    private function getEMIStatistics($bookingId)
    {
        $hasTotalEmiLb = $this->hasColumn('lead_bookings', 'total_emi_no');
        $hasEmiAuditLb = $this->hasColumn('lead_bookings', 'emi_updated_by');

        if ($hasTotalEmiLb && $hasEmiAuditLb) {
            $totalEmiStmt = $this->db->prepare("
                SELECT 
                    COALESCE(NULLIF(lb.total_emi_no, 0), lp.total_emi_no, 0) AS total_emi_no,
                    lb.emi_updated_by,
                    lb.emi_updated_at,
                    u.full_name AS emi_updated_by_name
                FROM lead_bookings lb
                LEFT JOIN (
                    SELECT booking_id, MAX(total_emi_no) as total_emi_no
                    FROM lead_payments
                    WHERE booking_id = ? AND total_emi_no IS NOT NULL
                    GROUP BY booking_id
                ) lp ON lp.booking_id = lb.id
                LEFT JOIN users u ON u.id = lb.emi_updated_by
                WHERE lb.id = ?
                LIMIT 1
            ");
            $totalEmiStmt->bind_param("ii", $bookingId, $bookingId);
        } else {
            $totalEmiStmt = $this->db->prepare("
                SELECT 
                    COALESCE(MAX(total_emi_no), 0) AS total_emi_no,
                    NULL AS emi_updated_by,
                    NULL AS emi_updated_at,
                    NULL AS emi_updated_by_name
                FROM lead_payments
                WHERE booking_id = ? AND total_emi_no IS NOT NULL
                LIMIT 1
            ");
            $totalEmiStmt->bind_param("i", $bookingId);
        }
        $totalEmiStmt->execute();
        $totalEmiResult = $totalEmiStmt->get_result();
        $totalEmiRow = $totalEmiResult ? $totalEmiResult->fetch_assoc() : null;
        $totalEmi = $totalEmiRow && $totalEmiRow['total_emi_no'] ? (int) $totalEmiRow['total_emi_no'] : 0;
        if ($totalEmi > 120) {
            $totalEmi = 42; // Safety cap against abnormal corrupted numbers
        }

        $stats = [
            'total_emi' => $totalEmi,
            'completed_emi' => 0,
            'unverified_emi_count' => 0,
            'unverified_emi_amount' => 0.0,
            'pending_emi' => 0,
            'paid_emi_numbers' => [],
            'unverified_emi_numbers' => [],
            'pending_emi_numbers' => [],
            'emi_updated_by' => $totalEmiRow['emi_updated_by'] ?? null,
            'emi_updated_at' => $totalEmiRow['emi_updated_at'] ?? null,
            'emi_updated_by_name' => $totalEmiRow['emi_updated_by_name'] ?? null,
        ];

        // Get paid EMI numbers (verified only if linked to payment, or legacy paid schedule)
        $paidEmiStmt = $this->db->prepare("
            SELECT DISTINCT es.emi_no
            FROM emi_schedules es
            LEFT JOIN lead_payments lp ON lp.id = es.lead_payment_id
            WHERE es.lead_booking_id = ? 
              AND (
                  (lp.id IS NOT NULL AND lp.is_trashed = 0 AND lp.verification_status = 'verified' AND lp.payment_status IN ('paid','partially_paid'))
                  OR (es.lead_payment_id IS NULL AND es.status = 'paid')
              )
            ORDER BY es.emi_no
        ");
        $paidEmiStmt->bind_param("i", $bookingId);
        $paidEmiStmt->execute();
        $paidEmiResult = $paidEmiStmt->get_result();

        $paidEmiNumbers = [];
        if ($paidEmiResult) {
            while ($row = $paidEmiResult->fetch_assoc()) {
                $paidEmiNumbers[] = (int) $row['emi_no'];
            }
        }

        $stats['completed_emi'] = count($paidEmiNumbers);
        $stats['paid_emi_numbers'] = $paidEmiNumbers;
        $stats['next_emi_no'] = $this->getNextAvailableEmiNo($bookingId);

        if ($totalEmi < $stats['completed_emi'] || $totalEmi <= 0) {
            $maxSchedStmt = $this->db->prepare("SELECT MAX(emi_no) as max_emi FROM emi_schedules WHERE lead_booking_id = ?");
            if ($maxSchedStmt) {
                $maxSchedStmt->bind_param("i", $bookingId);
                $maxSchedStmt->execute();
                $maxSchedRes = $maxSchedStmt->get_result();
                $maxSchedRow = $maxSchedRes ? $maxSchedRes->fetch_assoc() : null;
                $maxSched = (int)($maxSchedRow['max_emi'] ?? 0);
                if ($maxSched > 0) {
                    $totalEmi = max($totalEmi, $maxSched);
                }
            }
            if ($totalEmi < $stats['completed_emi']) {
                $totalEmi = $stats['completed_emi'];
            }
            if ($totalEmi <= 0) {
                $totalEmi = 36;
            }
            $stats['total_emi'] = $totalEmi;
        }

        // Get unverified EMI numbers & total amount
        $unverifiedEmiStmt = $this->db->prepare("
            SELECT DISTINCT es.emi_no, lp.amount
            FROM emi_schedules es
            INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id
            WHERE lp.is_trashed = 0 AND lp.booking_id = ? 
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status IN ('pending', 'unverified')
            ORDER BY es.emi_no
        ");
        if ($unverifiedEmiStmt) {
            $unverifiedEmiStmt->bind_param("i", $bookingId);
            $unverifiedEmiStmt->execute();
            $unverifiedEmiResult = $unverifiedEmiStmt->get_result();

            $unverifiedEmiNumbers = [];
            $unverifiedEmiAmount = 0.0;
            if ($unverifiedEmiResult) {
                while ($row = $unverifiedEmiResult->fetch_assoc()) {
                    $unverifiedEmiNumbers[] = (int) $row['emi_no'];
                    $unverifiedEmiAmount += (float) $row['amount'];
                }
            }
            $stats['unverified_emi_count'] = count($unverifiedEmiNumbers);
            $stats['unverified_emi_amount'] = $unverifiedEmiAmount;
            $stats['unverified_emi_numbers'] = $unverifiedEmiNumbers;
        }

        // Pending = total plan - completed (simple subtraction, not sequence gap check)
        $stats['pending_emi'] = max(0, $totalEmi - $stats['completed_emi']);

        // Build pending numbers list as (completed+1) .. totalEmi
        $stats['pending_emi_numbers'] = [];
        if ($stats['pending_emi'] > 0) {
            $nextStart = $stats['completed_emi'] + 1;
            for ($i = $nextStart; $i <= $totalEmi; $i++) {
                $stats['pending_emi_numbers'][] = $i;
            }
        }

        return $stats;
    }

    /**
     * Get total follow up notes count for a booking (including General, Dev Charge, and EMIs)
     */
    public function getTotalFollowupCount($bookingId)
    {
        if (!$bookingId) return 0;
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM emi_followups WHERE booking_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            return (int)($row['cnt'] ?? 0);
        }
        return 0;
    }

    /**
     * Get Virtual EMI Schedule (Yield to Maturity Logic)
     * Calculates the maturity status of all EMIs based on the current date.
     */
    public function getVirtualEmiSchedule($bookingId)
    {
        $clientDetails = $this->getClientByBookingId($bookingId);
        if (!$clientDetails) return [];

        $totalEmi = $clientDetails['total_emi'] ?? 0;
        if ($totalEmi > 120) {
            $totalEmi = 42;
        }
        if ($totalEmi <= 0) return [];

        // 1. Fetch verified paid/partially paid EMIs via emi_schedules
        $paidEmiStmt = $this->db->prepare("
            SELECT 
                es.emi_no, 
                SUM(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status != 'rejected' THEN es.emi_amount ELSE 0 END) as total_paid,
                MIN(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status != 'rejected' THEN es.due_emi_amount ELSE NULL END) as min_due_emi,
                MAX(lp.payment_date) as last_payment_date,
                MIN(lp.payment_date) as first_payment_date,
                MAX(es.due_date) as schedule_due_date,
                SUBSTRING_INDEX(GROUP_CONCAT(lp.payment_status ORDER BY lp.id DESC), ',', 1) as latest_payment_status,
                COUNT(lp.id) as payment_count
            FROM emi_schedules es
            INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id
            WHERE es.lead_booking_id = ? 
              AND lp.is_trashed = 0 
              AND lp.payment_status IN ('paid','partially_paid')
              AND lp.verification_status != 'rejected'
            GROUP BY es.emi_no
            ORDER BY es.emi_no ASC
        ");
        $paidEmiStmt->bind_param("i", $bookingId);
        $paidEmiStmt->execute();
        $paidResult = $paidEmiStmt->get_result();
        
        $paidEmis = [];
        $lastPaidDueDate = null;
        if ($paidResult) {
            while ($row = $paidResult->fetch_assoc()) {
                $paidEmis[(int)$row['emi_no']] = $row;
                if (!empty($row['schedule_due_date'])) {
                    $lastPaidDueDate = $row['schedule_due_date'];
                }
            }
        }
        $paidEmiStmt->close();

        // 2. Fetch custom fixed dates set by CED
        $fixedDates = [];
        $fixedStmt = $this->db->prepare("
            SELECT cef.emi_no, cef.due_date, cef.notes, cef.fixed_by, cef.fixed_at,
                   u.full_name as fixed_by_name,
                   COALESCE(NULLIF(e.employee_id, ''), NULLIF(u.employee_id, '')) as fixed_by_code
            FROM client_emi_fixed_dates cef
            LEFT JOIN users u ON u.id = cef.fixed_by
            LEFT JOIN employees e ON e.user_id = u.id
            WHERE cef.booking_id = ?
        ");
        if ($fixedStmt) {
            $fixedStmt->bind_param("i", $bookingId);
            $fixedStmt->execute();
            $fRes = $fixedStmt->get_result();
            if ($fRes) {
                while ($frow = $fRes->fetch_assoc()) {
                    $fixedDates[(int)$frow['emi_no']] = $frow;
                }
            }
            $fixedStmt->close();
        }

        // 3. Followup counts
        $followupCounts = [];
        $followupStmt = $this->db->prepare("
            SELECT emi_no, COUNT(*) as cnt 
            FROM emi_followups 
            WHERE booking_id = ? 
            GROUP BY emi_no
        ");
        if ($followupStmt) {
            $followupStmt->bind_param("i", $bookingId);
            $followupStmt->execute();
            $followupRes = $followupStmt->get_result();
            if ($followupRes) {
                while ($f = $followupRes->fetch_assoc()) {
                    $followupCounts[(int)$f['emi_no']] = (int)$f['cnt'];
                }
            }
            $followupStmt->close();
        }

        $schedule = [];
        $today = new \DateTime();
        $todayStr = $today->format('Y-m-d');
        $currentMonth = (int)$today->format('Ym');
        
        // 4. Anchor date for the very first unanchored EMI
        $anchorDate = null;
        if (!empty($lastPaidDueDate)) {
            $anchorDate = new \DateTime($lastPaidDueDate);
        } else {
            $lastPaymentStmt = $this->db->prepare("
                SELECT MAX(payment_date) as last_payment_date 
                FROM lead_payments 
                WHERE booking_id = ? 
                AND payment_status IN ('paid','partially_paid') 
                AND verification_status = 'verified'
            ");
            $lastPaymentRow = null;
            if ($lastPaymentStmt) {
                $lastPaymentStmt->bind_param("i", $bookingId);
                $lastPaymentStmt->execute();
                $lastPaymentResult = $lastPaymentStmt->get_result();
                $lastPaymentRow = $lastPaymentResult ? $lastPaymentResult->fetch_assoc() : null;
                $lastPaymentStmt->close();
            }
            
            $anchorDateStr = !empty($lastPaymentRow['last_payment_date']) 
                                ? $lastPaymentRow['last_payment_date'] 
                                : (!empty($clientDetails['booking_date']) ? $clientDetails['booking_date'] : date('Y-m-d'));
            
            $anchorDate = new \DateTime($anchorDateStr);
            $anchorDate->modify('+1 month');
        }

        $runnerDate = clone $anchorDate;
        
        for ($i = 1; $i <= $totalEmi; $i++) {
            $hasPayment = isset($paidEmis[$i]);
            $hasFixedDate = isset($fixedDates[$i]);
            
            // Determine due date & CED fixed metadata
            $isDateCustom = false;
            $fixedByName = null;
            $fixedByCode = null;
            $fixedAt = null;
            $fixedNotes = null;
            $dueDateStr = null;
            $dueDateObj = null;
            
            if ($hasFixedDate) {
                $dueDateStr = $fixedDates[$i]['due_date'];
                $isDateCustom = true;
                $fixedByName = $fixedDates[$i]['fixed_by_name'] ?? 'CED Team';
                $fixedByCode = $fixedDates[$i]['fixed_by_code'] ?? null;
                $fixedAt = $fixedDates[$i]['fixed_at'] ?? null;
                $fixedNotes = $fixedDates[$i]['notes'] ?? null;
                $dueDateObj = new \DateTime($dueDateStr);
                $runnerDate = clone $dueDateObj;
            } elseif ($hasPayment && !empty($paidEmis[$i]['schedule_due_date'])) {
                $dueDateStr = $paidEmis[$i]['schedule_due_date'];
                $dueDateObj = new \DateTime($dueDateStr);
                $runnerDate = clone $dueDateObj;
            } else {
                // Unpaid & no custom fixed date by CED:
                // Only project forward if CED or payment has anchored at least one date in this booking
                if (!empty($fixedDates) || !empty($lastPaidDueDate)) {
                    if (count($paidEmis) > 0 || count($fixedDates) > 0 || $i > 1) {
                        $runnerDate->modify('+1 month');
                    }
                    $dueDateStr = $runnerDate->format('Y-m-d');
                    $dueDateObj = clone $runnerDate;
                } else {
                    // No date has ever been scheduled by CED or recorded via payment.
                    // Keep unassigned (Not Scheduled) so artificial past dates are never fabricated.
                    $dueDateStr = null;
                    $dueDateObj = null;
                }
            }

            $dueMonth = $dueDateObj ? (int)$dueDateObj->format('Ym') : null;
            $isOverdueDate = ($dueDateStr !== null && $dueDateStr < $todayStr);
            
            // Amounts & payment stats
            $scheduledAmt = (float)($clientDetails['per_emi_amount'] ?? 0);
            $paidAmt = 0.0;
            $dueAmt = $scheduledAmt;
            $lastPaymentDate = null;
            $paymentStatus = 'unpaid';
            $isFullyPaid = false;
            
            if ($hasPayment) {
                $payRec = $paidEmis[$i];
                $paidAmt = (float)($payRec['total_paid'] ?? 0);
                $minDue = $payRec['min_due_emi'];
                $dueAmt = ($minDue !== null) ? (float)$minDue : max(0, $scheduledAmt - $paidAmt);
                $lastPaymentDate = $payRec['last_payment_date'] ?? null;
                $paymentStatus = $payRec['latest_payment_status'] ?? ($dueAmt <= 0 ? 'paid' : 'partially_paid');
                
                if ($dueAmt <= 0.01 && $paidAmt > 0) {
                    $isFullyPaid = true;
                    $dueAmt = 0;
                    $paymentStatus = 'paid';
                }
                
                if ($paidAmt + $dueAmt > 0) {
                    $scheduledAmt = $paidAmt + $dueAmt;
                }
            }

            // Lock check (Best Practice #1): Locked if fully paid
            $isLocked = $isFullyPaid;
            
            // Maturity & delay calculation
            $delayDays = 0;
            $overdueDays = 0;
            $maturityStatus = '';
            
            if ($isFullyPaid) {
                if (!empty($lastPaymentDate) && !empty($dueDateStr)) {
                    if ($lastPaymentDate <= $dueDateStr) {
                        $maturityStatus = 'paid_on_time';
                        $delayDays = 0;
                    } else {
                        $maturityStatus = 'paid_late';
                        $diff = (new \DateTime($lastPaymentDate))->diff($dueDateObj);
                        $delayDays = (int)$diff->days;
                    }
                } else {
                    $maturityStatus = ($dueMonth && $dueMonth < $currentMonth) ? 'matured' : 'paid';
                }
            } elseif ($paidAmt > 0 && $dueAmt > 0) {
                // Partially Paid
                if ($isOverdueDate) {
                    $maturityStatus = 'due';
                    $diff = $today->diff($dueDateObj);
                    $overdueDays = (int)$diff->days;
                } elseif ($dueMonth && $dueMonth == $currentMonth) {
                    $maturityStatus = 'pending';
                } else {
                    $maturityStatus = 'future';
                }
            } else {
                // Unpaid (no payment)
                if ($hasFixedDate) {
                    // CED set an explicit due date for this unpaid EMI
                    if ($isOverdueDate) {
                        $maturityStatus = 'due';
                        $diff = $today->diff($dueDateObj);
                        $overdueDays = (int)$diff->days;
                    } elseif ($dueMonth && $dueMonth == $currentMonth) {
                        $maturityStatus = 'pending';
                    } else {
                        $maturityStatus = 'future';
                    }
                } else {
                    // Unpaid and NOT scheduled by CED yet:
                    // Cannot be overdue or penalized!
                    $maturityStatus = !empty($dueDateStr) ? 'future' : 'unscheduled';
                    $overdueDays = 0;
                }
            }

            $penaltyCalc = ($maturityStatus === 'due' && !empty($dueDateStr))
                ? $this->calculateCompoundedPenalty($scheduledAmt, $dueDateStr)
                : ['months_overdue' => 0, 'penalty_amount' => 0, 'total_payable' => $dueAmt];

            $schedule[] = [
                'emi_no'            => $i,
                'due_date'          => $dueDateStr,
                'amount'            => $scheduledAmt,
                'paid_amount'       => $paidAmt,
                'due_amount'        => $dueAmt,
                'status'            => $paymentStatus,
                'maturity_status'   => $maturityStatus,
                'is_virtual'        => !$hasPayment,
                'is_locked'         => $isLocked,
                'is_date_custom'    => $isDateCustom,
                'fixed_by_name'     => $fixedByName,
                'fixed_by_code'     => $fixedByCode,
                'fixed_at'          => $fixedAt,
                'notes'             => $fixedNotes,
                'last_payment_date' => $lastPaymentDate,
                'delay_days'        => $delayDays,
                'overdue_days'      => $overdueDays,
                'followup_count'    => $followupCounts[$i] ?? 0,
                'months_overdue'    => ($maturityStatus === 'due') ? $penaltyCalc['months_overdue'] : 0,
                'penalty_amount'    => ($maturityStatus === 'due') ? $penaltyCalc['penalty_amount'] : 0,
                'total_payable'     => ($maturityStatus === 'due') ? $penaltyCalc['total_payable'] : $dueAmt
            ];
        }
        
        return $schedule;
    }

    /**
     * Save custom EMI Due Date fixed by CED
     * Enforces Best Practice #1: Fully paid EMIs are locked and cannot be edited.
     */
    public function saveEmiDueDate($bookingId, $emiNo, $dueDate, $notes = '', $userId = 0, $applySubsequent = 0)
    {
        $bookingId = (int)$bookingId;
        $emiNo = (int)$emiNo;
        $dueDate = trim($dueDate);
        $userId = (int)$userId;
        
        if ($bookingId <= 0 || $emiNo <= 0 || empty($dueDate)) {
            return ['success' => false, 'message' => 'Invalid parameters provided.'];
        }

        $d = \DateTime::createFromFormat('Y-m-d', $dueDate);
        if (!$d || $d->format('Y-m-d') !== $dueDate) {
            return ['success' => false, 'message' => 'Invalid date format (must be YYYY-MM-DD).'];
        }

        // Lock check: check if this EMI is already fully paid
        $checkStmt = $this->db->prepare("
            SELECT es.due_emi_amount, es.status
            FROM emi_schedules es
            INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id
            WHERE es.lead_booking_id = ? AND es.emi_no = ?
              AND lp.is_trashed = 0
              AND lp.verification_status != 'rejected'
            ORDER BY es.id DESC LIMIT 1
        ");
        if ($checkStmt) {
            $checkStmt->bind_param("ii", $bookingId, $emiNo);
            $checkStmt->execute();
            $chkRes = $checkStmt->get_result();
            if ($chkRes && $chkRow = $chkRes->fetch_assoc()) {
                if ($chkRow['status'] === 'paid' && (float)$chkRow['due_emi_amount'] <= 0.01) {
                    $checkStmt->close();
                    return [
                        'success' => false, 
                        'message' => "EMI #{$emiNo} is already fully paid. The due date is locked and cannot be modified."
                    ];
                }
            }
            $checkStmt->close();
        }

        // Save to client_emi_fixed_dates
        $saveStmt = $this->db->prepare("
            INSERT INTO client_emi_fixed_dates (booking_id, emi_no, due_date, notes, fixed_by, fixed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                due_date = VALUES(due_date),
                notes = VALUES(notes),
                fixed_by = VALUES(fixed_by),
                fixed_at = NOW()
        ");
        if (!$saveStmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->db->error];
        }
        $saveStmt->bind_param("iissi", $bookingId, $emiNo, $dueDate, $notes, $userId);
        if (!$saveStmt->execute()) {
            $err = $saveStmt->error;
            $saveStmt->close();
            return ['success' => false, 'message' => 'Failed to save EMI due date: ' . $err];
        }
        $saveStmt->close();

        // If applySubsequent is enabled, set following unpaid EMIs consecutively (+1 month)
        if ($applySubsequent) {
            $client = $this->getClientByBookingId($bookingId);
            $totalEmi = (int)($client['total_emi'] ?? 0);
            if ($totalEmi > 120) $totalEmi = 42;
            
            $baseDate = new \DateTime($dueDate);
            for ($nextEmi = $emiNo + 1; $nextEmi <= $totalEmi; $nextEmi++) {
                // Check if nextEmi is already paid
                $pChk = $this->db->prepare("
                    SELECT 1 FROM emi_schedules es
                    INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id
                    WHERE es.lead_booking_id = ? AND es.emi_no = ?
                      AND lp.is_trashed = 0 AND lp.verification_status != 'rejected'
                      AND es.status = 'paid' AND es.due_emi_amount <= 0.01
                    LIMIT 1
                ");
                $isPaid = false;
                if ($pChk) {
                    $pChk->bind_param("ii", $bookingId, $nextEmi);
                    $pChk->execute();
                    $isPaid = (bool)$pChk->get_result()->fetch_row();
                    $pChk->close();
                }
                if ($isPaid) continue;

                $nextDate = clone $baseDate;
                $nextDate->modify('+' . ($nextEmi - $emiNo) . ' month');
                $nextDateStr = $nextDate->format('Y-m-d');
                $subNote = 'Auto-adjusted based on EMI #' . $emiNo;

                $subStmt = $this->db->prepare("
                    INSERT INTO client_emi_fixed_dates (booking_id, emi_no, due_date, notes, fixed_by, fixed_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        due_date = VALUES(due_date),
                        notes = VALUES(notes),
                        fixed_by = VALUES(fixed_by),
                        fixed_at = NOW()
                ");
                if ($subStmt) {
                    $subStmt->bind_param("iissi", $bookingId, $nextEmi, $nextDateStr, $subNote, $userId);
                    $subStmt->execute();
                    $subStmt->close();
                }
            }
        }

        return ['success' => true, 'message' => "EMI #{$emiNo} Due Date saved successfully!"];
    }

    /**
     * Calculate 5% Compounded Monthly Late Penalty
     * Formula: Payable = Base * (1 + 0.05)^m
     */
    public function calculateCompoundedPenalty($baseAmount, $dueDateStr, $rate = 0.05)
    {
        $baseAmount = (float)$baseAmount;
        if (empty($dueDateStr) || $baseAmount <= 0) {
            return ['months_overdue' => 0, 'penalty_amount' => 0, 'total_payable' => $baseAmount];
        }

        try {
            $dueDate = new \DateTime($dueDateStr);
            $currentDate = new \DateTime();

            if ($currentDate <= $dueDate) {
                return ['months_overdue' => 0, 'penalty_amount' => 0, 'total_payable' => $baseAmount];
            }

            // Calculate full or partial overdue months
            $diff = $dueDate->diff($currentDate);
            $monthsOverdue = ($diff->y * 12) + $diff->m;
            if ($diff->d > 0) {
                $monthsOverdue += 1;
            }

            if ($monthsOverdue <= 0) {
                return ['months_overdue' => 0, 'penalty_amount' => 0, 'total_payable' => $baseAmount];
            }

            $compoundedFactor = pow((1 + $rate), $monthsOverdue);
            $totalPayable = round($baseAmount * $compoundedFactor, 2);
            $penaltyAmount = round($totalPayable - $baseAmount, 2);

            return [
                'months_overdue' => $monthsOverdue,
                'penalty_amount' => $penaltyAmount,
                'total_payable'  => $totalPayable
            ];
        } catch (\Exception $e) {
            return ['months_overdue' => 0, 'penalty_amount' => 0, 'total_payable' => $baseAmount];
        }
    }

    /**
     * Get detailed late penalty history for a booking
     * Calculates delay days, target installment, due dates, paid penalty, waived amount & approval status
     */
    public function getPenaltyHistoryByBookingId($bookingId)
    {
        $bookingId = (int)$bookingId;
        if ($bookingId <= 0) {
            return [];
        }

        $hasTrashed = $this->hasColumn('lead_payments', 'is_trashed');
        $lpTrash = $hasTrashed ? "lp.is_trashed = 0 AND " : "";

        $sql = "
            SELECT 
                lp.id,
                lp.booking_id,
                lp.batch_id,
                lp.payment_date,
                lp.amount,
                lp.payment_type,
                lp.settlement_type,
                lp.parent_payment_id,
                lp.payment_method,
                lp.transaction_ref,
                lp.bank_name,
                lp.penalty_amount,
                lp.penalty_paid,
                lp.penalty_waived,
                lp.waiver_reason,
                lp.waiver_status,
                lp.waiver_attachment,
                lp.attachment_paths,
                lp.verification_status,
                u.full_name as created_by_name,
                uv.full_name as verified_by_name,
                COALESCE(es_curr.emi_no, es_parent.emi_no, lp.emi_no) as emi_no,
                es_curr.due_date as curr_due_date,
                es_parent.due_date as parent_due_date,
                cef.due_date as fixed_due_date,
                batch_lp.parent_payment_id as batch_parent_id,
                batch_parent_es.due_date as batch_parent_due_date,
                batch_parent_es.emi_no as batch_parent_emi_no,
                COALESCE(es_curr.emi_amount, es_parent.emi_amount, lp.amount) as base_emi_amount
            FROM lead_payments lp
            LEFT JOIN emi_schedules es_curr ON es_curr.lead_payment_id = lp.id
            LEFT JOIN emi_schedules es_parent ON (lp.parent_payment_id IS NOT NULL AND es_parent.lead_payment_id = lp.parent_payment_id)
            LEFT JOIN client_emi_fixed_dates cef ON (cef.booking_id = lp.booking_id AND cef.emi_no = COALESCE(es_curr.emi_no, es_parent.emi_no))
            LEFT JOIN lead_payments batch_lp ON (lp.batch_id IS NOT NULL AND lp.batch_id != '' AND batch_lp.batch_id = lp.batch_id AND batch_lp.parent_payment_id IS NOT NULL AND batch_lp.id != lp.id)
            LEFT JOIN emi_schedules batch_parent_es ON (batch_lp.parent_payment_id IS NOT NULL AND batch_parent_es.lead_payment_id = batch_lp.parent_payment_id)
            LEFT JOIN users u ON u.id = lp.created_by
            LEFT JOIN users uv ON uv.id = lp.verified_by
            WHERE {$lpTrash}lp.booking_id = ?
              AND (lp.penalty_amount > 0 OR lp.penalty_paid > 0 OR lp.penalty_waived > 0)
            ORDER BY lp.payment_date ASC, lp.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $res = $stmt->get_result();
        $history = [];

        while ($r = $res->fetch_assoc()) {
            $targetEmi = $r['batch_parent_emi_no'] ?: $r['emi_no'];
            $dueDate = null;
            if (!empty($r['batch_parent_due_date']) && $r['batch_parent_due_date'] < $r['payment_date']) {
                $dueDate = $r['batch_parent_due_date'];
            } elseif (!empty($r['parent_due_date']) && $r['parent_due_date'] < $r['payment_date']) {
                $dueDate = $r['parent_due_date'];
            } elseif (!empty($r['fixed_due_date']) && $r['fixed_due_date'] < $r['payment_date']) {
                $dueDate = $r['fixed_due_date'];
            } elseif (!empty($r['curr_due_date']) && $r['curr_due_date'] < $r['payment_date']) {
                $dueDate = $r['curr_due_date'];
            }

            $daysLate = 0;
            $monthsOverdue = 0;
            if ($dueDate && $r['payment_date'] > $dueDate) {
                try {
                    $d1 = new \DateTime($dueDate);
                    $d2 = new \DateTime($r['payment_date']);
                    $diff = $d1->diff($d2);
                    $daysLate = (int)$diff->days;
                    $monthsOverdue = ($diff->y * 12) + $diff->m + ($diff->d > 0 ? 1 : 0);
                } catch (\Exception $e) {
                    $daysLate = 0;
                    $monthsOverdue = 0;
                }
            } else {
                // If explicit schedule due date wasn't stored, estimate from 5% compounded monthly rate
                $base = (float)$r['base_emi_amount'];
                $pen = (float)$r['penalty_amount'];
                if ($base > 0 && $pen > 0) {
                    $ratio = 1 + ($pen / $base);
                    $monthsOverdue = max(1, (int)round(log($ratio) / log(1.05)));
                    $daysLate = $monthsOverdue * 30;
                    if (empty($dueDate) && !empty($r['payment_date'])) {
                        try {
                            $calcDue = new \DateTime($r['payment_date']);
                            $calcDue->modify("-{$monthsOverdue} months");
                            $dueDate = $calcDue->format('Y-m-d');
                        } catch (\Exception $e) {}
                    }
                }
            }

            // Installment label
            $installmentLabel = 'EMI Installment';
            if ($targetEmi) {
                $suffix = 'th';
                if (!in_array($targetEmi % 100, [11, 12, 13])) {
                    if ($targetEmi % 10 == 1) $suffix = 'st';
                    elseif ($targetEmi % 10 == 2) $suffix = 'nd';
                    elseif ($targetEmi % 10 == 3) $suffix = 'rd';
                }
                $installmentLabel = "{$targetEmi}{$suffix} Installment";
            }
            if ($r['settlement_type'] === 'emi_due_settlement') {
                $installmentLabel .= ' (Settlement)';
            }

            $r['target_emi_no']     = $targetEmi;
            $r['installment_label'] = $installmentLabel;
            $r['scheduled_due_date']= $dueDate;
            $r['days_late']         = $daysLate;
            $r['months_overdue']    = $monthsOverdue;
            $r['penalty_amount']    = (float)$r['penalty_amount'];
            $r['penalty_paid']      = (float)$r['penalty_paid'];
            $r['penalty_waived']    = (float)$r['penalty_waived'];
            $r['amount']            = (float)$r['amount'];
            $r['base_emi_amount']   = (float)$r['base_emi_amount'];

            $history[] = $r;
        }
        $stmt->close();

        return $history;
    }

    /**
     * Get client by lead ID
     */
    public function getClientByLeadId($leadId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.id as lead_id,
                COALESCE(NULLIF(TRIM(l.full_name), ''), NULLIF(TRIM(l.lead_name), '')) as client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), NULLIF(TRIM(l.alt_phone_number), ''), NULLIF(TRIM(l.`2nd_owner_phone`), '')) AS phone,
                l.phone_number,
                l.email,
                l.designation,
                u.full_name as agent_name
            FROM leads l
            LEFT JOIN users u ON l.assigned_id = u.id
            WHERE l.id = ?
        ");

        if (!$stmt) {
            error_log("Prepare statement failed: " . $this->db->error);
            return null;
        }

        $stmt->bind_param("i", $leadId);

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return null;
        }

        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get payment summary by booking ID
     */
    public function getPaymentSummaryByBookingId($bookingId)
    {
        $clientDetails = $this->getClientByBookingId($bookingId);

        if (!$clientDetails) {
            return [
                'total_price'                          => 0,
                'agreement_value'                      => 0,
                'total_land_share'                     => 0,
                'flat_development_charges'             => 0,
                'service_charge_per_emi'               => 0,
                'booking_money'                        => 0,
                'booking_money_paid'                   => 0,
                'is_booking_fully_paid'                => false,
                'additional_land_share_paid'           => 0,
                'total_land_share_paid'                => 0,
                'landshare_paid'                       => 0,
                'remaining_land_share'                 => 0,
                'discount_amount'                      => 0,
                'registration_paid'                    => 0,
                'registration_money'                   => 0,
                'is_registration_paid'                 => false,
                'is_land_share_completed'              => false,
                'is_dev_charge_completed'              => false,
                'development_charge_paid'              => 0,
                'construction_fee'                     => 0,
                'per_emi_amount'                       => 0,
                'construction_fee_paid'                => 0,
                'construction_fee_paid_after_service'  => 0,
                'remaining_construction_fee'           => 0,
                'total_paid'                           => 0,
                'total_due'                            => 0,
                'total_penalty_paid'                   => 0,
                'total_penalty_amount'                 => 0,
                'total_penalty_waived'                 => 0,
                'total_emi_due'                        => 0,
                'total_emi'                            => 0,
                'completed_emi'                        => 0,
                'unverified_emi_count'                 => 0,
                'unverified_emi_amount'                => 0,
                'pending_emi'                          => 0,
                'last_payment_date'                    => null,
                'total_service_fee'                    => 0,
                'net_received'                         => 0,
                'paid_emi_numbers'                     => [],
                'pending_emi_numbers'                  => []
            ];
        }

        // agreement_value is the contractual booking target from lead_bookings
        $agreementValue       = (float) ($clientDetails['agreement_value']      ?? 0);
        $discountForBooking   = (float) ($clientDetails['discount_amount']       ?? 0);
        // booking_money_total: non-rejected entries (pending + verified) — used to compute remaining balance shown in form
        $bookingMoneyTotal   = (float) ($clientDetails['booking_money_total']  ?? 0);
        // booking_money_paid: VERIFIED-ONLY entries — the real "confirmed received" amount
        $bookingMoneyPaid    = (float) ($clientDetails['booking_money_paid']   ?? 0);
        // Effective booking target: discount reduces how much the client must physically pay
        $effectiveBookingTarget = max(0, $agreementValue - $discountForBooking);
        // Booking is fully paid when verified-only payments cover the effective target (after discount)
        // We add a 1.0 TK tolerance to avoid 0.01 float issues locking the system
        $isBookingFullyPaid  = ($effectiveBookingTarget > 0 && $bookingMoneyPaid >= ($effectiveBookingTarget - 1.0));
        // If total land share is already completed (or remaining land share <= 0), booking money is also fully settled
        if (!empty($clientDetails['is_land_share_completed']) || ((float)($clientDetails['remaining_land_share_total'] ?? $clientDetails['remaining_land_share'] ?? 0) <= 0 && (float)($clientDetails['total_land_share_paid'] ?? 0) > 0)) {
            $isBookingFullyPaid = true;
        }
        // Also consider fully paid if no agreement set at all
        if ($effectiveBookingTarget <= 0 && $agreementValue <= 0) {
            $isBookingFullyPaid = false; // no agreement set, assume not paid
        }

        return [
            'total_price'                          => $clientDetails['total_price'],
            'agreement_value'                      => $agreementValue,
            'total_land_share'                     => $clientDetails['total_land_share'],
            'flat_development_charges'             => $clientDetails['flat_development_charges'] ?? 0,
            'service_charge_per_emi'               => $clientDetails['service_charge_per_emi'] ?? 0,
            'booking_money'                        => $clientDetails['booking_money'],       // verified only (stat cards)
            'booking_money_total'                  => $bookingMoneyTotal,                    // all entries (dropdown remaining)
            'booking_money_paid'                   => $bookingMoneyPaid,                     // verified only
            'effective_booking_target'             => $effectiveBookingTarget,               // agreement - discount
            'is_booking_fully_paid'                => $isBookingFullyPaid,
            'additional_land_share_paid'           => $clientDetails['additional_land_share_paid'],
            'additional_land_share_paid_total'     => $clientDetails['additional_land_share_paid_total'] ?? $clientDetails['additional_land_share_paid'],
            'total_land_share_paid'                => $clientDetails['total_land_share_paid'],  // booking+landshare+registration (verified)
            'total_land_share_paid_total'          => $clientDetails['total_land_share_paid_total'] ?? $clientDetails['total_land_share_paid'], // unrejected total
            'landshare_paid'                       => $clientDetails['landshare_paid'],          // alias
            'remaining_land_share'                 => $clientDetails['remaining_land_share'],
            'remaining_land_share_total'           => $clientDetails['remaining_land_share_total'] ?? $clientDetails['remaining_land_share'],
            'discount_amount'                      => $clientDetails['discount_amount'],
            'registration_paid'                    => $clientDetails['registration_paid'],
            'registration_paid_total'              => $clientDetails['registration_paid_total'] ?? $clientDetails['registration_paid'],
            'registration_money'                   => $clientDetails['registration_paid'],
            'is_registration_paid'                 => $clientDetails['is_registration_paid'] ?? false,
            'mutation_cost_paid'                   => $clientDetails['mutation_cost_paid'] ?? 0,
            'mutation_cost_paid_total'             => $clientDetails['mutation_cost_paid_total'] ?? 0,
            'is_land_share_completed'              => $clientDetails['is_land_share_completed'],
            'is_dev_charge_completed'              => $clientDetails['is_dev_charge_completed'] ?? false,
            'development_charge_paid'              => $clientDetails['development_charge_paid'] ?? 0,
            'development_charge_paid_total'        => $clientDetails['development_charge_paid_total'] ?? 0,
            'construction_fee'                     => $clientDetails['construction_fee'],
            'per_emi_amount'                       => $clientDetails['per_emi_amount'] ?? 0,
            'construction_fee_paid'                => $clientDetails['construction_fee_paid'],
            'emi_paid_total'                       => $clientDetails['emi_paid_total'] ?? 0,
            'construction_fee_paid_after_service'  => $clientDetails['construction_fee_paid_after_service'],
            'remaining_construction_fee'           => $clientDetails['remaining_construction_fee'],
            'total_paid'                           => $clientDetails['total_paid'],
            'others_paid'                          => $clientDetails['others_paid'] ?? 0,
            'others_paid_total'                    => $clientDetails['others_paid_total'] ?? 0,
            'total_due'                            => $clientDetails['total_due'],
            'total_penalty_paid'                   => $clientDetails['total_penalty_paid'] ?? 0,
            'total_penalty_amount'                 => $clientDetails['total_penalty_amount'] ?? 0,
            'total_penalty_waived'                 => $clientDetails['total_penalty_waived'] ?? 0,
            'total_emi_due'                        => $clientDetails['total_emi_due'] ?? 0,
            'total_bulk_emi_discount'              => $clientDetails['total_bulk_emi_discount'] ?? 0,
            'total_emi'                            => $clientDetails['total_emi'],
            'completed_emi'                        => $clientDetails['completed_emi'],
            'unverified_emi_count'                 => $clientDetails['unverified_emi_count'] ?? 0,
            'unverified_emi_amount'                => $clientDetails['unverified_emi_amount'] ?? 0,
            'pending_emi'                          => $clientDetails['pending_emi'],
            'emi_updated_by'                       => $clientDetails['emi_updated_by'] ?? null,
            'emi_updated_at'                       => $clientDetails['emi_updated_at'] ?? null,
            'emi_updated_by_name'                  => $clientDetails['emi_updated_by_name'] ?? null,
            'last_payment_date'                    => $clientDetails['last_payment_date'],
            'total_service_fee'                    => $clientDetails['total_service_fee'],
            'net_received'                         => $clientDetails['net_received'],
            'paid_emi_numbers'                     => $clientDetails['paid_emi_numbers'],
            'pending_emi_numbers'                  => $clientDetails['pending_emi_numbers'],
            'next_emi_no'                          => $clientDetails['next_emi_no'] ?? $this->getNextAvailableEmiNo($bookingId)
        ];
    }

    /**
     * Add new transaction with proper validations
     */
    public function addTransaction($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            // Validate required fields
            if (
                empty($transactionData['booking_id']) || empty($transactionData['payment_type']) ||
                empty($transactionData['amount']) || empty($transactionData['payment_status'])
            ) {
                throw new Exception("Missing required transaction data");
            }

            $bookingId = (int) $transactionData['booking_id'];
            $paymentType = $transactionData['payment_type'];
            $amount = (float) $transactionData['amount'];
            $landShareAmount = 0;
            $serviceFee = 0;

            // Get current payment summary for validations
            $summary = $this->getPaymentSummaryByBookingId($bookingId);

            // Validate based on payment type
            switch ($paymentType) {
                case 'booking_money':
                    if ($summary['is_booking_fully_paid']) {
                        throw new Exception("Booking money has already been fully paid for this booking.");
                    }
                    $effectiveBookingTarget = (float) ($summary['effective_booking_target'] ?? max(0, (float)($summary['agreement_value'] ?? 0) - (float)($summary['discount_amount'] ?? 0)));
                    $remainingBooking = max(0, $effectiveBookingTarget - (float)($summary['booking_money_paid'] ?? 0));
                    if ($amount > $remainingBooking) {
                        throw new Exception("Amount exceeds outstanding booking balance: Tk. " . number_format($remainingBooking, 2));
                    }
                    $landShareAmount = $amount;
                    break;

                case 'land_share':
                    $remLSTarget = (float)($summary['remaining_land_share_total'] ?? $summary['remaining_land_share']);
                    if ($remLSTarget <= 0) {
                        throw new Exception("Land share payment is already completed!");
                    }
                    if ($amount > $remLSTarget) {
                        throw new Exception("Land share amount cannot exceed remaining land share: Tk. " . number_format($remLSTarget, 2));
                    }
                    $landShareAmount = $amount;
                    break;

                case 'registration':
                    $landShareAmount = $amount;
                    break;

                case 'mutation_cost':
                    $landShareAmount = $amount;
                    break;

                case 'development_charge':
                    $devTarget = (float) ($summary['flat_development_charges'] ?? 0);
                    $devPaid   = (float) ($summary['development_charge_paid_total'] ?? 0);
                    $devRemaining = max(0, $devTarget - $devPaid);
                    if ($devRemaining <= 0) {
                        throw new Exception("Development charge is already fully paid!");
                    }
                    if ($amount > $devRemaining) {
                        throw new Exception("Amount exceeds remaining development charge: Tk. " . number_format($devRemaining, 2));
                    }
                    break;

                case 'emi':
                    // Service fee = amount_paid × (service_charge_per_emi% / 100)
                    $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
                    $serviceFee        = ($serviceChargeRate > 0) ? round($amount * ($serviceChargeRate / 100)) : 0;

                    // Compute due: shortfall from the suggested per-EMI amount
                    // BUT if this payment covers the entire remaining CF, there is NO real due
                    $perEmiSuggested   = (float) ($summary['per_emi_amount'] ?? 0);
                    $emiPaidSoFar      = (float) ($summary['emi_paid_total'] ?? 0);
                    $cfTotal           = (float) ($summary['construction_fee'] ?? 0);
                    $bulkDisc          = (float) ($summary['total_bulk_emi_discount'] ?? 0);
                    $effectiveCFRemaining = max(0, $cfTotal - $emiPaidSoFar - $bulkDisc);

                    if ($effectiveCFRemaining <= 0) {
                        throw new Exception("Construction fee is already fully paid! No more EMIs required.");
                    }
                    if ($amount > $effectiveCFRemaining) {
                        throw new Exception("Amount exceeds remaining construction fee: Tk. " . number_format($effectiveCFRemaining, 2));
                    }

                    $remainingAfterThis   = max(0, $effectiveCFRemaining - $amount);

                    if ($remainingAfterThis <= 0) {
                        // This payment completes or exceeds the construction fee — NO due
                        $dueEmiAmount = 0;
                    } else {
                        // The "expected" amount for this installment is capped at actual remaining
                        $expectedForThisEmi = ($perEmiSuggested > 0) ? min($perEmiSuggested, $effectiveCFRemaining) : 0;
                        $dueEmiAmount = ($expectedForThisEmi > 0 && $amount < $expectedForThisEmi)
                                        ? round($expectedForThisEmi - $amount)
                                        : 0;
                    }

                    // Override payment_status if partial
                    if ($dueEmiAmount > 0) {
                        $transactionData['payment_status'] = 'partially_paid';
                    }
                    if (($emiPaidSoFar + $amount) > $effectiveCFRemaining + $emiPaidSoFar) {
                        throw new Exception("EMI payment cannot exceed total construction fee: Tk. " . number_format($cfTotal, 2));
                    }
                    break;

                case 'emi_due_settlement':
                    // Validate parent EMI payment
                    if (empty($transactionData['parent_payment_id'])) {
                        throw new Exception("Parent EMI payment ID is required for EMI due settlement.");
                    }
                    $parentId  = (int) $transactionData['parent_payment_id'];
                    $parentRow = $this->db->query("
                        SELECT id, booking_id, due_emi_amount FROM lead_payments
                        WHERE id = {$parentId} AND payment_type = 'emi' AND booking_id = {$bookingId}
                    ");
                    if (!$parentRow || !($parentData = $parentRow->fetch_assoc())) {
                        throw new Exception("Invalid parent EMI payment reference.");
                    }
                    $remainingDue = (float) $parentData['due_emi_amount'];
                    if ($remainingDue <= 0) {
                        throw new Exception("This EMI has no outstanding due balance.");
                    }
                    if ($amount > $remainingDue) {
                        throw new Exception("Settle amount (Tk. " . number_format($amount, 2) . ") cannot exceed outstanding due (Tk. " . number_format($remainingDue, 2) . ").");
                    }
                    // Service fee applies on the settled amount at same rate
                    $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
                    $serviceFee        = ($serviceChargeRate > 0) ? round($amount * ($serviceChargeRate / 100)) : 0;
                    $dueEmiAmount      = 0; // settlement itself has no new due
                    // Change $paymentType to 'emi' to satisfy ENUM constraint, settlement_type will track the difference
                    $paymentType = 'emi';
                    break;

                case 'others':
                    $landShareAmount = 0;
                    $serviceFee = 0;
                    break;

                default:
                    break;
            }

            $otherPurpose      = !empty($transactionData['other_purpose']) ? trim($transactionData['other_purpose']) : null;
            $bankId            = !empty($transactionData['bank_id']) ? (int)$transactionData['bank_id'] : null;
            $attachmentPaths   = !empty($transactionData['attachment_paths']) ? json_encode($transactionData['attachment_paths']) : null;
            $parentPaymentId   = !empty($transactionData['parent_payment_id']) ? (int)$transactionData['parent_payment_id'] : null;
            $settlementType    = !empty($transactionData['settlement_type']) ? $transactionData['settlement_type'] : null;

            $stmt = $this->db->prepare("
                INSERT INTO lead_payments (
                    booking_id, payment_type, other_purpose, amount, land_share_amount, service_fee,
                    payment_method, payment_status, payment_date, transaction_ref,
                    bank_id, bank_name, bank_account_no, cheque_no, online_type,
                    attachment_paths, due_emi_amount, parent_payment_id, settlement_type, created_by,
                    handover_to, handover_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            $bankNameLookup = '';
            if ($bankId) {
                $bRow = $this->db->query("SELECT CONCAT(bank_name, ' - ', account_name) as label FROM company_bank_accounts WHERE id = {$bankId}");
                if ($bRow) { $bData = $bRow->fetch_assoc(); $bankNameLookup = $bData['label'] ?? ''; }
            }

            // Extract values — bind_param requires pass-by-reference variables
            $p_method    = $transactionData['payment_method'] ?? 'cash';
            $p_status    = $transactionData['payment_status'];
            $p_date      = $transactionData['payment_date'] ?? date('Y-m-d');
            $p_txRef     = $transactionData['transaction_ref'] ?? '';
            $p_bankAccNo = $transactionData['bank_account_no'] ?? '';
            $p_chequeNo  = $transactionData['cheque_no'] ?? '';
            $p_onlineType= $transactionData['online_type'] ?? '';
            $p_createdBy = (int) ($transactionData['created_by'] ?? 0);

            $handoverToId = !empty($transactionData['handover_to']) ? (int) $transactionData['handover_to'] : null;
            $handoverStatus = $handoverToId ? 'pending' : null;

            $stmt->bind_param(
                "issdddssssisssssdisiis",
                $bookingId,
                $paymentType,
                $otherPurpose,
                $amount,
                $landShareAmount,
                $serviceFee,
                $p_method,
                $p_status,
                $p_date,
                $p_txRef,
                $bankId,
                $bankNameLookup,
                $p_bankAccNo,
                $p_chequeNo,
                $p_onlineType,
                $attachmentPaths,
                $dueEmiAmount,
                $parentPaymentId,
                $settlementType,
                $p_createdBy,
                $handoverToId,
                $handoverStatus
            );

            if (!$stmt->execute()) {
                throw new Exception("Transaction insert failed: " . $stmt->error);
            }

            $paymentId = $this->db->insert_id;

            // If this is an EMI due settlement, reduce the due on the original payment
            if (!empty($transactionData['settlement_type']) && $transactionData['settlement_type'] === 'emi_due_settlement' && $parentPaymentId) {
                $newDue = max(0, (float)$parentData['due_emi_amount'] - $amount);
                $newStatus = ($newDue <= 0) ? 'paid' : 'partially_paid';
                $updStmt = $this->db->prepare("
                    UPDATE lead_payments SET due_emi_amount = ?, payment_status = ? WHERE id = ?
                ");
                $updStmt->bind_param('dsi', $newDue, $newStatus, $parentPaymentId);
                $updStmt->execute();

                // Also sync emi_schedules
                $schedSyncStmt = $this->db->prepare("UPDATE emi_schedules SET due_emi_amount = ?, status = ? WHERE lead_payment_id = ?");
                $schedSyncStmt->bind_param('dsi', $newDue, $newStatus, $parentPaymentId);
                $schedSyncStmt->execute();
                $schedSyncStmt->close();
            }

            if ($paymentType === 'emi' && $emiScheduleData) {
                // Ensure the backend-calculated due amount is passed to the schedule!
                $emiScheduleData['due_emi_amount'] = $dueEmiAmount;
                $this->createEMISchedule($bookingId, $paymentId, $amount, $serviceFee, $emiScheduleData);
            }

            if (!empty($handoverToId) && $handoverStatus === 'pending') {
                if (!class_exists('ApprovalRequest')) {
                    require_once __DIR__ . '/ApprovalRequest.php';
                }
                $approvalModel = new ApprovalRequest($this->db);
                $approvalModel->create([
                    'department' => 'Accounts',
                    'request_type' => 'Cash In Handover',
                    'title' => 'Handover Approval Request for Payment #' . $paymentId,
                    'source_table' => 'lead_payments',
                    'source_id' => $paymentId,
                    'requested_days' => 0,
                    'requested_by' => $transactionData['created_by'] ?? ($_SESSION['user']['id'] ?? 1),
                    'approve_role' => 'management',
                    'meta_data' => json_encode([
                        'Project' => $clientDetails['project_name'] ?? 'N/A',
                        'Unit' => $clientDetails['unit_name'] ?? 'N/A',
                        'Amount' => $amount,
                        'Payment Type' => ucfirst(str_replace('_', ' ', $paymentType))
                    ]),
                    'request_amount' => $amount
                ]);
            }

            $this->db->commit();

            return [
                'success'           => true,
                'message'           => 'Transaction added successfully!',
                'payment_id'        => $paymentId,
                'service_fee'       => $serviceFee,
                'land_share_amount' => $landShareAmount,
                'due_emi_amount'    => $dueEmiAmount
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get the next available EMI number for a booking.
     * Finds the first gap in the non-rejected sequence (e.g., if #5 was rejected, returns 5).
     * If no gaps, returns COUNT(non-rejected) + 1.
     */
    public function getNextAvailableEmiNo($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT es.emi_no 
            FROM emi_schedules es
            LEFT JOIN lead_payments lp ON lp.id = es.lead_payment_id
            WHERE es.lead_booking_id = ?
            AND (
                (lp.id IS NOT NULL AND lp.verification_status != 'rejected' AND lp.is_trashed = 0)
                OR (es.lead_payment_id IS NULL AND es.status = 'paid')
            )
            ORDER BY es.emi_no ASC
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();

        $usedNos = [];
        while ($row = $result->fetch_assoc()) {
            $usedNos[] = (int) $row['emi_no'];
        }

        // Find first gap: check 1, 2, 3... until we find one not in use
        for ($i = 1; $i <= count($usedNos) + 1; $i++) {
            if (!in_array($i, $usedNos)) {
                return $i;
            }
        }

        return count($usedNos) + 1;
    }

    /**
     * Ensure all valid EMI payments (including EMI discounts) have an emi_schedules record.
     * Auto-syncs any legacy/missing EMI payments to maintain sequential installment numbers.
     */
    public function syncMissingEmiSchedules($bookingId)
    {
        $bookingId = (int)$bookingId;
        if ($bookingId <= 0) return;

        // Purge any orphaned emi_schedules whose linked payment was deleted or trashed
        $cleanup = $this->db->prepare("
            DELETE es FROM emi_schedules es
            LEFT JOIN lead_payments lp ON lp.id = es.lead_payment_id
            WHERE es.lead_booking_id = ?
              AND es.lead_payment_id IS NOT NULL
              AND (lp.id IS NULL OR lp.is_trashed = 1)
        ");
        if ($cleanup) {
            $cleanup->bind_param("i", $bookingId);
            $cleanup->execute();
            $cleanup->close();
        }

        $stmt = $this->db->prepare("
            SELECT lp.id, lp.amount, lp.due_emi_amount, lp.service_fee, lp.payment_date, lp.payment_status, lp.emi_no
            FROM lead_payments lp
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            WHERE lp.booking_id = ?
              AND lp.is_trashed = 0
              AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
              AND lp.payment_type = 'emi'
              AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
              AND es.id IS NULL
            ORDER BY lp.payment_date ASC, lp.id ASC
        ");
        if (!$stmt) return;
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $res = $stmt->get_result();
        $missing = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        if (empty($missing)) return;

        foreach ($missing as $row) {
            $nextEmiNo = !empty($row['emi_no']) ? (int)$row['emi_no'] : $this->getNextAvailableEmiNo($bookingId);
            $due = (float)($row['due_emi_amount'] ?? 0);
            $amount = (float)($row['amount'] ?? 0);
            $svcFee = (float)($row['service_fee'] ?? 0);
            $payDate = $row['payment_date'] ?? date('Y-m-d');
            $status = 'paid';

            $ins = $this->db->prepare("
                INSERT INTO emi_schedules (
                    lead_booking_id, emi_no, emi_amount, service_fee,
                    due_emi_amount, due_date, status, lead_payment_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($ins) {
                $ins->bind_param(
                    "iidddssi",
                    $bookingId, $nextEmiNo, $amount, $svcFee,
                    $due, $payDate, $status, $row['id']
                );
                $ins->execute();
                $ins->close();
            }
        }
    }

    /**
     * Delete a transaction and its associated EMI schedule row.
     * Renumbers remaining EMI schedules to eliminate gaps.
     */
    public function deleteTransactionWithCleanup($paymentId, $bookingId)
    {
        $this->db->begin_transaction();

        try {
            $paymentId = (int) $paymentId;
            $bookingId = (int) $bookingId;

            // Verify the payment exists and belongs to this booking
            $chk = $this->db->prepare("SELECT id, payment_type, settlement_type, parent_payment_id, amount, verification_status FROM lead_payments WHERE id = ? AND booking_id = ?");
            $chk->bind_param("ii", $paymentId, $bookingId);
            $chk->execute();
            $row = $chk->get_result()->fetch_assoc();

            if (!$row) {
                throw new Exception("Transaction not found or does not belong to this booking.");
            }

            $paymentType = $row['payment_type'];
            $settlementType = $row['settlement_type'] ?? null;
            $isSettlement = ($paymentType === 'emi_due_settlement' || $settlementType === 'emi_due_settlement' || !empty($row['parent_payment_id']));

            // 0. If deleting a settlement, restore the parent EMI's outstanding due
            // Only restore if the settlement was NOT rejected (because rejection already restores it)
            if ($isSettlement && !empty($row['parent_payment_id']) && $row['verification_status'] !== 'rejected') {
                $parentId = (int) $row['parent_payment_id'];
                $settlementAmount = (float) $row['amount'];

                // Restore due_emi_amount on parent and set status back to partially_paid
                $restoreStmt = $this->db->prepare("
                    UPDATE lead_payments 
                    SET due_emi_amount = due_emi_amount + ?, 
                        payment_status = 'partially_paid'
                    WHERE id = ? AND booking_id = ?
                ");
                $restoreStmt->bind_param("dii", $settlementAmount, $parentId, $bookingId);
                if (!$restoreStmt->execute()) {
                    throw new Exception("Failed to restore parent EMI due: " . $restoreStmt->error);
                }
            }

            // 1. Delete associated emi_schedule row (if EMI and not a settlement)
            if ($paymentType === 'emi' && !$isSettlement) {
                $delSched = $this->db->prepare("DELETE FROM emi_schedules WHERE lead_payment_id = ? AND lead_booking_id = ?");
                $delSched->bind_param("ii", $paymentId, $bookingId);
                $delSched->execute();
            }

            // 2. Delete any settlement children (emi_due_settlement rows pointing to this payment)
            $delChildren = $this->db->prepare("DELETE FROM lead_payments WHERE parent_payment_id = ? AND booking_id = ?");
            $delChildren->bind_param("ii", $paymentId, $bookingId);
            $delChildren->execute();

            // 3. Delete the payment itself
            $delPay = $this->db->prepare("DELETE FROM lead_payments WHERE id = ? AND booking_id = ?");
            $delPay->bind_param("ii", $paymentId, $bookingId);
            if (!$delPay->execute()) {
                throw new Exception("Failed to delete payment: " . $delPay->error);
            }

            if ($delPay->affected_rows === 0) {
                throw new Exception("No payment was deleted. It may have already been removed.");
            }

            // 4. Renumber EMI schedules to eliminate gaps (DISABLED AS PER REQUEST)
            /*
            if ($paymentType === 'emi') {
                $allEmis = $this->db->prepare("
                    SELECT id FROM emi_schedules 
                    WHERE lead_booking_id = ? 
                    ORDER BY due_date ASC, id ASC
                ");
                $allEmis->bind_param("i", $bookingId);
                $allEmis->execute();
                $result = $allEmis->get_result();

                $newNo = 1;
                $updEmi = $this->db->prepare("UPDATE emi_schedules SET emi_no = ? WHERE id = ?");
                while ($emiRow = $result->fetch_assoc()) {
                    $emiId = (int) $emiRow['id'];
                    $updEmi->bind_param("ii", $newNo, $emiId);
                    $updEmi->execute();
                    $newNo++;
                }
            }
            */

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction deleted successfully.' . ($paymentType === 'emi' ? ' EMI numbers recalculated.' : '')
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all EMI payments with outstanding due balances for a booking.
     * Used to populate the "Settle EMI Due" dropdown in the transaction form.
     */
    public function getOutstandingEmiDues($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT
                lp.id,
                lp.payment_date,
                lp.amount,
                lp.due_emi_amount,
                lp.payment_status,
                COALESCE(es.emi_no, 0) AS emi_no
            FROM lead_payments lp
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            WHERE lp.is_trashed = 0 AND lp.booking_id = ?
              AND lp.payment_type = 'emi'
              AND lp.due_emi_amount > 0
              AND lp.verification_status IN ('verified', 'unverified', 'pending')
              AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
              AND NOT EXISTS (
                  SELECT 1 FROM lead_payments child
                  WHERE child.is_trashed = 0 AND child.parent_payment_id = lp.id
                  AND child.settlement_type = 'emi_due_settlement'
                  AND child.verification_status = 'pending'
              )
            ORDER BY COALESCE(NULLIF(es.emi_no, 0), 999999) ASC, lp.payment_date ASC
        ");

        if (!$stmt) {
            error_log("getOutstandingEmiDues prepare failed: " . $this->db->error);
            return [];
        }

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Create EMI schedule
     */
    protected function createEMISchedule($bookingId, $paymentId, $emiAmount, $serviceFee, $emiScheduleData)
    {
        $nextEmiNo = $this->getNextAvailableEmiNo($bookingId);

        $emiStmt = $this->db->prepare("
            INSERT INTO emi_schedules (
                lead_booking_id, emi_no, emi_amount, service_fee,
                due_emi_amount, due_date, status, lead_payment_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $status = 'paid';
        $dueEmiAmount = isset($emiScheduleData['due_emi_amount']) ? (float) $emiScheduleData['due_emi_amount'] : 0;
        $dueDate = isset($emiScheduleData['due_date']) ? $emiScheduleData['due_date'] : date('Y-m-d');

        $emiStmt->bind_param(
            "iidddssi",
            $bookingId,
            $nextEmiNo,
            $emiAmount,
            $serviceFee,
            $dueEmiAmount,
            $dueDate,
            $status,
            $paymentId
        );

        if (!$emiStmt->execute()) {
            throw new Exception("EMI schedule insert failed: " . $emiStmt->error);
        }
    }

    /**
     * Get transactions by booking ID
     */
    public function getTransactionsByBookingId($bookingId)
    {
        $this->syncMissingEmiSchedules($bookingId);

        $stmt = $this->db->prepare("
            SELECT 
                lp.*,
                l.full_name as client_name,
                p.name as project_name,
                f.floor_no,
                f.flat_name as unit_name,
                CONCAT(f.flat_name, ' - FLOOR ', f.floor_no) as flat_no,
                u.full_name as created_by_name,
                uv.full_name as verified_by_name,
                uh.full_name as handover_to_name,
                uu.full_name as updated_by_name,
                es.emi_no,
                parent_es.emi_no as parent_emi_no,
                es.emi_amount,
                es.due_emi_amount,
                es.due_date as emi_due_date,
                es.status as emi_status,
                es.service_fee as emi_service_fee,
                CASE 
                    WHEN lp.settlement_type = 'emi_due_settlement' AND parent_es.emi_no IS NOT NULL 
                    THEN CONCAT(parent_es.emi_no, 
                        CASE 
                            WHEN parent_es.emi_no % 100 IN (11,12,13) THEN 'th'
                            WHEN parent_es.emi_no % 10 = 1 THEN 'st'
                            WHEN parent_es.emi_no % 10 = 2 THEN 'nd'
                            WHEN parent_es.emi_no % 10 = 3 THEN 'rd'
                            ELSE 'th'
                        END, ' Installment(Settlement)')
                    WHEN lp.settlement_type = 'emi_due_settlement' THEN 'EMI SETTLEMENT'
                    WHEN lp.payment_type = 'emi' AND es.emi_no IS NOT NULL 
                    THEN CONCAT(
                        CASE WHEN lp.payment_status = 'partially_paid' THEN 'Advance ' ELSE '' END,
                        es.emi_no, 
                        CASE 
                            WHEN es.emi_no % 100 IN (11,12,13) THEN 'th'
                            WHEN es.emi_no % 10 = 1 THEN 'st'
                            WHEN es.emi_no % 10 = 2 THEN 'nd'
                            WHEN es.emi_no % 10 = 3 THEN 'rd'
                            ELSE 'th'
                        END, ' Installment'
                    )
                    WHEN lp.payment_type = 'registration' THEN 'REGISTRATION'
                    WHEN lp.payment_type = 'booking_money' THEN 'BOOKING'
                    WHEN lp.payment_type = 'land_share' THEN 'LAND SHARE'
                    WHEN lp.payment_type = 'construction_fee' THEN 'CONSTRUCTION'
                    WHEN lp.payment_type = 'development_charge' THEN 'DEV CHARGE'
                    WHEN lp.payment_type = 'shop_service_charge' THEN 'SHOP SVC CHARGE'
                    WHEN lp.payment_type = 'others' AND lp.other_purpose IS NOT NULL AND TRIM(lp.other_purpose) != '' THEN CONCAT('OTHERS (', UPPER(TRIM(lp.other_purpose)), ')')
                    WHEN lp.payment_type = 'others' THEN 'OTHERS'
                    WHEN lp.payment_type = 'refund' THEN 'REFUND'
                    ELSE UPPER(REPLACE(lp.payment_type, '_', ' '))
                END as installment_label,
                CASE 
                    WHEN lp.settlement_type = 'emi_due_settlement' AND parent_es.emi_no IS NOT NULL 
                    THEN CONCAT('EMI Due Settlement for ', parent_es.emi_no, 
                        CASE 
                            WHEN parent_es.emi_no % 100 IN (11,12,13) THEN 'th'
                            WHEN parent_es.emi_no % 10 = 1 THEN 'st'
                            WHEN parent_es.emi_no % 10 = 2 THEN 'nd'
                            WHEN parent_es.emi_no % 10 = 3 THEN 'rd'
                            ELSE 'th'
                        END, ' Installment')
                    WHEN lp.settlement_type = 'emi_due_settlement' THEN 'EMI Due Settlement'
                    WHEN COALESCE(lp.is_emi_discount, 0) = 1 AND lp.payment_status = 'partially_paid' THEN 'Advance Installment'
                    WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN 'EMI Discount'
                    WHEN lp.payment_type = 'emi' AND lp.payment_status = 'partially_paid' THEN 'Advance Installment'
                    WHEN lp.payment_type = 'emi' THEN 'EMI Payment'
                    WHEN lp.payment_type = 'registration' THEN 'Registration Money'
                    WHEN lp.payment_type = 'mutation_cost' THEN 'Mutation Cost'
                    WHEN lp.payment_type = 'booking_money' THEN 'Booking Money'
                    WHEN lp.payment_type = 'land_share' THEN 'Land Share Payment'
                    WHEN lp.payment_type = 'construction_fee' THEN 'Construction Fee'
                    WHEN lp.payment_type = 'development_charge' THEN 'Development Charge'
                    WHEN lp.payment_type = 'shop_service_charge' THEN 'Shop Service Charge'
                    WHEN lp.payment_type = 'others' AND lp.other_purpose IS NOT NULL AND TRIM(lp.other_purpose) != '' THEN CONCAT('Others: ', TRIM(lp.other_purpose))
                    WHEN lp.payment_type = 'others' THEN 'Other Payment'
                    WHEN lp.payment_type = 'refund' THEN 'Client Refund Payout'
                    ELSE 'Other Payment'
                END as payment_details,
                cb.bank_name as company_bank_name,
                cb.account_name as company_account_name,
                cb.account_number as company_account_number
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN users u ON u.id = lp.created_by
            LEFT JOIN users uv ON uv.id = lp.verified_by
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            LEFT JOIN emi_schedules parent_es ON parent_es.lead_payment_id = lp.parent_payment_id
            LEFT JOIN company_bank_accounts cb ON cb.id = lp.bank_id
            LEFT JOIN users uh ON uh.id = lp.handover_to
            LEFT JOIN users uu ON uu.id = lp.updated_by
            WHERE lp.is_trashed = 0 AND lp.booking_id = ?
            ORDER BY lp.created_at DESC, lp.id DESC
        ");

        if (!$stmt) {
            die("Database query prepare failed: (" . $this->db->errno . ") " . $this->db->error);
        }

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transactions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        foreach ($transactions as &$transaction) {
            if (isset($transaction['payment_date'])) {
                $transaction['formatted_date'] = date('d M, Y', strtotime($transaction['payment_date']));
            }

            if (in_array($transaction['payment_type'], ['booking_money', 'registration', 'mutation_cost']) && $transaction['land_share_amount'] == 0) {
                $transaction['land_share_amount'] = $transaction['amount'];
            }

            if (isset($transaction['emi_service_fee']) && $transaction['emi_service_fee'] > 0) {
                $transaction['service_fee'] = $transaction['emi_service_fee'];
            }

            if (($transaction['payment_type'] ?? '') === 'refund') {
                $transaction['status_class'] = 'bg-danger';
                $transaction['status_text']  = 'Refunded';
            } elseif (isset($transaction['payment_status'])) {
                // If verified by Accounts, always show as Paid regardless of raw payment_status
                if (($transaction['verification_status'] ?? '') === 'verified') {
                    $transaction['status_class'] = 'bg-success';
                    $transaction['status_text']  = 'Paid';
                } else {
                    switch ($transaction['payment_status']) {
                        case 'paid':
                            if (($transaction['verification_status'] ?? '') !== 'verified') {
                                $transaction['status_class'] = 'bg-warning text-dark';
                                $transaction['status_text'] = 'Unverified (Paid)';
                            } else {
                                $transaction['status_class'] = 'bg-success';
                                $transaction['status_text'] = 'Paid';
                            }
                            break;
                        case 'partially_paid':
                            $transaction['status_class'] = 'bg-info';
                            $transaction['status_text'] = 'Partially Paid';
                            break;
                        case 'pending':
                            $transaction['status_class'] = 'bg-warning';
                            $transaction['status_text'] = 'Pending';
                            break;
                        case 'failed':
                            $transaction['status_class'] = 'bg-danger';
                            $transaction['status_text'] = 'Failed';
                            break;
                        default:
                            $transaction['status_class'] = 'bg-secondary';
                            $transaction['status_text'] = ucfirst(str_replace('_', ' ', $transaction['payment_status']));
                    }
                }
            }
        }
        unset($transaction);
        $this->sortTransactions($transactions);

        return $transactions;
    }

    /**
     * Get filtered transactions by booking ID with date filter
     */
    public function getFilteredTransactionsByBookingId($bookingId, $dateFilter = [])
    {
        $this->syncMissingEmiSchedules($bookingId);

        $sql = "
            SELECT 
                lp.*,
                l.full_name as client_name,
                p.name as project_name,
                f.floor_no,
                f.flat_name as unit_name,
                CONCAT(f.flat_name, ' - FLOOR ', f.floor_no) as flat_no,
                u.full_name as created_by_name,
                uv.full_name as verified_by_name,
                uu.full_name as updated_by_name,
                es.emi_no,
                parent_es.emi_no as parent_emi_no,
                es.emi_amount,
                es.due_emi_amount,
                es.due_date as emi_due_date,
                es.status as emi_status,
                lb.discount_amount,
                lb.booking_date as booking_date,
                CASE 
                    WHEN lp.settlement_type = 'emi_due_settlement' AND parent_es.emi_no IS NOT NULL 
                    THEN CONCAT(parent_es.emi_no, 
                        CASE 
                            WHEN parent_es.emi_no % 100 IN (11,12,13) THEN 'th'
                            WHEN parent_es.emi_no % 10 = 1 THEN 'st'
                            WHEN parent_es.emi_no % 10 = 2 THEN 'nd'
                            WHEN parent_es.emi_no % 10 = 3 THEN 'rd'
                            ELSE 'th'
                        END, ' Installment(Settlement)')
                    WHEN lp.settlement_type = 'emi_due_settlement' THEN 'EMI SETTLEMENT'
                    WHEN lp.payment_type = 'emi' AND es.emi_no IS NOT NULL 
                    THEN CONCAT(
                        CASE WHEN lp.payment_status = 'partially_paid' THEN 'Advance ' ELSE '' END,
                        es.emi_no, 
                        CASE 
                            WHEN es.emi_no % 100 IN (11,12,13) THEN 'th'
                            WHEN es.emi_no % 10 = 1 THEN 'st'
                            WHEN es.emi_no % 10 = 2 THEN 'nd'
                            WHEN es.emi_no % 10 = 3 THEN 'rd'
                            ELSE 'th'
                        END, ' Installment'
                    )
                    WHEN lp.payment_type = 'registration' THEN 'REGISTRATION'
                    WHEN lp.payment_type = 'booking_money' THEN 'BOOKING'
                    WHEN lp.payment_type = 'land_share' THEN 'LAND SHARE'
                    WHEN lp.payment_type = 'construction_fee' THEN 'CONSTRUCTION'
                    WHEN lp.payment_type = 'development_charge' THEN 'DEV CHARGE'
                    WHEN lp.payment_type = 'shop_service_charge' THEN 'SHOP SVC CHARGE'
                    WHEN lp.payment_type = 'others' AND lp.other_purpose IS NOT NULL AND TRIM(lp.other_purpose) != '' THEN CONCAT('OTHERS (', UPPER(TRIM(lp.other_purpose)), ')')
                    WHEN lp.payment_type = 'others' THEN 'OTHERS'
                    ELSE UPPER(REPLACE(lp.payment_type, '_', ' '))
                END as installment_label,
                CASE 
                    WHEN lp.settlement_type = 'emi_due_settlement' AND parent_es.emi_no IS NOT NULL 
                    THEN CONCAT('EMI Due Settlement for ', parent_es.emi_no, 
                        CASE 
                            WHEN parent_es.emi_no % 100 IN (11,12,13) THEN 'th'
                            WHEN parent_es.emi_no % 10 = 1 THEN 'st'
                            WHEN parent_es.emi_no % 10 = 2 THEN 'nd'
                            WHEN parent_es.emi_no % 10 = 3 THEN 'rd'
                            ELSE 'th'
                        END, ' Installment')
                    WHEN lp.settlement_type = 'emi_due_settlement' THEN 'EMI Due Settlement'
                    WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN 'EMI Discount'
                    WHEN lp.payment_type = 'emi' THEN 'EMI Payment'
                    WHEN lp.payment_type = 'registration' THEN 'Registration Money'
                    WHEN lp.payment_type = 'mutation_cost' THEN 'Mutation Cost'
                    WHEN lp.payment_type = 'booking_money' THEN 'Booking Money'
                    WHEN lp.payment_type = 'land_share' THEN 'Land Share Payment'
                    WHEN lp.payment_type = 'construction_fee' THEN 'Construction Fee'
                    WHEN lp.payment_type = 'development_charge' THEN 'Development Charge'
                    WHEN lp.payment_type = 'shop_service_charge' THEN 'Shop Service Charge'
                    WHEN lp.payment_type = 'others' AND lp.other_purpose IS NOT NULL AND TRIM(lp.other_purpose) != '' THEN CONCAT('Others: ', TRIM(lp.other_purpose))
                    WHEN lp.payment_type = 'others' THEN 'Other Payment'
                    ELSE 'Other Payment'
                END as payment_details,
                CONCAT(b.bank_name, ' (', b.account_name, ')') as bank_display_name
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN users u ON u.id = lp.created_by
            LEFT JOIN users uv ON uv.id = lp.verified_by
            LEFT JOIN users uu ON uu.id = lp.updated_by
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            LEFT JOIN emi_schedules parent_es ON parent_es.lead_payment_id = lp.parent_payment_id
            LEFT JOIN company_bank_accounts b ON b.id = lp.bank_id
            WHERE lp.is_trashed = 0 AND lp.booking_id = ?
        ";

        $params = [$bookingId];
        $types = "i";

        if (!empty($dateFilter['from']) && !empty($dateFilter['to'])) {
            $sql .= " AND lp.payment_date BETWEEN ? AND ?";
            $params[] = $dateFilter['from'];
            $params[] = $dateFilter['to'];
            $types .= "ss";
        }

        $sql .= " ORDER BY 
            lp.payment_date ASC,
            lp.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return [];
        }

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return [];
        }

        $result = $stmt->get_result();
        $transactions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        foreach ($transactions as &$transaction) {
            if (isset($transaction['payment_date'])) {
                $transaction['formatted_date'] = date('d M, Y', strtotime($transaction['payment_date']));
            }

            if (in_array($transaction['payment_type'], ['booking_money', 'registration', 'mutation_cost']) && $transaction['land_share_amount'] == 0) {
                $transaction['land_share_amount'] = $transaction['amount'];
            }

            if (isset($transaction['payment_status'])) {
                switch ($transaction['payment_status']) {
                    case 'paid':
                        if (($transaction['verification_status'] ?? '') !== 'verified') {
                            $transaction['status_class'] = 'bg-warning text-dark';
                            $transaction['status_text'] = 'Unverified (Paid)';
                        } else {
                            $transaction['status_class'] = 'bg-success';
                            $transaction['status_text'] = 'Paid';
                        }
                        break;
                    case 'partially_paid':
                        $transaction['status_class'] = 'bg-info';
                        $transaction['status_text'] = 'Partially Paid';
                        break;
                    case 'pending':
                        $transaction['status_class'] = 'bg-warning';
                        $transaction['status_text'] = 'Pending';
                        break;
                    case 'failed':
                        $transaction['status_class'] = 'bg-danger';
                        $transaction['status_text'] = 'Failed';
                        break;
                    default:
                        $transaction['status_class'] = 'bg-secondary';
                        $transaction['status_text'] = ucfirst(str_replace('_', ' ', $transaction['payment_status']));
                }
            }
        }
        unset($transaction);
        $this->sortTransactions($transactions);

        return $transactions;
    }

    /**
     * Sort client transactions in chronological business order:
     * 1. Booking Money
     * 2. Registration / Land Share / Mutation Cost
     * 3. Development Charge / Construction Fee / Shop Service Charge
     * 4. EMI Installments sorted by Installment Number (Advance -> Regular -> Settlement)
     * 5. Other Transactions (e.g. Refunds)
     */
    public function sortTransactions(array &$transactions)
    {
        usort($transactions, function ($a, $b) {
            $getGroup = function ($t) {
                $type = strtolower($t['payment_type'] ?? '');
                if ($type === 'booking_money') return 1;
                if (in_array($type, ['registration', 'land_share', 'mutation_cost'], true)) return 2;
                if (in_array($type, ['development_charge', 'construction_fee', 'shop_service_charge'], true)) return 3;
                if ($type === 'emi' 
                    || $type === 'emi_due_settlement' 
                    || (!empty($t['settlement_type']) && $t['settlement_type'] === 'emi_due_settlement')
                    || !empty($t['emi_no']) 
                    || !empty($t['parent_emi_no'])
                    || stripos($t['installment_label'] ?? '', 'installment') !== false
                    || stripos($t['payment_details'] ?? '', 'installment') !== false
                    || stripos($t['installment_label'] ?? '', 'settlement') !== false
                    || stripos($t['payment_details'] ?? '', 'settlement') !== false
                    || stripos($t['installment_label'] ?? '', 'emi') !== false
                    || stripos($t['payment_details'] ?? '', 'emi') !== false
                ) {
                    return 4;
                }
                return 5;
            };

            $groupA = $getGroup($a);
            $groupB = $getGroup($b);

            if ($groupA !== $groupB) {
                return $groupA <=> $groupB;
            }

            // Both are in EMI group
            if ($groupA === 4) {
                $isSettlement = function ($t) {
                    return (!empty($t['settlement_type']) && $t['settlement_type'] === 'emi_due_settlement') 
                        || (($t['payment_type'] ?? '') === 'emi_due_settlement')
                        || (stripos($t['installment_label'] ?? '', 'settlement') !== false)
                        || (stripos($t['payment_details'] ?? '', 'settlement') !== false);
                };

                $getEmiNo = function ($t) use ($isSettlement) {
                    if ($isSettlement($t)) {
                        // For a settlement, the true installment number is the installment being settled!
                        if (!empty($t['parent_emi_no'])) return (int)$t['parent_emi_no'];
                        if (!empty($t['installment_label']) && preg_match('/(\d+)/', $t['installment_label'], $m)) {
                            return (int)$m[1];
                        }
                        if (!empty($t['payment_details']) && preg_match('/(\d+)/', $t['payment_details'], $m)) {
                            return (int)$m[1];
                        }
                        if (!empty($t['emi_no'])) return (int)$t['emi_no'];
                        return 9999;
                    }

                    // Regular / advance EMI
                    if (!empty($t['emi_no'])) return (int)$t['emi_no'];
                    if (!empty($t['parent_emi_no'])) return (int)$t['parent_emi_no'];
                    if (!empty($t['installment_label']) && preg_match('/(\d+)/', $t['installment_label'], $m)) {
                        return (int)$m[1];
                    }
                    if (!empty($t['payment_details']) && preg_match('/(\d+)/', $t['payment_details'], $m)) {
                        return (int)$m[1];
                    }
                    return 9999;
                };

                $emiNoA = $getEmiNo($a);
                $emiNoB = $getEmiNo($b);

                if ($emiNoA !== $emiNoB) {
                    return $emiNoA <=> $emiNoB;
                }

                // Within the SAME installment:
                // 1: Advance installment (partially_paid and not settlement)
                // 2: Regular installment (paid and not settlement)
                // 3: Settlement installment (emi_due_settlement)
                $getSubRank = function ($t) use ($isSettlement) {
                    if ($isSettlement($t)) return 3;
                    $status = strtolower($t['payment_status'] ?? '');
                    if ($status === 'partially_paid') return 1;
                    return 2;
                };

                $subA = $getSubRank($a);
                $subB = $getSubRank($b);

                if ($subA !== $subB) {
                    return $subA <=> $subB;
                }
            }

            // Tie breaker: payment_date ASC, then id ASC
            $dateA = $a['payment_date'] ?? '';
            $dateB = $b['payment_date'] ?? '';
            if ($dateA !== $dateB) {
                return strcmp($dateA, $dateB);
            }

            return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
        });
    }

    /**
     * Update transaction with proper validations
     */
    public function updateTransaction($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            $paymentId = (int) $transactionData['payment_id'];

            $oldTransaction = $this->getTransactionById($paymentId);
            if (!$oldTransaction) {
                throw new Exception("Transaction not found");
            }

            $bookingId = (int) $oldTransaction['booking_id'];
            $newPaymentType = $transactionData['payment_type'];
            $newAmount = (float) $transactionData['amount'];

            $summary = $this->getPaymentSummaryByBookingId($bookingId);

            $landShareAmount = 0;
            $serviceFee = 0;

            if ($newPaymentType === 'land_share') {
                $oldAmount = $oldTransaction['payment_type'] === 'land_share' ?
                    (float) $oldTransaction['amount'] : 0;

                $effectiveAmountChange = $newAmount - $oldAmount;
                $remLSTarget = (float)($summary['remaining_land_share_total'] ?? $summary['remaining_land_share']);
                $remainingAfterChange = $remLSTarget - $effectiveAmountChange;

                if ($remainingAfterChange < 0) {
                    throw new Exception("Land share amount would exceed remaining land share by: Tk. " .
                        number_format(abs($remainingAfterChange), 2));
                }
                $landShareAmount = $newAmount;
            } elseif (in_array($newPaymentType, ['booking_money', 'registration', 'mutation_cost'])) {
                $landShareAmount = $newAmount;
            } elseif ($newPaymentType === 'emi') {
                $serviceFee = $newAmount * 0.10;
            }

            // Encode attachment_paths for storage
            $attachmentPathsJson = null;
            if (!empty($transactionData['attachment_paths'])) {
                $attachmentPathsJson = is_array($transactionData['attachment_paths'])
                    ? json_encode($transactionData['attachment_paths'])
                    : $transactionData['attachment_paths'];
            }

            $otherPurpose = ($newPaymentType === 'others') ? (!empty($transactionData['other_purpose']) ? trim($transactionData['other_purpose']) : ($oldTransaction['other_purpose'] ?? null)) : null;

            $stmt = $this->db->prepare("
                UPDATE lead_payments SET
                    payment_type = ?,
                    other_purpose = ?,
                    amount = ?,
                    land_share_amount = ?,
                    service_fee = ?,
                    payment_method = ?,
                    payment_status = ?,
                    payment_date = ?,
                    transaction_ref = ?,
                    bank_name = ?,
                    bank_id = ?,
                    bank_account_no = ?,
                    cheque_no = ?,
                    online_type = ?,
                    attachment_paths = ?,
                    updated_by = ?
                WHERE id = ?
            ");

            $updatedBy = $_SESSION['user']['id'] ?? null;
            $bankId = !empty($transactionData['bank_id']) ? (int)$transactionData['bank_id'] : null;

            $stmt->bind_param(
                "ssdddsssssisssiii",  // 17
                $newPaymentType,
                $otherPurpose,
                $newAmount,
                $landShareAmount,
                $serviceFee,
                $transactionData['payment_method'],
                $transactionData['payment_status'],
                $transactionData['payment_date'],
                $transactionData['transaction_ref'],
                $transactionData['bank_name'],
                $bankId,
                $transactionData['bank_account_no'],
                $transactionData['cheque_no'],
                $transactionData['online_type'],
                $attachmentPathsJson,
                $updatedBy,
                $paymentId
            );

            if (!$stmt->execute()) {
                throw new Exception("Transaction update failed: " . $stmt->error);
            }

            if ($newPaymentType === 'emi' && $emiScheduleData) {
                $this->updateOrCreateEMISchedule($bookingId, $paymentId, $newAmount, $serviceFee, $emiScheduleData);
            } elseif ($oldTransaction['payment_type'] === 'emi' && $newPaymentType !== 'emi') {
                $this->deleteEMISchedule($paymentId);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction updated successfully!'
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update or create EMI schedule
     */
    private function updateOrCreateEMISchedule($bookingId, $paymentId, $emiAmount, $serviceFee, $emiScheduleData)
    {
        $checkStmt = $this->db->prepare("SELECT id FROM emi_schedules WHERE lead_payment_id = ?");
        $checkStmt->bind_param("i", $paymentId);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        $status = 'paid';
        $dueEmiAmount = isset($emiScheduleData['due_emi_amount']) ? (float) $emiScheduleData['due_emi_amount'] : 0;
        $dueDate = isset($emiScheduleData['due_date']) ? $emiScheduleData['due_date'] : date('Y-m-d');

        if ($existing) {
            $emiStmt = $this->db->prepare("
                UPDATE emi_schedules SET
                    emi_amount = ?,
                    service_fee = ?,
                    due_emi_amount = ?,
                    due_date = ?,
                    status = ?
                WHERE lead_payment_id = ?
            ");

            $emiStmt->bind_param(
                "dddssi",
                $emiAmount,
                $serviceFee,
                $dueEmiAmount,
                $dueDate,
                $status,
                $paymentId
            );
        } else {
            $nextEmiNo = $this->getNextAvailableEmiNo($bookingId);

            $emiStmt = $this->db->prepare("
                INSERT INTO emi_schedules (
                    lead_booking_id, emi_no, emi_amount, service_fee,
                    due_emi_amount, due_date, status, lead_payment_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $emiStmt->bind_param(
                "iidddssi",
                $bookingId,
                $nextEmiNo,
                $emiAmount,
                $serviceFee,
                $dueEmiAmount,
                $dueDate,
                $status,
                $paymentId
            );
        }

        if (!$emiStmt->execute()) {
            throw new Exception("EMI schedule operation failed: " . $emiStmt->error);
        }
    }

    /**
     * Delete EMI schedule
     */
    private function deleteEMISchedule($paymentId)
    {
        $deleteStmt = $this->db->prepare("DELETE FROM emi_schedules WHERE lead_payment_id = ?");
        $deleteStmt->bind_param("i", $paymentId);
        $deleteStmt->execute();
    }

    /**
     * Get transaction by ID
     */
    public function getTransactionById($paymentId)
    {
        $stmt = $this->db->prepare("
            SELECT lp.*,
                   l.full_name as client_name,
                   p.name as project_name,
                   p.category_id,
                   pc.name as category_name,
                   f.floor_no as flat_no,
                   f.flat_name as unit_name,
                   es.emi_no,
                   es.due_emi_amount,
                   es.service_fee as emi_service_fee,
                   es.emi_amount as emi_schedule_amount,
                   uv.full_name as verified_by_name,
                   uv.role_id as verified_by_role_id,
                   (SELECT r.name FROM roles r WHERE r.id = uv.role_id) as verified_by_role_name,
                   (SELECT ev.employee_id 
                    FROM employees ev 
                    LEFT JOIN users uv_sub ON (uv_sub.employee_id = ev.id OR uv_sub.employee_id = ev.employee_id OR ev.user_id = uv_sub.id)
                    WHERE (ev.user_id = lp.verified_by OR uv_sub.id = lp.verified_by) 
                      AND ev.deleted_at IS NULL 
                      AND ev.employee_id IS NOT NULL 
                      AND TRIM(ev.employee_id) != '' 
                    ORDER BY ev.id DESC LIMIT 1) as verified_by_employee_id,
                   uu.full_name as updated_by_name,
                   cb.bank_name as company_bank_name,
                   cb.account_name as company_account_name,
                   cb.account_number as company_account_number,
                   u.full_name as created_by_name,
                   (SELECT ec.employee_id 
                    FROM employees ec 
                    LEFT JOIN users uc_sub ON (uc_sub.employee_id = ec.id OR uc_sub.employee_id = ec.employee_id OR ec.user_id = uc_sub.id)
                    WHERE (ec.user_id = lp.created_by OR uc_sub.id = lp.created_by) 
                      AND ec.deleted_at IS NULL 
                      AND ec.employee_id IS NOT NULL 
                      AND TRIM(ec.employee_id) != '' 
                    ORDER BY ec.id DESC LIMIT 1) as created_by_employee_id,
                   lb.sales_by_id,
                   l.assigned_id,
                   sales_u.full_name as sales_by_name,
                   (SELECT es_sub.employee_id 
                    FROM employees es_sub 
                    LEFT JOIN users us_sub ON (us_sub.employee_id = es_sub.id OR us_sub.employee_id = es_sub.employee_id OR es_sub.user_id = us_sub.id)
                    WHERE (es_sub.user_id = sales_u.id OR us_sub.id = sales_u.id) 
                      AND es_sub.deleted_at IS NULL 
                      AND es_sub.employee_id IS NOT NULL 
                      AND TRIM(es_sub.employee_id) != '' 
                    ORDER BY es_sub.id DESC LIMIT 1) as sales_by_employee_id,
                   CASE 
                       WHEN lp.settlement_type = 'emi_due_settlement' AND parent_es.emi_no IS NOT NULL 
                       THEN CONCAT(parent_es.emi_no, 
                           CASE 
                               WHEN parent_es.emi_no % 100 IN (11,12,13) THEN 'th'
                               WHEN parent_es.emi_no % 10 = 1 THEN 'st'
                               WHEN parent_es.emi_no % 10 = 2 THEN 'nd'
                               WHEN parent_es.emi_no % 10 = 3 THEN 'rd'
                               ELSE 'th'
                           END, ' Installment(Settlement)')
                       WHEN lp.payment_type = 'emi' AND es.emi_no IS NOT NULL THEN CONCAT(
                           es.emi_no, 
                           CASE 
                               WHEN es.emi_no % 100 IN (11,12,13) THEN 'th'
                               WHEN es.emi_no % 10 = 1 THEN 'st'
                               WHEN es.emi_no % 10 = 2 THEN 'nd'
                               WHEN es.emi_no % 10 = 3 THEN 'rd'
                               ELSE 'th'
                           END, ' Installment'
                       )
                       WHEN lp.payment_type = 'registration' THEN 'REGISTRATION'
                       WHEN lp.payment_type = 'booking_money' THEN 'BOOKING'
                       WHEN lp.payment_type = 'land_share' THEN 'LAND SHARE'
                       WHEN lp.payment_type = 'construction_fee' THEN 'CONSTRUCTION'
                       WHEN lp.payment_type = 'development_charge' THEN 'DEV CHARGE'
                       WHEN lp.payment_type = 'shop_service_charge' THEN 'SHOP SVC CHARGE'
                       WHEN lp.payment_type = 'others' AND lp.other_purpose IS NOT NULL AND TRIM(lp.other_purpose) != '' THEN CONCAT('OTHERS (', UPPER(TRIM(lp.other_purpose)), ')')
                       WHEN lp.payment_type = 'others' THEN 'OTHERS'
                       WHEN lp.payment_type = 'refund' THEN 'REFUND'
                       ELSE UPPER(REPLACE(lp.payment_type, '_', ' '))
                   END as installment_label,
                   CASE 
                       WHEN lp.settlement_type = 'emi_due_settlement' AND parent_es.emi_no IS NOT NULL 
                       THEN CONCAT('EMI Due Settlement for ', parent_es.emi_no, 
                           CASE 
                               WHEN parent_es.emi_no % 100 IN (11,12,13) THEN 'th'
                               WHEN parent_es.emi_no % 10 = 1 THEN 'st'
                               WHEN parent_es.emi_no % 10 = 2 THEN 'nd'
                               WHEN parent_es.emi_no % 10 = 3 THEN 'rd'
                               ELSE 'th'
                       END, ' Installment')
                       WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN 'EMI Discount'
                       WHEN lp.payment_type = 'emi' THEN 'EMI Payment'
                       WHEN lp.payment_type = 'registration' THEN 'Registration Money'
                       WHEN lp.payment_type = 'booking_money' THEN 'Booking Money'
                       WHEN lp.payment_type = 'land_share' THEN 'Land Share Payment'
                       WHEN lp.payment_type = 'construction_fee' THEN 'Construction Fee'
                       WHEN lp.payment_type = 'development_charge' THEN 'Development Charge'
                       WHEN lp.payment_type = 'shop_service_charge' THEN 'Shop Service Charge'
                       WHEN lp.payment_type = 'others' AND lp.other_purpose IS NOT NULL AND TRIM(lp.other_purpose) != '' THEN CONCAT('Others: ', TRIM(lp.other_purpose))
                       WHEN lp.payment_type = 'others' THEN 'Other Payment'
                       WHEN lp.payment_type = 'refund' THEN 'Client Refund Payout'
                       ELSE 'Other Payment'
                   END as payment_details
            FROM lead_payments lp
            LEFT JOIN users u ON u.id = lp.created_by
            LEFT JOIN company_bank_accounts cb ON cb.id = lp.bank_id
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            LEFT JOIN emi_schedules parent_es ON parent_es.lead_payment_id = lp.parent_payment_id
            LEFT JOIN users uv ON uv.id = lp.verified_by
            LEFT JOIN users uu ON uu.id = lp.updated_by
            LEFT JOIN users sales_u ON sales_u.id = COALESCE(NULLIF(lb.sales_by_id, 0), NULLIF(l.assigned_id, 0))
            WHERE lp.id = ?
        ");

        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result ? $result->fetch_assoc() : null;

        if ($transaction && isset($transaction['emi_service_fee']) && $transaction['emi_service_fee'] > 0) {
            $transaction['service_fee'] = $transaction['emi_service_fee'];
        }

        return $transaction;
    }

    /**
     * Get all transactions in a batch (e.g. split settlement + EMI payments)
     */
    public function getTransactionsByBatchId($batchId, $bookingId = null)
    {
        $batchId = trim((string)$batchId);
        if ($batchId === '') {
            return [];
        }

        $sql = "SELECT id FROM lead_payments WHERE batch_id = ? AND (is_trashed = 0 OR is_trashed IS NULL)";
        $params = [$batchId];
        $types = "s";
        if (!empty($bookingId)) {
            $sql .= " AND booking_id = ?";
            $params[] = (int)$bookingId;
            $types .= "i";
        }
        $sql .= " ORDER BY id ASC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        $transactions = [];
        foreach ($rows as $r) {
            $t = $this->getTransactionById($r['id']);
            if ($t) {
                $transactions[] = $t;
            }
        }
        return $transactions;
    }

    /**
     * Get EMI schedule by payment ID
     */
    public function getEMIScheduleByPaymentId($paymentId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM emi_schedules 
            WHERE lead_payment_id = ?
        ");

        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get date-wise aggregated payment summary grouped by batch_id (or single payment)
     * For client date-wise ledger without internal payment_type breakdowns.
     * Computes running balance (total due decreasing chronologically).
     */
    public function getDatewiseBatchPaymentSummary($bookingId)
    {
        $bookingId = (int)$bookingId;
        $clientDetails = $this->getClientByBookingId($bookingId);
        if (!$clientDetails) {
            return null;
        }

        $totalPrice = (float)($clientDetails['total_price'] ?? 0);
        if ($totalPrice <= 0) {
            $totalPrice = (float)($clientDetails['agreement_value'] ?? 0);
        }

        $transactions = $this->getFilteredTransactionsByBookingId($bookingId);

        // Include agreement booking money if not in lead_payments table
        $hasBookingMoneyInDB = $this->hasBookingMoneyInDatabase($bookingId);
        if (!$hasBookingMoneyInDB && !empty($clientDetails['booking_money'])) {
            $bookingMoneyTxn = $this->getBookingMoneyTransaction($bookingId);
            if (!$bookingMoneyTxn) {
                $bookingMoneyTxn = [
                    'id'                  => 'booking-' . $bookingId,
                    'payment_date'        => $clientDetails['booking_date'],
                    'formatted_date'      => date('d M, Y', strtotime($clientDetails['booking_date'])),
                    'payment_type'        => 'booking_money',
                    'amount'              => $clientDetails['booking_money'],
                    'payment_status'      => 'paid',
                    'payment_method'      => 'cash',
                    'bank_name'           => '',
                    'bank_display_name'   => '',
                    'cheque_no'           => '',
                    'transaction_ref'     => '',
                    'created_by_name'     => $clientDetails['agent_name'] ?? 'System',
                    'verification_status' => 'verified',
                    'batch_id'            => 'booking_initial_' . $bookingId
                ];
            }
            array_unshift($transactions, $bookingMoneyTxn);
        }

        // Group by batch_id (or fallback to single_{id})
        $batches = [];
        foreach ($transactions as $txn) {
            if (!empty($txn['is_trashed'])) continue;

            $batchKey = !empty($txn['batch_id']) ? $txn['batch_id'] : ('single_' . $txn['id']);

            if (!isset($batches[$batchKey])) {
                $bankDisplay = !empty($txn['bank_name']) ? $txn['bank_name'] : ($txn['bank_display_name'] ?? '');
                $batches[$batchKey] = [
                    'batch_id'            => $txn['batch_id'] ?? null,
                    'primary_payment_id'  => $txn['id'],
                    'payment_ids'         => [$txn['id']],
                    'payment_date'        => $txn['payment_date'] ?? date('Y-m-d'),
                    'payment_method'      => ucfirst(strtolower($txn['payment_method'] ?? 'cash')),
                    'bank_name'           => $bankDisplay,
                    'bank_account_no'     => $txn['bank_account_no'] ?? '',
                    'cheque_no'           => $txn['cheque_no'] ?? '',
                    'transaction_ref'     => $txn['transaction_ref'] ?? '',
                    'created_by_name'     => !empty($txn['created_by_name']) ? $txn['created_by_name'] : 'System',
                    'created_at'          => $txn['created_at'] ?? null,
                    'verified_by_name'    => $txn['verified_by_name'] ?? null,
                    'verification_status' => $txn['verification_status'] ?? 'pending',
                    'total_paid'          => 0.0,
                    'total_discount'      => 0.0,
                    'item_count'          => 1,
                    'is_bulk_batch'       => false,
                    'other_purposes'      => []
                ];
            } else {
                $batches[$batchKey]['payment_ids'][] = $txn['id'];
                $batches[$batchKey]['item_count']++;
                $batches[$batchKey]['is_bulk_batch'] = true;

                if (empty($batches[$batchKey]['bank_name']) && !empty($txn['bank_name'])) {
                    $batches[$batchKey]['bank_name'] = $txn['bank_name'];
                }
                if (empty($batches[$batchKey]['cheque_no']) && !empty($txn['cheque_no'])) {
                    $batches[$batchKey]['cheque_no'] = $txn['cheque_no'];
                }
                if (empty($batches[$batchKey]['transaction_ref']) && !empty($txn['transaction_ref'])) {
                    $batches[$batchKey]['transaction_ref'] = $txn['transaction_ref'];
                }
            }

            if (!empty($txn['other_purpose'])) {
                $batches[$batchKey]['other_purposes'][] = trim($txn['other_purpose']);
            }

            $amt = (float)($txn['amount'] ?? 0);
            $isDiscount = !empty($txn['is_emi_discount']) && (int)$txn['is_emi_discount'] === 1;
            if ($isDiscount) {
                $batches[$batchKey]['total_discount'] += $amt;
            } else {
                $batches[$batchKey]['total_paid'] += $amt;
            }
        }

        // Query database to get authoritative counts of total vs verified payments for every batch_id in this booking
        $batchIdStats = [];
        $batchIds = [];
        foreach ($transactions as $txn) {
            if (!empty($txn['batch_id'])) {
                $batchIds[] = trim((string)$txn['batch_id']);
            }
        }
        $batchIds = array_values(array_unique(array_filter($batchIds)));

        if (!empty($batchIds)) {
            $inClause = implode(',', array_fill(0, count($batchIds), '?'));
            $types = str_repeat('s', count($batchIds));
            $bSql = "SELECT batch_id,
                            COUNT(*) as total_count,
                            SUM(CASE WHEN LOWER(TRIM(verification_status)) = 'verified' THEN 1 ELSE 0 END) as verified_count
                     FROM lead_payments
                     WHERE batch_id IN ($inClause) AND (is_trashed = 0 OR is_trashed IS NULL)
                     GROUP BY batch_id";
            $bStmt = $this->db->prepare($bSql);
            if ($bStmt) {
                $bStmt->bind_param($types, ...$batchIds);
                $bStmt->execute();
                $bRes = $bStmt->get_result();
                while ($bRow = $bRes->fetch_assoc()) {
                    $batchIdStats[$bRow['batch_id']] = [
                        'total_count'    => (int)$bRow['total_count'],
                        'verified_count' => (int)$bRow['verified_count']
                    ];
                }
                $bStmt->close();
            }
        }

        // Determine strict batch verification: ALL payments in a batch_id MUST be verified
        foreach ($batches as &$b) {
            $batchId = $b['batch_id'] ?? null;
            if (!empty($batchId) && isset($batchIdStats[$batchId])) {
                $totalInDb = $batchIdStats[$batchId]['total_count'];
                $verifiedInDb = $batchIdStats[$batchId]['verified_count'];
                $b['total_payments_in_batch'] = $totalInDb;
                $b['verified_payments_in_batch'] = $verifiedInDb;
                $b['is_fully_verified'] = ($totalInDb > 0 && $totalInDb === $verifiedInDb);
                if ($b['is_fully_verified']) {
                    $b['verification_status'] = 'verified';
                } elseif ($verifiedInDb > 0) {
                    $b['verification_status'] = 'partially_verified';
                } else {
                    $b['verification_status'] = 'pending';
                }
            } else {
                $isSingleVerified = (strtolower(trim($b['verification_status'] ?? '')) === 'verified');
                $b['total_payments_in_batch'] = 1;
                $b['verified_payments_in_batch'] = $isSingleVerified ? 1 : 0;
                $b['is_fully_verified'] = $isSingleVerified;
                $b['verification_status'] = $isSingleVerified ? 'verified' : 'pending';
            }
        }
        unset($b);

        // Sort chronologically: payment_date ASC, primary_payment_id ASC
        usort($batches, function($a, $b) {
            $tA = strtotime($a['payment_date']);
            $tB = strtotime($b['payment_date']);
            if ($tA === $tB) {
                return ($a['primary_payment_id'] <=> $b['primary_payment_id']);
            }
            return ($tA <=> $tB);
        });

        // Compute running balance (Due decreasing chronologically)
        $runningDue = $totalPrice;
        $grandTotalPaid = 0.0;
        $grandTotalDiscount = 0.0;

        foreach ($batches as &$b) {
            $paid = (float)$b['total_paid'];
            $discount = (float)$b['total_discount'];

            $grandTotalPaid += $paid;
            $grandTotalDiscount += $discount;

            $runningDue -= ($paid + $discount);
            if ($runningDue < 0) $runningDue = 0;

            $b['remaining_due'] = $runningDue;
            $b['total_price_reference'] = $totalPrice;

            // Receipt formatting
            if (is_numeric($b['primary_payment_id'])) {
                $b['receipt_no'] = 'REC-' . str_pad($b['primary_payment_id'], 6, '0', STR_PAD_LEFT);
            } else {
                $b['receipt_no'] = 'REC-' . strtoupper(str_replace('booking-', 'BKG-', (string)$b['primary_payment_id']));
            }
            $b['formatted_date'] = !empty($b['payment_date']) ? date('d M, Y', strtotime($b['payment_date'])) : '—';
        }
        unset($b);

        // Construct batch verification lookup map
        $batchMap = [];
        foreach ($batches as $b) {
            $mapVal = [
                'is_fully_verified'           => !empty($b['is_fully_verified']),
                'verification_status'         => $b['verification_status'] ?? 'pending',
                'total_payments_in_batch'     => $b['total_payments_in_batch'] ?? 1,
                'verified_payments_in_batch'  => $b['verified_payments_in_batch'] ?? 0
            ];
            if (!empty($b['batch_id'])) {
                $batchMap[$b['batch_id']] = $mapVal;
            }
            if (!empty($b['primary_payment_id'])) {
                $batchMap['single_' . $b['primary_payment_id']] = $mapVal;
                $batchMap[(string)$b['primary_payment_id']] = $mapVal;
            }
        }

        return [
            'client_details'        => $clientDetails,
            'total_agreement_value' => $totalPrice,
            'total_price'           => $totalPrice,
            'total_paid'            => $grandTotalPaid,
            'total_discount'        => $grandTotalDiscount,
            'current_due'           => max(0, $totalPrice - ($grandTotalPaid + $grandTotalDiscount)),
            'batches'               => array_values($batches),
            'batch_map'             => $batchMap
        ];
    }

    /**
     * Delete transaction
     */
    public function deleteTransaction($paymentId)
    {
        $this->db->begin_transaction();

        try {
            $this->deleteEMISchedule($paymentId);

            $deleteStmt = $this->db->prepare("DELETE FROM lead_payments WHERE id = ?");
            $deleteStmt->bind_param("i", $paymentId);

            if (!$deleteStmt->execute()) {
                throw new Exception("Failed to delete transaction");
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction deleted successfully!'
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if registration is paid
     */
    public function isRegistrationPaid($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM lead_payments 
            WHERE booking_id = ? 
            AND payment_type = 'registration' 
            AND payment_status = 'paid'
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    /**
     * Check if land share is completed
     */
    public function isLandShareCompleted($bookingId)
    {
        $summary = $this->getPaymentSummaryByBookingId($bookingId);
        return $summary['is_land_share_completed'];
    }

    /**
     * Get remaining land share
     */
    public function getRemainingLandShare($bookingId)
    {
        $summary = $this->getPaymentSummaryByBookingId($bookingId);
        return $summary['remaining_land_share'];
    }

    /**
     * Check if booking money exists
     */
    public function hasBookingMoneyInDatabase($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM lead_payments 
            WHERE booking_id = ? 
            AND payment_type = 'booking_money'
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public function addTransactionWithEMI($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            $batchId = 'batch_' . uniqid() . '_' . time();
            $bookingId = (int) $transactionData['booking_id'];
            $paymentType = $transactionData['payment_type'];
            $totalAmount = (float) $transactionData['amount'];
            
            $clientDetails = $this->getClientByBookingId($bookingId);
            $summary = $this->getPaymentSummaryByBookingId($bookingId);
            
            $pool = $totalAmount;
            $paymentsToInsert = [];
            
            // Calculate remaining targets
            $agreementValForBooking = (float) ($summary['agreement_value'] ?? 0);
            $discountForBooking     = (float) ($summary['discount_amount'] ?? 0);
            $bookingTargetForTx     = ($agreementValForBooking > 0) 
                ? max(0, $agreementValForBooking - $discountForBooking) 
                : (float) ($summary['effective_booking_target'] ?? 300000);
            $landShareRemaining = max(0, (float)($summary['remaining_land_share_total'] ?? $summary['remaining_land_share']));
            $bookingMoneyRemaining = ($landShareRemaining <= 0 || !empty($summary['is_land_share_completed']))
                ? 0
                : min($landShareRemaining, max(0, $bookingTargetForTx - (float) ($summary['booking_money_total'] ?? 0)));
            $isShop = !empty($clientDetails['is_shop']);
            // Development Charge (flats.development_charges) — applies to both flats and shops
            $flatDevTarget    = (float) ($summary['flat_development_charges'] ?? 0);
            $flatDevPaid      = (float) ($summary['development_charge_paid_total'] ?? 0);
            $flatDevRemaining = max(0, $flatDevTarget - $flatDevPaid);
            // Shop Service Charge — shops only
            $shopSvcRemaining = $isShop
                ? max(0, (float) ($summary['shop_service_charge_target'] ?? 0) - (float) ($summary['shop_service_charge_paid_total'] ?? 0))
                : 0;
            // Combined pre-EMI remaining (used further below)
            $devRemaining = $flatDevRemaining + $shopSvcRemaining;
            
            // ─── CASCADING WATERFALL LOGIC ───
            // If payment type is 'others', do not cascade into booking/land share/dev charge/emi
            if ($paymentType === 'others') {
                $paymentsToInsert[] = [
                    'type'          => 'others',
                    'amount'        => $pool,
                    'other_purpose' => !empty($transactionData['other_purpose']) ? trim($transactionData['other_purpose']) : null
                ];
                $pool = 0;
            }
            
            // Order: Booking → Land Share/Registration → Development Charge → Shop Service Charge (shops) → EMI
            // Stage 1: Booking Money
            // Auto-settles Booking Money first if any remaining, even if user selected land_share, dev_charge, or emi!
            if (in_array($paymentType, ['booking_money', 'land_share', 'registration', 'mutation_cost', 'development_charge', 'shop_service_charge', 'emi', 'emi_due_settlement'])) {
                if ($bookingMoneyRemaining > 0 && $pool > 0) {
                    $payAmount = min($pool, $bookingMoneyRemaining);
                    $paymentsToInsert[] = ['type' => 'booking_money', 'amount' => $payAmount];
                    $pool -= $payAmount;
                    $bookingMoneyRemaining -= $payAmount;
                    $landShareRemaining = max(0, $landShareRemaining - $payAmount);
                }
            }
            
            // Stage 2: Land Share / Registration
            // Auto-settles Land Share next if any remaining, even if user selected development_charge or emi!
            if (in_array($paymentType, ['booking_money', 'land_share', 'registration', 'mutation_cost', 'development_charge', 'shop_service_charge', 'emi', 'emi_due_settlement'])) {
                if ($landShareRemaining > 0 && $pool > 0) {
                    $targetLandType = in_array($paymentType, ['land_share', 'registration', 'mutation_cost']) ? $paymentType : ($isShop ? 'registration' : 'land_share');
                    $payAmount = min($pool, $landShareRemaining);
                    $paymentsToInsert[] = ['type' => $targetLandType, 'amount' => $payAmount];
                    $pool -= $payAmount;
                    $landShareRemaining -= $payAmount;
                }
            }
            
            // Stage 3: Development Charge & Shop Service Charge
            if (in_array($paymentType, ['booking_money', 'land_share', 'registration', 'mutation_cost', 'development_charge', 'shop_service_charge', 'emi', 'emi_due_settlement'])) {
                // Stage: Development Charge
                if ($flatDevRemaining > 0 && $pool > 0) {
                    $payAmount = min($pool, $flatDevRemaining);
                    $paymentsToInsert[] = ['type' => 'development_charge', 'amount' => $payAmount];
                    $pool -= $payAmount;
                    $flatDevRemaining -= $payAmount;
                }
                // Stage: Shop Service Charge (shops only)
                if ($isShop && $shopSvcRemaining > 0 && $pool > 0) {
                    $payAmount = min($pool, $shopSvcRemaining);
                    $paymentsToInsert[] = ['type' => 'shop_service_charge', 'amount' => $payAmount];
                    $pool -= $payAmount;
                    $shopSvcRemaining -= $payAmount;
                }
                if ($pool > 0) $paymentType = 'emi';
            }
            
            // Encode attachment paths once
            $attachmentPathsJson = null;
            if (!empty($transactionData['attachment_paths'])) {
                $attachmentPathsJson = is_array($transactionData['attachment_paths'])
                    ? json_encode($transactionData['attachment_paths'])
                    : $transactionData['attachment_paths'];
            }
            
            // Insert Non-EMI Payments
            $paymentMethod  = $transactionData['payment_method']   ?? 'cash';
            $paymentStatus  = $transactionData['payment_status']   ?? 'paid';
            $paymentDate    = $transactionData['payment_date']     ?? date('Y-m-d');
            $transactionRef = $transactionData['transaction_ref']  ?? '';
            $bankId         = !empty($transactionData['bank_id']) ? (int) $transactionData['bank_id'] : null;
            $bankName       = $transactionData['bank_name']        ?? '';
            $bankAccNo      = $transactionData['bank_account_no']  ?? '';
            $chequeNo       = $transactionData['cheque_no']        ?? '';
            $onlineType     = $transactionData['online_type']      ?? '';
            $createdBy      = (int) ($transactionData['created_by'] ?? 1);
            $handoverTo     = !empty($transactionData['handover_to']) ? (int) $transactionData['handover_to'] : null;
            $handoverStatus = $handoverTo ? 'pending' : null;

            $lastPaymentId = null;
            
            foreach ($paymentsToInsert as $pay) {
                if ($pay['amount'] <= 0) continue;
                
                $pType = $pay['type'];
                $pAmount = $pay['amount'];
                $pPurpose = $pay['other_purpose'] ?? (!empty($transactionData['other_purpose']) ? trim($transactionData['other_purpose']) : null);
                // Only booking_money, land_share, registration, mutation_cost contribute to land_share target
                $pLandShare = in_array($pType, ['land_share', 'booking_money', 'registration', 'mutation_cost']) ? $pAmount : 0;
                $pSvcFee = 0.0;
                
                $stmt = $this->db->prepare("
                    INSERT INTO lead_payments (
                        booking_id, payment_type, other_purpose, amount, land_share_amount, service_fee,
                        payment_method, payment_status, payment_date, transaction_ref,
                        bank_id, bank_name, bank_account_no, cheque_no, online_type,
                        attachment_paths, created_by, handover_to, handover_status, batch_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$stmt) throw new Exception("Prepare failed: " . $this->db->error);
                
                $stmt->bind_param(
                    "issdddssssisssssiiss",
                    $bookingId, $pType, $pPurpose, $pAmount, $pLandShare, $pSvcFee,
                    $paymentMethod, $paymentStatus, $paymentDate, $transactionRef,
                    $bankId, $bankName, $bankAccNo, $chequeNo, $onlineType,
                    $attachmentPathsJson, $createdBy, $handoverTo, $handoverStatus, $batchId
                );
                
                if (!$stmt->execute()) throw new Exception("Insert failed: " . $stmt->error);
                $lastPaymentId = $this->db->insert_id;
                
                // Handover Approval
                if (!empty($handoverTo)) {
                    if (!class_exists('ApprovalRequest')) {
                        require_once __DIR__ . '/ApprovalRequest.php';
                    }
                    $approvalModel = new ApprovalRequest($this->db);
                    $approvalModel->create([
                        'department' => 'Accounts',
                        'request_type' => 'Cash In Handover',
                        'title' => 'Handover Approval Request for Payment #' . $lastPaymentId,
                        'source_table' => 'lead_payments',
                        'source_id' => $lastPaymentId,
                        'requested_days' => 0,
                        'requested_by' => $createdBy,
                        'approve_role' => 'management',
                        'meta_data' => json_encode([
                            'Amount' => $pAmount,
                            'Payment Type' => ucfirst(str_replace('_', ' ', $pType))
                        ]),
                        'request_amount' => $pAmount
                    ]);
                }
            }
            
            $isEmiDiscount = !empty($transactionData['is_emi_discount']);
            $bulkEmiDiscount = (float) ($transactionData['bulk_emi_discount'] ?? 0);
            
            // If pool is fully depleted, return success (unless this is a virtual discount transaction)
            if ($pool <= 0 && !($isEmiDiscount && $bulkEmiDiscount > 0)) {
                $this->db->commit();
                return [
                    'success' => true,
                    'message' => 'Transaction added successfully! ' . (count($paymentsToInsert) > 1 ? 'Amount was automatically divided across ' . count($paymentsToInsert) . ' payment stages.' : ''),
                    'payment_id' => $lastPaymentId,
                    'service_fee' => 0,
                    'land_share_amount' => 0
                ];
            }

            // ═══════════════════════════════════════════════════════════════════
            // ─── EMI PAYMENT: Smart FIFO Allocation ──────────────────────────
            // ═══════════════════════════════════════════════════════════════════
            
            // Sync any missing EMI schedules before allocating new installments
            $this->syncMissingEmiSchedules($bookingId);

            // Check dev charge status dynamically
            $devPaidInThisTx = 0;
            foreach ($paymentsToInsert as $p) {
                if ($p['type'] === 'development_charge' || $p['type'] === 'shop_service_charge') $devPaidInThisTx += $p['amount'];
            }
            $devRemainingAfterTx = $devRemaining - $devPaidInThisTx;

            // Service charge on FULL amount (user's answer: YES)
            $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);

            // Construction fee cap check (accounts for existing bulk discounts)
            $constructionFee      = (float) ($summary['construction_fee'] ?? 0);
            $emiPaidSoFar         = (float) ($summary['emi_paid_total'] ?? 0);
            $perEmiAmount         = (float) ($summary['per_emi_amount'] ?? 0);
            $existingBulkDiscount = (float) ($summary['total_bulk_emi_discount'] ?? 0);
            $effectiveCF          = max(0, $constructionFee - $existingBulkDiscount);

            // Bulk discount validation
            $bulkEmiDiscount = (float) ($transactionData['bulk_emi_discount'] ?? 0);
            $isEmiDiscount = !empty($transactionData['is_emi_discount']);

            $emiPaymentsToProcess = [
                ['amount' => $pool, 'is_discount' => 0]
            ];
            
            if ($isEmiDiscount && $bulkEmiDiscount > 0) {
                $emiPaymentsToProcess[] = ['amount' => $bulkEmiDiscount, 'is_discount' => 1];
            } else if ($bulkEmiDiscount > 0) {
                // Discount only allowed when paying >= 2 EMIs worth
                if ($perEmiAmount > 0 && $pool < ($perEmiAmount * 2)) {
                    throw new Exception("Bulk EMI discount requires paying at least 2 EMI installments at once (minimum: Tk. " . number_format($perEmiAmount * 2, 2) . ").");
                }
                // Discount cannot exceed remaining construction fee after this payment
                $remainingAfterPayment = $effectiveCF - $emiPaidSoFar - $pool;
                if ($bulkEmiDiscount > $remainingAfterPayment) {
                    throw new Exception("Bulk discount (Tk. " . number_format($bulkEmiDiscount, 2) . ") exceeds remaining construction fee after payment (Tk. " . number_format(max(0, $remainingAfterPayment), 2) . "). Max discount: Tk. " . number_format(max(0, $remainingAfterPayment), 2));
                }
            }

            if (($emiPaidSoFar + $pool + ($isEmiDiscount ? $bulkEmiDiscount : 0)) > $effectiveCF) {
                $maxPayable = max(0, $effectiveCF - $emiPaidSoFar);
                throw new Exception("EMI payment (including discount) exceeds remaining construction fee. Maximum payable: Tk. " . number_format($maxPayable, 2));
            }

            // Common payment metadata
            $attachmentPathsJson = null;
            if (!empty($transactionData['attachment_paths'])) {
                $attachmentPathsJson = is_array($transactionData['attachment_paths'])
                    ? json_encode($transactionData['attachment_paths'])
                    : $transactionData['attachment_paths'];
            }
            $paymentDate    = $transactionData['payment_date']     ?? date('Y-m-d');
            $transactionRef = $transactionData['transaction_ref']  ?? '';
            $bankName       = $transactionData['bank_name']        ?? '';
            $bankAccNo      = $transactionData['bank_account_no']  ?? '';
            $chequeNo       = $transactionData['cheque_no']        ?? '';
            $onlineType     = $transactionData['online_type']      ?? '';
            $createdBy      = (int) ($transactionData['created_by'] ?? 1);

            $settledDues = 0;
            $newInstallments = 0;
            $settlementDetails = [];
            $totalServiceFee = 0;
            $firstPaymentId = null;

            foreach ($emiPaymentsToProcess as $pProcess) {
                $pool = $pProcess['amount'];
                if ($pool <= 0) continue;
                $isDiscountItem = $pProcess['is_discount'];
                
                $currentPaymentMethod = $isDiscountItem ? 'cash' : ($transactionData['payment_method'] ?? 'cash');
                $currentServiceFee = (!$isDiscountItem && $serviceChargeRate > 0) ? round($pool * ($serviceChargeRate / 100)) : 0;
                $totalServiceFee += $currentServiceFee;

                // ─── Unified Sequential Installment & Due Settlement Loop (Ascending 1 -> 2 -> 3 ...) ───
                $totalAllocated = 0;  // Track total EMI (new + settlements) allocated in this transaction

                while ($pool > 0) {
                    // Check next available empty installment slot
                    $nextEmptyEmiNo = $this->getNextAvailableEmiNo($bookingId);

                    // Check lowest outstanding due
                    $outstandingDues = $this->getOutstandingEmiDues($bookingId);
                    $lowestDue = !empty($outstandingDues) ? $outstandingDues[0] : null;
                    $lowestDueNo = ($lowestDue && isset($lowestDue['emi_no']) && (int)$lowestDue['emi_no'] > 0)
                        ? (int)$lowestDue['emi_no']
                        : null;

                    // Settle due if it belongs to an earlier or current slot, or if there is no earlier empty gap
                    $shouldSettleDue = false;
                    if ($lowestDue !== null) {
                        if ($lowestDueNo !== null) {
                            $shouldSettleDue = ($lowestDueNo <= $nextEmptyEmiNo);
                        } else {
                            $shouldSettleDue = true;
                        }
                    }

                    if ($shouldSettleDue) {
                        // Settle outstanding due on EMI #$lowestDueNo
                        $dueAmount = (float) $lowestDue['due_emi_amount'];
                        $settle = min($pool, $dueAmount);

                        // Service fee for this settlement portion
                        $settleSvcFee = (!$isDiscountItem && $serviceChargeRate > 0) ? round($settle * ($serviceChargeRate / 100)) : 0;

                        // Create settlement audit record
                        $settleStmt = $this->db->prepare("
                            INSERT INTO lead_payments (
                                booking_id, payment_type, amount, land_share_amount, service_fee,
                                payment_method, payment_status, payment_date, transaction_ref,
                                bank_id, bank_name, bank_account_no, cheque_no, online_type,
                                attachment_paths, due_emi_amount, parent_payment_id, settlement_type, created_by,
                                handover_to, handover_status, batch_id, is_emi_discount
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        if (!$settleStmt) throw new Exception("Settlement prepare failed: " . $this->db->error);

                        $stType = 'emi_due_settlement';
                        $stPayType = 'emi'; // Use 'emi' for DB ENUM consistency; settlement tracked via settlement_type
                        $stStatus = 'paid';
                        $stLandShare = 0.0;
                        $stDue = 0.0;
                        $parentId = (int) $lowestDue['id'];

                        $handoverToId = (!$isDiscountItem && !empty($transactionData['handover_to'])) ? (int) $transactionData['handover_to'] : null;
                        $handoverStatus = $handoverToId ? 'pending' : null;

                        $settleStmt->bind_param(
                            "isdddssssisssssdisiissi",
                            $bookingId, $stPayType, $settle, $stLandShare, $settleSvcFee,
                            $currentPaymentMethod, $stStatus, $paymentDate, $transactionRef,
                            $bankId, $bankName, $bankAccNo, $chequeNo, $onlineType,
                            $attachmentPathsJson, $stDue, $parentId, $stType, $createdBy,
                            $handoverToId, $handoverStatus, $batchId, $isDiscountItem
                        );

                        if (!$settleStmt->execute()) throw new Exception("Settlement insert failed: " . $settleStmt->error);
                        
                        $settlePaymentId = $this->db->insert_id;
                        if (!$firstPaymentId) {
                            $firstPaymentId = $settlePaymentId;
                        }

                        if (!empty($handoverToId)) {
                            if (!class_exists('ApprovalRequest')) {
                                require_once __DIR__ . '/ApprovalRequest.php';
                            }
                            $approvalModel = new ApprovalRequest($this->db);
                            $approvalModel->create([
                                'department' => 'Accounts',
                                'request_type' => 'Cash In Handover',
                                'title' => 'Handover Approval Request for Payment #' . $settlePaymentId,
                                'source_table' => 'lead_payments',
                                'source_id' => $settlePaymentId,
                                'requested_days' => 0,
                                'requested_by' => $createdBy,
                                'approve_role' => 'management',
                                'meta_data' => json_encode([
                                    'Amount' => $settle,
                                    'Payment Type' => 'EMI Settlement'
                                ]),
                                'request_amount' => $settle
                            ]);
                        }

                        // Update parent's due_emi_amount in lead_payments
                        $newDue = max(0, $dueAmount - $settle);
                        $newParentStatus = ($newDue <= 0) ? 'paid' : 'partially_paid';
                        $updStmt = $this->db->prepare("UPDATE lead_payments SET due_emi_amount = ?, payment_status = ? WHERE id = ?");
                        $updStmt->bind_param('dsi', $newDue, $newParentStatus, $parentId);
                        $updStmt->execute();

                        // Also sync emi_schedules
                        $schedSyncStmt = $this->db->prepare("UPDATE emi_schedules SET due_emi_amount = ?, status = ? WHERE lead_payment_id = ?");
                        $schedSyncStmt->bind_param('dsi', $newDue, $newParentStatus, $parentId);
                        $schedSyncStmt->execute();
                        $schedSyncStmt->close();

                        $pool -= $settle;
                        $totalAllocated += $settle;
                        $settledDues++;
                        $settlementDetails[] = [
                            'emi_no' => $lowestDue['emi_no'],
                            'settled' => $settle,
                            'remaining_due' => $newDue
                        ];
                    } else {
                        // Allocate to next empty installment slot (#$nextEmptyEmiNo)
                        $thisEmi = ($perEmiAmount > 0) ? min($pool, $perEmiAmount) : $pool;

                        // Calculate remaining CF after ALL installments so far (including this one)
                        $cfRemainingBeforeThis = max(0, $effectiveCF - $emiPaidSoFar - $totalAllocated);
                        $cfRemainingAfterThis = max(0, $cfRemainingBeforeThis - $thisEmi);
                        if ($cfRemainingAfterThis <= 0) {
                            $thisDue = 0;
                        } else {
                            $expectedForThis = ($perEmiAmount > 0) ? min($perEmiAmount, $cfRemainingBeforeThis) : 0;
                            $thisDue = ($expectedForThis > 0 && $thisEmi < $expectedForThis)
                                       ? max(0, round($expectedForThis - $thisEmi))
                                       : 0;
                        }
                        $thisStatus = ($thisDue > 0) ? 'partially_paid' : 'paid';

                        // Service fee for this installment
                        $thisSvcFee = (!$isDiscountItem && $serviceChargeRate > 0) ? round($thisEmi * ($serviceChargeRate / 100)) : 0;

                        // Insert lead_payments row for this installment
                        $emiStmt = $this->db->prepare("
                            INSERT INTO lead_payments (
                                booking_id, payment_type, amount, land_share_amount, service_fee,
                                payment_method, payment_status, payment_date, transaction_ref,
                                bank_id, bank_name, bank_account_no, cheque_no, online_type,
                                attachment_paths, due_emi_amount, bulk_emi_discount, bulk_pay_amount, created_by,
                                handover_to, handover_status, bulk_parent_id, batch_id, is_emi_discount
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        if (!$emiStmt) throw new Exception("EMI payment prepare failed: " . $this->db->error);

                        $emiPayType = 'emi';
                        $emiLandShare = 0.0;
                        // Attach discount + bulk pay amount only to the FIRST installment (for cash only)
                        $thisDiscount = (!$firstPaymentId && !$isDiscountItem && !$isEmiDiscount && $bulkEmiDiscount > 0) ? $bulkEmiDiscount : 0.0;
                        $thisBulkPayAmount = (!$firstPaymentId && !$isDiscountItem && $newInstallments === 0) ? $pProcess['amount'] : null;

                        $handoverToId = (!$isDiscountItem && !empty($transactionData['handover_to'])) ? (int) $transactionData['handover_to'] : null;
                        $handoverStatus = $handoverToId ? 'pending' : null;
                        $thisHandoverToId = $handoverToId;
                        $thisBulkParentId = ($firstPaymentId) ? $firstPaymentId : null;

                        $emiStmt->bind_param(
                            "isdddssssisssssdddiisisi",
                            $bookingId, $emiPayType, $thisEmi, $emiLandShare, $thisSvcFee,
                            $currentPaymentMethod, $thisStatus, $paymentDate, $transactionRef,
                            $bankId, $bankName, $bankAccNo, $chequeNo, $onlineType,
                            $attachmentPathsJson, $thisDue, $thisDiscount, $thisBulkPayAmount, $createdBy,
                            $thisHandoverToId, $handoverStatus, $thisBulkParentId, $batchId, $isDiscountItem
                        );

                        if (!$emiStmt->execute()) throw new Exception("EMI payment insert failed: " . $emiStmt->error);

                        $paymentId = $this->db->insert_id;
                        
                        if (!empty($handoverToId) && !$firstPaymentId) {
                            if (!class_exists('ApprovalRequest')) {
                                require_once __DIR__ . '/ApprovalRequest.php';
                            }
                            $approvalModel = new ApprovalRequest($this->db);
                            $approvalModel->create([
                                'department' => 'Accounts',
                                'request_type' => 'Cash In Handover',
                                'title' => 'Handover Approval Request for Payment #' . $paymentId,
                                'source_table' => 'lead_payments',
                                'source_id' => $paymentId,
                                'requested_days' => 0,
                                'requested_by' => $createdBy,
                                'approve_role' => 'management',
                                'meta_data' => json_encode([
                                    'Project' => $clientDetails['project_name'] ?? 'N/A',
                                    'Unit' => $clientDetails['unit_name'] ?? 'N/A',
                                    'Amount' => $pProcess['amount'], // Send the total bulk amount
                                    'Discount' => (!$isEmiDiscount ? $bulkEmiDiscount : 0),
                                    'Payment Type' => 'Bulk EMI'
                                ]),
                                'request_amount' => $pProcess['amount'] // Use total bulk amount
                            ]);
                        }
                        if (!$firstPaymentId) {
                            $firstPaymentId = $paymentId;
                        }

                        // Insert emi_schedule row
                        $schedStmt = $this->db->prepare("
                            INSERT INTO emi_schedules (
                                lead_booking_id, emi_no, emi_amount, service_fee,
                                due_emi_amount, due_date, status, lead_payment_id
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        if (!$schedStmt) throw new Exception("EMI schedule prepare failed: " . $this->db->error);

                        $schedStatus = 'paid';

                        $schedStmt->bind_param(
                            "iidddssi",
                            $bookingId, $nextEmptyEmiNo, $thisEmi, $thisSvcFee,
                            $thisDue, $paymentDate, $schedStatus, $paymentId
                        );

                        if (!$schedStmt->execute()) throw new Exception("EMI schedule insert failed: " . $schedStmt->error);

                        $pool -= $thisEmi;
                        $totalAllocated += $thisEmi;
                        $newInstallments++;
                    }
                } // end while pool > 0
            } // end foreach emiPaymentsToProcess

            // Update penalty & waiver details on the primary payment record (whether settlement or new installment)
            if ($firstPaymentId) {
                $penaltyAmount = (float)($transactionData['penalty_amount'] ?? 0);
                $penaltyPaid   = (float)($transactionData['penalty_paid'] ?? 0);
                $penaltyWaived = (float)($transactionData['penalty_waived'] ?? 0);
                $waiverReason  = $transactionData['waiver_reason'] ?? null;
                $waiverAtt     = $transactionData['waiver_attachment'] ?? null;
                $waiverStatus  = $transactionData['waiver_status'] ?? 'none';

                if ($penaltyAmount > 0 || $penaltyWaived > 0) {
                    $pUpd = $this->db->prepare("UPDATE lead_payments SET penalty_amount = ?, penalty_paid = ?, penalty_waived = ?, waiver_reason = ?, waiver_attachment = ?, waiver_status = ? WHERE id = ?");
                    $pUpd->bind_param("dddsssi", $penaltyAmount, $penaltyPaid, $penaltyWaived, $waiverReason, $waiverAtt, $waiverStatus, $firstPaymentId);
                    $pUpd->execute();
                    $pUpd->close();
                }

                if ($waiverStatus === 'pending' && $penaltyWaived > 0) {
                    if (!class_exists('ApprovalRequest')) {
                        require_once __DIR__ . '/ApprovalRequest.php';
                    }
                    $approvalModel = new ApprovalRequest($this->db);
                    $approvalModel->create([
                        'department'     => 'CED',
                        'request_type'   => 'Penalty Waiver Request',
                        'title'          => 'Penalty Waiver Request for Client Payment #' . $firstPaymentId,
                        'source_table'   => 'lead_payments',
                        'source_id'      => $firstPaymentId,
                        'requested_days' => 0,
                        'requested_by'   => $createdBy,
                        'approve_role'   => 'management',
                        'meta_data'      => json_encode([
                            'Project'          => $clientDetails['project_name'] ?? 'N/A',
                            'Client Name'      => $clientDetails['client_name'] ?? 'N/A',
                            'Penalty Amount'   => $penaltyAmount,
                            'Waived Amount'    => $penaltyWaived,
                            'Penalty Paid'     => $penaltyPaid,
                            'Waiver Reason'    => $waiverReason,
                            'Attachment Path'  => $waiverAtt
                        ]),
                        'request_amount' => $penaltyWaived
                    ]);
                }
            }

            $this->db->commit();

            // Build result message
            $msgParts = [];
            if ($settledDues > 0) {
                $settledTotal = array_sum(array_column($settlementDetails, 'settled'));
                $msgParts[] = $settledDues . ' outstanding due(s) settled (Tk. ' . number_format($settledTotal, 2) . ')';
            }
            if ($newInstallments > 0) {
                $msgParts[] = $newInstallments . ' new EMI installment(s) recorded';
            }
            if ($totalServiceFee > 0) {
                $msgParts[] = 'Service fee: Tk. ' . number_format($totalServiceFee, 2);
            }
            if ($bulkEmiDiscount > 0) {
                $msgParts[] = 'Bulk EMI discount: Tk. ' . number_format($bulkEmiDiscount, 2) . ' (applied after verification)';
            }

            return [
                'success'             => true,
                'message'             => 'EMI payment processed! ' . implode('. ', $msgParts) . '.',
                'payment_id'          => $firstPaymentId,
                'service_fee'         => $totalServiceFee,
                'land_share_amount'   => 0,
                'bulk_emi_discount'   => $bulkEmiDiscount,
                'settled_dues'        => $settledDues,
                'new_installments'    => $newInstallments,
                'settlement_details'  => $settlementDetails
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
    }

    public function updateTransactionWithEMI($transactionData, $emiScheduleData = null)
    {
        error_log("=== MODEL: updateTransactionWithEMI START ===");
        error_log("Transaction Data: " . print_r($transactionData, true));
        error_log("EMI Schedule Data: " . print_r($emiScheduleData, true));

        $this->db->begin_transaction();

        try {
            $oldTransaction = $this->getTransactionById($transactionData['payment_id']);
            if (!$oldTransaction) {
                throw new Exception("Transaction not found");
            }

            $isManagementRole = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 33, 34, 35, 38]) || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'management') !== false || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'admin') !== false;
            $isEditableByManagement = $isManagementRole && in_array($oldTransaction['payment_type'] ?? '', ['development_charge', 'land_share', 'shop_service_charge', 'emi', 'booking_money', 'registration', 'mutation_cost', 'others']);

            if (($oldTransaction['verification_status'] ?? '') === 'verified' && !$isEditableByManagement) {
                throw new Exception("Verified transactions cannot be edited. Please contact Accounts.");
            }

            $finalAmount = (float) $transactionData['amount'];
            $finalServiceFee = 0;
            $landShareAmount = 0;

            if (in_array($transactionData['payment_type'], ['land_share', 'booking_money', 'registration', 'mutation_cost'])) {
                $landShareAmount = $finalAmount;
            }

            if ($transactionData['payment_type'] === 'emi') {
                $summary = $this->getPaymentSummaryByBookingId($oldTransaction['booking_id']);
                $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
                $finalServiceFee = ($serviceChargeRate > 0) ? round($finalAmount * ($serviceChargeRate / 100)) : round($finalAmount * 0.10);
            }

            // Encode attachment paths
            $attachmentPathsJson = null;
            if (!empty($transactionData['attachment_paths'])) {
                $attachmentPathsJson = is_array($transactionData['attachment_paths'])
                    ? json_encode($transactionData['attachment_paths'])
                    : $transactionData['attachment_paths'];
            }

            $stmt = $this->db->prepare("
                UPDATE lead_payments SET
                    payment_type = ?,
                    other_purpose = ?,
                    amount = ?,
                    land_share_amount = ?,
                    service_fee = ?,
                    due_emi_amount = ?,
                    payment_method = ?,
                    payment_status = ?,
                    payment_date = ?,
                    transaction_ref = ?,
                    bank_name = ?,
                    bank_id = ?,
                    bank_account_no = ?,
                    cheque_no = ?,
                    online_type = ?,
                    attachment_paths = ?,
                    handover_to = COALESCE(?, handover_to),
                    handover_status = COALESCE(?, handover_status),
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            // Extract to plain variables
            $u_paymentType  =         $transactionData['payment_type'];
            $u_otherPurpose = ($u_paymentType === 'others' && !empty($transactionData['other_purpose'])) ? trim($transactionData['other_purpose']) : null;
            $u_amount       = (float) $finalAmount;
            $u_landShare    = (float) $landShareAmount;
            $u_serviceFee   = (float) $finalServiceFee;
            $u_dueEmiAmt    = ($u_paymentType === 'emi' && isset($emiScheduleData['due_emi_amount'])) ? (float) $emiScheduleData['due_emi_amount'] : 0.0;
            $u_method       =         $transactionData['payment_method']   ?? 'cash';
            $u_status       =         $transactionData['payment_status']   ?? 'pending';
            $u_date         =         $transactionData['payment_date']     ?? date('Y-m-d');
            $u_txRef        =         $transactionData['transaction_ref']  ?? '';
            $u_bankName     =         $transactionData['bank_name']        ?? '';
            $u_bankId       = !empty($transactionData['bank_id']) ? (int)$transactionData['bank_id'] : null;
            $u_bankAccNo    =         $transactionData['bank_account_no']  ?? '';
            $u_chequeNo     =         $transactionData['cheque_no']        ?? '';
            $u_onlineType   =         $transactionData['online_type']      ?? '';
            $u_paymentId    = (int)   $transactionData['payment_id'];
            $u_updatedBy    =         !empty($transactionData['updated_by']) ? (int)$transactionData['updated_by'] : ($_SESSION['user']['id'] ?? null);
            
            // Only update handover if it actually changed
            $u_handoverTo = null;
            $u_handoverStat = null;
            if (!empty($transactionData['handover_to'])) {
                $newHandoverTo = (int)$transactionData['handover_to'];
                $oldHandoverTo = !empty($oldTransaction['handover_to']) ? (int)$oldTransaction['handover_to'] : 0;
                
                if ($newHandoverTo !== $oldHandoverTo) {
                    $u_handoverTo = $newHandoverTo;
                    $u_handoverStat = 'pending';
                }
            }

            $stmt->bind_param(
                "ssddddsssssissssisii",
                $u_paymentType,
                $u_otherPurpose,
                $u_amount,
                $u_landShare,
                $u_serviceFee,
                $u_dueEmiAmt,
                $u_method,
                $u_status,
                $u_date,
                $u_txRef,
                $u_bankName,
                $u_bankId,
                $u_bankAccNo,
                $u_chequeNo,
                $u_onlineType,
                $attachmentPathsJson,
                $u_handoverTo,
                $u_handoverStat,
                $u_updatedBy,
                $u_paymentId
            );

            if (!$stmt->execute()) {
                throw new Exception("Transaction update failed: " . $stmt->error);
            }

            error_log("Payment updated successfully. Land share amount: " . $landShareAmount);

            if ($transactionData['payment_type'] === 'emi' && $emiScheduleData) {
                $checkStmt = $this->db->prepare("
                    SELECT id FROM emi_schedules WHERE lead_payment_id = ?
                ");
                $checkStmt->bind_param("i", $transactionData['payment_id']);
                $checkStmt->execute();
                $existingSchedule = $checkStmt->get_result()->fetch_assoc();

                $status = $transactionData['payment_status'] === 'paid' ? 'paid' : ($transactionData['payment_status'] === 'partially_paid' ? 'partially_paid' : 'pending');

                if ($existingSchedule) {
                    $emiStmt = $this->db->prepare("
                        UPDATE emi_schedules SET
                            emi_amount = ?,
                            service_fee = ?,
                            due_emi_amount = ?,
                            due_date = ?,
                            status = ?
                        WHERE lead_payment_id = ?
                    ");

                    $emiStmt->bind_param(
                        "dddssi",
                        $finalAmount,
                        $finalServiceFee,
                        $emiScheduleData['due_emi_amount'],
                        $transactionData['payment_date'],
                        $status,
                        $transactionData['payment_id']
                    );

                    if (!$emiStmt->execute()) {
                        throw new Exception("EMI schedule update failed: " . $emiStmt->error);
                    }
                } else {
                    $nextEmiNo = $this->getNextAvailableEmiNo($transactionData['booking_id']);

                    $emiStmt = $this->db->prepare("
                        INSERT INTO emi_schedules (
                            lead_booking_id, emi_no, emi_amount, service_fee,
                            due_emi_amount, due_date, status, lead_payment_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $emiStmt->bind_param(
                        "iidddssi",
                        $transactionData['booking_id'],
                        $nextEmiNo,
                        $finalAmount,
                        $finalServiceFee,
                        $emiScheduleData['due_emi_amount'],
                        $transactionData['payment_date'],
                        $status,
                        $transactionData['payment_id']
                    );

                    if (!$emiStmt->execute()) {
                        throw new Exception("EMI schedule insert failed: " . $emiStmt->error);
                    }
                }
            } elseif ($transactionData['payment_type'] !== 'emi') {
                $deleteEmiStmt = $this->db->prepare("
                    DELETE FROM emi_schedules WHERE lead_payment_id = ?
                ");
                $deleteEmiStmt->bind_param("i", $transactionData['payment_id']);
                $deleteEmiStmt->execute();
            }

            // If handover was initiated/changed, create the Approval Request
            if (!empty($u_handoverTo) && $u_handoverStat === 'pending') {
                if (!class_exists('ApprovalRequest')) {
                    require_once __DIR__ . '/ApprovalRequest.php';
                }
                $approvalModel = new ApprovalRequest($this->db);
                $approvalModel->create([
                    'department' => 'Accounts',
                    'request_type' => 'Cash In Handover',
                    'title' => 'Handover Approval Request for Payment #' . $u_paymentId,
                    'source_table' => 'lead_payments',
                    'source_id' => $u_paymentId,
                    'requested_days' => 0,
                    'requested_by' => $transactionData['created_by'] ?? ($_SESSION['user']['id'] ?? 1),
                    'approve_role' => 'management',
                    'meta_data' => json_encode([
                        'Project' => $clientDetails['project_name'] ?? 'N/A',
                        'Unit' => $clientDetails['unit_name'] ?? 'N/A',
                        'Amount' => $u_amount,
                        'Payment Type' => ucfirst(str_replace('_', ' ', $u_paymentType))
                    ]),
                    'request_amount' => $u_amount
                ]);
            }

            $this->db->commit();
            error_log("=== MODEL: Transaction committed successfully ===");

            return [
                'success' => true,
                'message' => 'Transaction updated successfully!' .
                    ($finalServiceFee > 0 ? ' Service fee: Tk. ' . number_format($finalServiceFee, 2) : '')
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("=== MODEL: ERROR - " . $e->getMessage() . " ===");
            return [
                'success' => false,
                'message' => 'Transaction update failed: ' . $e->getMessage()
            ];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // CED Dashboard — Project Filter & Flat Statistics
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get all projects that have at least one flat (for CED filter dropdown)
     */
    public function getProjectsForCED()
    {
        $sql = $this->db->prepare("
            SELECT p.id, p.name
            FROM projects p
            WHERE p.deleted_at IS NULL
            ORDER BY p.name ASC
        ");

        if (!$sql) {
            error_log("getProjectsForCED prepare failed: " . $this->db->error);
            return [];
        }

        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get flat statistics for a specific project
     * Returns: total_flats, booked_flats, available_flats, sold_flats
     */
    public function getProjectFlatStats($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_flats,
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM lead_bookings lb 
                    WHERE lb.flat_id = f.id 
                      AND lb.deleted_at IS NULL 
                      AND lb.booking_status IN ('completed', 'booked') 
                      AND lb.id NOT IN (SELECT booking_id FROM client_refund_requests WHERE status IN ('pending', 'approved', 'refunded'))
                ) THEN 1 ELSE 0 END) as booked_flats,
                SUM(CASE WHEN NOT EXISTS (
                    SELECT 1 FROM lead_bookings lb 
                    WHERE lb.flat_id = f.id 
                      AND lb.deleted_at IS NULL 
                      AND lb.booking_status IN ('completed', 'booked') 
                      AND lb.id NOT IN (SELECT booking_id FROM client_refund_requests WHERE status IN ('pending', 'approved', 'refunded'))
                ) AND (f.status != 'sold') THEN 1 ELSE 0 END) as available_flats,
                SUM(CASE WHEN f.status = 'sold' THEN 1 ELSE 0 END) as sold_flats
            FROM flats f
            WHERE f.project_id = ?
            AND f.deleted_at IS NULL
        ");

        if (!$stmt) {
            error_log("getProjectFlatStats prepare failed: " . $this->db->error);
            return ['total_flats' => 0, 'booked_flats' => 0, 'available_flats' => 0, 'sold_flats' => 0];
        }

        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : ['total_flats' => 0, 'booked_flats' => 0, 'available_flats' => 0, 'sold_flats' => 0];
    }

    /**
     * Get booked flats list for a project (with client info)
     */
    public function getBookedFlatsByProject($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT
                f.id as flat_id,
                f.flat_name,
                f.floor_no,
                f.sqft,
                f.price_value,
                f.status as flat_status,
                lb.id as booking_id,
                lb.booking_date,
                lb.agreement_value,
                lb.booking_status,
                lb.landshare_status,
                COALESCE(NULLIF(TRIM(l.full_name), ''), NULLIF(TRIM(l.lead_name), '')) as client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), NULLIF(TRIM(l.alt_phone_number), ''), NULLIF(TRIM(l.`2nd_owner_phone`), '')) as client_phone,
                COALESCE(
                    (SELECT SUM(lp.amount)
                     FROM lead_payments lp
                     WHERE lp.is_trashed = 0 AND lp.booking_id = lb.id
                     AND lp.payment_status IN ('paid','partially_paid')), 0
                ) as total_paid,
                COALESCE(
                    (SELECT u.full_name FROM users u WHERE u.id = COALESCE(NULLIF(lb.sales_by_id, 0), NULLIF(l.assigned_id, 0), NULLIF(lb.created_by, 0)) LIMIT 1),
                    (SELECT GROUP_CONCAT(DISTINCT u_agent.full_name SEPARATOR ', ')
                     FROM lead_payments lp_sub
                     INNER JOIN users u_agent ON u_agent.id = lp_sub.created_by
                     WHERE lp_sub.booking_id = lb.id AND lp_sub.payment_type = 'booking_money'
                    )
                ) AS agent_name
            FROM flats f
            INNER JOIN lead_bookings lb ON lb.flat_id = f.id 
                AND lb.deleted_at IS NULL
                AND lb.booking_status NOT IN ('refunded', 'cancelled')
                AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
            INNER JOIN leads l ON l.id = lb.lead_id
            WHERE f.project_id = ?
            AND f.deleted_at IS NULL
            ORDER BY f.floor_no ASC, f.flat_name ASC
        ");

        if (!$stmt) {
            error_log("getBookedFlatsByProject prepare failed: " . $this->db->error);
            return [];
        }

        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get all conflicted / double-booked units across active bookings
     * Returns associative array keyed by lowercase project_name||flat_name
     */
    public function getConflictedUnits()
    {
        $sql = "
            SELECT 
                lb.project_id, 
                p.name as project_name,
                LOWER(TRIM(f.flat_name)) as unit_norm, 
                f.flat_name,
                COUNT(DISTINCT lb.id) as booking_count, 
                GROUP_CONCAT(DISTINCT l.full_name SEPARATOR ', ') as client_names
            FROM lead_bookings lb
            JOIN flats f ON lb.flat_id = f.id
            JOIN projects p ON lb.project_id = p.id
            JOIN leads l ON lb.lead_id = l.id
            WHERE lb.deleted_at IS NULL 
              AND lb.booking_status NOT IN ('refunded', 'cancelled')
              AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
              AND lb.id NOT IN (
                  SELECT crr.booking_id FROM client_refund_requests crr 
                  WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded')
              )
            GROUP BY lb.project_id, LOWER(TRIM(f.flat_name))
            HAVING COUNT(DISTINCT lb.id) > 1
        ";
        $res = $this->db->query($sql);
        $conflicts = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $key = strtolower(trim($row['project_name'])) . '||' . strtolower(trim($row['flat_name']));
                $conflicts[$key] = $row;
            }
        }
        return $conflicts;
    }

    /**
     * Get full details of all conflicted / double-booked bookings grouped by unit
     */
    public function getConflictedBookingsDetail()
    {
        $sql = "
            SELECT 
                lb.id as booking_id,
                lb.project_id, 
                p.name as project_name,
                f.id as flat_id,
                f.flat_name,
                f.floor_no,
                l.id as lead_id,
                COALESCE(NULLIF(TRIM(l.full_name), ''), NULLIF(TRIM(l.lead_name), '')) as client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), NULLIF(TRIM(l.alt_phone_number), ''), NULLIF(TRIM(l.`2nd_owner_phone`), '')) as phone,
                l.phone_number,
                l.email,
                lb.booking_date,
                lb.agreement_value,
                lb.booking_status,
                lb.landshare_status,
                (SELECT COALESCE(SUM(lp.amount), 0) FROM lead_payments lp WHERE lp.booking_id = lb.id) as total_paid,
                (SELECT u.full_name FROM users u WHERE u.id = lb.sales_by_id LIMIT 1) as agent_name
            FROM lead_bookings lb
            JOIN flats f ON lb.flat_id = f.id
            JOIN projects p ON lb.project_id = p.id
            JOIN leads l ON lb.lead_id = l.id
            WHERE lb.deleted_at IS NULL 
              AND lb.booking_status NOT IN ('refunded', 'cancelled')
              AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
              AND lb.id NOT IN (
                  SELECT crr.booking_id FROM client_refund_requests crr 
                  WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded')
              )
              AND (lb.project_id, LOWER(TRIM(f.flat_name))) IN (
                  SELECT lb2.project_id, LOWER(TRIM(f2.flat_name))
                  FROM lead_bookings lb2
                  JOIN flats f2 ON lb2.flat_id = f2.id
                  WHERE lb2.deleted_at IS NULL
                    AND lb2.booking_status NOT IN ('refunded', 'cancelled')
                    AND (lb2.landshare_status IS NULL OR lb2.landshare_status != 'cancelled')
                    AND lb2.id NOT IN (
                        SELECT crr2.booking_id FROM client_refund_requests crr2 
                        WHERE crr2.booking_id IS NOT NULL AND crr2.status IN ('pending', 'approved', 'refunded')
                    )
                  GROUP BY lb2.project_id, LOWER(TRIM(f2.flat_name))
                  HAVING COUNT(DISTINCT lb2.id) > 1
              )
            ORDER BY p.name ASC, f.flat_name ASC, lb.booking_date ASC, lb.id ASC
        ";
        $res = $this->db->query($sql);
        $list = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $unitKey = strtolower(trim($r['project_name'])) . '||' . strtolower(trim($r['flat_name']));
                $list[$unitKey][] = $r;
            }
        }
        return $list;
    }

    /**
     * Get available flats list for a project
     */
    public function getAvailableFlatsByProject($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT
                f.id as flat_id,
                f.flat_name,
                f.floor_no,
                f.sqft,
                f.price_value,
                f.status as flat_status
            FROM flats f
            WHERE f.project_id = ?
            AND f.deleted_at IS NULL
            AND (f.status != 'sold')
            AND NOT EXISTS (
                SELECT 1 FROM lead_bookings lb 
                WHERE lb.flat_id = f.id 
                  AND lb.deleted_at IS NULL 
                  AND lb.booking_status IN ('completed', 'booked') 
                  AND lb.id NOT IN (SELECT booking_id FROM client_refund_requests WHERE status IN ('pending', 'approved', 'refunded'))
            )
            ORDER BY f.floor_no ASC, f.flat_name ASC
        ");

        if (!$stmt) {
            error_log("getAvailableFlatsByProject prepare failed: " . $this->db->error);
            return [];
        }

        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get accurate collection statistics for the CED dashboard
     */
    public function getDashboardCollectionStats($projectId = null)
    {
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');

        $stats = [
            'this_month_collection' => 0,
            'monthly_data' => [],
            'weekly_data' => array_fill(0, 7, 0)
        ];

        $joinLb = "";
        $whereProject = "";
        $projectParams = [];
        $projectTypes = "";

        if (!empty($projectId)) {
            $joinLb = " INNER JOIN lead_bookings lb ON lp.booking_id = lb.id ";
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereProject = " AND lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $projectParams[] = $pId;
                        $projectTypes .= "i";
                    }
                }
            } else {
                $whereProject = " AND lb.project_id = ?";
                $projectParams[] = (int)$projectId;
                $projectTypes .= "i";
            }
        }

        // This Month Collection
        $sql1 = "
            SELECT COALESCE(SUM(lp.amount), 0) as total
            FROM lead_payments lp
            {$joinLb}
            WHERE lp.payment_status IN ('paid', 'partially_paid')
            AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
            AND lp.is_trashed = 0
            AND COALESCE(lp.is_emi_discount, 0) = 0
            AND lp.payment_date BETWEEN ? AND ?
            {$whereProject}
        ";
        $stmt = $this->db->prepare($sql1);
        if ($stmt) {
            $types1 = "ss" . $projectTypes;
            $params1 = array_merge([$currentMonthStart, $currentMonthEnd], $projectParams);
            $stmt->bind_param($types1, ...$params1);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $stats['this_month_collection'] = floatval($row['total']);
            }
            $stmt->close();
        }

        // Monthly Data (last 6 months)
        $sixMonthsAgo = date('Y-m-01', strtotime('-5 months'));
        $sql2 = "
            SELECT 
                DATE_FORMAT(lp.payment_date, '%Y-%m') as month,
                SUM(lp.amount) as total
            FROM lead_payments lp
            {$joinLb}
            WHERE lp.payment_status IN ('paid', 'partially_paid')
            AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
            AND lp.is_trashed = 0
            AND COALESCE(lp.is_emi_discount, 0) = 0
            AND lp.payment_date >= ?
            {$whereProject}
            GROUP BY DATE_FORMAT(lp.payment_date, '%Y-%m')
        ";
        $stmt2 = $this->db->prepare($sql2);
        if ($stmt2) {
            $types2 = "s" . $projectTypes;
            $params2 = array_merge([$sixMonthsAgo], $projectParams);
            $stmt2->bind_param($types2, ...$params2);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2) {
                while ($row = $res2->fetch_assoc()) {
                    $stats['monthly_data'][$row['month']] = floatval($row['total']);
                }
            }
            $stmt2->close();
        }

        // Weekly Data (last 7 days, 0 is today, 6 is 6 days ago)
        $sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));
        $sql3 = "
            SELECT 
                DATE(lp.payment_date) as day_date,
                SUM(lp.amount) as total
            FROM lead_payments lp
            {$joinLb}
            WHERE lp.payment_status IN ('paid', 'partially_paid')
            AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
            AND lp.is_trashed = 0
            AND COALESCE(lp.is_emi_discount, 0) = 0
            AND lp.payment_date >= ?
            {$whereProject}
            GROUP BY DATE(lp.payment_date)
        ";
        $stmt3 = $this->db->prepare($sql3);
        if ($stmt3) {
            $types3 = "s" . $projectTypes;
            $params3 = array_merge([$sevenDaysAgo], $projectParams);
            $stmt3->bind_param($types3, ...$params3);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            if ($res3) {
                while ($row = $res3->fetch_assoc()) {
                    $daysSince = (strtotime(date('Y-m-d')) - strtotime($row['day_date'])) / 86400;
                    $idx = max(0, min(6, 6 - (int)floor($daysSince)));
                    $stats['weekly_data'][$idx] += floatval($row['total']);
                }
            }
            $stmt3->close();
        }

        return $stats;
    }

    /**
     * Get Payment Status Counts for Dashboard Pie Chart [On Track, Pending, Overdue, Completed]
     *
     * @param int|array|null $projectId
     * @return array [on_track, pending, overdue, completed]
     */
    public function getPaymentStatusCounts($projectId = null)
    {
        $whereProject = "";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereProject = " AND lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= "i";
                    }
                }
            } else {
                $whereProject = " AND lb.project_id = ?";
                $params[] = (int)$projectId;
                $types .= "i";
            }
        }

        $sql = "
            SELECT 
                COUNT(CASE WHEN calc.overall_due <= 0 THEN 1 END) AS completed,
                COUNT(CASE WHEN calc.overall_due > 0 AND calc.overdue_emi_amount > 0 THEN 1 END) AS overdue,
                COUNT(CASE WHEN calc.overall_due > 0 AND calc.overdue_emi_amount <= 0 AND calc.total_paid >= (calc.total_price * 0.5) THEN 1 END) AS on_track,
                COUNT(CASE WHEN calc.overall_due > 0 AND calc.overdue_emi_amount <= 0 AND calc.total_paid < (calc.total_price * 0.5) THEN 1 END) AS pending
            FROM (
                SELECT 
                    lb.id,
                    COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) AS total_price,
                    COALESCE(p_agg.total_paid, 0) AS total_paid,
                    COALESCE(p_agg.overdue_emi_amount, 0) AS overdue_emi_amount,
                    GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - COALESCE(p_agg.total_paid, 0))) AS overall_due
                FROM lead_bookings lb
                LEFT JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN (
                    SELECT 
                        lp.booking_id,
                        SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                      AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS total_paid,
                        SUM(CASE WHEN lp.payment_type = 'emi' AND lp.due_emi_amount > 0 AND lp.verification_status = 'verified' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') THEN lp.due_emi_amount ELSE 0 END) AS overdue_emi_amount
                    FROM lead_payments lp
                    WHERE lp.is_trashed = 0
                    GROUP BY lp.booking_id
                ) p_agg ON p_agg.booking_id = lb.id
                WHERE lb.deleted_at IS NULL
                  AND lb.booking_status NOT IN ('cancelled', 'refunded')
                  AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                  AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                  $whereProject
            ) calc
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    return [
                        (int)$row['on_track'],
                        (int)$row['pending'],
                        (int)$row['overdue'],
                        (int)$row['completed']
                    ];
                }
            }
        } else {
            $res = $this->db->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return [
                    (int)$row['on_track'],
                    (int)$row['pending'],
                    (int)$row['overdue'],
                    (int)$row['completed']
                ];
            }
        }

        return [0, 0, 0, 0];
    }

    public function saveTransferNote($bookingId, $transferDate, $transferNote, $transferStatus, $updatedBy)
    {
        $stmt = $this->db->prepare("
            UPDATE lead_bookings 
            SET transfer_date = ?, 
                transfer_note = ?, 
                transfer_status = ?, 
                transfer_updated_by = ?
            WHERE id = ?
        ");
        
        if (!$stmt) {
            error_log("Prepare statement failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param("ssiii", $transferDate, $transferNote, $transferStatus, $updatedBy, $bookingId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ─── CSV BULK IMPORT METHODS ──────────────────────────────────────────
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Resolve a booking by client phone, project name, and flat name.
     * Returns booking details or null if not found.
     * Handles: phone in both `phone` and `phone_number` columns,
     * flat names with/without floor suffix (e.g. "6 B - FLOOR 6" → "6 B").
     */
    public function resolveBookingByProjectAndFlat($clientPhone, $projectName, $flatName)
    {
        // Normalize phone: handle scientific notation e.g. "6.143E+10"
        if (is_string($clientPhone) && preg_match('/E\+/i', $clientPhone)) {
            $clientPhone = sprintf('%.0f', (float)$clientPhone);
        }
        $phone = preg_replace('/[^0-9]/', '', (string)$clientPhone);
        
        // Build multiple phone variants for flexible matching
        $phoneVariants = [$phone];
        $shortPhone = $phone;
        if (str_starts_with($shortPhone, '880')) {
            $shortPhone = substr($shortPhone, 3);
            $phoneVariants[] = $shortPhone;           // 1875293008
            $phoneVariants[] = '0' . $shortPhone;     // 01875293008
        }
        if (str_starts_with($shortPhone, '0')) {
            $shortPhone = substr($shortPhone, 1);
            $phoneVariants[] = $shortPhone;
        }
        // Use the last 10 digits for robust matching
        if (strlen($shortPhone) > 10) {
            $shortPhone = substr($shortPhone, -10);
        }
        $phoneLike = '%' . $shortPhone;

        // Sanitize project name to remove NBSP, tabs, and carriage returns
        $projectName = trim(str_replace(["\xC2\xA0", "\xA0", "\r", "\n", "\t"], ' ', $projectName));

        // Auto-fix Excel date conversions like "Jan-36" to "1-36"
        $months = ['Jan' => '1', 'Feb' => '2', 'Mar' => '3', 'Apr' => '4', 'May' => '5', 'Jun' => '6', 'Jul' => '7', 'Aug' => '8', 'Sep' => '9', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'];
        if (preg_match('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s*[-–]\s*(\d+)$/i', $flatName, $m)) {
            $month = ucfirst(strtolower($m[1]));
            if (isset($months[$month])) {
                $flatName = $months[$month] . '-' . $m[2];
            }
        }

        // Normalize flat name: strip floor suffix like " - FLOOR 6", " - Floor 6"
        $cleanFlatName = trim(str_replace(["\xC2\xA0", "\xA0", "\r", "\n", "\t"], ' ', $flatName));
        $flatNameBase = $cleanFlatName;
        if (preg_match('/^(.+?)\s*[-–]\s*(FLOOR|Floor|floor)\s*\d+/i', $cleanFlatName, $fMatch)) {
            $flatNameBase = trim($fMatch[1]);
        }

        // Try exact flat name first, then base name
        $flatNamesToTry = [$cleanFlatName];
        if ($flatNameBase !== $cleanFlatName) {
            $flatNamesToTry[] = $flatNameBase;
        }

        foreach ($flatNamesToTry as $tryFlatName) {
            // Flexible flat match (ignoring spaces and hyphens e.g. "7 A" vs "7-A")
            $compactTryFlat = str_replace([' ', '-'], '', strtolower($tryFlatName));

            $stmt = $this->db->prepare("
                SELECT 
                    lb.id AS booking_id,
                    lb.project_id,
                    lb.flat_id,
                    lb.lead_id,
                    l.full_name AS client_name,
                    COALESCE(l.phone, l.phone_number) AS client_phone,
                    p.name AS project_name,
                    f.flat_name,
                    f.floor_no,
                    lb.booking_status
                FROM lead_bookings lb
                INNER JOIN leads l ON l.id = lb.lead_id
                INNER JOIN projects p ON p.id = lb.project_id
                INNER JOIN flats f ON f.id = lb.flat_id
                WHERE lb.deleted_at IS NULL
                  AND l.deleted_at IS NULL
                  AND LOWER(REPLACE(p.name, ' ', '')) = LOWER(REPLACE(?, ' ', ''))
                  AND (
                      LOWER(REPLACE(REPLACE(f.flat_name, ' ', ''), '''', '')) = LOWER(REPLACE(REPLACE(?, ' ', ''), '''', ''))
                      OR REPLACE(REPLACE(LOWER(REPLACE(f.flat_name, ' ', '')), ' ', ''), '-', '') = ?
                      OR FIND_IN_SET(LOWER(REPLACE(REPLACE(?, ' ', ''), '''', '')), LOWER(REPLACE(REPLACE(f.flat_name, ' ', ''), '''', ''))) > 0
                  )
                  AND (
                      REGEXP_REPLACE(COALESCE(l.phone,''), '[^0-9]', '') LIKE ?
                      OR REGEXP_REPLACE(COALESCE(l.phone_number,''), '[^0-9]', '') LIKE ?
                  )
                ORDER BY lb.created_at DESC
                LIMIT 1
            ");

            if (!$stmt) {
                error_log("resolveBookingByProjectAndFlat prepare failed: " . $this->db->error);
                return null;
            }

            $stmt->bind_param("ssssss", $projectName, $tryFlatName, $compactTryFlat, $tryFlatName, $phoneLike, $phoneLike);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($row) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Diagnose why a booking was not found for the given details.
     * Returns an array of specific error messages.
     */
    public function diagnoseBookingFailure($clientName, $clientPhone, $projectName, $flatName)
    {
        $errors = [];
        
        // Normalize phone
        if (is_string($clientPhone) && preg_match('/E\+/i', $clientPhone)) {
            $clientPhone = sprintf('%.0f', (float)$clientPhone);
        }
        $phone = preg_replace('/[^0-9]/', '', (string)$clientPhone);
        $shortPhone = $phone;
        if (str_starts_with($shortPhone, '880')) $shortPhone = substr($shortPhone, 3);
        if (str_starts_with($shortPhone, '0')) $shortPhone = substr($shortPhone, 1);
        if (strlen($shortPhone) > 10) $shortPhone = substr($shortPhone, -10);
        $phoneLike = '%' . $shortPhone;
        // Sanitize project name to remove NBSP, tabs, and carriage returns
        $projectName = trim(str_replace(["\xC2\xA0", "\xA0", "\r", "\n", "\t"], ' ', $projectName));

        // Auto-fix Excel date conversions like "Jan-36" to "1-36"
        $months = ['Jan' => '1', 'Feb' => '2', 'Mar' => '3', 'Apr' => '4', 'May' => '5', 'Jun' => '6', 'Jul' => '7', 'Aug' => '8', 'Sep' => '9', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'];
        if (preg_match('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s*[-–]\s*(\d+)$/i', $flatName, $m)) {
            $month = ucfirst(strtolower($m[1]));
            if (isset($months[$month])) {
                $flatName = $months[$month] . '-' . $m[2];
            }
        }

        // Normalize flat name
        $cleanFlatName = trim(str_replace(["\xC2\xA0", "\xA0", "\r", "\n", "\t"], ' ', $flatName));
        $flatNameBase = $cleanFlatName;
        if (preg_match('/^(.+?)\s*[-–]\s*(FLOOR|Floor|floor)\s*\d+/i', $cleanFlatName, $fMatch)) {
            $flatNameBase = trim($fMatch[1]);
        }
        // 1. Check if client exists by phone
        $stmt = $this->db->prepare("
            SELECT id, full_name, phone, phone_number 
            FROM leads 
            WHERE deleted_at IS NULL 
              AND (
                  REGEXP_REPLACE(COALESCE(phone,''), '[^0-9]', '') LIKE ?
                  OR REGEXP_REPLACE(COALESCE(phone_number,''), '[^0-9]', '') LIKE ?
              )
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("ss", $phoneLike, $phoneLike);
            $stmt->execute();
            $leadResult = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$leadResult) {
                $errors[] = "Client not found for phone '{$clientPhone}'";
            } else {
                // Client found by phone, check if name matches roughly
                $dbNameClean = preg_replace('/[^a-z0-9]/', '', strtolower($leadResult['full_name']));
                $csvNameClean = preg_replace('/[^a-z0-9]/', '', strtolower($clientName));

                if (!empty($csvNameClean) && !empty($dbNameClean)) {
                    $isMatch = ($dbNameClean === $csvNameClean) ||
                               (strlen($csvNameClean) >= 3 && strpos($dbNameClean, $csvNameClean) !== false) ||
                               (strlen($dbNameClean) >= 3 && strpos($csvNameClean, $dbNameClean) !== false);

                    if (!$isMatch) {
                        $dbTokens = array_filter(explode(' ', preg_replace('/[^a-z0-9]/', ' ', strtolower($leadResult['full_name']))));
                        $csvTokens = array_filter(explode(' ', preg_replace('/[^a-z0-9]/', ' ', strtolower($clientName))));
                        $commonTokens = array_intersect($dbTokens, $csvTokens);
                        if (count($commonTokens) == 0) {
                            $errors[] = "Client name '{$clientName}' doesn't match with DB name '{$leadResult['full_name']}' for phone '{$clientPhone}'";
                        }
                    }
                }
            }
        }
        
        // 2. Check if project and flat exist
        $stmt = $this->db->prepare("
            SELECT f.id, p.id as project_id
            FROM flats f
            INNER JOIN projects p ON p.id = f.project_id
            WHERE LOWER(REPLACE(p.name, ' ', '')) = LOWER(REPLACE(?, ' ', ''))
              AND (
                  LOWER(REPLACE(REPLACE(f.flat_name, ' ', ''), '''', '')) = LOWER(REPLACE(REPLACE(?, ' ', ''), '''', '')) 
                  OR 
                  LOWER(REPLACE(REPLACE(f.flat_name, ' ', ''), '''', '')) = LOWER(REPLACE(REPLACE(?, ' ', ''), '''', ''))
                  OR
                  FIND_IN_SET(LOWER(REPLACE(REPLACE(?, ' ', ''), '''', '')), LOWER(REPLACE(REPLACE(f.flat_name, ' ', ''), '''', ''))) > 0
                  OR
                  FIND_IN_SET(LOWER(REPLACE(REPLACE(?, ' ', ''), '''', '')), LOWER(REPLACE(REPLACE(f.flat_name, ' ', ''), '''', ''))) > 0
              )
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("sssss", $projectName, $cleanFlatName, $flatNameBase, $cleanFlatName, $flatNameBase);
            $stmt->execute();
            $flatResult = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$flatResult) {
                $errors[] = "Associated flat '{$flatName}' not found in project '{$projectName}'";
            } else {
                // Flat exists. Check if it's booked
                $stmt = $this->db->prepare("
                    SELECT l.id AS lead_id, l.full_name, COALESCE(l.phone, l.phone_number) AS phone
                    FROM lead_bookings lb
                    INNER JOIN leads l ON l.id = lb.lead_id
                    WHERE lb.flat_id = ? AND lb.deleted_at IS NULL AND l.deleted_at IS NULL
                    LIMIT 1
                ");
                if ($stmt) {
                    $stmt->bind_param("i", $flatResult['id']);
                    $stmt->execute();
                    $bookingResult = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    if ($bookingResult) {
                        if (isset($leadResult['id']) && $bookingResult['lead_id'] == $leadResult['id']) {
                            // Valid booking for this client, no error.
                        } else {
                            $errors[] = "Associated flat '{$flatName}' in project '{$projectName}' is booked by another client ('{$bookingResult['full_name']}', phone: '{$bookingResult['phone']}')";
                        }
                    } else {
                        $errors[] = "Associated flat '{$flatName}' in project '{$projectName}' is not currently booked by any client";
                    }
                }
            }
        }
        
        if (empty($errors)) {
            $errors[] = "No valid booking found for phone '{$clientPhone}', project '{$projectName}', flat '{$flatName}'";
        }
        
        return $errors;
    }

    /**
     * Parse and validate a CSV file for bulk payment import.
     * Returns ['valid_rows' => [...], 'errors' => [...], 'summary' => [...]]
     */
    public function parseAndValidateCSV($filePath)
    {
        $validRows = [];
        $errors = [];
        $summary = [
            'total_rows' => 0,
            'valid_count' => 0,
            'error_count' => 0,
            'total_amount' => 0,
            'dev_charge_count' => 0,
            'emi_count' => 0
        ];

        $expectedHeaders = [
            'client_name', 'client_phone', 'project_name', 'flat_name',
            'amount', 'payment_date', 'payment_type', 'payment_method',
            'verification_status', 'transaction_ref',
            'bank_name', 'bank_account_no', 'cheque_no', 'online_type',
            'payment_details', 'bulk_emi_discount', 'emi_due_date', 'emi_no',
            'service_fee'
        ];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $errors[] = ['row' => 0, 'message' => 'CSV file not found or not readable.'];
            return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $errors[] = ['row' => 0, 'message' => 'Failed to open CSV file.'];
            return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
        }

        // Read and validate headers (handle BOM)
        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            $errors[] = ['row' => 0, 'message' => 'CSV file is empty.'];
            return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
        }

        // Remove BOM if present
        $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headerRow[0]);
        $headers = array_map(function ($h) {
            return strtolower(trim($h));
        }, $headerRow);

        // Check required headers
        $requiredHeaders = ['client_name', 'client_phone', 'project_name', 'flat_name',
                            'amount', 'payment_date', 'payment_type', 'payment_method'];
        $missingHeaders = array_diff($requiredHeaders, $headers);
        if (!empty($missingHeaders)) {
            fclose($handle);
            $errors[] = ['row' => 0, 'message' => 'Missing required columns: ' . implode(', ', $missingHeaders)];
            return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
        }

        $rowNum = 1; // 1-based (header is row 0)
        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            $summary['total_rows']++;

            // Skip completely empty rows
            if (count(array_filter($data, fn($v) => trim($v) !== '')) === 0) {
                continue;
            }

            // Map CSV columns to associative array
            $row = [];
            foreach ($headers as $idx => $header) {
                $row[$header] = isset($data[$idx]) ? trim($data[$idx]) : '';
            }

            $rowErrors = [];

            // === REQUIRED FIELD VALIDATION ===
            if (empty($row['client_name'])) $rowErrors[] = 'client_name is required';
            if (empty($row['client_phone'])) $rowErrors[] = 'client_phone is required';
            if (empty($row['project_name'])) $rowErrors[] = 'project_name is required';
            if (empty($row['flat_name'])) $rowErrors[] = 'flat_name is required';

            // Strip thousand separators (commas) from amount before validation
            if (!empty($row['amount'])) {
                $row['amount'] = str_replace(',', '', $row['amount']);
            }
            if (empty($row['amount']) || !is_numeric($row['amount']) || (float)$row['amount'] <= 0) {
                $rowErrors[] = 'amount must be a positive number';
            }

            // Date validation (supports Y-m-d, d/m/Y, d-m-Y)
            $parsedDate = null;
            if (!empty($row['payment_date'])) {
                $parsedDate = $this->parseCSVDate($row['payment_date']);
                if (!$parsedDate) {
                    $rowErrors[] = 'payment_date is invalid (use YYYY-MM-DD or DD/MM/YYYY)';
                }
            } else {
                $rowErrors[] = 'payment_date is required';
            }

            // Payment type validation
            $validPaymentTypes = ['development_charge', 'emi', 'emi_due_settlement'];
            if (empty($row['payment_type']) || !in_array(strtolower($row['payment_type']), $validPaymentTypes)) {
                $rowErrors[] = 'payment_type must be: development_charge, emi, or emi_due_settlement';
            }

            // Payment method validation
            $validMethods = ['cash', 'bank', 'online', 'cheque'];
            if (empty($row['payment_method']) || !in_array(strtolower($row['payment_method']), $validMethods)) {
                $rowErrors[] = 'payment_method must be: cash, bank, online, or cheque';
            }

            // Verification status: optional, defaults to 'pending' if blank
            $verificationStatus = 'pending';
            if (!empty($row['verification_status'])) {
                $vs = strtolower($row['verification_status']);
                if (in_array($vs, ['pending', 'verified', 'rejected'])) {
                    $verificationStatus = $vs;
                }
            }

            // === BOOKING RESOLUTION ===
            $bookingData = null;
            if (empty($rowErrors)) {
                $bookingData = $this->resolveBookingByProjectAndFlat(
                    $row['client_phone'],
                    $row['project_name'],
                    $row['flat_name']
                );
                if (!$bookingData) {
                    $diagnostics = $this->diagnoseBookingFailure(
                        $row['client_name'],
                        $row['client_phone'],
                        $row['project_name'],
                        $row['flat_name']
                    );
                    foreach ($diagnostics as $diagError) {
                        $rowErrors[] = $diagError;
                    }
                } else {
                    // Booking found, verify if the name roughly matches to catch major typos
                    $dbNameClean = preg_replace('/[^a-z0-9]/', '', strtolower($bookingData['client_name']));
                    $csvNameClean = preg_replace('/[^a-z0-9]/', '', strtolower($row['client_name']));

                    if (!empty($csvNameClean) && !empty($dbNameClean)) {
                        $isMatch = ($dbNameClean === $csvNameClean) ||
                                   (strlen($csvNameClean) >= 3 && strpos($dbNameClean, $csvNameClean) !== false) ||
                                   (strlen($dbNameClean) >= 3 && strpos($csvNameClean, $dbNameClean) !== false);

                        if (!$isMatch) {
                            // Check token word overlap (ignoring dots, &, etc.)
                            $dbTokens = array_filter(explode(' ', preg_replace('/[^a-z0-9]/', ' ', strtolower($bookingData['client_name']))));
                            $csvTokens = array_filter(explode(' ', preg_replace('/[^a-z0-9]/', ' ', strtolower($row['client_name']))));
                            $commonTokens = array_intersect($dbTokens, $csvTokens);
                            if (count($commonTokens) == 0) {
                                $rowErrors[] = "Warning/Error: Client name '{$row['client_name']}' in CSV doesn't match booked client name '{$bookingData['client_name']}'";
                            }
                        }
                    }
                }
            }

            // === OPTIONAL FIELD VALIDATION ===
            $bulkDiscount = 0;
            if (!empty($row['bulk_emi_discount'])) {
                if (!is_numeric($row['bulk_emi_discount']) || (float)$row['bulk_emi_discount'] < 0) {
                    $rowErrors[] = 'bulk_emi_discount must be a non-negative number';
                } else {
                    $bulkDiscount = (float)$row['bulk_emi_discount'];
                }
            }

            $emiNo = null;
            if (!empty($row['emi_no'])) {
                if (!is_numeric($row['emi_no']) || (int)$row['emi_no'] <= 0) {
                    $rowErrors[] = 'emi_no must be a positive integer';
                } else {
                    $emiNo = (int)$row['emi_no'];
                }
            }

            $emiDueDate = null;
            if (!empty($row['emi_due_date'])) {
                $emiDueDate = $this->parseCSVDate($row['emi_due_date']);
                if (!$emiDueDate) {
                    $rowErrors[] = 'emi_due_date is invalid (use YYYY-MM-DD or DD/MM/YYYY)';
                }
            }

            // Service fee validation (optional — auto-calc if empty)
            $serviceFee = null;
            if (isset($row['service_fee']) && $row['service_fee'] !== '') {
                if (!is_numeric($row['service_fee']) || (float)$row['service_fee'] < 0) {
                    $rowErrors[] = 'service_fee must be a non-negative number';
                } else {
                    $serviceFee = (float)$row['service_fee'];
                }
            }

            // Bank resolution
            $bankId = null;
            if (!empty($row['bank_name'])) {
                $bankResult = $this->db->query("SELECT id FROM company_bank_accounts WHERE LOWER(bank_name) LIKE '%" . $this->db->real_escape_string(strtolower($row['bank_name'])) . "%' AND status = 'active' LIMIT 1");
                if ($bankResult && $bankRow = $bankResult->fetch_assoc()) {
                    $bankId = (int)$bankRow['id'];
                }
            }

            if (!empty($rowErrors)) {
                $errors[] = [
                    'row' => $rowNum,
                    'data' => $row,
                    'messages' => $rowErrors
                ];
                $summary['error_count']++;
            } else {
                $paymentType = strtolower($row['payment_type']);
                $validRows[] = [
                    'row_num'             => $rowNum,
                    'booking_id'          => $bookingData['booking_id'],
                    'lead_id'             => $bookingData['lead_id'],
                    'project_id'          => $bookingData['project_id'],
                    'flat_id'             => $bookingData['flat_id'],
                    'client_name'         => $row['client_name'],
                    'client_phone'        => $row['client_phone'],
                    'resolved_client'     => $bookingData['client_name'],
                    'project_name'        => $row['project_name'],
                    'flat_name'           => $row['flat_name'],
                    'amount'              => (float)$row['amount'],
                    'payment_date'        => $parsedDate,
                    'payment_type'        => $paymentType,
                    'payment_method'      => strtolower($row['payment_method']),
                    'verification_status' => $verificationStatus,
                    'transaction_ref'     => $row['transaction_ref'] ?? '',
                    'bank_name'           => $row['bank_name'] ?? '',
                    'bank_id'             => $bankId,
                    'bank_account_no'     => $row['bank_account_no'] ?? '',
                    'cheque_no'           => $row['cheque_no'] ?? '',
                    'online_type'         => $row['online_type'] ?? '',
                    'payment_details'     => $row['payment_details'] ?? '',
                    'bulk_emi_discount'   => $bulkDiscount,
                    'emi_due_date'        => $emiDueDate,
                    'emi_no'              => $emiNo,
                    'service_fee'         => $serviceFee
                ];
                $summary['valid_count']++;
                $summary['total_amount'] += (float)$row['amount'];
                if ($paymentType === 'development_charge') $summary['dev_charge_count']++;
                if ($paymentType === 'emi') $summary['emi_count']++;
                if ($paymentType === 'emi_due_settlement') $summary['emi_count']++;
            }
        }

        fclose($handle);
        return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
    }

    /**
     * Parse various date formats from CSV.
     * Supports: Y-m-d, d/m/Y, d-m-Y, m/d/Y
     */
    private function parseCSVDate($dateStr)
    {
        $dateStr = trim($dateStr);
        if (empty($dateStr)) return null;

        $formats = [
            'Y-m-d', 'Y/m/d',
            'd/m/Y', 'd-m-Y', 'm/d/Y', 'm-d-Y',
            'd/m/y', 'd-m-y', 'm/d/y', 'm-d-y',
            'j/n/Y', 'j/n/y', 'j-n-Y', 'j-n-y',
            'n/j/Y', 'n/j/y', 'n-j-Y', 'n-j-y', // Added MM/DD/YYYY without leading zero support
            'd-M-Y', 'd-M-y', 'j-M-Y', 'j-M-y',
            'd M Y', 'd M y', 'j M Y', 'j M y'
        ];

        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $dateStr);
            if ($dt && strtolower($dt->format($fmt)) === strtolower($dateStr)) {
                return $dt->format('Y-m-d');
            }
        }

        // Handle DD/MM/YY or DD-MM-YY explicitly (e.g. 23/07/25 -> 2025-07-23)
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/', $dateStr, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            if (strlen($year) === 2) {
                $year = '20' . $year;
            }
            if (checkdate((int)$month, (int)$day, (int)$year)) {
                return "{$year}-{$month}-{$day}";
            }
        }

        // Try strtotime with slash replacement as fallback
        $ts = strtotime(str_replace('/', '-', $dateStr));
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    /**
     * Process a single validated CSV import row.
     * Inserts directly into lead_payments (and emi_schedules for EMI type).
     * Supports custom verification_status, emi_no, and bypasses sequential validation
     * since this is for historical data import.
     */
    public function processCSVImportRow($rowData, $createdBy = 1, $csvImportLogId = null)
    {
        // Auto-ensure csv_import_log_id column exists in lead_payments table
        try {
            $colCheck = $this->db->query("SHOW COLUMNS FROM lead_payments LIKE 'csv_import_log_id'");
            if ($colCheck && $colCheck->num_rows === 0) {
                $this->db->query("ALTER TABLE lead_payments ADD COLUMN csv_import_log_id INT(11) NULL DEFAULT NULL");
            }
        } catch (Throwable $t) {}

        $this->db->begin_transaction();

        try {
            $bookingId        = (int)$rowData['booking_id'];
            $paymentType      = $rowData['payment_type'];
            $amount           = (float)str_replace(',', '', $rowData['amount']);
            $paymentMethod    = $rowData['payment_method'];
            $paymentStatus    = 'paid'; // Auto-calculated by backend
            $verificationStatus = $rowData['verification_status'];
            $paymentDate      = $rowData['payment_date'];
            $transactionRef   = $rowData['transaction_ref'];
            $bankId           = $rowData['bank_id'];
            $bankName         = $rowData['bank_name'];
            $bankAccountNo    = $rowData['bank_account_no'];
            $chequeNo         = $rowData['cheque_no'];
            $onlineType       = $rowData['online_type'];
            $bulkDiscount     = (float)$rowData['bulk_emi_discount'];

            // Document/attachment from CSV (just filename, stored under uploads/transactions/)
            $attachmentPaths = null;
            $docFilename = trim($rowData['document'] ?? '');
            if (!empty($docFilename)) {
                $attachmentPaths = json_encode(['uploads/transactions/' . $docFilename]);
            }

            $landShareAmount  = 0;
            $dueEmiAmount     = 0;
            $parentPaymentId  = null;
            $settlementType   = null;
            $isSettlement     = ($paymentType === 'emi_due_settlement');

            // Service fee: use CSV value if provided, otherwise auto-calculate
            $serviceFee = 0;
            $csvServiceFee = $rowData['service_fee'] ?? null;

            // === OVERPAYMENT BOUNDARY CHECKS ===
            if ($paymentType === 'development_charge') {
                $summary = $this->getPaymentSummaryByBookingId($bookingId);
                $devTarget = (float) ($summary['flat_development_charges'] ?? 0);
                $devPaid   = (float) ($summary['development_charge_paid_total'] ?? 0);
                $devRemaining = max(0, $devTarget - $devPaid);
                if ($devTarget > 0 && $devRemaining <= 0) {
                    // Allow overpayment for CSV imports and general cases (will be recorded as extra)
                    // throw new Exception("Development charge is already fully paid for {$rowData['client_name']} (Flat {$rowData['flat_name']})!");
                }
            } elseif ($paymentType === 'emi') {
                $summary = $this->getPaymentSummaryByBookingId($bookingId);
                $cfTotal           = (float) ($summary['construction_fee'] ?? 0);
                $emiPaidSoFar      = (float) ($summary['emi_paid_total'] ?? 0);
                $bulkDisc          = (float) ($summary['total_bulk_emi_discount'] ?? 0);
                $effectiveCFRemaining = max(0, $cfTotal - $emiPaidSoFar - $bulkDisc);

                if ($cfTotal > 0 && $effectiveCFRemaining <= 0) {
                    // Allow overpayment for CSV imports and general cases (will be recorded as extra)
                    // throw new Exception("Construction fee is already fully paid for {$rowData['client_name']} (Flat {$rowData['flat_name']})!");
                }
            }

            // === EMI DUE SETTLEMENT HANDLING ===
            if ($isSettlement) {
                // Find the parent EMI with outstanding due (by emi_no first, then FIFO fallback)
                $emiNoTarget = $rowData['emi_no'] ?? null;
                $parentData = null;

                // Try by emi_no first if specified
                if ($emiNoTarget) {
                    $findStmt = $this->db->prepare("
                        SELECT lp.id, lp.due_emi_amount, es.emi_no
                        FROM lead_payments lp
                        INNER JOIN emi_schedules es ON es.lead_payment_id = lp.id
                        WHERE lp.is_trashed = 0 AND lp.booking_id = ? AND lp.payment_type = 'emi'
                          AND lp.due_emi_amount > 0 AND es.emi_no = ?
                          AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
                        LIMIT 1
                    ");
                    $findStmt->bind_param("ii", $bookingId, $emiNoTarget);
                    $findStmt->execute();
                    $findResult = $findStmt->get_result();
                    $parentData = $findResult ? $findResult->fetch_assoc() : null;
                    $findStmt->close();
                }

                // Fallback to FIFO if emi_no not found or not specified
                if (!$parentData) {
                    $findStmt = $this->db->prepare("
                        SELECT lp.id, lp.due_emi_amount, COALESCE(es.emi_no, 0) AS emi_no
                        FROM lead_payments lp
                        LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
                        WHERE lp.is_trashed = 0 AND lp.booking_id = ? AND lp.payment_type = 'emi'
                          AND lp.due_emi_amount > 0
                          AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
                        ORDER BY lp.payment_date ASC
                        LIMIT 1
                    ");
                    $findStmt->bind_param("i", $bookingId);
                    $findStmt->execute();
                    $findResult = $findStmt->get_result();
                    $parentData = $findResult ? $findResult->fetch_assoc() : null;
                    $findStmt->close();
                }

                if (!$parentData) {
                    // Fallback for CSV bulk import: If no parent overdue EMI exists in DB to settle,
                    // safely process this row as an EMI installment payment!
                    $isSettlement = false;
                    $paymentType = 'emi';
                } else {
                    $parentPaymentId = (int)$parentData['id'];
                    $remainingDue = (float)$parentData['due_emi_amount'];

                    if ($amount > $remainingDue) {
                        // Settle the remaining due amount, surplus becomes regular EMI payment
                        $settleAmount = $remainingDue;
                    } else {
                        $settleAmount = $amount;
                    }

                    // Service fee for settlement
                    if ($csvServiceFee !== null) {
                        $serviceFee = (float)$csvServiceFee;
                    } else {
                        $summary = $this->getPaymentSummaryByBookingId($bookingId);
                        $serviceChargeRate = (float)($summary['service_charge_per_emi'] ?? 0);
                        $serviceFee = ($serviceChargeRate > 0) ? round($settleAmount * ($serviceChargeRate / 100)) : 0;
                    }

                    // Use 'emi' for payment_type to satisfy DB ENUM constraint,
                    // track the difference via settlement_type column
                    $paymentType = 'emi';
                    $settlementType = 'emi_due_settlement';
                    $dueEmiAmount = 0; // Settlement itself has no new due
                }
            }

            if ($paymentType === 'emi' && !$isSettlement) {
                $summary = $this->getPaymentSummaryByBookingId($bookingId);
                $serviceChargeRate = (float)($summary['service_charge_per_emi'] ?? 0);
                $perEmiSuggested = (float)($summary['per_emi_amount'] ?? 0);

                // === PHASE 1: Auto-settle outstanding dues from the payment pool ===
                $pool = $amount;
                $outstandingDues = $this->getOutstandingEmiDues($bookingId);
                $csvSettlements = []; // Track settlements to create after main insert

                foreach ($outstandingDues as $due) {
                    if ($pool <= 0) break;

                    $dueAmount = (float)$due['due_emi_amount'];
                    $settle = min($pool, $dueAmount);

                    $csvSettlements[] = [
                        'parent_id' => (int)$due['id'],
                        'settle_amount' => $settle,
                        'old_due' => $dueAmount,
                    ];
                    $pool -= $settle;
                }

                // === PHASE 2: Remaining pool goes to new installment(s) ===
                // Store pool for multi-installment loop (handled after bank lookup)
                $emiPool = $pool;
                $skipMainInsert = true; // We handle inserts in the multi-installment loop below

            } elseif ($csvServiceFee !== null) {
                $serviceFee = (float)$csvServiceFee;
            }

            // Bank name lookup if bank_id provided
            $bankNameLookup = $bankName;
            if ($bankId) {
                $bRow = $this->db->query("SELECT CONCAT(bank_name, ' - ', account_name) as label FROM company_bank_accounts WHERE id = {$bankId}");
                if ($bRow) {
                    $bData = $bRow->fetch_assoc();
                    $bankNameLookup = $bData['label'] ?? $bankName;
                }
            }

            // Verified by (set if verification_status is verified)
            $verifiedBy = ($verificationStatus === 'verified') ? $createdBy : null;
            $verifiedAt = ($verificationStatus === 'verified') ? date('Y-m-d H:i:s') : null;

            // === CREATE SETTLEMENT RECORDS (for EMI auto-settle) ===
            $firstPaymentId = null;
            if (!empty($csvSettlements)) {
                foreach ($csvSettlements as $cs) {
                    $csAmount = $cs['settle_amount'];
                    $csParentId = $cs['parent_id'];
                    $csSvcFee = ($serviceChargeRate > 0) ? round($csAmount * ($serviceChargeRate / 100)) : 0;
                    $csPayType = 'emi';
                    $csStType = 'emi_due_settlement';
                    $csDue = 0.0;
                    $csLandShare = 0.0;
                    $csBulkDisc = 0.0;

                    $csBulkPay = (!$firstPaymentId) ? $amount : null;

                    $csAttach = null;
                    $csStmt = $this->db->prepare("
                        INSERT INTO lead_payments (
                            booking_id, payment_type, amount, land_share_amount, service_fee,
                            payment_method, payment_status, payment_date, transaction_ref,
                            bank_id, bank_name, bank_account_no, cheque_no, online_type,
                            due_emi_amount, bulk_emi_discount, bulk_pay_amount, verification_status,
                            verified_by, verified_at, parent_payment_id, settlement_type, attachment_paths, csv_import_log_id, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    if (!$csStmt) throw new Exception("Settlement prepare failed: " . $this->db->error);

                    $csStmt->bind_param(
                        "isdddssssissssdddsisissii",
                        $bookingId, $csPayType, $csAmount, $csLandShare, $csSvcFee,
                        $paymentMethod, $paymentStatus, $paymentDate, $transactionRef,
                        $bankId, $bankNameLookup, $bankAccountNo, $chequeNo, $onlineType,
                        $csDue, $csBulkDisc, $csBulkPay, $verificationStatus,
                        $verifiedBy, $verifiedAt, $csParentId, $csStType, $csAttach, $csvImportLogId, $createdBy
                    );

                    if (!$csStmt->execute()) throw new Exception("Settlement insert failed: " . $csStmt->error);
                    if (!$firstPaymentId) $firstPaymentId = $this->db->insert_id;
                    $csStmt->close();

                    // Update parent's due_emi_amount
                    $newDue = max(0, $cs['old_due'] - $csAmount);
                    $newParentStatus = ($newDue <= 0) ? 'paid' : 'partially_paid';
                    $updStmt = $this->db->prepare("UPDATE lead_payments SET due_emi_amount = ?, payment_status = ? WHERE id = ?");
                    $updStmt->bind_param('dsi', $newDue, $newParentStatus, $csParentId);
                    $updStmt->execute();
                    $updStmt->close();

                    // Sync emi_schedules
                    $schedSyncStmt = $this->db->prepare("UPDATE emi_schedules SET due_emi_amount = ?, status = ? WHERE lead_payment_id = ?");
                    $schedSyncStmt->bind_param('dsi', $newDue, $newParentStatus, $csParentId);
                    $schedSyncStmt->execute();
                    $schedSyncStmt->close();
                }
            }

            // === MAIN INSERT: New installment(s) or single payment ===
            $paymentId = $firstPaymentId; // Default to settlement ID if no new installment
            $skipMainInsert = isset($skipMainInsert) && $skipMainInsert;

            if ($skipMainInsert && isset($emiPool) && $emiPool > 0) {
                // ── Multi-installment loop: split remaining pool into per_emi-sized chunks ──
                $pool = $emiPool;
                $isFirstInstallment = true;

                while ($pool > 0) {
                    $thisEmi = ($perEmiSuggested > 0) ? min($pool, $perEmiSuggested) : $pool;

                    // Calculate due for this installment
                    $thisDue = 0;
                    if ($perEmiSuggested > 0 && $thisEmi < $perEmiSuggested) {
                        $thisDue = round($perEmiSuggested - $thisEmi);
                    }
                    $thisStatus = ($thisDue > 0) ? 'partially_paid' : 'paid';

                    // Service fee for this installment
                    if ($csvServiceFee !== null && $isFirstInstallment) {
                        $thisSvcFee = (float)$csvServiceFee;
                    } else {
                        $thisSvcFee = ($serviceChargeRate > 0) ? round($thisEmi * ($serviceChargeRate / 100)) : 0;
                    }

                    // Discount only on first installment
                    $thisDiscount = $isFirstInstallment ? $bulkDiscount : 0.0;

                    $emiStmt = $this->db->prepare("
                        INSERT INTO lead_payments (
                            booking_id, payment_type, amount, land_share_amount, service_fee,
                            payment_method, payment_status, payment_date, transaction_ref,
                            bank_id, bank_name, bank_account_no, cheque_no, online_type,
                            due_emi_amount, bulk_emi_discount, bulk_pay_amount, verification_status,
                            verified_by, verified_at, parent_payment_id, settlement_type, attachment_paths, csv_import_log_id, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    if (!$emiStmt) throw new Exception("EMI prepare failed: " . $this->db->error);

                    $emiPayType = 'emi';
                    $emiLandShare = 0.0;
                    // Only attach document to first installment
                    $emiAttach = $isFirstInstallment ? $attachmentPaths : null;
                    // Only set bulk pay amount on the first actual payment record
                    $emiBulkPay = ($isFirstInstallment && !$firstPaymentId) ? $amount : null;

                    $emiStmt->bind_param(
                        "isdddssssissssdddsisissii",
                        $bookingId, $emiPayType, $thisEmi, $emiLandShare, $thisSvcFee,
                        $paymentMethod, $thisStatus, $paymentDate, $transactionRef,
                        $bankId, $bankNameLookup, $bankAccountNo, $chequeNo, $onlineType,
                        $thisDue, $thisDiscount, $emiBulkPay, $verificationStatus,
                        $verifiedBy, $verifiedAt, $parentPaymentId, $settlementType, $emiAttach, $csvImportLogId, $createdBy
                    );

                    if (!$emiStmt->execute()) throw new Exception("EMI insert failed: " . $emiStmt->error);
                    $thisPaymentId = $this->db->insert_id;
                    if (!$paymentId) $paymentId = $thisPaymentId;
                    $emiStmt->close();

                    // Create emi_schedule for this installment
                    $nextEmiNo = $this->getNextAvailableEmiNo($bookingId);
                    $emiDueDate = $rowData['emi_due_date'] ?? $paymentDate;
                    $schedStatus = ($thisDue > 0) ? 'pending' : 'paid';

                    $schedStmt = $this->db->prepare("
                        INSERT INTO emi_schedules (
                            lead_booking_id, emi_no, emi_amount, service_fee,
                            due_emi_amount, due_date, status, lead_payment_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    if (!$schedStmt) throw new Exception("EMI schedule prepare failed: " . $this->db->error);

                    $schedStmt->bind_param(
                        "iidddssi",
                        $bookingId, $nextEmiNo, $thisEmi, $thisSvcFee,
                        $thisDue, $emiDueDate, $schedStatus, $thisPaymentId
                    );
                    if (!$schedStmt->execute()) throw new Exception("EMI schedule insert failed: " . $schedStmt->error);
                    $schedStmt->close();

                    $pool -= $thisEmi;
                    $isFirstInstallment = false;
                }
            } elseif (!$skipMainInsert) {
                // ── Non-EMI types (dev charge, settlement, etc.) — single insert ──
                $stmt = $this->db->prepare("
                    INSERT INTO lead_payments (
                        booking_id, payment_type, amount, land_share_amount, service_fee,
                        payment_method, payment_status, payment_date, transaction_ref,
                        bank_id, bank_name, bank_account_no, cheque_no, online_type,
                        due_emi_amount, bulk_emi_discount, bulk_pay_amount, verification_status,
                        verified_by, verified_at, parent_payment_id, settlement_type, attachment_paths, csv_import_log_id, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$stmt) throw new Exception("Prepare failed: " . $this->db->error);

                $stmtBulkPay = (!$firstPaymentId && $amount > 0) ? $amount : null;

                $stmt->bind_param(
                    "isdddssssissssdddsisissii",
                    $bookingId, $paymentType, $amount, $landShareAmount, $serviceFee,
                    $paymentMethod, $paymentStatus, $paymentDate, $transactionRef,
                    $bankId, $bankNameLookup, $bankAccountNo, $chequeNo, $onlineType,
                    $dueEmiAmount, $bulkDiscount, $stmtBulkPay, $verificationStatus,
                    $verifiedBy, $verifiedAt, $parentPaymentId, $settlementType, $attachmentPaths, $csvImportLogId, $createdBy
                );
                if (!$stmt->execute()) throw new Exception("Payment insert failed: " . $stmt->error);
                $paymentId = $this->db->insert_id;
                $stmt->close();
            }

            $this->db->commit();

            return [
                'success'    => true,
                'payment_id' => $paymentId,
                'message'    => "Row imported successfully (Payment #{$paymentId})"
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Log a CSV import batch for audit purposes.
     */
    public function logCSVImport($filename, $totalRows, $successCount, $errorCount, $errorDetails, $createdBy)
    {
        $stmt = $this->db->prepare("
            INSERT INTO csv_import_logs (filename, total_rows, success_count, error_count, error_details, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            error_log("logCSVImport prepare failed: " . $this->db->error);
            return false;
        }

        $errorJson = json_encode($errorDetails);
        $status = ($errorCount === 0) ? 'completed' : (($successCount > 0) ? 'completed' : 'failed');

        $stmt->bind_param("siissis", $filename, $totalRows, $successCount, $errorCount, $errorJson, $status, $createdBy);
        $result = $stmt->execute();
        $insertId = $this->db->insert_id;
        $stmt->close();

        return $result ? $insertId : false;
    }

    public function initiateHandover($paymentId, $handoverToId, $createdBy)
    {
        $this->db->begin_transaction();
        try {
            // Update the lead_payments table
            $stmt = $this->db->prepare("UPDATE lead_payments SET handover_to = ?, handover_status = 'pending' WHERE id = ?");
            $stmt->bind_param("ii", $handoverToId, $paymentId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update handover status.");
            }

            // Get payment details for the approval request
            $payStmt = $this->db->prepare("
                SELECT lp.amount, lp.payment_type, p.name as project_name, f.flat_name as unit_name
                FROM lead_payments lp
                LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN projects p ON f.project_id = p.id
                WHERE lp.id = ?
            ");
            $payStmt->bind_param("i", $paymentId);
            $payStmt->execute();
            $paymentData = $payStmt->get_result()->fetch_assoc();

            if (!class_exists('ApprovalRequest')) {
                require_once __DIR__ . '/ApprovalRequest.php';
            }
            $approvalModel = new ApprovalRequest($this->db);
            $approvalModel->create([
                'department' => 'Accounts',
                'request_type' => 'Cash In Handover',
                'title' => 'Handover Approval Request for Payment #' . $paymentId,
                'source_table' => 'lead_payments',
                'source_id' => $paymentId,
                'requested_days' => 0,
                'requested_by' => $createdBy,
                'approve_role' => 'management',
                'meta_data' => json_encode([
                    'Project' => $paymentData['project_name'] ?? 'N/A',
                    'Unit' => $paymentData['unit_name'] ?? 'N/A',
                    'Amount' => $paymentData['amount'] ?? 0,
                    'Payment Type' => $paymentData['payment_type'] ?? ''
                ]),
                'request_amount' => $paymentData['amount'] ?? 0
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Handover request initiated successfully.'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Move a transaction to trash
     */
    public function trashTransaction($paymentId, $bookingId, $userId)
    {
        $this->db->begin_transaction();
        try {
            $paymentId = (int)$paymentId;
            $bookingId = (int)$bookingId;
            $userId    = (int)$userId;

            $chk = $this->db->prepare("SELECT id, payment_type, settlement_type, parent_payment_id, amount, verification_status FROM lead_payments WHERE id = ? AND booking_id = ?");
            $chk->bind_param("ii", $paymentId, $bookingId);
            $chk->execute();
            $row = $chk->get_result()->fetch_assoc();
            if (!$row) {
                throw new Exception("Transaction not found or does not belong to this booking.");
            }

            $paymentType = $row['payment_type'];
            $settlementType = $row['settlement_type'] ?? null;
            $isSettlement = ($paymentType === 'emi_due_settlement' || $settlementType === 'emi_due_settlement' || !empty($row['parent_payment_id']));

            // If trashing a settlement, restore the parent EMI's outstanding due
            if ($isSettlement && !empty($row['parent_payment_id']) && $row['verification_status'] !== 'rejected') {
                $parentId = (int)$row['parent_payment_id'];
                $settlementAmount = (float)$row['amount'];
                $restoreStmt = $this->db->prepare("
                    UPDATE lead_payments 
                    SET due_emi_amount = due_emi_amount + ?, 
                        payment_status = 'partially_paid'
                    WHERE id = ? AND booking_id = ?
                ");
                $restoreStmt->bind_param("dii", $settlementAmount, $parentId, $bookingId);
                $restoreStmt->execute();
            }

            $stmt = $this->db->prepare("UPDATE lead_payments SET is_trashed = 1, trashed_by = ?, trashed_at = NOW() WHERE id = ? AND booking_id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            $stmt->bind_param("iii", $userId, $paymentId, $bookingId);
            if (!$stmt->execute() || $stmt->affected_rows === 0) {
                throw new Exception("Transaction not found or already trashed.");
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Transaction moved to trash successfully.'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to trash transaction: ' . $e->getMessage()];
        }
    }

    /**
     * Restore a transaction from trash
     */
    public function restoreTransaction($paymentId, $bookingId)
    {
        $this->db->begin_transaction();
        try {
            $paymentId = (int)$paymentId;
            $bookingId = (int)$bookingId;

            $stmt = $this->db->prepare("UPDATE lead_payments SET is_trashed = 0, trashed_by = NULL, trashed_at = NULL WHERE id = ? AND booking_id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            $stmt->bind_param("ii", $paymentId, $bookingId);
            if (!$stmt->execute() || $stmt->affected_rows === 0) {
                throw new Exception("Transaction not found or not trashed.");
            }

            // Check if this payment is an EMI schedule row and avoid duplicate emi_no
            $schedStmt = $this->db->prepare("SELECT id, emi_no FROM emi_schedules WHERE lead_payment_id = ? AND lead_booking_id = ?");
            if ($schedStmt) {
                $schedStmt->bind_param("ii", $paymentId, $bookingId);
                $schedStmt->execute();
                $schedRes = $schedStmt->get_result();
                $schedRow = $schedRes ? $schedRes->fetch_assoc() : null;

                if ($schedRow) {
                    $currentEmiNo = (int)$schedRow['emi_no'];
                    $schedId = (int)$schedRow['id'];

                    // Check if another active payment has taken this same emi_no
                    $conflictStmt = $this->db->prepare("
                        SELECT es.id 
                        FROM emi_schedules es
                        INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id
                        WHERE es.lead_booking_id = ? 
                          AND es.emi_no = ? 
                          AND es.id != ?
                          AND lp.is_trashed = 0 
                          AND lp.verification_status != 'rejected'
                    ");
                    if ($conflictStmt) {
                        $conflictStmt->bind_param("iii", $bookingId, $currentEmiNo, $schedId);
                        $conflictStmt->execute();
                        $conflictRes = $conflictStmt->get_result();

                        if ($conflictRes && $conflictRes->num_rows > 0) {
                            // Slot occupied! Reassign restored EMI to the next available free number
                            $newEmiNo = $this->getNextAvailableEmiNo($bookingId);
                            $updSched = $this->db->prepare("UPDATE emi_schedules SET emi_no = ? WHERE id = ?");
                            if ($updSched) {
                                $updSched->bind_param("ii", $newEmiNo, $schedId);
                                $updSched->execute();
                            }
                        }
                    }
                }
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Transaction restored successfully.'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to restore transaction: ' . $e->getMessage()];
        }
    }

    /**
     * Get all trashed transactions
     */
    public function getTrashedTransactions()
    {
        $sql = "
            SELECT 
                lp.*,
                lb.id as booking_id,
                l.full_name as client_name,
                COALESCE(p.name, 'No Project') as project_name,
                COALESCE(f.flat_name, 'N/A') as unit_name,
                COALESCE(u.full_name, u.user_name, 'System') as trashed_by_name,
                r.name as trashed_by_role
            FROM lead_payments lp
            JOIN lead_bookings lb ON lp.booking_id = lb.id
            JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u ON lp.trashed_by = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE lp.is_trashed = 1
            ORDER BY lp.trashed_at DESC
        ";
        $result = $this->db->query($sql);
        if (!$result) return [];
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update/Approve Booking Discount by Management
     */
    public function updateManagementBookingDiscount($bookingId, $approvedDiscount, $approvedBy)
    {
        $bookingId = (int)$bookingId;
        $approvedDiscount = (float)$approvedDiscount;
        $approvedBy = (int)$approvedBy;

        $this->db->begin_transaction();
        try {
            // 1. Update lead_bookings table
            $stmt = $this->db->prepare("
                UPDATE lead_bookings SET
                    discount_amount = ?,
                    approved_amount = ?,
                    approval_status = 'approved',
                    updated_at = NOW()
                WHERE id = ?
            ");
            if (!$stmt) {
                throw new Exception($this->db->error);
            }
            $stmt->bind_param("ddi", $approvedDiscount, $approvedDiscount, $bookingId);
            $stmt->execute();

            // 2. Synchronize approval_requests table
            $lbRow = $this->db->query("SELECT sales_by_id, lead_id, past_approval_attachment FROM lead_bookings WHERE id = {$bookingId} LIMIT 1")->fetch_assoc();
            $salesPersonId = (int)($lbRow['sales_by_id'] ?? 0);
            if ($salesPersonId <= 0 && !empty($lbRow['lead_id'])) {
                $lRow = $this->db->query("SELECT assigned_id FROM leads WHERE id = " . (int)$lbRow['lead_id'] . " LIMIT 1")->fetch_assoc();
                $salesPersonId = (int)($lRow['assigned_id'] ?? 0);
            }
            $requestedBy = ($salesPersonId > 0) ? $salesPersonId : $approvedBy;
            $bookingAtt = $lbRow['past_approval_attachment'] ?? null;

            $check = $this->db->query("SELECT id FROM approval_requests WHERE source_table = 'lead_bookings' AND source_id = {$bookingId} LIMIT 1")->fetch_assoc();
            if ($check) {
                $appId = (int)$check['id'];
                $stmtApp = $this->db->prepare("
                    UPDATE approval_requests SET
                        request_amount = ?,
                        approved_amount = ?,
                        status = 'approved',
                        op_head_status = 'approved',
                        approve_role = 'completed',
                        requested_by = ?,
                        approved_by = ?,
                        approved_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmtApp->bind_param("ddiii", $approvedDiscount, $approvedDiscount, $requestedBy, $approvedBy, $appId);
                $stmtApp->execute();
            } else {
                $stmtApp = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, status, requested_by, approve_role, attachment, approved_by, approved_at, created_at)
                    VALUES ('Sales', 'discount', 'Lead Booking Discount Approval', 'lead_bookings', ?, ?, ?, 'approved', ?, 'Management', ?, ?, NOW(), NOW())
                ");
                $stmtApp->bind_param("iddsii", $bookingId, $approvedDiscount, $approvedDiscount, $requestedBy, $bookingAtt, $approvedBy);
                $stmtApp->execute();
            }

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get aggregate KPI statistics for Client-wise Due Tracking
     *
     * @param int|null $projectId
     * @return array
     */
    public function getClientDueTrackingStats($projectId = null)
    {
        $whereParts = [
            "lb.deleted_at IS NULL",
            "lb.booking_status NOT IN ('refunded', 'cancelled')",
            "(lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')",
            "lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))"
        ];
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereParts[] = "lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= "i";
                    }
                }
            } else {
                $whereParts[] = "lb.project_id = ?";
                $params[] = (int)$projectId;
                $types .= "i";
            }
        }

        $whereClause = "WHERE " . implode(" AND ", $whereParts);

        $sql = "
            SELECT 
                COUNT(*) AS total_clients,
                COALESCE(SUM(calc.total_price), 0) AS total_value,
                
                -- Collections (Verified, Unverified/Pending, All)
                COALESCE(SUM(calc.total_paid_verified), 0) AS total_paid_verified,
                COALESCE(SUM(calc.total_paid_unverified), 0) AS total_paid_unverified,
                COALESCE(SUM(calc.total_paid_all), 0) AS total_paid_all,
                
                -- Overall Dues (Verified & Net after unverified)
                COALESCE(SUM(calc.overall_due_verified), 0) AS overall_due_verified,
                COALESCE(SUM(calc.overall_due_net), 0) AS overall_due_net,
                
                -- Land Share
                COALESCE(SUM(calc.total_land_share), 0) AS total_land_share_target,
                COALESCE(SUM(calc.land_share_paid_verified), 0) AS total_land_share_paid_verified,
                COALESCE(SUM(calc.land_share_paid_unverified), 0) AS total_land_share_paid_unverified,
                COALESCE(SUM(calc.land_share_paid_all), 0) AS total_land_share_paid_all,
                COALESCE(SUM(calc.land_share_due_net), 0) AS total_land_share_due_net,
                COALESCE(SUM(calc.land_share_due_verified), 0) AS total_land_share_due_verified,

                -- Dev Charges
                COALESCE(SUM(calc.development_charges), 0) AS total_dev_charges_target,
                COALESCE(SUM(calc.dev_charge_paid_verified), 0) AS total_dev_charges_paid_verified,
                COALESCE(SUM(calc.dev_charge_paid_unverified), 0) AS total_dev_charges_paid_unverified,
                COALESCE(SUM(calc.dev_charge_paid_all), 0) AS total_dev_charges_paid_all,
                COALESCE(SUM(calc.dev_charge_due_net), 0) AS total_dev_charge_due_net,
                COALESCE(SUM(calc.dev_charge_due_verified), 0) AS total_dev_charge_due_verified,

                -- Shop Service
                COALESCE(SUM(CASE WHEN calc.is_shop = 1 THEN calc.shop_service_charge ELSE 0 END), 0) AS total_shop_service_charge_target,
                COALESCE(SUM(CASE WHEN calc.is_shop = 1 THEN calc.shop_service_charge_paid_verified ELSE 0 END), 0) AS total_shop_service_charge_paid_verified,
                COALESCE(SUM(CASE WHEN calc.is_shop = 1 THEN calc.shop_service_charge_paid_unverified ELSE 0 END), 0) AS total_shop_service_charge_paid_unverified,
                COALESCE(SUM(CASE WHEN calc.is_shop = 1 THEN calc.shop_service_charge_paid_all ELSE 0 END), 0) AS total_shop_service_charge_paid_all,
                COALESCE(SUM(CASE WHEN calc.is_shop = 1 THEN calc.shop_service_charge_due_net ELSE 0 END), 0) AS total_shop_service_charge_due_net,
                COALESCE(SUM(CASE WHEN calc.is_shop = 1 THEN calc.shop_service_charge_due_verified ELSE 0 END), 0) AS total_shop_service_charge_due_verified,

                -- Construction / EMI
                COALESCE(SUM(calc.construction_fee), 0) AS total_construction_target,
                COALESCE(SUM(calc.emi_paid_verified), 0) AS total_emi_paid_verified,
                COALESCE(SUM(calc.emi_paid_unverified), 0) AS total_emi_paid_unverified,
                COALESCE(SUM(calc.emi_paid_all), 0) AS total_emi_paid_all,
                COALESCE(SUM(calc.remaining_emi_due_net), 0) AS total_emi_due_net,
                COALESCE(SUM(calc.remaining_emi_due_verified), 0) AS total_emi_due_verified,
                
                COALESCE(SUM(calc.overdue_emi_amount), 0) AS total_overdue_emi,
                COALESCE(SUM(calc.pending_payment_count), 0) AS total_pending_payments
            FROM (
                SELECT 
                    raw_c.*,
                    -- Net Component Dues (after verified + unverified collections)
                    CASE WHEN raw_c.overall_due_net <= 0 THEN 0 ELSE LEAST(raw_c.raw_ls_due_net, raw_c.overall_due_net) END AS land_share_due_net,
                    CASE WHEN raw_c.overall_due_net <= 0 THEN 0 ELSE LEAST(raw_c.raw_dev_due_net, GREATEST(0, raw_c.overall_due_net - LEAST(raw_c.raw_ls_due_net, raw_c.overall_due_net))) END AS dev_charge_due_net,
                    CASE WHEN raw_c.overall_due_net <= 0 THEN 0 ELSE LEAST(raw_c.raw_shop_due_net, GREATEST(0, 
                        raw_c.overall_due_net 
                        - LEAST(raw_c.raw_ls_due_net, raw_c.overall_due_net)
                        - LEAST(raw_c.raw_dev_due_net, GREATEST(0, raw_c.overall_due_net - LEAST(raw_c.raw_ls_due_net, raw_c.overall_due_net)))
                    )) END AS shop_service_charge_due_net,
                    GREATEST(0, 
                        raw_c.overall_due_net
                        - (CASE WHEN raw_c.overall_due_net <= 0 THEN 0 ELSE LEAST(raw_c.raw_ls_due_net, raw_c.overall_due_net) END)
                        - (CASE WHEN raw_c.overall_due_net <= 0 THEN 0 ELSE LEAST(raw_c.raw_dev_due_net, GREATEST(0, raw_c.overall_due_net - LEAST(raw_c.raw_ls_due_net, raw_c.overall_due_net))) END)
                        - (CASE WHEN raw_c.overall_due_net <= 0 THEN 0 ELSE LEAST(raw_c.raw_shop_due_net, GREATEST(0, 
                            raw_c.overall_due_net 
                            - LEAST(raw_c.raw_ls_due_net, raw_c.overall_due_net)
                            - LEAST(raw_c.raw_dev_due_net, GREATEST(0, raw_c.overall_due_net - LEAST(raw_c.raw_ls_due_net, raw_c.overall_due_net)))
                        )) END)
                    ) AS remaining_emi_due_net,

                    -- Verified Component Dues (strictly verified collections)
                    CASE WHEN raw_c.overall_due_verified <= 0 THEN 0 ELSE LEAST(raw_c.raw_ls_due_verified, raw_c.overall_due_verified) END AS land_share_due_verified,
                    CASE WHEN raw_c.overall_due_verified <= 0 THEN 0 ELSE LEAST(raw_c.raw_dev_due_verified, GREATEST(0, raw_c.overall_due_verified - LEAST(raw_c.raw_ls_due_verified, raw_c.overall_due_verified))) END AS dev_charge_due_verified,
                    CASE WHEN raw_c.overall_due_verified <= 0 THEN 0 ELSE LEAST(raw_c.raw_shop_due_verified, GREATEST(0, 
                        raw_c.overall_due_verified 
                        - LEAST(raw_c.raw_ls_due_verified, raw_c.overall_due_verified)
                        - LEAST(raw_c.raw_dev_due_verified, GREATEST(0, raw_c.overall_due_verified - LEAST(raw_c.raw_ls_due_verified, raw_c.overall_due_verified)))
                    )) END AS shop_service_charge_due_verified,
                    GREATEST(0, 
                        raw_c.overall_due_verified
                        - (CASE WHEN raw_c.overall_due_verified <= 0 THEN 0 ELSE LEAST(raw_c.raw_ls_due_verified, raw_c.overall_due_verified) END)
                        - (CASE WHEN raw_c.overall_due_verified <= 0 THEN 0 ELSE LEAST(raw_c.raw_dev_due_verified, GREATEST(0, raw_c.overall_due_verified - LEAST(raw_c.raw_ls_due_verified, raw_c.overall_due_verified))) END)
                        - (CASE WHEN raw_c.overall_due_verified <= 0 THEN 0 ELSE LEAST(raw_c.raw_shop_due_verified, GREATEST(0, 
                            raw_c.overall_due_verified 
                            - LEAST(raw_c.raw_ls_due_verified, raw_c.overall_due_verified)
                            - LEAST(raw_c.raw_dev_due_verified, GREATEST(0, raw_c.overall_due_verified - LEAST(raw_c.raw_ls_due_verified, raw_c.overall_due_verified)))
                        )) END)
                    ) AS remaining_emi_due_verified
                FROM (
                    SELECT 
                        lb.id,
                        COALESCE(f.is_shop, 0) AS is_shop,
                        COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) AS total_price,
                        COALESCE(f.land_share_value, 0) AS total_land_share,
                        COALESCE(f.development_charges, 0) AS development_charges,
                        COALESCE(f.shop_service_charge, 0) AS shop_service_charge,
                        GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - (COALESCE(f.land_share_value, 0) + COALESCE(f.development_charges, 0) + CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END))) AS construction_fee,
                        
                        -- Verified Collections
                        COALESCE(p_agg.paid_verified, 0) AS total_paid_verified,
                        COALESCE(p_agg.land_paid_verified, 0) AS land_share_paid_verified,
                        COALESCE(p_agg.dev_paid_verified, 0) AS dev_charge_paid_verified,
                        COALESCE(p_agg.shop_paid_verified, 0) AS shop_service_charge_paid_verified,
                        COALESCE(p_agg.emi_paid_verified, 0) AS emi_paid_verified,
                        
                        -- Unverified Collections
                        COALESCE(p_agg.paid_unverified, 0) AS total_paid_unverified,
                        COALESCE(p_agg.land_paid_unverified, 0) AS land_share_paid_unverified,
                        COALESCE(p_agg.dev_paid_unverified, 0) AS dev_charge_paid_unverified,
                        COALESCE(p_agg.shop_paid_unverified, 0) AS shop_service_charge_paid_unverified,
                        COALESCE(p_agg.emi_paid_unverified, 0) AS emi_paid_unverified,

                        -- Combined Collections
                        COALESCE(p_agg.paid_all, 0) AS total_paid_all,
                        COALESCE(p_agg.land_paid_all, 0) AS land_share_paid_all,
                        COALESCE(p_agg.dev_paid_all, 0) AS dev_charge_paid_all,
                        COALESCE(p_agg.shop_paid_all, 0) AS shop_service_charge_paid_all,
                        COALESCE(p_agg.emi_paid_all, 0) AS emi_paid_all,

                        COALESCE(p_agg.overdue_emi_amount, 0) AS overdue_emi_amount,
                        COALESCE(p_agg.pending_payment_count, 0) AS pending_payment_count,

                        -- Raw Component Dues (Verified)
                        GREATEST(0, ROUND(GREATEST(0, COALESCE(f.land_share_value, 0) - COALESCE(lb.discount_amount, 0)) - COALESCE(p_agg.land_paid_verified, 0))) AS raw_ls_due_verified,
                        GREATEST(0, ROUND(COALESCE(f.development_charges, 0) - COALESCE(p_agg.dev_paid_verified, 0))) AS raw_dev_due_verified,
                        GREATEST(0, ROUND(CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END - COALESCE(p_agg.shop_paid_verified, 0))) AS raw_shop_due_verified,
                        GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - COALESCE(lb.discount_amount, 0) - COALESCE(p_agg.paid_verified, 0) - COALESCE(p_agg.emi_discount_verified, 0))) AS overall_due_verified,

                        -- Raw Component Dues (Net: after verified + unverified)
                        GREATEST(0, ROUND(GREATEST(0, COALESCE(f.land_share_value, 0) - COALESCE(lb.discount_amount, 0)) - COALESCE(p_agg.land_paid_all, 0))) AS raw_ls_due_net,
                        GREATEST(0, ROUND(COALESCE(f.development_charges, 0) - COALESCE(p_agg.dev_paid_all, 0))) AS raw_dev_due_net,
                        GREATEST(0, ROUND(CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END - COALESCE(p_agg.shop_paid_all, 0))) AS raw_shop_due_net,
                        GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - COALESCE(lb.discount_amount, 0) - COALESCE(p_agg.paid_all, 0) - COALESCE(p_agg.emi_discount_all, 0))) AS overall_due_net
                    FROM lead_bookings lb
                    INNER JOIN leads l ON lb.lead_id = l.id
                    LEFT JOIN flats f ON lb.flat_id = f.id
                    LEFT JOIN (
                        SELECT 
                            lp.booking_id,
                            -- Verified
                            SUM(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS paid_verified,
                            SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                                          AND lp.payment_status IN ('paid', 'partially_paid') 
                                          AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS land_paid_verified,
                            SUM(CASE WHEN lp.payment_type = 'development_charge' 
                                          AND lp.payment_status IN ('paid', 'partially_paid') 
                                          AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS dev_paid_verified,
                            SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                                          AND lp.payment_status IN ('paid', 'partially_paid') 
                                          AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS shop_paid_verified,
                            SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                          AND lp.payment_status IN ('paid', 'partially_paid') 
                                          AND lp.verification_status = 'verified' 
                                          AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS emi_paid_verified,
                            SUM(CASE WHEN (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1) AND lp.verification_status = 'verified' 
                                     THEN CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END ELSE 0 END) AS emi_discount_verified,

                            -- Unverified
                            SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid', 'pending')) AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') AND lp.verification_status != 'rejected' AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS paid_unverified,
                            SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                                          AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                          AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') AND lp.verification_status != 'rejected' THEN lp.amount ELSE 0 END) AS land_paid_unverified,
                            SUM(CASE WHEN lp.payment_type = 'development_charge' 
                                          AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                          AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') AND lp.verification_status != 'rejected' THEN lp.amount ELSE 0 END) AS dev_paid_unverified,
                            SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                                          AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                          AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') AND lp.verification_status != 'rejected' THEN lp.amount ELSE 0 END) AS shop_paid_unverified,
                            SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                          AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                          AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') AND lp.verification_status != 'rejected' 
                                          AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS emi_paid_unverified,
                            SUM(CASE WHEN (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1) AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') AND lp.verification_status != 'rejected' 
                                     THEN CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END ELSE 0 END) AS emi_discount_unverified,

                            -- Combined All
                            SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid', 'pending')) AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS paid_all,
                            SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                                          AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                          AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) AS land_paid_all,
                            SUM(CASE WHEN lp.payment_type = 'development_charge' 
                                          AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                          AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) AS dev_paid_all,
                            SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                                          AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                          AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) AS shop_paid_all,
                            SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                          AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                          AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                          AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS emi_paid_all,
                            SUM(CASE WHEN (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1) AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                     THEN CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END ELSE 0 END) AS emi_discount_all,

                            SUM(CASE WHEN lp.payment_type = 'emi' AND lp.due_emi_amount > 0 AND lp.verification_status = 'verified' 
                                          AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') THEN lp.due_emi_amount ELSE 0 END) AS overdue_emi_amount,
                            SUM(CASE WHEN lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS pending_payment_count
                        FROM lead_payments lp
                        WHERE lp.is_trashed = 0
                        GROUP BY lp.booking_id
                    ) AS p_agg ON p_agg.booking_id = lb.id
                    $whereClause
                ) AS raw_c
            ) AS calc
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("getClientDueTrackingStats prepare failed: " . $this->db->error);
            return [
                'total_clients'                    => 0,
                'total_value'                      => 0,
                'total_paid'                       => 0,
                'total_paid_verified'              => 0,
                'total_paid_unverified'            => 0,
                'total_paid_all'                   => 0,
                'overall_due'                      => 0,
                'overall_due_net'                  => 0,
                'overall_due_verified'             => 0,
                'total_land_share_target'          => 0,
                'total_land_share_paid'            => 0,
                'total_land_share_paid_verified'   => 0,
                'total_land_share_paid_unverified' => 0,
                'total_land_share_paid_all'        => 0,
                'total_land_share_due'             => 0,
                'total_land_share_due_net'         => 0,
                'total_land_share_due_verified'    => 0,
                'total_dev_charges_target'         => 0,
                'total_dev_charges_paid'           => 0,
                'total_dev_charges_paid_verified'  => 0,
                'total_dev_charges_paid_unverified'=> 0,
                'total_dev_charges_paid_all'       => 0,
                'total_dev_charge_due'             => 0,
                'total_dev_charge_due_net'         => 0,
                'total_dev_charge_due_verified'    => 0,
                'total_shop_service_charge_target' => 0,
                'total_shop_service_charge_paid'   => 0,
                'total_shop_service_charge_paid_verified' => 0,
                'total_shop_service_charge_paid_unverified' => 0,
                'total_shop_service_charge_paid_all' => 0,
                'total_shop_service_charge_due'    => 0,
                'total_shop_service_charge_due_net'=> 0,
                'total_shop_service_charge_due_verified' => 0,
                'total_construction_target'        => 0,
                'total_emi_paid'                   => 0,
                'total_emi_paid_verified'          => 0,
                'total_emi_paid_unverified'        => 0,
                'total_emi_paid_all'               => 0,
                'total_emi_due'                    => 0,
                'total_emi_due_net'                => 0,
                'total_emi_due_verified'           => 0,
                'total_overdue_emi'                => 0,
                'total_pending_payments'           => 0
            ];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;

        return [
            'total_clients'                    => (int)($row['total_clients'] ?? 0),
            'total_value'                      => (float)($row['total_value'] ?? 0),
            
            // Collections
            'total_paid'                       => (float)($row['total_paid_verified'] ?? 0), // legacy verified
            'total_paid_verified'              => (float)($row['total_paid_verified'] ?? 0),
            'total_paid_unverified'            => (float)($row['total_paid_unverified'] ?? 0),
            'total_paid_all'                   => (float)($row['total_paid_all'] ?? 0),

            // Dues
            'overall_due'                      => (float)($row['overall_due_net'] ?? 0), // Default to Net Due (after verified + unverified)
            'overall_due_net'                  => (float)($row['overall_due_net'] ?? 0),
            'overall_due_verified'             => (float)($row['overall_due_verified'] ?? 0),

            // Land Share
            'total_land_share_target'          => (float)($row['total_land_share_target'] ?? 0),
            'total_land_share_paid'            => (float)($row['total_land_share_paid_verified'] ?? 0),
            'total_land_share_paid_verified'   => (float)($row['total_land_share_paid_verified'] ?? 0),
            'total_land_share_paid_unverified' => (float)($row['total_land_share_paid_unverified'] ?? 0),
            'total_land_share_paid_all'        => (float)($row['total_land_share_paid_all'] ?? 0),
            'total_land_share_due'             => (float)($row['total_land_share_due_net'] ?? 0),
            'total_land_share_due_net'         => (float)($row['total_land_share_due_net'] ?? 0),
            'total_land_share_due_verified'    => (float)($row['total_land_share_due_verified'] ?? 0),

            // Dev Charges
            'total_dev_charges_target'         => (float)($row['total_dev_charges_target'] ?? 0),
            'total_dev_charges_paid'           => (float)($row['total_dev_charges_paid_verified'] ?? 0),
            'total_dev_charges_paid_verified'  => (float)($row['total_dev_charges_paid_verified'] ?? 0),
            'total_dev_charges_paid_unverified'=> (float)($row['total_dev_charges_paid_unverified'] ?? 0),
            'total_dev_charges_paid_all'       => (float)($row['total_dev_charges_paid_all'] ?? 0),
            'total_dev_charge_due'             => (float)($row['total_dev_charge_due_net'] ?? 0),
            'total_dev_charge_due_net'         => (float)($row['total_dev_charge_due_net'] ?? 0),
            'total_dev_charge_due_verified'    => (float)($row['total_dev_charge_due_verified'] ?? 0),

            // Shop Service
            'total_shop_service_charge_target' => (float)($row['total_shop_service_charge_target'] ?? 0),
            'total_shop_service_charge_paid'   => (float)($row['total_shop_service_charge_paid_verified'] ?? 0),
            'total_shop_service_charge_paid_verified' => (float)($row['total_shop_service_charge_paid_verified'] ?? 0),
            'total_shop_service_charge_paid_unverified' => (float)($row['total_shop_service_charge_paid_unverified'] ?? 0),
            'total_shop_service_charge_paid_all' => (float)($row['total_shop_service_charge_paid_all'] ?? 0),
            'total_shop_service_charge_due'    => (float)($row['total_shop_service_charge_due_net'] ?? 0),
            'total_shop_service_charge_due_net'=> (float)($row['total_shop_service_charge_due_net'] ?? 0),
            'total_shop_service_charge_due_verified' => (float)($row['total_shop_service_charge_due_verified'] ?? 0),

            // Construction / EMI
            'total_construction_target'        => (float)($row['total_construction_target'] ?? 0),
            'total_emi_paid'                   => (float)($row['total_emi_paid_verified'] ?? 0),
            'total_emi_paid_verified'          => (float)($row['total_emi_paid_verified'] ?? 0),
            'total_emi_paid_unverified'        => (float)($row['total_emi_paid_unverified'] ?? 0),
            'total_emi_paid_all'               => (float)($row['total_emi_paid_all'] ?? 0),
            'total_emi_due'                    => (float)($row['total_emi_due_net'] ?? 0),
            'total_emi_due_net'                => (float)($row['total_emi_due_net'] ?? 0),
            'total_emi_due_verified'           => (float)($row['total_emi_due_verified'] ?? 0),

            'total_overdue_emi'                => (float)($row['total_overdue_emi'] ?? 0),
            'total_pending_payments'           => (int)($row['total_pending_payments'] ?? 0),
        ];
    }

    /**
     * Get Project-wise Due and Payment Summary Breakdown
     *
     * @param string|null $search
     * @return array
     */
    public function getProjectWiseDueBreakdown($search = null, ?array $projectIds = null)
    {
        $projectWhere = "";
        $params = [];
        $types = "";

        if (!empty($projectIds)) {
            $pIds = array_values(array_filter(array_map('intval', $projectIds)));
            if (!empty($pIds)) {
                $projectWhere .= " AND p.id IN (" . implode(',', $pIds) . ")";
            }
        }

        if (!empty($search)) {
            $projectWhere .= " AND p.name LIKE ?";
            $params[] = '%' . trim($search) . '%';
            $types .= "s";
        }

        $sql = "
            SELECT 
                p.id AS project_id,
                p.name AS project_name,
                COUNT(DISTINCT calc.booking_id) AS total_clients,
                COALESCE(SUM(CASE WHEN calc.land_share_due <= 0 AND calc.total_land_share > 0 THEN 1 ELSE 0 END), 0) AS registered_clients_count,
                COALESCE(SUM(calc.total_price), 0) AS total_project_value,
                COALESCE(SUM(calc.total_paid), 0) AS total_paid,
                COALESCE(SUM(calc.overall_due), 0) AS overall_due,
                COALESCE(SUM(calc.total_land_share), 0) AS land_share_target,
                COALESCE(SUM(calc.land_share_paid), 0) AS land_share_paid,
                COALESCE(SUM(calc.land_share_due), 0) AS land_share_due,
                COALESCE(SUM(calc.development_charges), 0) AS dev_charge_target,
                COALESCE(SUM(calc.dev_charge_paid), 0) AS dev_charge_paid,
                COALESCE(SUM(calc.dev_charge_due), 0) AS dev_charge_due,
                COALESCE(SUM(CASE WHEN calc.is_shop = 1 THEN calc.shop_service_charge ELSE 0 END), 0) AS shop_service_charge_target,
                COALESCE(SUM(CASE WHEN calc.is_shop = 1 THEN calc.shop_service_charge_paid ELSE 0 END), 0) AS shop_service_charge_paid,
                COALESCE(SUM(CASE WHEN calc.is_shop = 1 THEN calc.shop_service_charge_due ELSE 0 END), 0) AS shop_service_charge_due,
                COALESCE(SUM(calc.construction_fee), 0) AS construction_target,
                COALESCE(SUM(calc.emi_paid), 0) AS emi_paid,
                COALESCE(SUM(calc.remaining_emi_due), 0) AS emi_due,
                COALESCE(SUM(calc.overdue_emi_amount), 0) AS overdue_emi_amount
            FROM (
                SELECT 
                    raw_c.booking_id,
                    raw_c.project_id,
                    raw_c.is_shop,
                    raw_c.total_price,
                    raw_c.total_land_share,
                    raw_c.land_share_paid,
                    raw_c.development_charges,
                    raw_c.dev_charge_paid,
                    raw_c.shop_service_charge,
                    raw_c.shop_service_charge_paid,
                    raw_c.construction_fee,
                    raw_c.total_paid,
                    raw_c.emi_paid,
                    raw_c.overdue_emi_amount,
                    raw_c.overall_due,
                    
                    -- Reconciled Dues
                    CASE 
                        WHEN raw_c.overall_due <= 0 THEN 0 
                        ELSE LEAST(raw_c.raw_ls_due, raw_c.overall_due) 
                    END AS land_share_due,
                    
                    CASE 
                        WHEN raw_c.overall_due <= 0 THEN 0 
                        ELSE LEAST(raw_c.raw_dev_due, GREATEST(0, raw_c.overall_due - LEAST(raw_c.raw_ls_due, raw_c.overall_due))) 
                    END AS dev_charge_due,
                    
                    CASE 
                        WHEN raw_c.overall_due <= 0 THEN 0 
                        ELSE LEAST(raw_c.raw_shop_due, GREATEST(0, 
                            raw_c.overall_due 
                            - LEAST(raw_c.raw_ls_due, raw_c.overall_due)
                            - LEAST(raw_c.raw_dev_due, GREATEST(0, raw_c.overall_due - LEAST(raw_c.raw_ls_due, raw_c.overall_due)))
                        )) 
                    END AS shop_service_charge_due,
                    
                    GREATEST(0, 
                        raw_c.overall_due
                        - (CASE WHEN raw_c.overall_due <= 0 THEN 0 ELSE LEAST(raw_c.raw_ls_due, raw_c.overall_due) END)
                        - (CASE WHEN raw_c.overall_due <= 0 THEN 0 ELSE LEAST(raw_c.raw_dev_due, GREATEST(0, raw_c.overall_due - LEAST(raw_c.raw_ls_due, raw_c.overall_due))) END)
                        - (CASE WHEN raw_c.overall_due <= 0 THEN 0 ELSE LEAST(raw_c.raw_shop_due, GREATEST(0, 
                            raw_c.overall_due 
                            - LEAST(raw_c.raw_ls_due, raw_c.overall_due)
                            - LEAST(raw_c.raw_dev_due, GREATEST(0, raw_c.overall_due - LEAST(raw_c.raw_ls_due, raw_c.overall_due)))
                        )) END)
                    ) AS remaining_emi_due
                FROM (
                    SELECT 
                        lb.id AS booking_id,
                        lb.project_id,
                        COALESCE(f.is_shop, 0) AS is_shop,
                        COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) AS total_price,
                        COALESCE(f.land_share_value, 0) AS total_land_share,
                        COALESCE(f.development_charges, 0) AS development_charges,
                        COALESCE(f.shop_service_charge, 0) AS shop_service_charge,
                        GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - (COALESCE(f.land_share_value, 0) + COALESCE(f.development_charges, 0) + CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END))) AS construction_fee,
                        COALESCE(p_agg.total_paid, 0) AS total_paid,
                        COALESCE(p_agg.land_share_paid, 0) AS land_share_paid,
                        COALESCE(p_agg.dev_charge_paid, 0) AS dev_charge_paid,
                        COALESCE(p_agg.shop_service_charge_paid, 0) AS shop_service_charge_paid,
                        COALESCE(p_agg.emi_paid, 0) AS emi_paid,
                        COALESCE(p_agg.overdue_emi_amount, 0) AS overdue_emi_amount,
                        GREATEST(0, ROUND(GREATEST(0, COALESCE(f.land_share_value, 0) - COALESCE(lb.discount_amount, 0)) - COALESCE(p_agg.land_share_paid, 0))) AS raw_ls_due,
                        GREATEST(0, ROUND(COALESCE(f.development_charges, 0) - COALESCE(p_agg.dev_charge_paid, 0))) AS raw_dev_due,
                        GREATEST(0, ROUND(CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END - COALESCE(p_agg.shop_service_charge_paid, 0))) AS raw_shop_due,
                        GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - COALESCE(lb.discount_amount, 0) - COALESCE(p_agg.total_paid, 0) - COALESCE(p_agg.total_bulk_emi_discount, 0))) AS overall_due
                    FROM lead_bookings lb
                    INNER JOIN leads l ON lb.lead_id = l.id
                    LEFT JOIN flats f ON lb.flat_id = f.id
                    LEFT JOIN (
                        SELECT 
                            lp.booking_id,
                            SUM(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS total_paid,
                            SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                                          AND lp.payment_status IN ('paid', 'partially_paid') 
                                          AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS land_share_paid,
                            SUM(CASE WHEN lp.payment_type = 'development_charge' 
                                          AND lp.payment_status IN ('paid', 'partially_paid') 
                                          AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS dev_charge_paid,
                            SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                                          AND lp.payment_status IN ('paid', 'partially_paid') 
                                          AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS shop_service_charge_paid,
                            SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                          AND lp.payment_status IN ('paid', 'partially_paid') 
                                          AND lp.verification_status = 'verified' 
                                          AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS emi_paid,
                            SUM(CASE WHEN lp.payment_type = 'emi' AND lp.due_emi_amount > 0 AND lp.verification_status = 'verified' 
                                          AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') THEN lp.due_emi_amount ELSE 0 END) AS overdue_emi_amount,
                            SUM(CASE WHEN (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1) AND lp.verification_status = 'verified' 
                                     THEN CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END ELSE 0 END) AS total_bulk_emi_discount
                        FROM lead_payments lp
                        WHERE lp.is_trashed = 0
                        GROUP BY lp.booking_id
                    ) AS p_agg ON p_agg.booking_id = lb.id
                    WHERE lb.deleted_at IS NULL
                      AND lb.booking_status NOT IN ('refunded', 'cancelled')
                      AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                      AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                ) AS raw_c
            ) AS calc
            INNER JOIN projects p ON p.id = calc.project_id
            WHERE p.deleted_at IS NULL {$projectWhere}
            GROUP BY p.id, p.name
            ORDER BY overall_due DESC, total_clients DESC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("getProjectWiseDueBreakdown prepare failed: " . $this->db->error);
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $projects = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $totalVal = (float)$row['total_project_value'];
                $totalPaid = (float)$row['total_paid'];
                $ratio = ($totalVal > 0) ? round(($totalPaid / $totalVal) * 100, 1) : 0;
                $row['collection_ratio'] = $ratio;
                $row['registered_clients_count'] = (int)($row['registered_clients_count'] ?? 0);
                $projects[] = $row;
            }
        }

        return $projects;
    }

    /**
     * Get Clients Who Have Completed Their Land Share for a Given Project
     * (Only counting verified active payments)
     *
     * @param int $projectId
     * @return array
     */
    public function getRegisteredClientsByProject($projectId)
    {
        $projectId = (int)$projectId;
        if ($projectId <= 0) {
            return [];
        }

        $sql = "
            SELECT 
                calc.*
            FROM (
                SELECT 
                    lb.id AS booking_id,
                    lb.lead_id,
                    lb.project_id,
                    p.name AS project_name,
                    f.id AS flat_id,
                    f.flat_name AS unit_name,
                    f.floor_no,
                    COALESCE(f.is_shop, 0) AS is_shop,
                    f.unit_type,
                    l.full_name AS client_name,
                    COALESCE(NULLIF(TRIM(l.phone), ''), TRIM(l.phone_number)) AS phone,
                    l.email,
                    u.full_name AS sales_agent_name,
                    COALESCE(lb.booking_date, DATE(lb.created_at)) AS booking_date,
                    COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) AS total_price,
                    COALESCE(f.land_share_value, 0) AS total_land_share,
                    COALESCE(lb.discount_amount, 0) AS discount_amount,
                    COALESCE(p_agg.total_paid, 0) AS total_paid,
                    COALESCE(p_agg.land_share_paid, 0) AS land_share_paid,
                    p_agg.last_payment_date,
                    GREATEST(0, ROUND(GREATEST(0, COALESCE(f.land_share_value, 0) - COALESCE(lb.discount_amount, 0)) - COALESCE(p_agg.land_share_paid, 0))) AS land_share_due,
                    GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - COALESCE(lb.discount_amount, 0) - COALESCE(p_agg.total_paid, 0) - COALESCE(p_agg.total_bulk_emi_discount, 0))) AS overall_due
                FROM lead_bookings lb
                INNER JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
                LEFT JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN users u ON COALESCE(NULLIF(lb.sales_by_id, 0), NULLIF(l.assigned_id, 0)) = u.id
                LEFT JOIN (
                    SELECT 
                        lp.booking_id,
                        SUM(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS total_paid,
                        SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                                      AND lp.payment_status IN ('paid', 'partially_paid') 
                                      AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS land_share_paid,
                        SUM(CASE WHEN (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1) AND lp.verification_status = 'verified' 
                                 THEN CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END ELSE 0 END) AS total_bulk_emi_discount,
                        MAX(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.payment_date ELSE NULL END) AS last_payment_date
                    FROM lead_payments lp
                    WHERE lp.is_trashed = 0
                    GROUP BY lp.booking_id
                ) AS p_agg ON p_agg.booking_id = lb.id
                WHERE lb.deleted_at IS NULL
                  AND lb.booking_status NOT IN ('refunded', 'cancelled')
                  AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                  AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                  AND lb.project_id = ?
            ) AS calc
            WHERE calc.land_share_due <= 0 AND calc.total_land_share > 0
            ORDER BY calc.client_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("getRegisteredClientsByProject prepare failed: " . $this->db->error);
            return [];
        }

        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get Client-wise Due List with Filters, Search, Sorting, and Pagination
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getClientWiseDueList($filters = [], $limit = 20, $offset = 0)
    {
        $projectId = !empty($filters['project_id']) ? $filters['project_id'] : null;
        $dueType   = !empty($filters['due_type']) ? trim($filters['due_type']) : 'all';
        $search    = !empty($filters['search']) ? trim($filters['search']) : null;
        $dateType  = !empty($filters['date_type']) ? trim($filters['date_type']) : null;
        $startDate = !empty($filters['start_date']) ? trim($filters['start_date']) : null;
        $endDate   = !empty($filters['end_date']) ? trim($filters['end_date']) : null;
        $sortBy    = !empty($filters['sort_by']) ? trim($filters['sort_by']) : 'booking_id';
        $sortOrder = (!empty($filters['sort_order']) && strtoupper($filters['sort_order']) === 'ASC') ? 'ASC' : 'DESC';

        $whereParts = [
            "lb.deleted_at IS NULL",
            "lb.booking_status NOT IN ('refunded', 'cancelled')",
            "(lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')",
            "lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))"
        ];
        $params = [];
        $types = "";
        $currentEndOfMonth = date('Y-m-t');

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereParts[] = "lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= "i";
                    }
                }
            } else {
                $whereParts[] = "lb.project_id = ?";
                $params[] = (int)$projectId;
                $types .= "i";
            }
        }

        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $whereParts[] = "(l.full_name LIKE ? OR l.lead_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ? OR l.email LIKE ? OR p.name LIKE ? OR f.flat_name LIKE ? OR u.full_name LIKE ? OR CAST(lb.id AS CHAR) = ?)";
            for ($i = 0; $i < 8; $i++) {
                $params[] = $searchTerm;
                $types .= "s";
            }
            $params[] = $search;
            $types .= "s";
        }

        if (!empty($startDate) || !empty($endDate)) {
            $targetDateType = !empty($dateType) ? $dateType : 'booking_date';
            if ($targetDateType === 'booking_date') {
                if (!empty($startDate)) {
                    $whereParts[] = "DATE(COALESCE(lb.booking_date, lb.created_at)) >= ?";
                    $params[] = $startDate;
                    $types .= "s";
                }
                if (!empty($endDate)) {
                    $whereParts[] = "DATE(COALESCE(lb.booking_date, lb.created_at)) <= ?";
                    $params[] = $endDate;
                    $types .= "s";
                }
            } elseif ($targetDateType === 'last_payment_date') {
                $whereParts[] = "p_agg.last_payment_date IS NOT NULL";
                if (!empty($startDate)) {
                    $whereParts[] = "DATE(p_agg.last_payment_date) >= ?";
                    $params[] = $startDate;
                    $types .= "s";
                }
                if (!empty($endDate)) {
                    $whereParts[] = "DATE(p_agg.last_payment_date) <= ?";
                    $params[] = $endDate;
                    $types .= "s";
                }
            }
        }

        $whereClause = "WHERE " . implode(" AND ", $whereParts);

        $outerWhere = ["1=1"];
        if ($dueType === 'land_share_due') {
            $outerWhere[] = "calc.land_share_due > 0";
        } elseif ($dueType === 'land_share_cleared' || $dueType === 'registered') {
            $outerWhere[] = "(calc.land_share_due <= 0 AND calc.total_land_share > 0)";
        } elseif ($dueType === 'dev_charge_due') {
            $outerWhere[] = "calc.dev_charge_due > 0";
        } elseif ($dueType === 'shop_service_due') {
            $outerWhere[] = "(calc.is_shop = 1 AND calc.shop_service_charge_due > 0)";
        } elseif ($dueType === 'emi_due') {
            $outerWhere[] = "(calc.remaining_emi_due > 0 OR calc.overdue_emi_amount > 0)";
        } elseif ($dueType === 'regular_emi_paid') {
            $outerWhere[] = "calc.regular_emi_paid > 0";
        } elseif ($dueType === 'advance_emi_paid') {
            $outerWhere[] = "calc.advance_emi_paid > 0";
        } elseif ($dueType === 'overall_due') {
            $outerWhere[] = "calc.overall_due > 0";
        } elseif ($dueType === 'cleared') {
            $outerWhere[] = "calc.overall_due <= 0";
        }
        $outerWhereClause = "WHERE " . implode(" AND ", $outerWhere);

        // Sorting mapping
        $sortColumnMap = [
            'booking_id'              => 'calc.booking_id',
            'client_name'             => 'calc.client_name',
            'project_name'            => 'calc.project_name',
            'overall_due'             => 'calc.overall_due',
            'land_share_due'          => 'calc.land_share_due',
            'dev_charge_due'          => 'calc.dev_charge_due',
            'shop_service_charge_due' => 'calc.shop_service_charge_due',
            'remaining_emi_due'       => 'calc.remaining_emi_due',
            'regular_emi_paid'        => 'calc.regular_emi_paid',
            'advance_emi_paid'        => 'calc.advance_emi_paid',
            'total_paid'              => 'calc.total_paid',
            'total_price'             => 'calc.total_price',
            'last_payment_date'       => 'calc.last_payment_date'
        ];
        $orderCol = $sortColumnMap[$sortBy] ?? 'calc.booking_id';

        $sql = "
            SELECT 
                calc.*
            FROM (
                SELECT 
                    lb.id AS booking_id,
                    lb.lead_id,
                    lb.project_id,
                    p.name AS project_name,
                    f.id AS flat_id,
                    f.flat_name AS unit_name,
                    f.floor_no,
                    COALESCE(f.is_shop, 0) AS is_shop,
                    f.unit_type,
                    COALESCE(f.shop_service_charge, 0) AS shop_service_charge_target,
                    l.full_name AS client_name,
                    COALESCE(NULLIF(TRIM(l.phone), ''), TRIM(l.phone_number)) AS phone,
                    l.email,
                    u.full_name AS sales_agent_name,
                    COALESCE(lb.booking_date, DATE(lb.created_at)) AS booking_date,
                    COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) AS total_price,
                    COALESCE(f.land_share_value, 0) AS total_land_share,
                    COALESCE(f.development_charges, 0) AS development_charges,
                    COALESCE(lb.discount_amount, 0) AS discount_amount,
                    GREATEST(
                        COALESCE(
                            NULLIF(CASE WHEN lb.total_emi_no > 0 AND lb.total_emi_no <= 120 THEN lb.total_emi_no ELSE NULL END, 0),
                            NULLIF(p_agg.max_payment_total_emi, 0),
                            NULLIF(es_agg.max_sched_emi_no, 0),
                            0
                        ),
                        COALESCE(es_agg.completed_emi_count, 0),
                        COALESCE(p_agg.direct_emi_count, 0)
                    ) AS total_emi_no,
                    GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - (COALESCE(f.land_share_value, 0) + COALESCE(f.development_charges, 0) + CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END))) AS construction_fee,
                    COALESCE(p_agg.total_paid, 0) AS total_paid,
                    COALESCE(p_agg.land_share_paid, 0) AS land_share_paid,
                    COALESCE(p_agg.dev_charge_paid, 0) AS dev_charge_paid,
                    COALESCE(p_agg.shop_service_charge_paid, 0) AS shop_service_charge_paid,
                    COALESCE(p_agg.emi_paid, 0) AS emi_paid,
                    COALESCE(p_agg.overdue_emi_amount, 0) AS overdue_emi_amount,
                    COALESCE(p_agg.total_bulk_emi_discount, 0) AS total_bulk_emi_discount,
                    GREATEST(
                        COALESCE(es_agg.completed_emi_count, 0),
                        COALESCE(p_agg.direct_emi_count, 0)
                    ) AS completed_emi_count,
                    COALESCE(p_agg.pending_payment_count, 0) AS pending_payment_count,
                    COALESCE(p_agg.pending_emi_payment_count, 0) AS pending_emi_payment_count,
                    p_agg.last_payment_date,
                    COALESCE(es_agg.max_regular_paid_due_date, NULL) AS max_regular_paid_due_date,
                    COALESCE(es_agg.max_regular_paid_emi_no, 0) AS max_regular_paid_emi_no,
                    COALESCE(es_agg.regular_completed_emi_count, 0) AS regular_completed_emi_count,
                    COALESCE(es_agg.advance_emi_count, 0) AS advance_emi_count,
                    COALESCE(es_agg.advance_emi_numbers, '') AS advance_emi_numbers,
                    COALESCE(es_agg.max_advance_due_date, NULL) AS max_advance_due_date,
                    COALESCE(es_agg.advance_emi_paid, 0) AS advance_emi_paid,
                    GREATEST(0, COALESCE(p_agg.emi_paid, 0) - COALESCE(es_agg.advance_emi_paid, 0)) AS regular_emi_paid,
                    COALESCE(p_agg.last_regular_paid_amount, p_agg.last_emi_paid_amount, 0) AS last_regular_paid_amount,
                    COALESCE(p_agg.last_regular_payment_date, p_agg.last_emi_payment_date) AS last_regular_payment_date,
                    p_agg.last_emi_payment_date,
                    GREATEST(0, ROUND(GREATEST(0, COALESCE(f.land_share_value, 0) - COALESCE(lb.discount_amount, 0)) - COALESCE(p_agg.land_share_paid, 0))) AS land_share_due,
                    GREATEST(0, ROUND(COALESCE(f.development_charges, 0) - COALESCE(p_agg.dev_charge_paid, 0))) AS dev_charge_due,
                    GREATEST(0, ROUND(CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END - COALESCE(p_agg.shop_service_charge_paid, 0))) AS shop_service_charge_due,
                    GREATEST(0, ROUND(GREATEST(0, COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - (COALESCE(f.land_share_value, 0) + COALESCE(f.development_charges, 0) + CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END)) - COALESCE(p_agg.emi_paid, 0) - COALESCE(p_agg.total_bulk_emi_discount, 0))) AS remaining_emi_due,
                    GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - COALESCE(lb.discount_amount, 0) - COALESCE(p_agg.total_paid, 0) - COALESCE(p_agg.total_bulk_emi_discount, 0))) AS overall_due
                FROM lead_bookings lb
                INNER JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
                LEFT JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN users u ON COALESCE(NULLIF(lb.sales_by_id, 0), NULLIF(l.assigned_id, 0)) = u.id
                LEFT JOIN (
                    SELECT 
                        lp.booking_id,
                        MAX(CASE WHEN lp.total_emi_no IS NOT NULL AND lp.total_emi_no > 0 AND lp.total_emi_no <= 120 THEN lp.total_emi_no ELSE 0 END) AS max_payment_total_emi,
                        SUM(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS total_paid,
                        SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                                      AND lp.payment_status IN ('paid', 'partially_paid') 
                                      AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS land_share_paid,
                        SUM(CASE WHEN lp.payment_type = 'development_charge' 
                                      AND lp.payment_status IN ('paid', 'partially_paid') 
                                      AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS dev_charge_paid,
                        SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                                      AND lp.payment_status IN ('paid', 'partially_paid') 
                                      AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS shop_service_charge_paid,
                        SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                      AND lp.payment_status IN ('paid', 'partially_paid') 
                                      AND lp.verification_status = 'verified' 
                                      AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS emi_paid,
                        SUM(CASE WHEN lp.payment_type = 'emi' AND lp.due_emi_amount > 0 AND lp.verification_status = 'verified' 
                                      AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') THEN lp.due_emi_amount ELSE 0 END) AS overdue_emi_amount,
                        SUM(CASE WHEN (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1) AND lp.verification_status = 'verified' 
                                 THEN CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END ELSE 0 END) AS total_bulk_emi_discount,
                        COUNT(DISTINCT CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') AND lp.payment_status = 'paid' AND lp.verification_status = 'verified' AND lp.emi_no IS NOT NULL AND lp.emi_no > 0 THEN lp.emi_no ELSE NULL END) AS direct_emi_count,
                        SUM(CASE WHEN lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS pending_payment_count,
                        SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') AND lp.verification_status = 'pending' THEN 1 ELSE 0 END) AS pending_emi_payment_count,
                        MAX(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.payment_date ELSE NULL END) AS last_payment_date,
                        MAX(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.payment_date ELSE NULL END) AS last_emi_payment_date,
                        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.amount END ORDER BY lp.payment_date DESC, lp.id DESC), ',', 1) AS last_emi_paid_amount,
                        MAX(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND DATE(lp.payment_date) <= '{$currentEndOfMonth}' THEN lp.payment_date ELSE NULL END) AS last_regular_payment_date,
                        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND DATE(lp.payment_date) <= '{$currentEndOfMonth}' THEN lp.amount END ORDER BY lp.payment_date DESC, lp.id DESC), ',', 1) AS last_regular_paid_amount
                    FROM lead_payments lp
                    WHERE lp.is_trashed = 0
                    GROUP BY lp.booking_id
                ) AS p_agg ON p_agg.booking_id = lb.id
                LEFT JOIN (
                    SELECT 
                        es.lead_booking_id AS booking_id,
                        COUNT(DISTINCT CASE 
                            WHEN (lp.id IS NOT NULL AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' AND lp.is_trashed = 0)
                              OR (es.lead_payment_id IS NULL AND es.status = 'paid')
                            THEN es.emi_no 
                            ELSE NULL 
                        END) AS completed_emi_count,
                        COUNT(DISTINCT CASE 
                            WHEN ((lp.id IS NOT NULL AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' AND lp.is_trashed = 0)
                              OR (es.lead_payment_id IS NULL AND es.status = 'paid'))
                              AND es.due_date <= '{$currentEndOfMonth}'
                            THEN es.emi_no 
                            ELSE NULL 
                        END) AS regular_completed_emi_count,
                        MAX(CASE 
                            WHEN ((lp.id IS NOT NULL AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' AND lp.is_trashed = 0)
                              OR (es.lead_payment_id IS NULL AND es.status = 'paid'))
                              AND es.due_date <= '{$currentEndOfMonth}'
                            THEN es.due_date 
                            ELSE NULL 
                        END) AS max_regular_paid_due_date,
                        MAX(CASE 
                            WHEN ((lp.id IS NOT NULL AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' AND lp.is_trashed = 0)
                              OR (es.lead_payment_id IS NULL AND es.status = 'paid'))
                              AND es.due_date <= '{$currentEndOfMonth}'
                            THEN es.emi_no 
                            ELSE 0 
                        END) AS max_regular_paid_emi_no,
                        COUNT(DISTINCT CASE 
                            WHEN ((lp.id IS NOT NULL AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' AND lp.is_trashed = 0)
                              OR (es.lead_payment_id IS NULL AND es.status = 'paid'))
                              AND es.due_date > '{$currentEndOfMonth}'
                            THEN es.emi_no 
                            ELSE NULL 
                        END) AS advance_emi_count,
                        GROUP_CONCAT(DISTINCT CASE 
                            WHEN ((lp.id IS NOT NULL AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' AND lp.is_trashed = 0)
                              OR (es.lead_payment_id IS NULL AND es.status = 'paid'))
                              AND es.due_date > '{$currentEndOfMonth}'
                            THEN es.emi_no 
                            ELSE NULL 
                        END ORDER BY es.emi_no SEPARATOR ', ') AS advance_emi_numbers,
                        MAX(CASE 
                            WHEN ((lp.id IS NOT NULL AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' AND lp.is_trashed = 0)
                              OR (es.lead_payment_id IS NULL AND es.status = 'paid'))
                              AND es.due_date > '{$currentEndOfMonth}'
                            THEN es.due_date 
                            ELSE NULL 
                        END) AS max_advance_due_date,
                        SUM(CASE 
                            WHEN ((lp.id IS NOT NULL AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' AND lp.is_trashed = 0)
                              OR (es.lead_payment_id IS NULL AND es.status = 'paid'))
                              AND es.due_date > '{$currentEndOfMonth}'
                            THEN es.emi_amount 
                            ELSE 0 
                        END) AS advance_emi_paid,
                        MAX(es.emi_no) AS max_sched_emi_no
                    FROM emi_schedules es
                    LEFT JOIN lead_payments lp ON lp.id = es.lead_payment_id AND lp.is_trashed = 0
                    GROUP BY es.lead_booking_id
                ) AS es_agg ON es_agg.booking_id = lb.id
                $whereClause
            ) AS calc
            $outerWhereClause
            ORDER BY {$orderCol} {$sortOrder}
        ";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            $types .= "ii";
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("getClientWiseDueList prepare failed: " . $this->db->error);
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get Total Count of Clients Matching Due Filters
     *
     * @param array $filters
     * @return int
     */
    public function getClientWiseDueCount($filters = [])
    {
        $projectId = !empty($filters['project_id']) ? $filters['project_id'] : null;
        $dueType   = !empty($filters['due_type']) ? trim($filters['due_type']) : 'all';
        $search    = !empty($filters['search']) ? trim($filters['search']) : null;
        $dateType  = !empty($filters['date_type']) ? trim($filters['date_type']) : null;
        $startDate = !empty($filters['start_date']) ? trim($filters['start_date']) : null;
        $endDate   = !empty($filters['end_date']) ? trim($filters['end_date']) : null;

        $whereParts = [
            "lb.deleted_at IS NULL",
            "lb.booking_status NOT IN ('refunded', 'cancelled')",
            "(lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')",
            "lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))"
        ];
        $params = [];
        $types = "";
        $currentEndOfMonth = date('Y-m-t');

        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $inPlaceholders = implode(',', array_fill(0, count($pIds), '?'));
                    $whereParts[] = "lb.project_id IN ($inPlaceholders)";
                    foreach ($pIds as $pId) {
                        $params[] = $pId;
                        $types .= "i";
                    }
                }
            } else {
                $whereParts[] = "lb.project_id = ?";
                $params[] = (int)$projectId;
                $types .= "i";
            }
        }

        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $whereParts[] = "(l.full_name LIKE ? OR l.lead_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ? OR l.email LIKE ? OR p.name LIKE ? OR f.flat_name LIKE ? OR u.full_name LIKE ? OR CAST(lb.id AS CHAR) = ?)";
            for ($i = 0; $i < 8; $i++) {
                $params[] = $searchTerm;
                $types .= "s";
            }
            $params[] = $search;
            $types .= "s";
        }

        if (!empty($startDate) || !empty($endDate)) {
            $targetDateType = !empty($dateType) ? $dateType : 'booking_date';
            if ($targetDateType === 'booking_date') {
                if (!empty($startDate)) {
                    $whereParts[] = "DATE(COALESCE(lb.booking_date, lb.created_at)) >= ?";
                    $params[] = $startDate;
                    $types .= "s";
                }
                if (!empty($endDate)) {
                    $whereParts[] = "DATE(COALESCE(lb.booking_date, lb.created_at)) <= ?";
                    $params[] = $endDate;
                    $types .= "s";
                }
            } elseif ($targetDateType === 'last_payment_date') {
                $whereParts[] = "p_agg.last_payment_date IS NOT NULL";
                if (!empty($startDate)) {
                    $whereParts[] = "DATE(p_agg.last_payment_date) >= ?";
                    $params[] = $startDate;
                    $types .= "s";
                }
                if (!empty($endDate)) {
                    $whereParts[] = "DATE(p_agg.last_payment_date) <= ?";
                    $params[] = $endDate;
                    $types .= "s";
                }
            }
        }

        $whereClause = "WHERE " . implode(" AND ", $whereParts);

        $outerWhere = ["1=1"];
        if ($dueType === 'land_share_due') {
            $outerWhere[] = "calc.land_share_due > 0";
        } elseif ($dueType === 'land_share_cleared' || $dueType === 'registered') {
            $outerWhere[] = "(calc.land_share_due <= 0 AND calc.total_land_share > 0)";
        } elseif ($dueType === 'dev_charge_due') {
            $outerWhere[] = "calc.dev_charge_due > 0";
        } elseif ($dueType === 'shop_service_due') {
            $outerWhere[] = "(calc.is_shop = 1 AND calc.shop_service_charge_due > 0)";
        } elseif ($dueType === 'emi_due') {
            $outerWhere[] = "(calc.remaining_emi_due > 0 OR calc.overdue_emi_amount > 0)";
        } elseif ($dueType === 'regular_emi_paid') {
            $outerWhere[] = "calc.regular_emi_paid > 0";
        } elseif ($dueType === 'advance_emi_paid') {
            $outerWhere[] = "calc.advance_emi_paid > 0";
        } elseif ($dueType === 'overall_due') {
            $outerWhere[] = "calc.overall_due > 0";
        } elseif ($dueType === 'cleared') {
            $outerWhere[] = "calc.overall_due <= 0";
        }
        $outerWhereClause = "WHERE " . implode(" AND ", $outerWhere);

        $sql = "
            SELECT COUNT(*) AS total_count
            FROM (
                SELECT 
                    lb.id,
                    COALESCE(f.is_shop, 0) AS is_shop,
                    COALESCE(f.land_share_value, 0) AS total_land_share,
                    GREATEST(0, ROUND(GREATEST(0, COALESCE(f.land_share_value, 0) - COALESCE(lb.discount_amount, 0)) - COALESCE(p_agg.land_share_paid, 0))) AS land_share_due,
                    GREATEST(0, ROUND(COALESCE(f.development_charges, 0) - COALESCE(p_agg.dev_charge_paid, 0))) AS dev_charge_due,
                    GREATEST(0, ROUND(CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END - COALESCE(p_agg.shop_service_charge_paid, 0))) AS shop_service_charge_due,
                    GREATEST(0, ROUND(GREATEST(0, COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - (COALESCE(f.land_share_value, 0) + COALESCE(f.development_charges, 0) + CASE WHEN COALESCE(f.is_shop, 0) = 1 THEN COALESCE(f.shop_service_charge, 0) ELSE 0 END)) - COALESCE(p_agg.emi_paid, 0) - COALESCE(p_agg.total_bulk_emi_discount, 0))) AS remaining_emi_due,
                    COALESCE(p_agg.overdue_emi_amount, 0) AS overdue_emi_amount,
                    COALESCE(es_agg.advance_emi_paid, 0) AS advance_emi_paid,
                    GREATEST(0, COALESCE(p_agg.emi_paid, 0) - COALESCE(es_agg.advance_emi_paid, 0)) AS regular_emi_paid,
                    GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - COALESCE(lb.discount_amount, 0) - COALESCE(p_agg.total_paid, 0) - COALESCE(p_agg.total_bulk_emi_discount, 0))) AS overall_due
                FROM lead_bookings lb
                INNER JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN (
                    SELECT 
                        lp.booking_id,
                        SUM(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS total_paid,
                        SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') 
                                      AND lp.payment_status IN ('paid', 'partially_paid') 
                                      AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS land_share_paid,
                        SUM(CASE WHEN lp.payment_type = 'development_charge' 
                                      AND lp.payment_status IN ('paid', 'partially_paid') 
                                      AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS dev_charge_paid,
                        SUM(CASE WHEN lp.payment_type = 'shop_service_charge' 
                                      AND lp.payment_status IN ('paid', 'partially_paid') 
                                      AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS shop_service_charge_paid,
                        SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') 
                                      AND lp.payment_status IN ('paid', 'partially_paid') 
                                      AND lp.verification_status = 'verified' 
                                      AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS emi_paid,
                        SUM(CASE WHEN lp.payment_type = 'emi' AND lp.due_emi_amount > 0 AND lp.verification_status = 'verified' 
                                      AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') THEN lp.due_emi_amount ELSE 0 END) AS overdue_emi_amount,
                        SUM(CASE WHEN (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1) AND lp.verification_status = 'verified' 
                                 THEN CASE WHEN COALESCE(lp.is_emi_discount, 0) = 1 THEN lp.amount ELSE lp.bulk_emi_discount END ELSE 0 END) AS total_bulk_emi_discount
                    FROM lead_payments lp
                    WHERE lp.is_trashed = 0
                    GROUP BY lp.booking_id
                ) AS p_agg ON p_agg.booking_id = lb.id
                LEFT JOIN (
                    SELECT 
                        es.lead_booking_id AS booking_id,
                        SUM(CASE 
                            WHEN ((lp.id IS NOT NULL AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' AND lp.is_trashed = 0)
                              OR (es.lead_payment_id IS NULL AND es.status = 'paid'))
                              AND es.due_date > '{$currentEndOfMonth}'
                            THEN es.emi_amount 
                            ELSE 0 
                        END) AS advance_emi_paid
                    FROM emi_schedules es
                    LEFT JOIN lead_payments lp ON lp.id = es.lead_payment_id AND lp.is_trashed = 0
                    GROUP BY es.lead_booking_id
                ) AS es_agg ON es_agg.booking_id = lb.id
                $whereClause
            ) AS calc
            $outerWhereClause
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("getClientWiseDueCount prepare failed: " . $this->db->error);
            return 0;
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? (int)($res->fetch_assoc()['total_count'] ?? 0) : 0;
    }

    /**
     * Get EMI Analytics Summary (Total EMI Paid, Regular EMI Paid, Advance EMI Paid, Due EMI)
     *
     * @param int|null $projectId
     * @return array
     */
    public function getEmiAnalyticsSummary($projectId = null)
    {
        $currentEndOfMonth = date('Y-m-t'); // e.g. current month end
        $projectFilterLb = "";
        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $projectFilterLb = " AND lb.project_id IN (" . implode(',', $pIds) . ")";
                }
            } elseif ((int)$projectId > 0) {
                $projectFilterLb = " AND lb.project_id = " . (int)$projectId;
            }
        }

        $sql = "
            SELECT 
                COUNT(DISTINCT c.booking_id) AS total_emi_clients,
                COALESCE(SUM(c.total_emi_paid), 0) AS total_emi_paid,
                
                COUNT(DISTINCT CASE WHEN (c.max_paid_due_date IS NULL OR c.max_paid_due_date <= '{$currentEndOfMonth}') AND c.total_emi_paid > 0 THEN c.booking_id END) AS regular_emi_clients,
                COALESCE(SUM(c.regular_emi_paid), 0) AS regular_emi_paid,
                
                COUNT(DISTINCT CASE WHEN c.max_paid_due_date > '{$currentEndOfMonth}' THEN c.booking_id END) AS advance_emi_clients,
                COALESCE(SUM(c.advance_emi_paid), 0) AS advance_emi_paid,
                
                COUNT(DISTINCT CASE WHEN c.overdue_emi_amount > 0 THEN c.booking_id END) AS due_emi_clients,
                COALESCE(SUM(c.overdue_emi_amount), 0) AS due_emi_amount
            FROM (
                SELECT 
                    lb.id AS booking_id,
                    COALESCE(p_agg.total_emi_paid, 0) AS total_emi_paid,
                    COALESCE(p_agg.overdue_emi_amount, 0) AS overdue_emi_amount,
                    es_agg.max_paid_due_date,
                    COALESCE(es_agg.advance_emi_paid, 0) AS advance_emi_paid,
                    GREATEST(0, COALESCE(p_agg.total_emi_paid, 0) - COALESCE(es_agg.advance_emi_paid, 0)) AS regular_emi_paid
                FROM lead_bookings lb
                INNER JOIN (
                    SELECT 
                        lp.booking_id,
                        SUM(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS total_emi_paid,
                        SUM(CASE WHEN lp.payment_type = 'emi' AND lp.due_emi_amount > 0 AND lp.verification_status = 'verified' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') THEN lp.due_emi_amount ELSE 0 END) AS overdue_emi_amount
                    FROM lead_payments lp
                    WHERE lp.is_trashed = 0 AND lp.payment_type IN ('emi', 'emi_due_settlement')
                    GROUP BY lp.booking_id
                    HAVING total_emi_paid > 0 OR overdue_emi_amount > 0
                ) AS p_agg ON p_agg.booking_id = lb.id
                LEFT JOIN (
                    SELECT 
                        es.lead_booking_id AS booking_id,
                        MAX(CASE WHEN es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified') THEN es.emi_no ELSE 0 END) AS max_paid_emi_no,
                        MAX(CASE WHEN es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified') THEN es.due_date ELSE NULL END) AS max_paid_due_date,
                        SUM(CASE WHEN (es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified')) AND es.due_date > '{$currentEndOfMonth}' THEN es.emi_amount ELSE 0 END) AS advance_emi_paid
                    FROM emi_schedules es
                    LEFT JOIN lead_payments lp ON lp.id = es.lead_payment_id AND lp.is_trashed = 0
                    GROUP BY es.lead_booking_id
                ) AS es_agg ON es_agg.booking_id = lb.id
                WHERE lb.deleted_at IS NULL
                  AND lb.booking_status NOT IN ('refunded', 'cancelled')
                  AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                  AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                  {$projectFilterLb}
            ) AS c
        ";

        $res = $this->db->query($sql);
        if ($res && ($row = $res->fetch_assoc())) {
            return [
                'total_emi_paid'       => (float)($row['total_emi_paid'] ?? 0),
                'total_emi_clients'    => (int)($row['total_emi_clients'] ?? 0),
                'regular_emi_paid'     => (float)($row['regular_emi_paid'] ?? 0),
                'regular_emi_clients'  => (int)($row['regular_emi_clients'] ?? 0),
                'advance_emi_paid'     => (float)($row['advance_emi_paid'] ?? 0),
                'advance_emi_clients'  => (int)($row['advance_emi_clients'] ?? 0),
                'due_emi_amount'       => (float)($row['due_emi_amount'] ?? 0),
                'due_emi_clients'      => (int)($row['due_emi_clients'] ?? 0)
            ];
        }

        return [
            'total_emi_paid'       => 0,
            'total_emi_clients'    => 0,
            'regular_emi_paid'     => 0,
            'regular_emi_clients'  => 0,
            'advance_emi_paid'     => 0,
            'advance_emi_clients'  => 0,
            'due_emi_amount'       => 0,
            'due_emi_clients'      => 0
        ];
    }

    /**
     * Get EMI Analytics Clients Breakdown List
     *
     * @param string $category 'all' | 'regular' | 'advance' | 'due'
     * @param int|null $projectId
     * @param string|null $search
     * @param int $limit
     * @return array
     */
    public function getEmiAnalyticsClientsList($category = 'all', $projectId = null, $search = null, $limit = 500)
    {
        $currentEndOfMonth = date('Y-m-t'); // e.g. current month end
        $projectFilterLb = "";
        if (!empty($projectId)) {
            if (is_array($projectId)) {
                $pIds = array_values(array_filter(array_map('intval', $projectId)));
                if (!empty($pIds)) {
                    $projectFilterLb = " AND lb.project_id IN (" . implode(',', $pIds) . ")";
                }
            } elseif ((int)$projectId > 0) {
                $projectFilterLb = " AND lb.project_id = " . (int)$projectId;
            }
        }

        $whereCategory = "";
        if ($category === 'advance') {
            $whereCategory = "AND c.advance_emi_paid > 0";
        } elseif ($category === 'regular') {
            $whereCategory = "AND c.regular_emi_paid > 0";
        } elseif ($category === 'due') {
            $whereCategory = "AND c.overdue_emi_amount > 0";
        } else {
            // 'all'
            $whereCategory = "AND (c.total_emi_paid > 0 OR c.overdue_emi_amount > 0)";
        }

        $searchClause = "";
        $params = [];
        $types = "";
        if (!empty($search)) {
            $searchClause = " AND (
                l.full_name LIKE ? OR 
                l.phone LIKE ? OR 
                l.phone_number LIKE ? OR 
                f.flat_name LIKE ? OR 
                p.name LIKE ? OR
                lb.id LIKE ?
            )";
            $sParam = '%' . trim($search) . '%';
            $params = [$sParam, $sParam, $sParam, $sParam, $sParam, $sParam];
            $types = "ssssss";
        }

        $limitVal = max(1, min(1000, (int)$limit));

        $orderSql = "c.total_emi_paid DESC";
        if ($category === 'due') {
            $orderSql = "c.overdue_emi_amount DESC, c.total_emi_paid DESC";
        } elseif ($category === 'advance') {
            $orderSql = "c.advance_emi_paid DESC, c.max_paid_due_date DESC";
        }

        $sql = "
            SELECT 
                c.*,
                l.full_name AS client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), TRIM(l.phone_number)) AS phone,
                l.email,
                p.name AS project_name,
                f.flat_name AS unit_name,
                f.floor_no,
                COALESCE(f.is_shop, 0) AS is_shop,
                u.full_name AS sales_agent_name,
                COALESCE(lb.booking_date, DATE(lb.created_at)) AS booking_date,
                GREATEST(0, ROUND(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - COALESCE(lb.discount_amount, 0) - COALESCE(p_all.total_paid, 0))) AS overall_due
            FROM (
                SELECT 
                    lb.id AS booking_id,
                    lb.lead_id,
                    lb.project_id,
                    lb.flat_id,
                    lb.sales_by_id,
                    COALESCE(p_agg.total_emi_paid, 0) AS total_emi_paid,
                    COALESCE(p_agg.overdue_emi_amount, 0) AS overdue_emi_amount,
                    p_agg.last_payment_date,
                    p_agg.last_paid_amount,
                    es_agg.max_paid_due_date,
                    COALESCE(es_agg.max_paid_emi_no, 0) AS max_paid_emi_no,
                    COALESCE(es_agg.max_regular_paid_due_date, NULL) AS max_regular_paid_due_date,
                    COALESCE(es_agg.max_regular_paid_emi_no, 0) AS max_regular_paid_emi_no,
                    COALESCE(es_agg.advance_emi_count, 0) AS advance_emi_count,
                    COALESCE(es_agg.advance_emi_numbers, '') AS advance_emi_numbers,
                    COALESCE(es_agg.max_advance_due_date, NULL) AS max_advance_due_date,
                    GREATEST(
                        COALESCE(
                            NULLIF(CASE WHEN lb.total_emi_no > 0 AND lb.total_emi_no <= 120 THEN lb.total_emi_no ELSE NULL END, 0),
                            NULLIF(es_agg.total_sched_emi_no, 0),
                            36
                        ),
                        COALESCE(es_agg.max_paid_emi_no, 0)
                    ) AS total_emi_no,
                    COALESCE(es_agg.advance_emi_paid, 0) AS advance_emi_paid,
                    GREATEST(0, COALESCE(p_agg.total_emi_paid, 0) - COALESCE(es_agg.advance_emi_paid, 0)) AS regular_emi_paid
                FROM lead_bookings lb
                INNER JOIN (
                    SELECT 
                        lp.booking_id,
                        SUM(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND COALESCE(lp.is_emi_discount, 0) = 0 THEN lp.amount ELSE 0 END) AS total_emi_paid,
                        SUM(CASE WHEN lp.payment_type = 'emi' AND lp.due_emi_amount > 0 AND lp.verification_status = 'verified' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') THEN lp.due_emi_amount ELSE 0 END) AS overdue_emi_amount,
                        MAX(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.payment_date ELSE NULL END) AS last_payment_date,
                        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.amount END ORDER BY lp.payment_date DESC, lp.id DESC), ',', 1) AS last_paid_amount
                    FROM lead_payments lp
                    WHERE lp.is_trashed = 0 AND lp.payment_type IN ('emi', 'emi_due_settlement')
                    GROUP BY lp.booking_id
                    HAVING total_emi_paid > 0 OR overdue_emi_amount > 0
                ) AS p_agg ON p_agg.booking_id = lb.id
                LEFT JOIN (
                    SELECT 
                        es.lead_booking_id AS booking_id,
                        MAX(CASE WHEN es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified') THEN es.emi_no ELSE 0 END) AS max_paid_emi_no,
                        MAX(CASE WHEN es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified') THEN es.due_date ELSE NULL END) AS max_paid_due_date,
                        MAX(CASE WHEN (es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified')) AND es.due_date <= '{$currentEndOfMonth}' THEN es.due_date ELSE NULL END) AS max_regular_paid_due_date,
                        MAX(CASE WHEN (es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified')) AND es.due_date <= '{$currentEndOfMonth}' THEN es.emi_no ELSE 0 END) AS max_regular_paid_emi_no,
                        COUNT(DISTINCT CASE WHEN (es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified')) AND es.due_date > '{$currentEndOfMonth}' THEN es.emi_no ELSE NULL END) AS advance_emi_count,
                        GROUP_CONCAT(DISTINCT CASE WHEN (es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified')) AND es.due_date > '{$currentEndOfMonth}' THEN es.emi_no ELSE NULL END ORDER BY es.emi_no SEPARATOR ', ') AS advance_emi_numbers,
                        MAX(CASE WHEN (es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified')) AND es.due_date > '{$currentEndOfMonth}' THEN es.due_date ELSE NULL END) AS max_advance_due_date,
                        MAX(es.emi_no) AS total_sched_emi_no,
                        SUM(CASE WHEN (es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified')) AND es.due_date > '{$currentEndOfMonth}' THEN es.emi_amount ELSE 0 END) AS advance_emi_paid
                    FROM emi_schedules es
                    LEFT JOIN lead_payments lp ON lp.id = es.lead_payment_id AND lp.is_trashed = 0
                    GROUP BY es.lead_booking_id
                ) AS es_agg ON es_agg.booking_id = lb.id
                WHERE lb.deleted_at IS NULL
                  AND lb.booking_status NOT IN ('refunded', 'cancelled')
                  AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                  AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                  {$projectFilterLb}
            ) AS c
            INNER JOIN lead_bookings lb ON c.booking_id = lb.id
            INNER JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u ON COALESCE(NULLIF(lb.sales_by_id, 0), NULLIF(l.assigned_id, 0)) = u.id
            LEFT JOIN (
                SELECT lp.booking_id, SUM(CASE WHEN lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' THEN lp.amount ELSE 0 END) AS total_paid
                FROM lead_payments lp
                WHERE lp.is_trashed = 0
                GROUP BY lp.booking_id
            ) AS p_all ON p_all.booking_id = lb.id
            WHERE 1=1
            {$whereCategory}
            {$searchClause}
            ORDER BY {$orderSql}
            LIMIT {$limitVal}
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("getEmiAnalyticsClientsList prepare failed: " . $this->db->error);
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            $res = $this->db->query($sql);
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }
    }
}


