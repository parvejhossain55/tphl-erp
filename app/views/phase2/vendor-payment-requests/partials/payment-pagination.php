<?php
/**
 * Partial: Server-Side Pagination Footer for Vendor Payment Requests
 * Variables expected: $pagination, $filters, $base_url
 */
$cur          = (int)($pagination['currentPage'] ?? 1);
$last         = (int)($pagination['totalPages'] ?? 1);
$perPage      = (int)($pagination['perPage'] ?? 25);
$totalRecords = (int)($pagination['totalRecords'] ?? 0);
$startRecord  = (int)($pagination['startRecord'] ?? 0);
$endRecord    = (int)($pagination['endRecord'] ?? 0);

$buildUrl = function ($p) use ($filters, $perPage, $base_url) {
    $q = array_filter([
        'project_id'  => $filters['project_id'] ?? '',
        'vendor_id'   => $filters['vendor_id'] ?? '',
        'category_id' => $filters['category_id'] ?? '',
        'status'      => $filters['status'] ?? '',
        'search'      => $filters['search'] ?? '',
        'per_page'    => $perPage,
        'page'        => $p,
    ], fn($v) => $v !== '' && $v !== null);
    return $base_url . '/con-vendor-payment-requests?' . http_build_query($q);
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="text-muted fs-13">
        <?php if ($totalRecords > 0): ?>
            Showing <strong class="text-dark"><?= number_format($startRecord) ?></strong> to
            <strong class="text-dark"><?= number_format($endRecord) ?></strong> of
            <strong class="text-primary"><?= number_format($totalRecords) ?></strong> entries
        <?php else: ?>
            Showing <strong class="text-dark">0</strong> entries
        <?php endif; ?>
    </div>

    <?php if ($last > 1): ?>
        <nav aria-label="Vendor Payment Requests Pagination">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link vpr-page-link" href="<?= $cur > 1 ? htmlspecialchars($buildUrl(1)) : 'javascript:void(0)' ?>" data-page="1" title="First Page">
                        <i class="fa-solid fa-angles-left"></i>
                    </a>
                </li>
                <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link vpr-page-link" href="<?= $cur > 1 ? htmlspecialchars($buildUrl($cur - 1)) : 'javascript:void(0)' ?>" data-page="<?= max(1, $cur - 1) ?>" title="Previous Page">
                        <i class="fa-solid fa-chevron-left me-1"></i>Prev
                    </a>
                </li>

                <?php
                $startPage = max(1, $cur - 2);
                $endPage   = min($last, $cur + 2);
                if ($startPage > 1) {
                    echo '<li class="page-item"><a class="page-link vpr-page-link" href="' . htmlspecialchars($buildUrl(1)) . '" data-page="1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
                    }
                }
                for ($p = $startPage; $p <= $endPage; $p++) {
                    $active = ($p === $cur) ? 'active' : '';
                    echo '<li class="page-item ' . $active . '"><a class="page-link vpr-page-link" href="' . htmlspecialchars($buildUrl($p)) . '" data-page="' . $p . '">' . $p . '</a></li>';
                }
                if ($endPage < $last) {
                    if ($endPage < $last - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link vpr-page-link" href="' . htmlspecialchars($buildUrl($last)) . '" data-page="' . $last . '">' . $last . '</a></li>';
                }
                ?>

                <li class="page-item <?= $cur >= $last ? 'disabled' : '' ?>">
                    <a class="page-link vpr-page-link" href="<?= $cur < $last ? htmlspecialchars($buildUrl($cur + 1)) : 'javascript:void(0)' ?>" data-page="<?= min($last, $cur + 1) ?>" title="Next Page">
                        Next<i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </li>
                <li class="page-item <?= $cur >= $last ? 'disabled' : '' ?>">
                    <a class="page-link vpr-page-link" href="<?= $cur < $last ? htmlspecialchars($buildUrl($last)) : 'javascript:void(0)' ?>" data-page="<?= $last ?>" title="Last Page">
                        <i class="fa-solid fa-angles-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
