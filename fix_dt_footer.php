<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$search1 = "$('#workspaceTable tfoot tr td').each(function(index) {";
$replace1 = "$('.dataTables_scrollFoot tfoot tr td, #workspaceTable tfoot tr td').each(function(index) {
            if ($(this).closest('table').find('thead').length === 0 && $(this).closest('.dataTables_scrollFoot').length === 0) {
                 // Check if it's the right td using class or data. 
                 // Wait, easiest is to just update BOTH possible footers based on column index
            }";
            
// Wait, if I use $('.dataTables_scrollFoot tfoot tr td, #workspaceTable tfoot tr td') it will iterate through BOTH lists of TDs, so index will go from 0..30, then 31..60!
// Instead:
$replace1 = "$('.dataTables_scrollFoot tfoot tr, #workspaceTable tfoot tr').each(function() {
            $(this).find('td').each(function(index) {";
            
$content = str_replace($search1, $replace1, $content);

// Now I need to fix the closing brace
$search2 = "                }
            }
        });
    }";
$replace2 = "                }
            }
        });
        });
    }";
$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Footer visibility patched for split tables.";
