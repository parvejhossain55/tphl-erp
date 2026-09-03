<?php
$filename = __DIR__ . '/Lead.php';
$content = file_get_contents($filename);
$replace = "} elseif (\$filter_card === 'not_assigned_leads') {
                \$sql .= \" AND (l.assigned_id IS NULL OR l.assigned_id = 0)\";
            } elseif (\$filter_card === 'has_follow_up') {
                \$sql .= \" AND EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.deleted_at IS NULL)\";
            }";
$content = preg_replace('/\} elseif \(\$filter_card === \'not_assigned_leads\'\) \{\s+\$sql \.= " AND \(l\.assigned_id IS NULL OR l\.assigned_id = 0\)";\s+\}/', $replace, $content);
file_put_contents($filename, $content);
echo "Done!\n";
