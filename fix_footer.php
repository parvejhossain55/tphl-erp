<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

// Inject TFOOT into table
$tfootHtml = "                    </tbody>\r\n                    <tfoot>\r\n                        <tr class=\"fw-bold\" style=\"background-color: #e6f2f1; color: #0f766e;\"></tr>\r\n                    </tfoot>";
$content = str_replace('                    </tbody>', $tfootHtml, $content);

// Update JS calculateFooterTotals
$js = "
    function calculateFooterTotals() {
        var nodes = [];
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#workspaceTable')) {
            nodes = $('#workspaceTable').DataTable().rows().nodes();
        } else {
            nodes = document.querySelectorAll('.calc-row');
        }
        
        let colCount = $('#workspaceTable thead th').length;
        let totals = new Array(colCount).fill(0);
        
        $(nodes).each(function() {
            var row = $(this);
            if (!row.find('.emp-checkbox').is(':checked')) return;

            row.find('td').each(function(index) {
                if (index < 7) return; // Skip strings
                
                let val = 0;
                let input = $(this).find('input[type=\"number\"]');
                if (input.length > 0) {
                    val = parseFloat(input.val()) || 0;
                } else if ($(this).hasClass('text-center') || $(this).find('button').length > 0 || $(this).text().includes('-') && $(this).text().trim().length > 6) {
                    // Skip dates and buttons (Salary Hold, Joining Date, Resignation Date)
                    val = 0;
                } else {
                    val = parseFloat($(this).text().replace(/,/g, '')) || 0;
                }
                if (!isNaN(val)) {
                    totals[index] += val;
                }
            });
        });

        $('#workspaceTable tfoot tr td').each(function(index) {
            if (index > 7) {
                // Formatting
                let valFormat = totals[index].toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (totals[index] === 0 && $('#workspaceTable tbody tr:first td').eq(index).find('input, button, .text-center').length === 0 && $('#workspaceTable tbody tr:first td').eq(index).text().trim() === '') {
                    // Skip empty columns like Remarks
                    $(this).text('');
                } else if ($(this).hasClass('tf-ignore')) {
                    $(this).text('');
                } else {
                    $(this).text(valFormat);
                }
            }
        });
    }
";

// First, inject the blank TDs matching Header inside DOMContentLoaded before DataTables initialization!
$js2 = "
        // Generate Footer Headers dynamically to perfectly match the columns
        let footData = '';
        $('#workspaceTable thead th').each(function(i) {
            if (i === 7) {
                footData += '<td class=\"text-end pe-3\">Totals:</td>';
            } else if (i < 8) {
                footData += '<td></td>';
            } else {
                let isIgnore = $(this).text().toLowerCase().includes('date') || $(this).text().toLowerCase().includes('remark') || $(this).text().toLowerCase().includes('account') || $(this).text().toLowerCase().includes('id check') || $(this).text().toLowerCase().includes('salary hold');
                footData += '<td class=\"text-end ' + (isIgnore ? 'tf-ignore' : '') + '\">0.00</td>';
            }
        });
        $('#workspaceTable tfoot tr').html(footData);

        function attachCalculations() {
";

$content = str_replace("        function attachCalculations() {", $js2, $content);
$content = str_replace("        attachCalculations();", "        attachCalculations();\r\n        " . $js, $content);

// Call calculateFooterTotals inside input listeners
$content = preg_replace(
    '/(row\.querySelector\(\'\.cell-amount\'\)\.textContent\s*=\s*dispBank[^\)]*\);)/',
    "$1\r\n                        calculateFooterTotals();",
    $content
);

// Call calculateFooterTotals when checkboxes change
$content = preg_replace(
    '/(updateMasterCheckbox\(\);[\r\n\s]*\})/',
    "$1\r\n            calculateFooterTotals();",
    $content
);
$content = preg_replace(
    '/(\$\(\'#selectAllEmployees\'\)\.prop\(\'checked\', false\);)/',
    "$1\r\n            calculateFooterTotals();",
    $content
);
$content = preg_replace(
    '/(\$\(nodes\)\.each\(function\(\) \{[\r\n\s]*\$\(this\)\.find\(\'\.emp-checkbox\'\)\.prop\(\'checked\', isChecked\);[\r\n\s]*\}\);)/',
    "$1\r\n        calculateFooterTotals();",
    $content
);

// Call calculateFooterTotals on DataTables Draw (paging/filtering)
$content = preg_replace(
    '/(updateMasterCheckbox\(\);)/',
    "$1\r\n                calculateFooterTotals();",
    $content
);

file_put_contents($file, $content);
echo "Injected.";
