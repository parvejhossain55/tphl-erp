<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

// Replace getCustomDailyStatement totals init
$old_daily_init = <<<'PHP'
        // Initialize totals
        $totals = [
            'landshare_payments' => 0,
            'registration_payments' => 0,
            'emi_payments' => 0,
            'booking_money' => 0,
            'service_fee' => 0,
            'management_cashin' => 0,
            'discount' => 0,
            'cash_out' => 0
        ];
PHP;
$new_daily_init = <<<'PHP'
        // Initialize totals
        $totals = [
            'landshare_payments' => 0,
            'registration_payments' => 0,
            'emi_payments' => 0,
            'booking_money' => 0,
            'service_fee' => 0,
            'management_cashin' => 0,
            'development_charge' => 0,
            'shop_service_charge' => 0,
            'expense_adjustment_cashin' => 0,
            'procurement_returns_cashin' => 0,
            'discount' => 0,
            'cash_out' => 0
        ];
PHP;
$content = str_replace($old_daily_init, $new_daily_init, $content);

// Replace getCustomDailyStatement fetching
$old_daily_fetch = <<<'PHP'
            $serviceFee = $this->getDailyServiceFeeSingle($date, $projectId);
            $managementCashin = $this->getDailyManagementCashinSingle($date);
            $discount = $this->getDailyDiscountSingle($date, $projectId);
PHP;
$new_daily_fetch = <<<'PHP'
            $serviceFee = $this->getDailyServiceFeeSingle($date, $projectId);
            $managementCashin = $this->getDailyManagementCashinSingle($date);
            $developmentCharge = $this->getDailyPaymentByTypeSingle('development_charge', $date, $projectId);
            $shopServiceCharge = $this->getDailyPaymentByTypeSingle('shop_service_charge', $date, $projectId);
            $expenseAdjCashin = $this->getTotalExpenseAdjustmentCashin($date, $date);
            $procurementReturns = $this->getTotalProcurementReturnsCashin($date, $date);
            $discount = $this->getDailyDiscountSingle($date, $projectId);
PHP;
$content = str_replace($old_daily_fetch, $new_daily_fetch, $content);

// Replace getCustomDailyStatement cashin calc
$old_daily_cashin = <<<'PHP'
            if ($category === 'all' || $category === 'total_cashin') {
                // Total cashin includes all revenue sources minus discount
                $cashin = $landsharePayments + $registrationPayments + $bookingMoney + $emiPayments + $managementCashin;
            } elseif ($category === 'emi') {
PHP;
$new_daily_cashin = <<<'PHP'
            if ($category === 'all' || $category === 'total_cashin') {
                // Total cashin includes all revenue sources minus discount
                $cashin = $landsharePayments + $registrationPayments + $bookingMoney + $emiPayments + $managementCashin + $developmentCharge + $shopServiceCharge + $expenseAdjCashin + $procurementReturns;
            } elseif ($category === 'emi') {
PHP;
$content = str_replace($old_daily_cashin, $new_daily_cashin, $content);

// Replace getCustomDailyStatement accumulation
$old_daily_accum = <<<'PHP'
            $totals['management_cashin'] += $managementCashin;
            $totals['discount'] += $discount;
PHP;
$new_daily_accum = <<<'PHP'
            $totals['management_cashin'] += $managementCashin;
            $totals['development_charge'] += $developmentCharge;
            $totals['shop_service_charge'] += $shopServiceCharge;
            $totals['expense_adjustment_cashin'] += $expenseAdjCashin;
            $totals['procurement_returns_cashin'] += $procurementReturns;
            $totals['discount'] += $discount;
PHP;
$content = str_replace($old_daily_accum, $new_daily_accum, $content);

// --- MONTHLY ---

// Replace getCustomMonthlyStatement fetching
$old_monthly_fetch = <<<'PHP'
            $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
            $managementCashin = $this->getMonthlyManagementCashin($monthStart, $monthEnd);
            $discount = $this->getMonthlyDiscount($monthStart, $monthEnd, $projectId);
PHP;
$new_monthly_fetch = <<<'PHP'
            $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
            $managementCashin = $this->getMonthlyManagementCashin($monthStart, $monthEnd);
            $developmentCharge = $this->getMonthlyPaymentByType('development_charge', $monthStart, $monthEnd, $projectId);
            $shopServiceCharge = $this->getMonthlyPaymentByType('shop_service_charge', $monthStart, $monthEnd, $projectId);
            $expenseAdjCashin = $this->getTotalExpenseAdjustmentCashin($monthStart, $monthEnd);
            $procurementReturns = $this->getTotalProcurementReturnsCashin($monthStart, $monthEnd);
            $discount = $this->getMonthlyDiscount($monthStart, $monthEnd, $projectId);
PHP;
$content = str_replace($old_monthly_fetch, $new_monthly_fetch, $content);

// Replace getCustomMonthlyStatement cashin calc
$old_monthly_cashin = <<<'PHP'
            if ($category === 'all' || $category === 'total_cashin') {
                $cashin = $landsharePayments + $registrationPayments + $bookingMoney + $emiPayments + $managementCashin;
            } elseif ($category === 'emi') {
PHP;
$new_monthly_cashin = <<<'PHP'
            if ($category === 'all' || $category === 'total_cashin') {
                $cashin = $landsharePayments + $registrationPayments + $bookingMoney + $emiPayments + $managementCashin + $developmentCharge + $shopServiceCharge + $expenseAdjCashin + $procurementReturns;
            } elseif ($category === 'emi') {
PHP;
$content = str_replace($old_monthly_cashin, $new_monthly_cashin, $content);

// Write to temp file
file_put_contents('temp_statement_custom.php', $content);
echo "Replacements attempted. Check temp_statement_custom.php.\n";
