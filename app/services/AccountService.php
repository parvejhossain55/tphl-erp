<?php
class AccountService {

    public static function getCashFlow($db) {
        // Cash In
        $stmt_in = $db->query("SELECT SUM(amount) AS cash_in FROM lead_payments");
        $in = 0;
        if($stmt_in){
            $row_in = $stmt_in->fetch_assoc();
            $in = $row_in['cash_in'] ?? 0;
        }

        // Cash Out
        $stmt_out = $db->query("SELECT SUM(amount) AS cash_out FROM management_cashout");
        $out = 0;
        if($stmt_out){
            $row_out = $stmt_out->fetch_assoc();
            $out = $row_out['cash_out'] ?? 0;
        }

        // Net balance
        $net = $in - $out;

        return [
            'cash_in' => $in,
            'cash_out' => $out,
            'net_balance' => $net
        ];
    }
}
