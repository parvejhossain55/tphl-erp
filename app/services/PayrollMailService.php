<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!class_exists('\TCPDF') && file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}
if (!function_exists('amountInWords') && file_exists(__DIR__ . '/../helpers/helper.php')) {
    require_once __DIR__ . '/../helpers/helper.php';
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

            $formatVal = function ($val) {
                if ($val <= 0) return '';
                return ((float)$val == (int)$val) ? number_format($val, 0, '.', '') : number_format($val, 2, '.', '');
            };

            $deductionTotal = 0;
            if (!empty($payslip['items'])) {
                foreach ($payslip['items'] as $item) {
                    if (($item['item_type'] ?? '') === 'deduction') {
                        $deductionTotal += (float)($item['amount'] ?? 0);
                    }
                }
            }
            $totEarning = (float)($payslip['total_earning'] ?? 0);
            $totDeduction = $deductionTotal > 0 ? $deductionTotal : (float)($payslip['total_deduction'] ?? 0);
            $netSalaryVal = $totEarning - $totDeduction;
            if ($netSalaryVal <= 0 && (float)($payslip['net_salary'] ?? 0) > 0) {
                $netSalaryVal = (float)$payslip['net_salary'];
            }

            $bankAmountVal = (float)($payslip['bank_amount'] ?? 0);
            $cashAmountVal = (float)($payslip['cash_amount'] ?? 0);
            if ($bankAmountVal <= 0 && $cashAmountVal <= 0) {
                $method = strtolower($payslip['payment_method'] ?? 'bank');
                if ($method === 'cash') {
                    $cashAmountVal = $netSalaryVal;
                } else {
                    $bankAmountVal = $netSalaryVal;
                }
            }
            $formatVal = function ($val) {
                if ($val <= 0) return '';
                return number_format($val, 2);
            };

            $netFormatted = number_format($netSalaryVal, 2);
            $bankFormatted = $formatVal($bankAmountVal);
            $cashFormatted = $formatVal($cashAmountVal);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = "Dear {$employeeName},\n\nPlease find attached your Payslip for {$payslipMonthText} with the salary breakdown details below:\n\nNet Salary: {$netFormatted}\nBank Payment: {$bankFormatted}\nCash Payment: {$cashFormatted}\n\nPlease review the attached Payslip for your reference.";

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
     * Build HTML Email Template matching user's requested format
     */
    protected function buildEmailHtmlTemplate(array $payslip, string $monthText, string $companyTitle): string
    {
        $deductionTotal = 0;
        if (!empty($payslip['items'])) {
            foreach ($payslip['items'] as $item) {
                if (($item['item_type'] ?? '') === 'deduction') {
                    $deductionTotal += (float)($item['amount'] ?? 0);
                }
            }
        }
        $totEarning = (float)($payslip['total_earning'] ?? 0);
        $totDeduction = $deductionTotal > 0 ? $deductionTotal : (float)($payslip['total_deduction'] ?? 0);
        $netSalaryVal = $totEarning - $totDeduction;
        if ($netSalaryVal <= 0 && (float)($payslip['net_salary'] ?? 0) > 0) {
            $netSalaryVal = (float)$payslip['net_salary'];
        }

        $bankAmountVal = (float) ($payslip['bank_amount'] ?? 0);
        $cashAmountVal = (float) ($payslip['cash_amount'] ?? 0);

        if ($bankAmountVal <= 0 && $cashAmountVal <= 0) {
            $method = strtolower($payslip['payment_method'] ?? 'bank');
            if ($method === 'cash') {
                $cashAmountVal = $netSalaryVal;
            } else {
                $bankAmountVal = $netSalaryVal;
            }
        }

        $formatVal = function ($val) {
            if ($val <= 0) return '';
            return number_format($val, 2);
        };

        $netSalaryFormatted = number_format($netSalaryVal, 2);
        $bankPaymentFormatted = $formatVal($bankAmountVal);
        $cashPaymentFormatted = $formatVal($cashAmountVal);

        $empName = htmlspecialchars(trim($payslip['employee_name'] ?? 'Employee'));

        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #222222;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        p {
            margin: 0 0 16px 0;
        }
        .salary-details {
            margin: 0 0 16px 0;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <p><strong>Dear {$empName},</strong></p>
    <p>Please find attached your Payslip for {$monthText} with the salary breakdown details below:</p>
    <div class='salary-details'>
        <strong>Net Salary:</strong> ৳ {$netSalaryFormatted}<br>
        <strong>Bank Payment:</strong> " . (!empty($bankPaymentFormatted) ? "৳ {$bankPaymentFormatted}" : "—") . "<br>
        <strong>Cash Payment:</strong> " . (!empty($cashPaymentFormatted) ? "৳ {$cashPaymentFormatted}" : "—") . "
    </div>
    <p>Please review the attached Payslip for your reference.</p>
</body>
</html>";
    }

    /**
     * Generate PDF Payslip in Memory using TCPDF, replicating show-payslip.php layout exactly without any '?'
     */
    protected function generatePayslipPdfContent(array $payslip, string $monthText, string $companyTitle): string
    {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor($companyTitle);
        $pdf->SetTitle("Payslip - " . ($payslip['employee_name'] ?? ''));
        $pdf->SetMargins(14, 14, 14);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('freesans', '', 10);
        $pdf->AddPage();

        $empName = htmlspecialchars($payslip['employee_name'] ?? '');
        $empOfficeId = htmlspecialchars($payslip['employee_office_id'] ?? ($payslip['employee_id'] ?? 'N/A'));
        $desgName = htmlspecialchars($payslip['designation_name'] ?? 'N/A');
        $deptName = htmlspecialchars($payslip['department_name'] ?? 'N/A');
        $joiningDateFormatted = !empty($payslip['joining_date']) ? date('d M Y', strtotime($payslip['joining_date'])) : '';

        // Categorize Earnings & Deductions exactly like show-payslip.php
        $basic = (float) ($payslip['basic_salary'] ?? 0);
        $totalEarning = (float) ($payslip['total_earning'] ?? 0);
        if ($basic <= 0 && $totalEarning > 0) {
            $basic = round($totalEarning * 0.50, 2);
        }

        $coreEarnings = [];
        $coreEarnings[] = ['name' => 'Basic Salary', 'amount' => $basic];
        $coreEarningTotal = $basic;
        $additionalEarnings = [];
        $additionalEarningTotal = 0;
        $deductions = [];
        $deductionTotal = 0;

        if (!empty($payslip['items'])) {
            foreach ($payslip['items'] as $item) {
                $name = trim($item['item_name'] ?? '');
                $type = $item['item_type'] ?? 'earning';
                $amt = (float)($item['amount'] ?? 0);
                if ($amt <= 0) continue;

                $lower = strtolower($name);
                if ($type === 'earning') {
                    if (strpos($lower, 'basic') !== false) {
                        continue;
                    } elseif (
                        strpos($lower, 'house rent') !== false ||
                        strpos($lower, 'medical') !== false ||
                        strpos($lower, 'conveyance') !== false ||
                        strpos($lower, 'recreation') !== false
                    ) {
                        $coreEarnings[] = ['name' => $name, 'amount' => $amt];
                        $coreEarningTotal += $amt;
                    } else {
                        $additionalEarnings[] = ['name' => $name, 'amount' => $amt];
                        $additionalEarningTotal += $amt;
                    }
                } elseif ($type === 'deduction') {
                    $deductions[] = ['name' => $name, 'amount' => $amt];
                    $deductionTotal += $amt;
                }
            }
        }

        $displayTotalEarnings = (float)($payslip['total_earning'] ?? ($coreEarningTotal + $additionalEarningTotal));
        $displayTotalDeduction = $deductionTotal > 0 ? $deductionTotal : (float)($payslip['total_deduction'] ?? 0);
        $displayNetSalary = $displayTotalEarnings - $displayTotalDeduction;

        $coreEarningsRows = '';
        foreach ($coreEarnings as $item) {
            $coreEarningsRows .= '<tr><td style="padding: 3px 0;">' . htmlspecialchars($item['name']) . '</td><td align="right" style="padding: 3px 0;">৳ ' . number_format($item['amount'], 2) . '</td></tr>';
        }

        $additionalEarningsRows = '';
        if (!empty($additionalEarnings)) {
            foreach ($additionalEarnings as $item) {
                $additionalEarningsRows .= '<tr><td style="padding: 3px 0;">' . htmlspecialchars($item['name']) . '</td><td align="right" style="padding: 3px 0;">৳ ' . number_format($item['amount'], 2) . '</td></tr>';
            }
        } else {
            $additionalEarningsRows = '<tr><td style="padding: 3px 0; color: #64748b;">No Additional Benefits</td><td align="right" style="padding: 3px 0; color: #64748b;">৳ 0.00</td></tr>';
        }

        $deductionsRows = '';
        if (!empty($deductions)) {
            foreach ($deductions as $item) {
                $deductionsRows .= '<tr><td style="padding: 3px 0;">' . htmlspecialchars($item['name']) . '</td><td align="right" style="padding: 3px 0;">৳ ' . number_format($item['amount'], 2) . '</td></tr>';
            }
        } else {
            $deductionsRows = '<tr><td style="padding: 3px 0; color: #64748b;">No Deduction</td><td align="right" style="padding: 3px 0; color: #64748b;">৳ 0.00</td></tr>';
        }

        $wordsText = function_exists('amountInWords') ? trim(amountInWords($displayNetSalary)) : '';

        // Logo resolution (prefer optimized thumbnail to prevent TCPDF memory exhaustion)
        $logoHtml = '';
        $logoThumb = realpath(__DIR__ . '/../../public/assets/images/logo/tphl_logo_thumb.png');
        if ($logoThumb && file_exists($logoThumb)) {
            $logoHtml = '<img src="' . $logoThumb . '" style="height: 38px;" /><br>';
        } else {
            $logoPath = realpath(__DIR__ . '/../../public/assets/images/logo/tphl_logo.png');
            if (!$logoPath || !file_exists($logoPath)) {
                $logoSetting = function_exists('getSetting') ? (getSetting('white_logo') ?: getSetting('logo')) : '';
                if ($logoSetting) {
                    $checkPath = realpath(__DIR__ . '/../../public/uploads/settings/' . $logoSetting);
                    if ($checkPath && file_exists($checkPath)) {
                        $logoPath = $checkPath;
                    }
                }
            }
            if ($logoPath && file_exists($logoPath)) {
                $info = @getimagesize($logoPath);
                if ($info && $info[0] <= 2500) {
                    $logoHtml = '<img src="' . $logoPath . '" style="height: 38px;" /><br>';
                }
            }
        }

        $companyEmail = function_exists('getSetting') ? getSetting('email') : 'career@dpremiumhomes.com';
        $companyPhone = function_exists('getSetting') ? (getSetting('phone') ?: getSetting('website_phone')) : '+8801958253300';

        $isDraft = (($payslip['status'] ?? '') === 'draft');
        $isPaid = (($payslip['status'] ?? '') === 'paid');

        $createdDate = !empty($payslip['created_at']) ? date('d M Y', strtotime($payslip['created_at'])) : date('d M Y');

        // Status badge
        $statusBadgeHtml = '';
        if ($isPaid) {
            $statusBadgeHtml = '<div style="margin-top: 8px;"><span style="color: #166534; font-weight: bold; background-color: #dcfce7; border: 1.5px solid #22c55e; padding: 4px 18px;">PAID</span></div>';
        }

        $displayBankName = !empty($payslip['emp_bank_name']) ? $payslip['emp_bank_name'] : ($payslip['bank_name'] ?? '');
        $displayAccountNo = !empty($payslip['emp_account_number']) ? $payslip['emp_account_number'] : ($payslip['account_number'] ?? '');
        $paymentDetailsHtml = '';
        if ($isPaid) {
            $bankPayment = (float) ($payslip['bank_amount'] ?? 0);
            $cashPayment = (float) ($payslip['cash_amount'] ?? 0);
            if ($bankPayment <= 0 && $cashPayment <= 0) {
                $method = strtolower($payslip['payment_method'] ?? 'bank');
                if ($method === 'cash') $cashPayment = $displayNetSalary;
                else $bankPayment = $displayNetSalary;
            }
            $paymentDetailsHtml = '
            <table width="100%" cellpadding="6" cellspacing="0">
                <tr>
                    <td style="border: 1px solid #d1d5db; background-color: #ffffff; padding: 10px;">
                        <span style="color: #0f766e; font-weight: bold; font-size: 11pt;">Payment Details</span><br><br>
                        <table width="100%" cellpadding="3" cellspacing="0">
                            <tr>
                                <td width="50%"><b>Payment Method:</b> ' . ucfirst($payslip['payment_method'] ?? 'Bank') . '</td>
                                <td width="50%" align="right">';
            if ($bankPayment > 0) $paymentDetailsHtml .= '<b>Bank Transfer:</b> ৳ ' . number_format($bankPayment, 2) . ' ';
            if ($cashPayment > 0) $paymentDetailsHtml .= '<b>Cash Payment:</b> ৳ ' . number_format($cashPayment, 2);
            $paymentDetailsHtml .= '</td>
                            </tr>';
            if (!empty($displayBankName) || !empty($displayAccountNo)) {
                $paymentDetailsHtml .= '
                            <tr>
                                <td width="50%" style="color: #475569;"><b>Bank Name:</b> ' . htmlspecialchars($displayBankName) . '</td>
                                <td width="50%" align="right" style="color: #475569;"><b>Account Number:</b> ' . htmlspecialchars($displayAccountNo) . '</td>
                            </tr>';
            }
            $paymentDetailsHtml .= '
                        </table>
                    </td>
                </tr>
            </table>';
        }

        $html = '
<style>
    body, table, div, p, span {
        font-family: freesans, sans-serif;
        color: #111111;
        font-size: 10pt;
    }
</style>

<!-- HEADER TABLE -->
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="52%" valign="top">
            ' . $logoHtml . '
            <span style="font-size: 11pt; font-weight: bold; color: #0f766e;">' . htmlspecialchars($companyTitle) . '</span><br>
            <span style="font-size: 9pt; color: #374151; line-height: 1.4;">
                ' . htmlspecialchars($companyEmail) . '<br>
                ' . htmlspecialchars($companyPhone) . '
            </span>
        </td>
        <td width="48%" align="right" valign="top" style="line-height: 1.5; font-size: 9.5pt;">
            <b>Employee ID:</b> ' . $empOfficeId . '<br>
            <b>Employee:</b> ' . $empName . '<br>
            <b>Designation:</b> ' . $desgName . '<br>
            <b>Department:</b> ' . $deptName .
            (!empty($joiningDateFormatted) ? '<br><b>Joining Date:</b> ' . $joiningDateFormatted : '') . '
        </td>
    </tr>
</table>

<!-- TEAL BORDER LINE -->
<table width="100%" cellpadding="0" cellspacing="0">
    <tr><td height="3" bgcolor="#0f766e" style="font-size: 1pt;">&nbsp;</td></tr>
</table>
<br><br>

<!-- MAIN TITLE -->
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="font-size: 20pt; font-weight: bold; color: #111827;">
            Payslip of ' . $monthText . '
        </td>
    </tr>
    <tr>
        <td align="center" style="padding-top: 4px;">
            <table width="90" cellpadding="0" cellspacing="0">
                <tr><td height="3" bgcolor="#0f766e" style="font-size: 1pt;">&nbsp;</td></tr>
            </table>
        </td>
    </tr>
</table>
<br><br>

<!-- PERIOD BAR -->
<table width="100%" cellpadding="6" cellspacing="0">
    <tr>
        <td style="border: 1px solid #d1d5db; background-color: #ffffff;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="33%"><span style="color: #0f766e; font-weight: bold;">Created:</span> ' . $createdDate . '</td>
                    <td width="37%" align="center"><span style="color: #0f766e; font-weight: bold;">Salary Month:</span> ' . $monthText . ($isDraft ? ' <span style="color:#64748b; font-size:8.5pt;">(Draft Certificate)</span>' : '') . '</td>
                    <td width="30%" align="right"><span style="color: #0f766e; font-weight: bold;">Payslip No:</span> PS-' . $payslip['id'] . '</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br><br>

<!-- EARNINGS & DEDUCTIONS CARDS (SIDE BY SIDE) -->
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <!-- Left: Core Earnings + Additional Earnings + Total Earnings -->
        <td width="48.5%" valign="top">
            <!-- Card 1: Earnings (Salary Breakdown) -->
            <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #d1d5db; background-color: #ffffff;">
                <tr>
                    <td>
                        <table width="100%" cellpadding="3" cellspacing="0">
                            <tr>
                                <td colspan="2" style="color: #0f766e; font-size: 11pt; font-weight: bold; padding-bottom: 4px;">Earnings (Salary Breakdown)</td>
                            </tr>
                            ' . $coreEarningsRows . '
                            <tr>
                                <td style="border-top: 1px solid #d1d5db; padding-top: 5px; font-weight: bold;">Gross Salary (Core)</td>
                                <td align="right" style="border-top: 1px solid #d1d5db; padding-top: 5px;"><span style="font-weight: normal;">৳</span> <b>' . number_format($coreEarningTotal, 2) . '</b></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <br><br>

            <!-- Card 2: Additional Earnings & Bonuses -->
            <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #d1d5db; background-color: #ffffff;">
                <tr>
                    <td>
                        <table width="100%" cellpadding="3" cellspacing="0">
                            <tr>
                                <td colspan="2" style="color: #2563eb; font-size: 11pt; font-weight: bold; padding-bottom: 4px;">Additional Earnings &amp; Bonuses</td>
                            </tr>
                            ' . $additionalEarningsRows . '
                            <tr>
                                <td style="border-top: 1px solid #d1d5db; padding-top: 5px; font-weight: bold; color: #2563eb;">Total Additional Earnings</td>
                                <td align="right" style="border-top: 1px solid #d1d5db; padding-top: 5px; color: #2563eb;"><span style="font-weight: normal;">৳</span> <b>' . number_format($additionalEarningTotal, 2) . '</b></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <br><br>

            <!-- Total Earnings Bar -->
            <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #86efac; background-color: #f0fdf4;">
                <tr>
                    <td style="color: #166534; font-weight: bold; font-size: 10pt;">Total Earnings</td>
                    <td align="right" style="color: #166534; font-size: 10pt;"><span style="font-weight: normal;">৳</span> <b>' . number_format($displayTotalEarnings, 2) . '</b></td>
                </tr>
            </table>
        </td>

        <td width="3%"></td>

        <!-- Right: Deductions -->
        <td width="48.5%" valign="top">
            <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #d1d5db; background-color: #ffffff;">
                <tr>
                    <td>
                        <table width="100%" cellpadding="3" cellspacing="0">
                            <tr>
                                <td colspan="2" style="color: #dc2626; font-size: 11pt; font-weight: bold; padding-bottom: 4px;">Deductions</td>
                            </tr>
                            ' . $deductionsRows . '
                            <tr>
                                <td style="border-top: 1px solid #d1d5db; padding-top: 5px; font-weight: bold; color: #dc2626;">Total Deductions</td>
                                <td align="right" style="border-top: 1px solid #d1d5db; padding-top: 5px; color: #dc2626;"><span style="font-weight: normal;">৳</span> <b>' . number_format($displayTotalDeduction, 2) . '</b></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br><br>

<!-- NET SALARY SECTION -->
<table width="100%" cellpadding="10" cellspacing="0">
    <tr>
        <td align="center" style="border: 2px solid #0f766e; background-color: #ffffff;">
            <span style="font-size: 11pt; font-weight: bold; color: #0f766e;">Net Salary</span><br><br>
            <span style="font-size: 24pt; color: #111827;"><span style="font-weight: normal;">৳</span> <b>' . number_format($displayNetSalary, 2) . '</b></span><br><br>
            <span style="font-size: 9.5pt; color: #374151;">
                <span style="color: #0f766e; font-weight: bold;">Amount in Words:</span> ' . $wordsText . '
            </span>
            ' . $statusBadgeHtml . '
        </td>
    </tr>
</table>

' . $paymentDetailsHtml . '

<br><br><br><br>

<!-- SIGNATURES SECTION -->
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="45%" align="center">
            <b>Employee Signature</b>
            <br><br><br><br>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td height="1" bgcolor="#111111" style="font-size: 1pt;">&nbsp;</td></tr>
            </table>
        </td>
        <td width="10%"></td>
        <td width="45%" align="center">
            <b>Authorized Signature</b>
            <br><br><br><br>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td height="1" bgcolor="#111111" style="font-size: 1pt;">&nbsp;</td></tr>
            </table>
        </td>
    </tr>
</table>
';

        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S');
    }
}

