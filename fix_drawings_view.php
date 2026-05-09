<?php
$file = __DIR__ . '/app/views/phase2/project_drawings/project-drawings.php';
$content = file_get_contents($file);

// Fix 1: Wrap edit/delete td with canModify check
$old1 = '                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS[\'base_url\'] ?>/con-project-drawings/edit?id=<?= $row[\'id\'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row[\'id\'] ?>"
                                                data-name="<?= htmlspecialchars($row[\'project_name\'] ?? \'\') ?>"
                                                data-action="delete-project-drawing">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>';

$new1 = '                                    <td class="table__icon-box">
                                        <?php if ($canModify): ?>
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS[\'base_url\'] ?>/con-project-drawings/edit?id=<?= $row[\'id\'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row[\'id\'] ?>"
                                                data-name="<?= htmlspecialchars($row[\'project_name\'] ?? \'\') ?>"
                                                data-action="delete-project-drawing">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                        <?php else: ?>
                                          <span style="font-size:11px;color:var(--text-muted)">—</span>
                                        <?php endif; ?>
                                    </td>';

// Normalize line endings for search
$old1_crlf = str_replace("\n", "\r\n", $old1);

if (strpos($content, $old1_crlf) !== false) {
    $content = str_replace($old1_crlf, str_replace("\n", "\r\n", $new1), $content);
    echo "Fix 1 applied (CRLF)\n";
} elseif (strpos($content, $old1) !== false) {
    $content = str_replace($old1, $new1, $content);
    echo "Fix 1 applied (LF)\n";
} else {
    echo "Fix 1 NOT FOUND\n";
}

// Fix 2: Fix colspan in empty row
$old2_crlf = "                        <?php else: ?>\r\n                             <tr>\r\n                                 <td colspan=\"8\" class=\"text-center text-muted py-4\">No drawings found.</td>\r\n                             </tr>";
$new2_crlf = "                        <?php else: ?>\r\n                             <tr>\r\n                                 <td colspan=\"<?= \$canModify ? 8 : 7 ?>\" class=\"text-center text-muted py-4\">No drawings found.</td>\r\n                             </tr>";

if (strpos($content, $old2_crlf) !== false) {
    $content = str_replace($old2_crlf, $new2_crlf, $content);
    echo "Fix 2 applied\n";
} else {
    echo "Fix 2 NOT FOUND\n";
    // Try fallback
    $content = preg_replace('/colspan="8" class="text-center text-muted py-4">No drawings found\./', 'colspan="<?= $canModify ? 8 : 7 ?>" class="text-center text-muted py-4">No drawings found.', $content);
    echo "Fix 2 fallback applied\n";
}

file_put_contents($file, $content);
echo "Done!\n";
