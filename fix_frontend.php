<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

// 1. Update the PHP template to output the checked attribute dynamically based on DB state
$searchHTML = '<td class="text-center"><input type="checkbox" class="form-check-input emp-checkbox" checked style="cursor:pointer;" autocomplete="off"></td>';
$replaceHTML = '<?php $excludedState = (isset($p[\'is_excluded\']) && $p[\'is_excluded\'] == 1) ? "" : "checked"; ?>
                                            <td class="text-center"><input type="checkbox" class="form-check-input emp-checkbox" <?= $excludedState ?> style="cursor:pointer;" autocomplete="off"></td>';
$content = str_replace($searchHTML, $replaceHTML, $content);

// In case the autocomplete was not found with exact space
$searchHTML2 = '<td class="text-center"><input type="checkbox" autocomplete="off" class="form-check-input emp-checkbox" checked style="cursor:pointer;"></td>';
$replaceHTML2 = '<?php $excludedState = (isset($p[\'is_excluded\']) && $p[\'is_excluded\'] == 1) ? "" : "checked"; ?>
                                            <td class="text-center"><input type="checkbox" autocomplete="off" class="form-check-input emp-checkbox" <?= $excludedState ?> style="cursor:pointer;"></td>';
$content = str_replace($searchHTML2, $replaceHTML2, $content);

// Let's replace using regex for the checkbox td to be safe
$content = preg_replace(
    '/<td\s+class="text-center">\s*<input\s+type="checkbox"[^>]*class="[^"]*emp-checkbox[^"]*"[^>]*checked[^>]*>\s*<\/td>/i',
    '<?php $excludedState = (isset($p[\'is_excluded\']) && $p[\'is_excluded\'] == 1) ? "" : "checked"; ?>' . "\r\n                                            " . '<td class="text-center"><input type="checkbox" autocomplete="off" class="form-check-input emp-checkbox" <?= $excludedState ?> style="cursor:pointer;"></td>',
    $content
);

// 2. Update saveWorkspaceData to NOT SKIP unchecked rows but include `is_excluded`
$content = preg_replace(
    "/if\s*\(!tr\.find\('\.emp-checkbox'\)\.is\(':checked'\)\)\s*return;\s*\/\/\s*Skip\s+unchecked(\r?\n)\s*var\s+payslipIdStr/",
    "var is_excluded = tr.find('.emp-checkbox').is(':checked') ? 0 : 1;\r\n                    var payslipIdStr",
    $content
);

// 3. Update the data payload in ajax request to include is_excluded
$searchPayload = "                        attendance_remarks: attRemarks,
                        details: dynamicDetails
                    });";
$replacePayload = "                        attendance_remarks: attRemarks,
                        is_excluded: is_excluded,
                        details: dynamicDetails
                    });";
$content = str_replace($searchPayload, $replacePayload, $content);

// 4. Remove all LocalStorage logic from JS block and replace with standard SelectAll logic
$searchJS = "    // --- LocalStorage persistence for Employee Selection ---";

// I'll grab everything from the start of LocalStorage to the end of script
$pattern = "/\s*\/\/\s*---\s*LocalStorage\s*persistence[\s\S]*<\/script>/i";

$standardJS = "
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
        setTimeout(updateMasterCheckbox, 200);
        if ($.fn.DataTable) {
            $('#workspaceTable').on('draw.dt', function() {
                updateMasterCheckbox();
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
    });
    
    $(document).on('change', '.emp-checkbox', function() {
        if (!$(this).is(':checked')) {
            $('#selectAllEmployees').prop('checked', false);
        } else {
            updateMasterCheckbox();
        }
    });

</script>";
$content = preg_replace($pattern, $standardJS, $content);

file_put_contents($file, $content);
echo "Frontend workspace sync updated.";
