<?php
if ($totalPages <= 1) {
    // Show total count even if only 1 page
    if ($totalRecords > 0) {
        echo '<div style="display:flex; justify-content:space-between; align-items:center; padding: 12px 20px; font-size:12px; color:#64748b; border-top:1px solid rgba(0,0,0,0.05);">';
        echo '<span>Showing 1 to ' . $totalRecords . ' of ' . $totalRecords . ' projects</span>';
        echo '</div>';
    }
    return;
}

$start = $offset + 1;
$end = min($offset + $perPage, $totalRecords);
?>
<div style="display:flex; justify-content:space-between; align-items:center; padding: 14px 20px; border-top:1px solid rgba(0,0,0,0.05); font-family:'Inter', sans-serif; flex-wrap:wrap; gap:12px;">
    <div style="font-size:12.5px; color:#64748b;">
        Showing <span style="font-weight:700; color:#1e293b;"><?= $start ?></span> to <span style="font-weight:700; color:#1e293b;"><?= $end ?></span> of <span style="font-weight:700; color:#1e293b;"><?= $totalRecords ?></span> projects
    </div>
    
    <div style="display:flex; align-items:center; gap:5px;">
        <!-- Previous Button -->
        <?php if ($currentPage > 1): ?>
            <button onclick="fetchPaginatedProjects(<?= $currentPage - 1 ?>)" style="padding: 6px 12px; border-radius: 6px; border:1px solid #e2e8f0; background:#fff; color:#475569; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.15s; display:inline-flex; align-items:center; gap:4px;">
                <i class="fal fa-chevron-left" style="font-size:10px"></i> Prev
            </button>
        <?php else: ?>
            <button disabled style="padding: 6px 12px; border-radius: 6px; border:1px solid #f1f5f9; background:#f8fafc; color:#cbd5e1; font-size:12px; font-weight:600; cursor:not-allowed; display:inline-flex; align-items:center; gap:4px;">
                <i class="fal fa-chevron-left" style="font-size:10px"></i> Prev
            </button>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php
        $maxVisible = 5;
        $half = floor($maxVisible / 2);
        $startPage = max(1, $currentPage - $half);
        $endPage = min($totalPages, $startPage + $maxVisible - 1);
        if ($endPage - $startPage < $maxVisible - 1) {
            $startPage = max(1, $endPage - $maxVisible + 1);
        }

        if ($startPage > 1): ?>
            <button onclick="fetchPaginatedProjects(1)" style="width:30px; height:30px; border-radius:6px; border:1px solid #e2e8f0; background:#fff; color:#475569; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;">1</button>
            <?php if ($startPage > 2): ?>
                <span style="color:#94a3b8; font-size:12px; width:20px; text-align:center;">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($p = $startPage; $p <= $endPage; $p++): 
            $isActive = ($p === $currentPage);
            $style = $isActive 
                ? 'background:var(--color-primary, #198754); color:#fff; border-color:var(--color-primary, #198754);'
                : 'background:#fff; color:#475569; border-color:#e2e8f0; cursor:pointer;';
            ?>
            <button onclick="fetchPaginatedProjects(<?= $p ?>)" style="width:30px; height:30px; border-radius:6px; border:1px solid; font-size:12px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; transition:all 0.15s; <?= $style ?>">
                <?= $p ?>
            </button>
        <?php endfor; ?>

        <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
                <span style="color:#94a3b8; font-size:12px; width:20px; text-align:center;">...</span>
            <?php endif; ?>
            <button onclick="fetchPaginatedProjects(<?= $totalPages ?>)" style="width:30px; height:30px; border-radius:6px; border:1px solid #e2e8f0; background:#fff; color:#475569; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;"><?= $totalPages ?></button>
        <?php endif; ?>

        <!-- Next Button -->
        <?php if ($currentPage < $totalPages): ?>
            <button onclick="fetchPaginatedProjects(<?= $currentPage + 1 ?>)" style="padding: 6px 12px; border-radius: 6px; border:1px solid #e2e8f0; background:#fff; color:#475569; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.15s; display:inline-flex; align-items:center; gap:4px;">
                Next <i class="fal fa-chevron-right" style="font-size:10px"></i>
            </button>
        <?php else: ?>
            <button disabled style="padding: 6px 12px; border-radius: 6px; border:1px solid #f1f5f9; background:#f8fafc; color:#cbd5e1; font-size:12px; font-weight:600; cursor:not-allowed; display:inline-flex; align-items:center; gap:4px;">
                Next <i class="fal fa-chevron-right" style="font-size:10px"></i>
            </button>
        <?php endif; ?>
    </div>
</div>
