<?php
$addPage = true;
$show_breadcrumb = false;
?>

<h4 class="mb-2">Inventory Stock Levels</h4>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Material</th>
                            <th>Unit</th>
                            <th>Warehouse</th>
                            <th>Current Stock</th>
                            <th>Min. Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($stocks)): ?>
                            <?php foreach ($stocks as $index => $row): ?>
                                <?php
                                    $isLow = $row['current_quantity'] < $row['min_stock_level'];
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['material_name']) ?></td>
                                    <td><?= htmlspecialchars($row['unit']) ?></td>
                                    <td><?= htmlspecialchars($row['warehouse_name']) ?></td>
                                    <td class="<?= $isLow ? 'text-danger fw-bold' : 'fw-bold' ?>">
                                        <?= number_format($row['current_quantity'], 3) ?>
                                    </td>
                                    <td><?= number_format($row['min_stock_level'], 3) ?></td>
                                    <td>
                                        <?php if ($isLow): ?>
                                            <span class="badge bg-danger">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">OK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No stock data available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
