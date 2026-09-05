<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!class_exists('\TCPDF') && file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

class PayrollMailService
{
    protected $db;
    protected $config;

    public function __construct($db)
    {
        $this->db = $db;
        $this->config = require __DIR__ . '/../config/config.php';
    }

    /**
     * Send payslip email to a single employee
     *
     * @param int $payslipId
     * @param int $senderUserId
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendPayslipEmail($payslipId, $senderUserId = 1)
    {
        $payslipId = (int) $payslipId;
        if ($payslipId <= 0) {
            return ['success' => false, 'message' => 'Invalid payslip ID.'];
        }

        // 1. Fetch full payslip and employee data
        $payslipModel = new Payslip($this->db);
        $payslip = $payslipModel->getByIdWithItems($payslipId);

        if (!$payslip) {
            return ['success' => false, 'message' => 'Payslip record not found.'];
        }

        $recipientEmail = trim($payslip['email'] ?? '');
        $employeeName = trim($payslip['employee_name'] ?? 'Employee');
        $employeeCode = trim($payslip['employee_id'] ?? 'N/A');

        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => "Employee '{$employeeName}' has no valid email address ({$recipientEmail})."
            ];
        }

        $payslipMonthText = !empty($payslip['payslip_month'])
            ? date('F Y', strtotime($payslip['payslip_month']))
            : date('F Y');

        $companyTitle = function_exists('getSetting') ? getSetting('website_title') : 'TPHL ERP';
        if (empty($companyTitle)) $companyTitle = 'TPHL ERP';

        $subject = "Payslip for {$payslipMonthText} - {$companyTitle}";

        try {
            // 2. Generate PDF Attachment using TCPDF
            $pdfContent = $this->generatePayslipPdfContent($payslip, $payslipMonthText, $companyTitle);

            // 3. Build HTML Email Body
            $htmlBody = $this->buildEmailHtmlTemplate($payslip, $payslipMonthText, $companyTitle);

            // 4. Initialize PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = 0;

            $mail->Host       = $this->config['smtp_host'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['smtp_user'] ?? 'farzana.tphl13@gmail.com';
            $mail->Password   = $this->config['smtp_pass'] ?? 'hvptjfnsprecofmi';
            $mail->SMTPSecure = $this->config['smtp_secure'] ?? 'ssl';
            $mail->Port       = (int) ($this->config['smtp_port'] ?? 465);

            $mail->setFrom($mail->Username, $companyTitle . ' Payroll');
            $mail->addAddress($recipientEmail, $employeeName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = "Dear {$employeeName},\n\nYour payslip for {$payslipMonthText} is attached. Net Payable: ৳ " . number_format((float)($payslip['net_salary'] ?? 0), 2) . ".\n\nRegards,\n{$companyTitle}";

            // Attach PDF
            $pdfFilename = 'Payslip_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $employeeCode) . '_' . date('Y_m', strtotime($payslip['payslip_month'] ?? 'now')) . '.pdf';
            $mail->addStringAttachment($pdfContent, $pdfFilename, 'base64', 'application/pdf');

            // Send
            $mail->send();

            // 5. Update email_sent_at timestamp in database
            $stmtUpd = $this->db->prepare("UPDATE payslips SET email_sent_at = NOW(), email_sent_by = ? WHERE id = ?");
            if ($stmtUpd) {
                $stmtUpd->bind_param("ii", $senderUserId, $payslipId);
                $stmtUpd->execute();
                $stmtUpd->close();
            }

            return [
                'success' => true,
                'message' => "Payslip successfully emailed to {$employeeName} ({$recipientEmail})."
            ];
        } catch (Exception $e) {
            error_log("Payslip Email Failed for ID {$payslipId}: " . $mail->ErrorInfo);
            return [
                'success' => false,
                'message' => "Mailer error for {$employeeName}: " . $mail->ErrorInfo
            ];
        } catch (\Throwable $e) {
            error_log("Payslip General Exception for ID {$payslipId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Failed to process payslip email: " . $e->getMessage()
            ];
        }
    }

    /**
     * Build HTML Email Template
     */
    protected function buildEmailHtmlTemplate(array $payslip, string $monthText, string $companyTitle): string
    {
        $netSalary = number_format((float) ($payslip['net_salary'] ?? 0), 2);
        $totalEarning = number_format((float) ($payslip['total_earning'] ?? 0), 2);
        $totalDeduction = number_format((float) ($payslip['total_deduction'] ?? 0), 2);
        $basicSalary = number_format((float) ($payslip['basic_salary'] ?? 0), 2);

        $empName = htmlspecialchars($payslip['employee_name'] ?? '');
        $empCode = htmlspecialchars($payslip['employee_id'] ?? '');
        $desgName = htmlspecialchars($payslip['designation_name'] ?? 'N/A');
        $deptName = htmlspecialchars($payslip['department_name'] ?? 'N/A');

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Payslip Notification</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: #333;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f4f6f8; padding: 30px 15px;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                            
                            <!-- Brand Header -->
                            <tr>
                                <td style='background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); padding: 25px 30px; text-align: left;'>
                                    <h1 style='margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;'>{$companyTitle}</h1>
                                    <p style='margin: 4px 0 0 0; color: #ccfbf1; font-size: 13px;'>Payroll & HR Department</p>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style='padding: 30px;'>
                                    <h2 style='margin: 0 0 15px 0; color: #1e293b; font-size: 18px; font-weight: 600;'>Monthly Payslip Notification</h2>
                                    <p style='margin: 0 0 20px 0; color: #475569; font-size: 14px; line-height: 1.6;'>
                                        Dear <strong>{$empName}</strong>,
                                    </p>
                                    <p style='margin: 0 0 25px 0; color: #475569; font-size: 14px; line-height: 1.6;'>
                                        Your official salary payslip for the month of <strong>{$monthText}</strong> has been generated and approved. A detailed summary is provided below, and your complete official payslip is attached as a PDF.
                                    </p>

                                    <!-- Employee Info Box -->
                                    <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 25px;'>
                                        <tr>
                                            <td style='padding: 12px 15px; font-size: 13px; color: #64748b; width: 50%; border-bottom: 1px solid #e2e8f0;'><strong>Employee ID:</strong> {$empCode}</td>
                                            <td style='padding: 12px 15px; font-size: 13px; color: #64748b; width: 50%; border-bottom: 1px solid #e2e8f0;'><strong>Department:</strong> {$deptName}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 12px 15px; font-size: 13px; color: #64748b;'><strong>Designation:</strong> {$desgName}</td>
                                            <td style='padding: 12px 15px; font-size: 13px; color: #64748b;'><strong>Salary Period:</strong> {$monthText}</td>
                                        </tr>
                                    </table>

                                    <!-- Salary Summary Card -->
                                    <table width='100%' cellpadding='0' cellspacing='0' style='border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; margin-bottom: 25px;'>
                                        <tr style='background-color: #f1f5f9;'>
                                            <th align='left' style='padding: 10px 15px; font-size: 13px; color: #334155;'>Particulars</th>
                                            <th align='right' style='padding: 10px 15px; font-size: 13px; color: #334155;'>Amount (BDT)</th>
                                        </tr>
                                        <tr>
                                            <td style='padding: 10px 15px; font-size: 13px; color: #475569; border-top: 1px solid #e2e8f0;'>Basic Salary</td>
                                            <td align='right' style='padding: 10px 15px; font-size: 13px; color: #475569; border-top: 1px solid #e2e8f0;'>৳ {$basicSalary}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 10px 15px; font-size: 13px; color: #475569; border-top: 1px solid #e2e8f0;'>Total Earnings (Gross + Allowances)</td>
                                            <td align='right' style='padding: 10px 15px; font-size: 13px; color: #10b981; font-weight: 600; border-top: 1px solid #e2e8f0;'>৳ {$totalEarning}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 10px 15px; font-size: 13px; color: #475569; border-top: 1px solid #e2e8f0;'>Total Deductions</td>
                                            <td align='right' style='padding: 10px 15px; font-size: 13px; color: #ef4444; font-weight: 600; border-top: 1px solid #e2e8f0;'>৳ {$totalDeduction}</td>
                                        </tr>
                                        <tr style='background-color: #f0fdf4;'>
                                            <td style='padding: 14px 15px; font-size: 15px; color: #065f46; font-weight: 700; border-top: 2px solid #0f766e;'>Net Payable Salary</td>
                                            <td align='right' style='padding: 14px 15px; font-size: 17px; color: #0f766e; font-weight: 800; border-top: 2px solid #0f766e;'>৳ {$netSalary}</td>
                                        </tr>
                                    </table>

                                    <div style='background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; padding: 12px 16px; margin-bottom: 25px;'>
                                        <p style='margin: 0; font-size: 12px; color: #92400e; line-height: 1.5;'>
                                            <strong>📎 Attachment Notice:</strong> Your comprehensive payslip (including full itemized allowances, attendance details, and tax breakdowns) is attached to this email as a PDF file.
                                        </p>
                                    </div>

                                    <p style='margin: 0; color: #64748b; font-size: 13px; line-height: 1.5;'>
                                        If you have any queries regarding your salary or deduction calculations, please reach out to the HR department.
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0;'>
                                    <p style='margin: 0; font-size: 12px; color: #94a3b8;'>
                                        © " . date('Y') . " {$companyTitle}. All rights reserved.<br>
                                        This is an automated system email. Please do not reply directly to this message.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }

    /**
     * Generate PDF Payslip in Memory using TCPDF
     */
    protected function generatePayslipPdfContent(array $payslip, string $monthText, string $companyTitle): string
    {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor($companyTitle);
        $pdf->SetTitle("Payslip - " . ($payslip['employee_name'] ?? ''));
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $empName = htmlspecialchars($payslip['employee_name'] ?? '');
        $empCode = htmlspecialchars($payslip['employee_id'] ?? '');
        $desgName = htmlspecialchars($payslip['designation_name'] ?? 'N/A');
        $deptName = htmlspecialchars($payslip['department_name'] ?? 'N/A');
        $joinDate = !empty($payslip['joining_date']) ? date('d M Y', strtotime($payslip['joining_date'])) : 'N/A';
        $email = htmlspecialchars($payslip['email'] ?? 'N/A');

        $basic = (float) ($payslip['basic_salary'] ?? 0);
        $totalEarning = (float) ($payslip['total_earning'] ?? 0);
        $totalDeduction = (float) ($payslip['total_deduction'] ?? 0);
        $netSalary = (float) ($payslip['net_salary'] ?? 0);

        // Earnings items HTML
        $earningsHtml = "<tr><td style='padding: 4px; border-bottom: 1px solid #eee;'>Basic Salary</td><td style='text-align: right; padding: 4px; border-bottom: 1px solid #eee;'>৳ " . number_format($basic, 2) . "</td></tr>";
        $hasExtraEarnings = false;
        if (!empty($payslip['items'])) {
            foreach ($payslip['items'] as $item) {
                if (($item['item_type'] ?? '') === 'earning' && stripos($item['item_name'], 'basic') === false) {
                    $hasExtraEarnings = true;
                    $earningsHtml .= "<tr><td style='padding: 4px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item['item_name']) . "</td><td style='text-align: right; padding: 4px; border-bottom: 1px solid #eee;'>৳ " . number_format((float)$item['amount'], 2) . "</td></tr>";
                }
            }
        }
        if (!$hasExtraEarnings && $totalEarning > $basic) {
            $hr = (float)($payslip['house_rent_allowance'] ?? round($basic * 0.50, 2));
            $med = (float)($payslip['medical_allowance'] ?? round($basic * 0.30, 2));
            $conv = (float)($payslip['conveyance_allowance'] ?? round($basic * 0.10, 2));
            $rec = (float)($payslip['recreation_allowance'] ?? round($basic * 0.10, 2));
            if ($hr > 0) $earningsHtml .= "<tr><td style='padding: 4px; border-bottom: 1px solid #eee;'>House Rent Allowance</td><td style='text-align: right; padding: 4px; border-bottom: 1px solid #eee;'>৳ " . number_format($hr, 2) . "</td></tr>";
            if ($med > 0) $earningsHtml .= "<tr><td style='padding: 4px; border-bottom: 1px solid #eee;'>Medical Allowance</td><td style='text-align: right; padding: 4px; border-bottom: 1px solid #eee;'>৳ " . number_format($med, 2) . "</td></tr>";
            if ($conv > 0) $earningsHtml .= "<tr><td style='padding: 4px; border-bottom: 1px solid #eee;'>Conveyance Allowance</td><td style='text-align: right; padding: 4px; border-bottom: 1px solid #eee;'>৳ " . number_format($conv, 2) . "</td></tr>";
            if ($rec > 0) $earningsHtml .= "<tr><td style='padding: 4px; border-bottom: 1px solid #eee;'>Recreation Allowance</td><td style='text-align: right; padding: 4px; border-bottom: 1px solid #eee;'>৳ " . number_format($rec, 2) . "</td></tr>";
        }

        // Deductions items HTML
        $deductionsHtml = "";
        $hasDeductions = false;
        if (!empty($payslip['items'])) {
            foreach ($payslip['items'] as $item) {
                if (($item['item_type'] ?? '') === 'deduction' && (float)$item['amount'] > 0) {
                    $hasDeductions = true;
                    $deductionsHtml .= "<tr><td style='padding: 4px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item['item_name']) . "</td><td style='text-align: right; padding: 4px; border-bottom: 1px solid #eee;'>৳ " . number_format((float)$item['amount'], 2) . "</td></tr>";
                }
            }
        }
        if (!$hasDeductions) {
            $dedItems = [];
            if ((float)($payslip['advance_salary'] ?? 0) > 0) $dedItems['Advance Salary'] = (float)$payslip['advance_salary'];
            if ((float)($payslip['subsidized_lunch'] ?? 0) > 0) $dedItems['Subsidized Lunch'] = (float)$payslip['subsidized_lunch'];
            if ((float)($payslip['unpaid_absent_amount'] ?? 0) > 0) $dedItems['Unpaid Absent'] = (float)$payslip['unpaid_absent_amount'];
            if ((float)($payslip['tax'] ?? 0) > 0) $dedItems['Tax'] = (float)$payslip['tax'];
            if ((float)($payslip['arrears_deduction'] ?? 0) > 0) $dedItems['Arrears Deduction'] = (float)$payslip['arrears_deduction'];
            if ((float)($payslip['others_deduction'] ?? 0) > 0) $dedItems['Others Deduction'] = (float)$payslip['others_deduction'];
            
            if (!empty($dedItems)) {
                foreach ($dedItems as $dName => $dAmt) {
                    $deductionsHtml .= "<tr><td style='padding: 4px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($dName) . "</td><td style='text-align: right; padding: 4px; border-bottom: 1px solid #eee;'>৳ " . number_format($dAmt, 2) . "</td></tr>";
                }
            } elseif ($totalDeduction > 0) {
                $deductionsHtml = "<tr><td style='padding: 4px; border-bottom: 1px solid #eee;'>General Deductions</td><td style='text-align: right; padding: 4px; border-bottom: 1px solid #eee;'>৳ " . number_format($totalDeduction, 2) . "</td></tr>";
            } else {
                $deductionsHtml = "<tr><td style='padding: 4px; color: #888;'>No Deductions</td><td style='text-align: right; padding: 4px; color: #888;'>৳ 0.00</td></tr>";
            }
        }

        $html = "
        <style>
            table { font-family: helvetica, sans-serif; font-size: 10pt; }
            .header-title { font-size: 16pt; font-weight: bold; color: #0f766e; }
            .sub-title { font-size: 9pt; color: #555; }
            .box { border: 1px solid #cbd5e1; background-color: #f8fafc; }
            .table-heading { background-color: #0f766e; color: #ffffff; font-weight: bold; font-size: 10pt; }
            .total-row { background-color: #e2e8f0; font-weight: bold; }
            .net-box { border: 2px solid #0f766e; background-color: #f0fdf4; text-align: center; }
        </style>

        <table width='100%' cellpadding='0' cellspacing='0'>
            <tr>
                <td width='60%'>
                    <span class='header-title'>{$companyTitle}</span><br>
                    <span class='sub-title'>Official Salary Payslip</span>
                </td>
                <td width='40%' align='right'>
                    <b>Payslip Month:</b> {$monthText}<br>
                    <b>Generated Date:</b> " . date('d M Y') . "<br>
                    <b>Payslip No:</b> PS-{$payslip['id']}
                </td>
            </tr>
        </table>
        <hr color='#0f766e' height='2'>
        <br>

        <!-- Employee Info Table -->
        <table width='100%' cellpadding='5' cellspacing='0' class='box'>
            <tr>
                <td width='20%'><b>Employee Name:</b></td>
                <td width='30%'>{$empName}</td>
                <td width='20%'><b>Employee ID:</b></td>
                <td width='30%'>{$empCode}</td>
            </tr>
            <tr>
                <td><b>Designation:</b></td>
                <td>{$desgName}</td>
                <td><b>Department:</b></td>
                <td>{$deptName}</td>
            </tr>
            <tr>
                <td><b>Joining Date:</b></td>
                <td>{$joinDate}</td>
                <td><b>Email:</b></td>
                <td>{$email}</td>
            </tr>
        </table>
        <br><br>

        <!-- Earnings & Deductions Table -->
        <table width='100%' cellpadding='6' cellspacing='0' border='1' bordercolor='#cbd5e1'>
            <thead>
                <tr class='table-heading'>
                    <th width='50%'>Earnings</th>
                    <th width='50%'>Deductions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td valign='top'>
                        <table width='100%' cellpadding='3' cellspacing='0'>
                            {$earningsHtml}
                        </table>
                    </td>
                    <td valign='top'>
                        <table width='100%' cellpadding='3' cellspacing='0'>
                            {$deductionsHtml}
                        </table>
                    </td>
                </tr>
                <tr class='total-row'>
                    <td>
                        <table width='100%' cellpadding='2' cellspacing='0'>
                            <tr>
                                <td><b>Total Earnings:</b></td>
                                <td align='right'><b>৳ " . number_format($totalEarning, 2) . "</b></td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table width='100%' cellpadding='2' cellspacing='0'>
                            <tr>
                                <td><b>Total Deductions:</b></td>
                                <td align='right'><b>৳ " . number_format($totalDeduction, 2) . "</b></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
        <br><br>

        <!-- Net Salary Box -->
        <table width='100%' cellpadding='10' cellspacing='0' class='net-box'>
            <tr>
                <td>
                    <span style='font-size: 11pt; color: #065f46;'><b>NET PAYABLE SALARY</b></span><br>
                    <span style='font-size: 16pt; color: #0f766e; font-weight: bold;'>৳ " . number_format($netSalary, 2) . "</span>
                </td>
            </tr>
        </table>
        <br><br><br><br>

        <!-- Signatures -->
        <table width='100%' cellpadding='0' cellspacing='0'>
            <tr>
                <td width='30%' align='center'>
                    -----------------------------------<br>
                    <b>Prepared By (HR)</b>
                </td>
                <td width='40%' align='center'>
                    -----------------------------------<br>
                    <b>Verified By (Accounts)</b>
                </td>
                <td width='30%' align='center'>
                    -----------------------------------<br>
                    <b>Authorized Signature</b>
                </td>
            </tr>
        </table>
        <br><br>
        <p style='font-size: 8pt; color: #888; text-align: center;'>This is a computer-generated payslip and does not require a physical signature when sent digitally.</p>
        ";

        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S');
    }
}

