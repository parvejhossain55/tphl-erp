<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$search = "    $(document).on('change', '#selectAllEmployees', function() {
        var isChecked = $(this).is(':checked');
        $('.emp-checkbox').prop('checked', isChecked);
    });
    
    $(document).on('change', '.emp-checkbox', function() {
        if (!$(this).is(':checked')) {
            $('#selectAllEmployees').prop('checked', false);
        } else {
            if ($('.emp-checkbox:checked').length === $('.emp-checkbox').length) {
                $('#selectAllEmployees').prop('checked', true);
            }
        }
    });";

$replace = "
    // --- LocalStorage persistence for Employee Selection ---
    const EXCLUDED_STORAGE_KEY = 'tphl_payroll_excluded_employees';
    function getExcludedUsers() {
        try { return JSON.parse(localStorage.getItem(EXCLUDED_STORAGE_KEY)) || []; } catch(e) { return []; }
    }
    function setExcludedUsers(arr) {
        localStorage.setItem(EXCLUDED_STORAGE_KEY, JSON.stringify(arr));
    }
    
    // Sync UI from storage on load
    function syncCheckboxesFromStorage() {
        const excluded = getExcludedUsers();
        let allSelected = true;
        let totalCount = 0;
        
        document.querySelectorAll('.calc-row').forEach(row => {
            const empId = parseInt(row.getAttribute('data-employee-id') || '0', 10);
            const cb = row.querySelector('.emp-checkbox');
            if (cb) {
                totalCount++;
                if (excluded.includes(empId)) {
                    cb.checked = false;
                    allSelected = false;
                } else {
                    cb.checked = true;
                }
            }
        });
        
        const masterCb = document.getElementById('selectAllEmployees');
        if (masterCb) masterCb.checked = allSelected && totalCount > 0;
    }
    
    // Call immediately (after DOM load)
    syncCheckboxesFromStorage();

    $(document).on('change', '#selectAllEmployees', function() {
        var isChecked = $(this).is(':checked');
        $('.emp-checkbox').prop('checked', isChecked);
        
        let excluded = getExcludedUsers();
        document.querySelectorAll('.calc-row').forEach(row => {
            const empId = parseInt(row.getAttribute('data-employee-id') || '0', 10);
            if (empId > 0) {
                if (isChecked) {
                    excluded = excluded.filter(id => id !== empId);
                } else {
                    if (!excluded.includes(empId)) excluded.push(empId);
                }
            }
        });
        setExcludedUsers(excluded);
    });
    
    $(document).on('change', '.emp-checkbox', function() {
        const row = $(this).closest('.calc-row');
        const empId = parseInt(row.attr('data-employee-id') || '0', 10);
        let excluded = getExcludedUsers();
        
        if (!$(this).is(':checked')) {
            if (empId > 0 && !excluded.includes(empId)) excluded.push(empId);
            $('#selectAllEmployees').prop('checked', false);
        } else {
            if (empId > 0) excluded = excluded.filter(id => id !== empId);
            
            if ($('.emp-checkbox:checked').length === $('.emp-checkbox').length) {
                $('#selectAllEmployees').prop('checked', true);
            }
        }
        setExcludedUsers(excluded);
    });";

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Replaced.";
