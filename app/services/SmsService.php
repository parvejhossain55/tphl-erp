<?php

class SmsService
{
    protected $db;
    protected $apiUrl;
    protected $apiKey;
    protected $senderId;
    protected $globalTestingMode;

    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            if (isset($GLOBALS['db'])) {
                $this->db = $GLOBALS['db'];
            }
        }

        $config = require dirname(__DIR__) . '/config/config.php';
        $this->apiUrl   = $config['automas_sms_api_url']   ?? 'https://api.automas.com.bd/smsapiv4';
        $this->apiKey   = $config['automas_sms_api_key']   ?? '734f99f126704a937debae6c0ce52516';
        $this->senderId = $config['automas_sms_sender_id'] ?? 'TPHL';
        $this->globalTestingMode = !empty($config['sms_testing_mode']);
    }

    /**
     * Validate Bangladeshi mobile number format
     */
    public function validatePhoneNumber($phone)
    {
        if (empty($phone)) {
            return ['valid' => false, 'normalized' => '', 'message' => 'Client phone number is empty'];
        }

        // Clean spaces, dashes, plus signs
        $cleaned = preg_replace('/[^\d]/', '', (string)$phone);

        // If starts with 8801...
        if (strlen($cleaned) === 13 && substr($cleaned, 0, 3) === '880') {
            $cleaned = substr($cleaned, 2); // convert to 01...
        }

        if (strlen($cleaned) === 11 && preg_match('/^01[3-9]\d{8}$/', $cleaned)) {
            return ['valid' => true, 'normalized' => $cleaned, 'message' => 'Valid phone number'];
        }

        return ['valid' => false, 'normalized' => $cleaned, 'message' => 'Invalid phone number format (must be 11-digit BD mobile number like 01XXXXXXXXX)'];
    }

    /**
     * Send SMS via Automas v4 JSON API
     */
    public function sendSms($mobileNo, $message, $metaData = [], $isTestingMode = false)
    {
        $valResult = $this->validatePhoneNumber($mobileNo);
        $normalizedPhone = $valResult['normalized'];

        $clientId  = $metaData['client_id']  ?? null;
        $bookingId = $metaData['booking_id'] ?? null;
        $paymentId = $metaData['payment_id'] ?? null;
        $smsType   = $metaData['sms_type']   ?? 'general';

        $shouldSkip = $isTestingMode || $this->globalTestingMode;

        if ($shouldSkip) {
            $this->logSms([
                'client_id'        => $clientId,
                'booking_id'       => $bookingId,
                'payment_id'       => $paymentId,
                'mobile_no'        => $mobileNo,
                'sms_type'         => $smsType,
                'message'          => $message,
                'is_testing'       => 1,
                'gateway_response' => 'Skipped SMS dispatch (Testing Mode ON)',
                'status'           => 'skipped_testing'
            ]);

            return [
                'success' => true,
                'testing' => true,
                'message' => 'Skipped SMS dispatch (Testing Mode)'
            ];
        }

        if (!$valResult['valid']) {
            $this->logSms([
                'client_id'        => $clientId,
                'booking_id'       => $bookingId,
                'payment_id'       => $paymentId,
                'mobile_no'        => $mobileNo,
                'sms_type'         => $smsType,
                'message'          => $message,
                'is_testing'       => 0,
                'gateway_response' => 'Validation error: ' . $valResult['message'],
                'status'           => 'failed'
            ]);

            return [
                'success'  => false,
                'message'  => $valResult['message'],
                'response' => 'Validation error: ' . $valResult['message']
            ];
        }

        // Number is already validated and formatted by validatePhoneNumber (e.g., 01XXXXXXXXX)
        $destination = $normalizedPhone;

        // Check if message has non-ASCII (Bangla) characters
        $isUnicode = preg_match('/[^\x00-\x7F]/', $message);

        $payload = [
            "api_key"           => $this->apiKey,
            "senderid"          => $this->senderId,
            "type"              => $isUnicode ? "unicode" : "text",
            "scheduledDateTime" => "",
            "msg"               => $message,
            "contacts"          => $destination
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        curl_close($ch);

        $status = 'failed';

        if ($response === false || !empty($curlError)) {
            $resText = "cURL Connection Error" . ($curlErrNo ? " (Code {$curlErrNo})" : "") . ": " . ($curlError ?: "No HTTP response received from Automas API");
        } else {
            $resText = (string)$response;
            $jsonRes = json_decode((string)$response, true);
            $firstItem = (is_array($jsonRes) && isset($jsonRes[0]) && is_array($jsonRes[0])) ? $jsonRes[0] : (is_array($jsonRes) ? $jsonRes : []);
            
            $itemStatus = isset($firstItem['status']) ? strtolower(trim((string)$firstItem['status'])) : (isset($jsonRes['status']) ? strtolower(trim((string)$jsonRes['status'])) : '');
            $hasId      = !empty($firstItem['id']) || !empty($firstItem['message_id']) || !empty($jsonRes['id']) || !empty($jsonRes['message_id']);

            if ($itemStatus === '0' || $itemStatus === 'success' || $itemStatus === 'true' || $itemStatus === '1' || $itemStatus === 'ok' || $itemStatus === 'queued' || $itemStatus === 'submitted' || $hasId) {
                $status = 'sent';
            }
        }

        $this->logSms([
            'client_id'        => $clientId,
            'booking_id'       => $bookingId,
            'payment_id'       => $paymentId,
            'mobile_no'        => $normalizedPhone,
            'sms_type'         => $smsType,
            'message'          => $message,
            'is_testing'       => 0,
            'gateway_response' => $resText,
            'status'           => $status
        ]);

        return [
            'success' => ($status === 'sent'),
            'response' => $resText
        ];
    }

    /**
     * Send Trigger 1: Payment Received SMS
     */
    public function sendPaymentReceivedSms($paymentId, $isTestingMode = false)
    {
        $info = $this->getPaymentInfo($paymentId);
        if (!$info) {
            return [
                'success' => false,
                'response' => "Payment info not found in database for payment ID #{$paymentId}",
                'message'  => "Payment info not found in database for payment ID #{$paymentId}"
            ];
        }

        $clientName  = $info['client_name'] ?? 'Client';
        $phone       = $info['phone'] ?? '';
        $amount      = number_format((float)($info['amount'] ?? 0), 2);
        $paymentType = ucfirst(str_replace('_', ' ', $info['payment_type'] ?? 'payment'));
        $projectName = $info['project_name'] ?? 'Project';
        $flatName    = $info['flat_name'] ?? 'Unit';
        $method      = ucfirst($info['payment_method'] ?? 'cash');
        $ref         = $info['transaction_ref'] ?? ($info['cheque_no'] ?? 'N/A');

        $message = "Dear {$clientName}, TK {$amount} received for {$paymentType} ({$projectName}, Unit: {$flatName}) via {$method}. Ref: {$ref}. Verification pending with Accounts. - TPHL";

        return $this->sendSms($phone, $message, [
            'client_id'  => $info['client_id']  ?? null,
            'booking_id' => $info['booking_id'] ?? null,
            'payment_id' => $paymentId,
            'sms_type'   => 'payment_received'
        ], $isTestingMode);
    }

    /**
     * Send Trigger 2: Payment Verified SMS
     */
    public function sendPaymentVerifiedSms($paymentId, $isTestingMode = false)
    {
        $info = $this->getPaymentInfo($paymentId);
        if (!$info) {
            return [
                'success' => false,
                'response' => "Payment info not found in database for payment ID #{$paymentId}",
                'message'  => "Payment info not found in database for payment ID #{$paymentId}"
            ];
        }

        $clientName  = $info['client_name'] ?? 'Client';
        $phone       = $info['phone'] ?? '';
        $amount      = number_format((float)($info['amount'] ?? 0), 2);
        $paymentType = ucfirst(str_replace('_', ' ', $info['payment_type'] ?? 'payment'));
        $projectName = $info['project_name'] ?? 'Project';
        $flatName    = $info['flat_name'] ?? 'Unit';

        $message = "Dear {$clientName}, your payment of TK {$amount} for {$paymentType} ({$projectName}, Unit: {$flatName}) has been verified & credited by Accounts. Thank you. - TPHL";

        return $this->sendSms($phone, $message, [
            'client_id'  => $info['client_id']  ?? null,
            'booking_id' => $info['booking_id'] ?? null,
            'payment_id' => $paymentId,
            'sms_type'   => 'payment_verified'
        ], $isTestingMode);
    }

    /**
     * Internal helper to query payment details
     */
    protected function getPaymentInfo($paymentId)
    {
        if (!$this->db) return null;

        $stmt = $this->db->prepare("
            SELECT 
                p.id as payment_id,
                p.booking_id,
                p.payment_type,
                p.amount,
                p.payment_method,
                p.transaction_ref,
                p.cheque_no,
                b.lead_id as client_id,
                l.full_name as client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), TRIM(l.phone_number)) as phone,
                pr.name as project_name,
                f.flat_name
            FROM lead_payments p
            LEFT JOIN lead_bookings b ON p.booking_id = b.id
            LEFT JOIN leads l ON b.lead_id = l.id
            LEFT JOIN flats f ON b.flat_id = f.id
            LEFT JOIN projects pr ON b.project_id = pr.id
            WHERE p.id = ?
        ");
        if (!$stmt) return null;

        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    /**
     * Log SMS dispatch in database
     */
    protected function logSms($data)
    {
        if (!$this->db) return false;

        $stmt = $this->db->prepare("
            INSERT INTO sms_logs (client_id, booking_id, payment_id, mobile_no, sms_type, message, is_testing, gateway_response, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmt) {
            error_log("sms_logs prepare failed: " . $this->db->error);
            return false;
        }

        $clientId  = !empty($data['client_id'])  ? (int)$data['client_id']  : null;
        $bookingId = !empty($data['booking_id']) ? (int)$data['booking_id'] : null;
        $paymentId = !empty($data['payment_id']) ? (int)$data['payment_id'] : null;
        $mobileNo  = $data['mobile_no'] ?? '';
        $smsType   = $data['sms_type'] ?? 'general';
        $message   = $data['message'] ?? '';
        $isTesting = (int)($data['is_testing'] ?? 0);
        $response  = $data['gateway_response'] ?? '';
        $status    = $data['status'] ?? 'pending';

        $stmt->bind_param("iiisssiss", $clientId, $bookingId, $paymentId, $mobileNo, $smsType, $message, $isTesting, $response, $status);
        $exec = $stmt->execute();
        if (!$exec) {
            error_log("sms_logs execute failed: " . $stmt->error);
        }
        $stmt->close();
        return $exec;
    }

}
