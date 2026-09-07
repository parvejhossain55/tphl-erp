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
            $netSalaryVal = (float)($payslip['net_salary'] ?? 0);
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
            $netFormatted = ((float)$netSalaryVal == (int)$netSalaryVal) ? number_format($netSalaryVal, 0, '.', '') : number_format($netSalaryVal, 2, '.', '');
            $bankFormatted = $formatVal($bankAmountVal);
            $cashFormatted = $formatVal($cashAmountVal);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = "Dear {$employeeName},\n\nPlease find attached your Payslip for {$payslipMonthText} with the salary breakdown details below:\n\nNet Payable Salary: {$netFormatted}\nBank Payment: {$bankFormatted}\nCash Payment: {$cashFormatted}\n\nPlease review the attached Payslip for your reference.";

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
        $netSalaryVal = (float) ($payslip['net_salary'] ?? 0);
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
            return ((float)$val == (int)$val) ? number_format($val, 0, '.', '') : number_format($val, 2, '.', '');
        };

        $netSalaryFormatted = ((float)$netSalaryVal == (int)$netSalaryVal) ? number_format($netSalaryVal, 0, '.', '') : number_format($netSalaryVal, 2, '.', '');
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
        <strong>Net Payable Salary:</strong> {$netSalaryFormatted}<br>
        <strong>Bank Payment:</strong> {$bankPaymentFormatted}<br>
        <strong>Cash Payment:</strong> {$cashPaymentFormatted}
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
        $desgName = htmlspecialchars($payslip['designation_name'] ?? 'N/A');
        $deptName = htmlspecialchars($payslip['department_name'] ?? 'N/A');

        $basic = (float) ($payslip['basic_salary'] ?? 0);
        $totalEarning = (float) ($payslip['total_earning'] ?? 0);
        $totalDeduction = (float) ($payslip['total_deduction'] ?? 0);
        $netSalary = (float) ($payslip['net_salary'] ?? 0);

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

        // Title: Exactly like screenshot
        $titleText = "Payslip of {$monthText}";

        $createdDate = !empty($payslip['created_at']) ? date('d M Y', strtotime($payslip['created_at'])) : date('d M Y');
        $typeOrDue = $isDraft ? 'Salary Certificate (Draft)' : (!empty($payslip['updated_at']) ? date('d M Y', strtotime($payslip['updated_at'])) : date('d M Y'));

        // Earnings breakdown
        // NOTE: Taka symbol ৳ must NOT be wrapped inside <b> to prevent FreeSans Bold missing glyph '?'
        $earningsRows = '<tr><td style="padding: 4px 0;">Basic Salary</td><td align="right" style="padding: 4px 0;">৳ ' . number_format($basic, 2) . '</td></tr>';
        $hasExtraEarnings = false;
        if (!empty($payslip['items'])) {
            foreach ($payslip['items'] as $item) {
                if (($item['item_type'] ?? '') === 'earning' && stripos($item['item_name'], 'basic') === false) {
                    $hasExtraEarnings = true;
                    $earningsRows .= '<tr><td style="padding: 4px 0;">' . htmlspecialchars($item['item_name']) . '</td><td align="right" style="padding: 4px 0;">৳ ' . number_format((float)$item['amount'], 2) . '</td></tr>';
                }
            }
        }
        if (!$hasExtraEarnings && $totalEarning > $basic) {
            $hr = (float)($payslip['house_rent_allowance'] ?? round($basic * 0.50, 2));
            $med = (float)($payslip['medical_allowance'] ?? round($basic * 0.30, 2));
            $conv = (float)($payslip['conveyance_allowance'] ?? round($basic * 0.10, 2));
            $rec = (float)($payslip['recreation_allowance'] ?? round($basic * 0.10, 2));
            if ($hr > 0) $earningsRows .= '<tr><td style="padding: 4px 0;">House Rent Allowance</td><td align="right" style="padding: 4px 0;">৳ ' . number_format($hr, 2) . '</td></tr>';
            if ($med > 0) $earningsRows .= '<tr><td style="padding: 4px 0;">Medical Allowance</td><td align="right" style="padding: 4px 0;">৳ ' . number_format($med, 2) . '</td></tr>';
            if ($conv > 0) $earningsRows .= '<tr><td style="padding: 4px 0;">Conveyance Allowance</td><td align="right" style="padding: 4px 0;">৳ ' . number_format($conv, 2) . '</td></tr>';
            if ($rec > 0) $earningsRows .= '<tr><td style="padding: 4px 0;">Recreation Allowance</td><td align="right" style="padding: 4px 0;">৳ ' . number_format($rec, 2) . '</td></tr>';
        }

        // Deductions breakdown
        $deductionsRows = '';
        $hasDeductions = false;
        if (!empty($payslip['items'])) {
            foreach ($payslip['items'] as $item) {
                if (($item['item_type'] ?? '') === 'deduction' && (float)$item['amount'] > 0) {
                    $hasDeductions = true;
                    $deductionsRows .= '<tr><td style="padding: 4px 0;">' . htmlspecialchars($item['item_name']) . '</td><td align="right" style="padding: 4px 0;">৳ ' . number_format((float)$item['amount'], 2) . '</td></tr>';
                }
            }
        }
        if (!$hasDeductions) {
            $dedItems = [];
            if ((float)($payslip['advance_salary'] ?? 0) > 0) $dedItems['Advance Salary'] = (float)$payslip['advance_salary'];
            if ((float)($payslip['subsidized_lunch'] ?? 0) > 0) $dedItems['Subsidized Lunch'] = (float)$payslip['subsidized_lunch'];
            if ((float)($payslip['unpaid_absent_amount'] ?? 0) > 0) $dedItems['Unpaid Absent'] = (float)$payslip['unpaid_absent_amount'];
            if ((float)($payslip['tax'] ?? 0) > 0) $dedItems['TAX Deduction'] = (float)$payslip['tax'];
            if ((float)($payslip['arrears_deduction'] ?? 0) > 0) $dedItems['Arrears Deduction'] = (float)$payslip['arrears_deduction'];
            if ((float)($payslip['others_deduction'] ?? 0) > 0) $dedItems['Others Deduction'] = (float)$payslip['others_deduction'];
            if (!empty($dedItems)) {
                foreach ($dedItems as $dName => $dAmt) {
                    $deductionsRows .= '<tr><td style="padding: 4px 0;">' . htmlspecialchars($dName) . '</td><td align="right" style="padding: 4px 0;">৳ ' . number_format($dAmt, 2) . '</td></tr>';
                }
            } elseif ($totalDeduction > 0) {
                $deductionsRows = '<tr><td style="padding: 4px 0;">General Deductions</td><td align="right" style="padding: 4px 0;">৳ ' . number_format($totalDeduction, 2) . '</td></tr>';
            } else {
                $deductionsRows = '<tr><td style="padding: 4px 0; color: #64748b;">No Deduction</td><td align="right" style="padding: 4px 0; color: #64748b;">৳ 0.00</td></tr>';
            }
        }

        $wordsAmount = function_exists('amountInWords') ? trim(amountInWords($netSalary)) : '';
        $wordsText = !empty($wordsAmount) ? "{$wordsAmount} Taka Only" : "Taka Only";

        // Status badge (only displayed for paid, no icon characters to avoid missing glyphs)
        $statusBadgeHtml = '';
        if ($isPaid) {
            $statusBadgeHtml = '<div style="margin-top: 8px;"><span style="color: #166534; font-weight: bold; background-color: #dcfce7; border: 1.5px solid #22c55e; padding: 4px 18px;">PAID</span></div>';
        }

        // Payment details (hidden in draft, displayed for paid)
        $paymentDetailsHtml = '';
        if ($isPaid) {
            $bankPayment = (float) ($payslip['bank_amount'] ?? 0);
            $cashPayment = (float) ($payslip['cash_amount'] ?? 0);
            if ($bankPayment <= 0 && $cashPayment <= 0) {
                $method = strtolower($payslip['payment_method'] ?? 'bank');
                if ($method === 'cash') $cashPayment = $netSalary;
                else $bankPayment = $netSalary;
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
            if (!empty($payslip['bank_name']) || !empty($payslip['account_number'])) {
                $paymentDetailsHtml .= '
                            <tr>
                                <td width="50%" style="color: #475569;"><b>Bank Name:</b> ' . htmlspecialchars($payslip['bank_name'] ?? 'N/A') . '</td>
                                <td width="50%" align="right" style="color: #475569;"><b>Account Number:</b> ' . htmlspecialchars($payslip['account_number'] ?? 'N/A') . '</td>
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
            <span style="font-size: 9pt; color: #374151; line-height: 1.4;">
                ' . $companyEmail . '<br>
                ' . $companyPhone . '
            </span>
        </td>
        <td width="48%" align="right" valign="top" style="line-height: 1.6; font-size: 10pt;">
            <b>Employee:</b> ' . $empName . '<br>
            <b>Designation:</b> ' . $desgName . '<br>
            <b>Department:</b> ' . $deptName . '
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
            ' . $titleText . '
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
                    <td width="35%"><span style="color: #0f766e; font-weight: bold;">Created:</span> ' . $createdDate . '</td>
                    <td width="35%" align="center"><span style="color: #0f766e; font-weight: bold;">Type:</span> ' . $typeOrDue . '</td>
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
        <!-- Left: Earnings -->
        <td width="48.5%" valign="top" style="border: 1px solid #d1d5db; background-color: #ffffff; padding: 10px;">
            <table width="100%" cellpadding="4" cellspacing="0">
                <tr>
                    <td colspan="2" style="color: #0f766e; font-size: 12pt; font-weight: bold; padding-bottom: 6px;">Earnings</td>
                </tr>
                ' . $earningsRows . '
                <tr>
                    <td style="border-top: 1px solid #d1d5db; padding-top: 8px; font-weight: bold;">Total Earnings</td>
                    <td align="right" style="border-top: 1px solid #d1d5db; padding-top: 8px;">৳ <b>' . number_format($totalEarning, 2) . '</b></td>
                </tr>
            </table>
        </td>

        <td width="3%"></td>

        <!-- Right: Deductions -->
        <td width="48.5%" valign="top" style="border: 1px solid #d1d5db; background-color: #ffffff; padding: 10px;">
            <table width="100%" cellpadding="4" cellspacing="0">
                <tr>
                    <td colspan="2" style="color: #0f766e; font-size: 12pt; font-weight: bold; padding-bottom: 6px;">Deductions</td>
                </tr>
                ' . $deductionsRows . '
                <tr>
                    <td style="border-top: 1px solid #d1d5db; padding-top: 8px; font-weight: bold;">Total Deductions</td>
                    <td align="right" style="border-top: 1px solid #d1d5db; padding-top: 8px;">৳ <b>' . number_format($totalDeduction, 2) . '</b></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br><br>

<!-- NET SALARY SECTION -->
<table width="100%" cellpadding="12" cellspacing="0">
    <tr>
        <td align="center" style="border: 2px solid #0f766e; background-color: #ffffff;">
            <span style="font-size: 11pt; font-weight: bold; color: #0f766e;">Net Salary (Take Home)</span><br><br>
            <span style="font-size: 24pt; color: #111827;"><span style="font-weight: normal;">৳</span> <b>' . number_format($netSalary, 2) . '</b></span><br><br>
            <span style="font-size: 9.5pt; color: #374151;">
                <span style="color: #0f766e; font-weight: bold;">Amount in Words:</span> ' . $wordsText . '
            </span>
            ' . $statusBadgeHtml . '
        </td>
    </tr>
</table>

' . $paymentDetailsHtml . '

<br><br><br><br><br>

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

