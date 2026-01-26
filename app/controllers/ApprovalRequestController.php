<?php

class ApprovalRequestController extends Controller
{
    protected $db;
    protected $approvalModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->approvalModel = new ApprovalRequest($this->db);
    }

    // Home dashboard এর জন্য pending requests fetch
    public function index()
    {
        // $pendingRequests = $this->approvalModel->getPending(10); // latest 10
        $roles = ['Super Admin', 'Admin']; // management
        $pendingRequests = $this->approvalModel->getPendingForRole($roles, 10);
        $this->view("home", [
            "current_route" => "approval-requests",
            'page_title' => 'Pending Approval Requests',
            'pendingRequests' => $pendingRequests
        ]);

        // Debug
        print_r($pendingRequests);
    }

    // Approve full
    // public function approveFull($id)
    // {
    //     $this->approvalModel->updateStatus($id, 'approved');
    //     header("Location: " . $GLOBALS['base_url'] . "/approval-requests");
    //     exit;
    // }

    // // Partial approve
    // public function approvePartial($id)
    // {
    //     $amount = $_POST['approved_amount'] ?? null;
    //     if ($amount) {
    //         $this->approvalModel->updateStatus($id, 'partially_approved', $amount);
    //     }
    //     header("Location: " . $GLOBALS['base_url'] . "/approval-requests");
    //     exit;
    // }

    // // Reject
    // public function reject($id)
    // {
    //     $this->approvalModel->updateStatus($id, 'rejected');
    //     header("Location: " . $GLOBALS['base_url'] . "/approval-requests");
    //     exit;
    // }

    // // View single request (optional)
    // public function show($id)
    // {
    //     $request = $this->approvalModel->find($id);
    //     $this->view('approvals/show', [
    //         'page_title' => 'Approval Request Details',
    //         'request' => $request
    //     ]);
    // }
}
