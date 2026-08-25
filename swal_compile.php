<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\payslip-lists.php';
$content = file_get_contents($file);

$pattern = '/function disburseSelectedPayroll\(\).*?\}\s*<\/script>/s';
$replacement = 'function disburseSelectedPayroll() {
        const selectedIds = [];
        document.querySelectorAll(\'.payslip-checkbox:checked\').forEach(cb => {
            selectedIds.push(cb.value);
        });

        if (selectedIds.length === 0) {
            Swal.fire(\'Warning\', \'Please select at least one payslip to disburse.\', \'warning\');
            return;
        }

        const bankSelect = document.getElementById(\'disburse_bank_account_id\');
        const bankId = bankSelect ? bankSelect.value : 0;
        const bankText = bankSelect ? bankSelect.options[bankSelect.selectedIndex].text : \'Petty Cash / Cash Account\';

        Swal.fire({
            title: \'Disburse Payroll?\',
            html: `Are you sure you want to disburse <b>${selectedIds.length}</b> selected payslips via <b>${bankText}</b>?<br><br><small class=\"text-muted\">This will mark the payslips as PAID and automatically post 1 Batch Accounting Journal Entry in General Journal.</small>`,
            icon: \'question\',
            showCancelButton: true,
            confirmButtonText: \'Yes, Disburse\',
            cancelButtonText: \'Cancel\',
            confirmButtonColor: \'#28a745\',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch(\''. ($GLOBALS['base_url'] ?? '') . '/disburse-batch-payslips\', {
                    method: \'POST\',
                    headers: { \'Content-Type\': \'application/json\' },
                    body: JSON.stringify({
                        payslip_ids: selectedIds,
                        bank_account_id: bankId
                    })
                })
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json().then(data => {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || \'Failed to disburse payroll.\');
                            }
                            return data;
                        });
                    } else {
                        return response.text().then(text => {
                            console.error("Server non-JSON response:", text);
                            throw new Error(\'Server returned an invalid format. Check console.\');
                        });
                    }
                })
                .catch(error => {
                    Swal.showValidationMessage(`${error}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: \'Payroll Disbursed Successfully\',
                    text: result.value.message || `${selectedIds.length} payslips were disbursed and the journal was posted successfully.`,
                    icon: \'success\'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }
</script>';

// Safely map in PHP globals evaluation dynamically against JS strings
$replacement = str_replace("fetch('", "fetch('<?= \$GLOBALS['base_url'] ?>", $replacement);

$content = preg_replace($pattern, $replacement, $content);
file_put_contents($file, $content);
echo "Frontend SwiftAlert integration complete.";
