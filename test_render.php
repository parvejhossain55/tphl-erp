<?php
session_start();
$_SESSION['user'] = ['id' => 200, 'role_id' => 20, 'role_name' => 'Site Operator'];
require_once 'app/config/config.php';
function hasPermission($p) { return false; }
$userRole = 20;
$b = ['id' => 1, 'status' => 'submitted', 'payment_by_name' => null];
$base_url = 'http://localhost';
ob_start();
?>
<td>
    <a href="<?= $base_url ?>/con-contractor-bill/<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
        <i class="bi bi-eye"></i>
    </a>
    <?php if (!in_array($b['status'], ['approved', 'paid', 'partially_paid', 'fully_paid'])): ?>
        <?php if (hasPermission('Contractor Bill') || in_array($userRole ?? ($_SESSION['user']['role_id'] ?? 0), [1, 3, 33, 17, 23, 31, 20, 32])): ?>
            <a href="<?= $base_url ?>/con-contractor-bill-edit/<?= $b['id'] ?>" class="btn btn-sm btn-outline-info" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>
        <?php endif; ?>
    <?php endif; ?>
</td>
<?php
$html = ob_get_clean();
echo "Output HTML:\n" . $html;
