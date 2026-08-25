<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\payslip-lists.php';
$content = file_get_contents($file);

$pattern = '/function disburseSelected\(\).*?\}\s*<\/script>/s';
$replacement = "function disburseSelected() {
        const selectedIds = [];
        document.querySelectorAll('.row-checkbox:checked').forEach(cb => {
            selectedIds.push(cb.value);
        });

        if (selectedIds.length === 0) {
            Swal.fire('Warning', 'Please select at least one payslip to disburse.', 'warning');
            return;
        }

        const bankId = document.getElementById('payingBankId') ? document.getElementById('payingBankId').value : 0;
        const bankName = document.getElementById('payingBankId') ? document.getElementById('payingBankId').options[document.getElementById('payingBankId').selectedIndex].text : 'Petty Cash / Cash Account';

        Swal.fire({
            title: 'Disburse Payroll?',
            html: `Are you sure you want to disburse <b>\${selectedIds.length}</b> selected payslips via <b>\${bankName}</b>?<br><br><small class=\"text-muted\">This will mark them as Paid and create double-entry records in the General Journal.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Disburse',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('<?= \$GLOBALS[\'base_url\'] ?? \'\' ?>/disburse-batch-payslips', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        payslip_ids: selectedIds,
                        bank_account_id: bankId
                    })
                })
                .then(response => {
                    const contentType = response.headers.get(\"content-type\");
                    if (contentType && contentType.indexOf(\"application/json\") !== -1) {
                        return response.json().then(data => {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Failed to disburse payroll.');
                            }
                            return data;
                        });
                    } else {
                        return response.text().then(text => {
                            console.error(\"Server non-JSON response:\", text);
                            throw new Error('Server returned an invalid format. Check console.');
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
                    title: 'Payroll Disbursed Successfully',
                    text: result.value.message || `${selectedIds.length} payslips were disbursed and the journal was posted successfully.`,
                    icon: 'success'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }
</script>";

$content = preg_replace($pattern, $replacement, $content);
file_put_contents($file, $content);
echo "SweetAlert successfully integrated.";
