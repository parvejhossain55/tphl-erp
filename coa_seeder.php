<?php
$conn = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// 1. Delete all existing records and reset auto-increment
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE chart_of_accounts");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$accounts = [
    // 1000 ASSETS
    [1, null, '1000', 'Assets', 'Asset'],
    [2, 1, '1100', 'Current Assets', 'Asset'],
    [3, 2, '1110', 'Cash and Bank Equivalents', 'Asset'],
    [4, 2, '1120', 'Accounts Receivable', 'Asset'],
    [5, 1, '1200', 'Construction Work-in-Progress (WIP)', 'Asset'],
    [6, 5, '1210', 'WIP - Land & Legal', 'Asset'],
    [7, 5, '1220', 'WIP - Civil Construction', 'Asset'],
    [8, 5, '1230', 'WIP - MEP (Mechanical, Electrical, Plumbing)', 'Asset'],
    [9, 5, '1240', 'WIP - Finishing', 'Asset'],
    [10, 5, '1250', 'WIP - Site Expenses & Overhead', 'Asset'],

    // 2000 LIABILITIES
    [11, null, '2000', 'Liabilities', 'Liability'],
    [12, 11, '2100', 'Current Liabilities', 'Liability'],
    [13, 12, '2110', 'Accounts Payable - Vendors', 'Liability'],
    [14, 12, '2120', 'Accounts Payable - Contractors', 'Liability'],
    [15, 12, '2130', 'Accounts Payable - Employees', 'Liability'],
    [16, 12, '2140', 'Retention Payable', 'Liability'],
    [17, 11, '2200', 'Long-Term Liabilities', 'Liability'],
    [18, 17, '2210', 'Customer Advances (Unearned Revenue)', 'Liability'],

    // 3000 EQUITY
    [19, null, '3000', 'Equity', 'Equity'],
    [20, 19, '3100', "Owner's Equity", 'Equity'],
    [21, 19, '3200', 'Retained Earnings', 'Equity'],

    // 4000 REVENUE
    [22, null, '4000', 'Revenue', 'Revenue'],
    [23, 22, '4100', 'Flat Sales Revenue', 'Revenue'],
    [24, 22, '4200', 'Other Income', 'Revenue'],

    // 5000 COGS
    [25, null, '5000', 'Cost of Goods Sold', 'Expense'],
    [26, 25, '5100', 'COGS - Real Estate Sold', 'Expense'],

    // 6000 EXPENSES (P&L)
    [27, null, '6000', 'Operating Expenses', 'Expense'],
    [28, 27, '6100', 'General & Administrative Expenses', 'Expense'],
    [29, 27, '6200', 'Salary & Wages', 'Expense'],
    [30, 27, '6300', 'Marketing & Sales Expenses', 'Expense']
];

$stmt = $conn->prepare("INSERT INTO chart_of_accounts (id, parent_id, account_code, account_name, account_type, status) VALUES (?, ?, ?, ?, ?, 'active')");

foreach ($accounts as $acc) {
    $stmt->bind_param("iisss", $acc[0], $acc[1], $acc[2], $acc[3], $acc[4]);
    if (!$stmt->execute()) {
        echo "Error inserting " . $acc[2] . ": " . $stmt->error . "\n";
    } else {
        echo "Inserted " . $acc[2] . " - " . $acc[3] . "\n";
    }
}
$stmt->close();
$conn->close();
echo "COA Seeding Complete!\n";
