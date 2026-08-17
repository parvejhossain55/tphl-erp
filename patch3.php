<?php
$content = file_get_contents('app/controllers/AccountClientController.php');

$methods = <<<'EOD'

    /**
     * Trash a transaction
     */
    public function trashTransaction()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $paymentId = isset($_GET['payment_id']) ? (int) $_GET['payment_id'] : 0;
        $bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

        if (!$paymentId || !$bookingId) {
            setError("Invalid request: missing payment or booking ID.");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $isAccounts = in_array($roleId, [1, 3, 6, 30]);
        $redirectUrl = "{$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}";

        if (!$isAccounts) {
            setError("You do not have permission to trash transactions.");
            header("Location: {$redirectUrl}");
            exit;
        }

        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $result = $this->clientModel->trashTransaction($paymentId, $bookingId, $userId);

        if ($result['success']) {
            setSuccess($result['message']);
        } else {
            setError($result['message']);
        }

        header("Location: {$redirectUrl}");
        exit;
    }

    /**
     * Restore a trashed transaction
     */
    public function restoreTransaction()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $paymentId = isset($_GET['payment_id']) ? (int) $_GET['payment_id'] : 0;
        $bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

        if (!$paymentId || !$bookingId) {
            setError("Invalid request: missing payment or booking ID.");
            header("Location: {$GLOBALS['base_url']}/trash-client-payment-list");
            exit;
        }

        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $isAccounts = in_array($roleId, [1, 3, 6, 30]);

        if (!$isAccounts) {
            setError("You do not have permission to restore transactions.");
            header("Location: {$GLOBALS['base_url']}/trash-client-payment-list");
            exit;
        }

        $result = $this->clientModel->restoreTransaction($paymentId, $bookingId);

        if ($result['success']) {
            setSuccess($result['message']);
        } else {
            setError($result['message']);
        }

        header("Location: {$GLOBALS['base_url']}/trash-client-payment-list");
        exit;
    }

    /**
     * View trashed client payments list
     */
    public function trashList()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $isAccounts = in_array($roleId, [1, 3, 6, 30]);

        if (!$isAccounts) {
            setError("You do not have permission to view trash.");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        $trashedTransactions = $this->clientModel->getTrashedTransactions();

        $this->view("accounts/cashIn/trash-client-payment-list", [
            "current_route" => "trash-client-payment-list",
            "page_title" => "Trash Client Payment",
            "trashedTransactions" => $trashedTransactions
        ]);
    }
}
EOD;

$content = preg_replace('/\}\s*$/', $methods, $content);
file_put_contents('app/controllers/AccountClientController.php', $content);
echo "Controller methods added successfully.\n";
