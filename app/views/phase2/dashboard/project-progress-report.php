<?php
$base_url = $GLOBALS['base_url'];
?>

<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Progress Report</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        <a href="<?= $base_url ?>/con-dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<section class="section">
    <div class="row g-4">

        <!-- ── Top Stats & Highlight Card ───────────────────── -->
        <div class="col-12">
            <div class="card__wrapper shadow-sm border-0 bg-white" style="border-radius: 16px; overflow: hidden;">
                <div class="p-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; position: relative;">
                    <div style="position: absolute; top:-30px; right:-30px; width: 180px; height: 180px; border-radius: 50%; background: rgba(255,255,255,0.06); pointer-events: none;"></div>
                    <div style="position: absolute; bottom:-40px; left:20%; width: 120px; height: 120px; border-radius: 50%; background: rgba(255,255,255,0.04); pointer-events: none;"></div>
                    
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="text-uppercase fw-semibold tracking-wider text-white-50" style="font-size: 0.8rem; letter-spacing: 1px;">Project Statistics & Overview</span>
                            <h2 class="fw-bold mb-2 text-white"><?= htmlspecialchars($project['name']) ?></h2>
                            <p class="mb-0 text-white-50 small">
                                <i class="bi bi-geo-alt me-1"></i> Location: <?= htmlspecialchars($project['location'] ?? 'Not Specified') ?> | 
                                <i class="bi bi-calendar-event me-1"></i> Created: <?= date('d M Y', strtotime($project['created_at'])) ?>
                            </p>
                        </div>
                        <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                            <div class="d-inline-block text-start p-3 bg-white bg-opacity-10 border border-white border-opacity-10 rounded-3">
                                <span class="text-white-50 d-block small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Overall Construction Progress</span>
                                <h2 class="fw-bold text-white mb-0 mt-1" style="font-size: 2.2rem;"><?= number_format($totalProgress, 2) ?>%</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4 bg-light-subtle border-top">
                    <div class="row align-items-center">
                        <div class="col-12">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-semibold text-dark small">Project Completion Track</span>
                                <span class="fw-bold text-primary small"><?= number_format($totalProgress, 2) ?>%</span>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 6px; background: #e2e8f0; overflow: hidden;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                     role="progressbar" 
                                     style="width: <?= min(100, $totalProgress) ?>%; border-radius: 6px;" 
                                     aria-valuenow="<?= $totalProgress ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Middle: Floor-wise vs Work-type Matrix Grid ───────────────────── -->
        <div class="col-12">
            <div class="card__wrapper shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold text-dark text-uppercase" style="font-size: 0.9rem;">
                        <i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i> floor-wise target & achievement matrix (ফ্লোর-ভিত্তিক টার্গেট ও অর্জন গ্রিড)
                    </h6>
                    <span class="badge bg-light text-dark border">Excel Layout Matrix</span>
                </div>
                <div class="card__body p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-0" style="font-size: 0.82rem; min-width: 1000px; table-layout: fixed;">
                            <thead>
                                <tr class="table-light">
                                    <th class="text-start text-dark fw-bold" style="width: 180px; font-size: 0.85rem;">Floor Name \ Work Type</th>
                                    <?php foreach ($workTypes as $wt): ?>
                                        <th class="fw-bold text-dark text-wrap" style="font-size: 0.8rem; word-break: break-word;">
                                            <?= htmlspecialchars($wt['work_type_name']) ?>
                                            <span class="d-block small text-muted font-normal mt-1" style="font-size: 0.68rem; font-weight: normal;">Weight: <?= number_format($wt['weight_percentage'], 2) ?>%</span>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($floors as $floor): ?>
                                    <tr>
                                        <!-- Floor Name -->
                                        <td class="text-start fw-bold text-dark bg-light-subtle" style="font-size: 0.85rem;">
                                            <i class="bi bi-layers me-1 text-secondary"></i> <?= htmlspecialchars($floor) ?>
                                        </td>

                                        <!-- Work Type Target & Gain -->
                                        <?php foreach ($workTypes as $wt): 
                                            $wtId = $wt['id'];
                                            $cell = $matrix[$floor][$wtId] ?? null;
                                            
                                            $cellStyle = '';
                                            if ($cell) {
                                                if ($cell['gain'] >= $cell['target'] && $cell['target'] > 0) {
                                                    $cellStyle = 'background: rgba(25, 135, 84, 0.08);'; // soft green
                                                } elseif ($cell['gain'] > 0) {
                                                    $cellStyle = 'background: rgba(255, 193, 7, 0.08);'; // soft yellow
                                                } else {
                                                    $cellStyle = 'background: rgba(13, 110, 253, 0.04);'; // soft blue
                                                }
                                            }
                                        ?>
                                            <td style="height: 60px; vertical-align: middle; <?= $cellStyle ?>">
                                                <?php if ($cell): ?>
                                                    <div class="px-1 py-1">
                                                        <div class="text-primary fw-semibold" style="font-size: 0.72rem;" title="Target Percentage">
                                                            T: <?= number_format($cell['target'], 2) ?>%
                                                        </div>
                                                        <div class="text-success fw-bold mt-1" style="font-size: 0.74rem;" title="Actual Achievement (Gain)">
                                                            A: <?= number_format($cell['gain'], 2) ?>%
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted opacity-50">—</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text mt-3 text-muted d-flex gap-4">
                        <span><span class="d-inline-block rounded-1 border" style="width: 14px; height: 14px; background: rgba(25, 135, 84, 0.08); vertical-align: middle; margin-right: 4px;"></span> Target Completed (অর্জন টার্গেট সমান বা বেশি)</span>
                        <span><span class="d-inline-block rounded-1 border" style="width: 14px; height: 14px; background: rgba(255, 193, 7, 0.08); vertical-align: middle; margin-right: 4px;"></span> Work In Progress (আংশিক অর্জিত)</span>
                        <span><span class="d-inline-block rounded-1 border" style="width: 14px; height: 14px; background: rgba(13, 110, 253, 0.04); vertical-align: middle; margin-right: 4px;"></span> Planned (পরিকল্পিত কিন্তু শুরু হয়নি)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Bottom: Progress Calculations & Breakdown ───────────────────── -->
        <div class="col-12">
            <div class="card__wrapper shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title mb-0 fw-bold text-dark text-uppercase" style="font-size: 0.9rem;">
                        <i class="bi bi-calculator me-2 text-primary"></i> Work-Type Progress Breakdown (কাজের বিবরণ ও প্রগতি হিসাব)
                    </h6>
                </div>
                <div class="card__body p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle mb-0" style="font-size: 0.88rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 5%;">নং</th>
                                    <th>কাজের বিবরণ (Work Type)</th>
                                    <th class="text-end" style="width: 20%;">বাজেট ওয়েট (Budget Weight %)</th>
                                    <th class="text-end" style="width: 20%;">মোট টার্গেট (Cumulative Target %)</th>
                                    <th class="text-end" style="width: 20%;">মোট অর্জিত (Cumulative Achieved %)</th>
                                    <th class="text-end fw-bold text-primary" style="width: 20%;">প্রজেক্ট প্রোগ্রেস অবদান (Weighted Progress Contribution %)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($workTypes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No approved budget/work type details found for this project.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($workTypes as $idx => $wt): ?>
                                        <tr>
                                            <td class="text-center fw-semibold"><?= $idx + 1 ?></td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($wt['work_type_name']) ?></td>
                                            <td class="text-end font-monospace"><?= number_format($wt['weight_percentage'], 2) ?>%</td>
                                            <td class="text-end font-monospace text-primary"><?= number_format($wt['cumulative_target'], 2) ?>%</td>
                                            <td class="text-end font-monospace text-success fw-semibold"><?= number_format($wt['cumulative_gain'], 2) ?>%</td>
                                            <td class="text-end font-monospace fw-bold text-primary-emphasis"><?= number_format($wt['contribution'], 4) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold text-dark">
                                <tr>
                                    <td colspan="2" class="text-end">Total Sum:</td>
                                    <td class="text-end font-monospace"><?= number_format(array_sum(array_column($workTypes, 'weight_percentage')), 2) ?>%</td>
                                    <td class="text-end font-monospace text-primary"><?= number_format(array_sum(array_column($workTypes, 'cumulative_target')), 2) ?>%</td>
                                    <td class="text-end font-monospace text-success"><?= number_format(array_sum(array_column($workTypes, 'cumulative_gain')), 2) ?>%</td>
                                    <td class="text-end font-monospace text-primary-emphasis fw-bold" style="font-size: 1.05rem; background: #e0f2fe;"><?= number_format($totalProgress, 2) ?>%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-4 mb-0 d-flex gap-3 align-items-center">
                        <i class="bi bi-info-circle-fill fs-4 text-info"></i>
                        <div class="small">
                            <strong>প্রোগ্রেস হিসাবের লজিক (Weighted Progress Formula):</strong><br>
                            মোট প্রজেক্ট প্রোগ্রেস = $\sum$ [ (কাজ অনুযায়ী মোট অর্জিত অগ্রগতি % / ১০০) $\times$ প্রজেক্টে কাজের প্রাক্কলিত বাজেট ওয়েট % ]
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
