<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: sans-serif; margin: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { padding: 5px; border: 1px solid #ddd; text-align: left; }
    th { background: #f0f0f0; }
    h2, p { margin: 5px 0; }
</style>
</head>
<body>

<h2>Payslip for <?= htmlspecialchars($payslip['employee_name']) ?></h2>
<p>Month: <?= $payslip['payslip_month'] ?></p>

<h4>Earnings</h4>
<table>
    <thead>
        <tr>
            <th>Title</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Basic Salary</td>
            <td><?= number_format($payslip['basic_salary'],2) ?></td>
        </tr>
        <?php foreach($payslip['items'] as $item): ?>
            <?php if($item['item_type'] === 'earning'): ?>
                <tr>
                    <td><?= $item['item_name'] ?></td>
                    <td><?= number_format($item['amount'],2) ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>

<p><strong>Net Salary: <?= number_format($payslip['net_salary'],2) ?></strong></p>

</body>
</html>
