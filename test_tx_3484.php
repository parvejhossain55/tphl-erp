<?php
$sortFixed = function (array &$transactions) {
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
};

$txs = [
    ['id' => 1, 'payment_date' => '2024-12-17', 'payment_type' => 'booking_money', 'installment_label' => 'BOOKING'],
    ['id' => 2, 'payment_date' => '2025-01-06', 'payment_type' => 'registration', 'installment_label' => 'REGISTRATION'],
    ['id' => 3, 'payment_date' => '2025-02-01', 'payment_type' => 'registration', 'installment_label' => 'REGISTRATION'],
    ['id' => 4, 'payment_date' => '2025-02-17', 'payment_type' => 'registration', 'installment_label' => 'REGISTRATION'],
    ['id' => 5, 'payment_date' => '2025-03-11', 'payment_type' => 'development_charge', 'installment_label' => 'DEV CHARGE'],
    ['id' => 6, 'payment_date' => '2025-04-22', 'payment_type' => 'emi', 'installment_label' => '1st Installment', 'emi_no' => 1],
    ['id' => 7, 'payment_date' => '2025-04-22', 'payment_type' => 'emi', 'installment_label' => '2nd Installment', 'emi_no' => 2],
    ['id' => 8, 'payment_date' => '2025-04-22', 'payment_type' => 'emi', 'installment_label' => '3rd Installment', 'emi_no' => 3, 'payment_status' => 'partially_paid'],
    ['id' => 9, 'payment_date' => '2026-03-30', 'payment_type' => 'emi', 'installment_label' => '4th Installment', 'emi_no' => 4],
    ['id' => 10, 'payment_date' => '2026-03-30', 'payment_type' => 'emi', 'installment_label' => '5th Installment', 'emi_no' => 5],
    ['id' => 11, 'payment_date' => '2026-03-30', 'payment_type' => 'emi', 'installment_label' => '6th Installment', 'emi_no' => 6],
    ['id' => 12, 'payment_date' => '2026-03-30', 'payment_type' => 'emi', 'settlement_type' => 'emi_due_settlement', 'installment_label' => '3rd Installment(Settlement)', 'payment_details' => 'EMI Due Settlement for 3rd Installment', 'emi_no' => 7, 'parent_emi_no' => 3],
];

shuffle($txs);
$sortFixed($txs);

echo "FIXED SORT RESULT:\n";
foreach ($txs as $idx => $t) {
    echo ($idx + 1) . ". ID: " . $t['id'] . " | " . $t['installment_label'] . "\n";
}
