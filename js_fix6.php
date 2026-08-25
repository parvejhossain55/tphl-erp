<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$search = "    // --- Database-driven Employee Selection ---";
// Actually, I can just do a precise regex replace for the bottom block

$pattern = "/\s*\/\/\s*---\s*Database-driven\s*Employee\s*Selection\s*---[\s\S]*<\/script>/i";
$replacement = "
    // --- Database-driven Employee Selection ---
    function updateMasterCheckbox() {
        let allChecked = true;
        let totalCount = 0;
        var nodes = [];
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#workspaceTable')) {
            nodes = $('#workspaceTable').DataTable().rows().nodes();
        } else {
            nodes = document.querySelectorAll('.calc-row');
        }
        $(nodes).each(function() {
            if ($(this).find('.emp-checkbox').length > 0) {
                totalCount++;
                if (!$(this).find('.emp-checkbox').is(':checked')) {
                    allChecked = false;
                }
            }
        });
        $('#selectAllEmployees').prop('checked', allChecked && totalCount > 0);
    }
    
    $(document).ready(function() {
        setTimeout(function() {
            updateMasterCheckbox();
            calculateFooterTotals();
        }, 200);
        if ($.fn.DataTable) {
            $('#workspaceTable').on('draw.dt', function() {
                updateMasterCheckbox();
                calculateFooterTotals();
            });
        }
    });

    $(document).on('change', '#selectAllEmployees', function() {
        var isChecked = $(this).is(':checked');
        var nodes = [];
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#workspaceTable')) {
            nodes = $('#workspaceTable').DataTable().rows().nodes();
        } else {
            nodes = document.querySelectorAll('.calc-row');
        }
        $(nodes).each(function() {
            $(this).find('.emp-checkbox').prop('checked', isChecked);
        });
        calculateFooterTotals();
    });
    
    $(document).on('change', '.emp-checkbox', function() {
        if (!$(this).is(':checked')) {
            $('#selectAllEmployees').prop('checked', false);
        } else {
            updateMasterCheckbox();
        }
        calculateFooterTotals();
    });

</script>";

$content = preg_replace($pattern, $replacement, $content);
file_put_contents($file, $content);
echo "Fixed";
