<?php
class BookingController extends Controller
{
    protected $db;
    protected $userModel;
    protected $leadModel;
    protected $bookingModel;
    protected $projectModel;
    protected $flatModel;
    protected $approvalRequestModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($this->db);
        $this->leadModel = new Lead($this->db);

        $this->bookingModel = new BookingLead($this->db);
        $this->projectModel = new Project($this->db);
        $this->flatModel = new FlatModel($this->db);
        $this->approvalRequestModel = new ApprovalRequest($this->db);
    }

    // Index page: show leads for follow-up
    public function index($lead_id = null)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($lead_id) {

            $lead = $this->leadModel->find($lead_id);

            // Optional: booking records for this lead
            $bookingRecords = $this->bookingModel->getByLead($lead_id);
        } else {
            $lead = null;
            $bookingRecords = [];
        }

        $projects = $this->projectModel->getAll();
        $flats = []; // empty initially

        $this->view("sales-developments/booking-leads/bookingLeads", [
            "lead" => $lead,
            "projects" => $projects,
            "flats" => $flats,
            "bookingRecords" => $bookingRecords,
            "selected_lead_id" => $lead_id,
            "current_route" => "booking-leads",
            "page_title" => "Booking Lead Page",
        ]);
    }

    // AJAX: Get flats by project
    public function getFlatsByProject($projectId)
    {
        if (ob_get_length()) ob_clean(); // clear accidental output

        $flats = $this->flatModel->getFlatsWithUnitByProject($projectId);

        header('Content-Type: application/json');
        echo json_encode($flats);
        exit;
    }
    // Store new follow-up
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $flatId = $_POST['flat_id'] ?? null;
        if (!$flatId) {
            setError("Please select a flat/unit!");
            $leadId = $_POST['lead_id'] ?? 0;
            header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            exit;
        }

        $discount = $_POST['discount_amount'] ?? 0;

        $data = [
            'lead_id' => $_POST['lead_id'],
            'project_id' => $_POST['project_id'],
            'flat_id' => $flatId,
            'created_by' => $_SESSION['user']['id'],
            'agreement_value' => $_POST['agreement_value'],
            'booking_date' => $_POST['booking_date'],
            'booking_status' => 'booked',
            'discount_amount' => $discount,
            'approval_status' => $discount > 0 ? 'pending' : 'not_required',
        ];

        $bookingId = $this->bookingModel->create($data);


        if ($discount > 0) {
            $this->approvalRequestModel->create([
                'department'     => 'Sales',
                'request_type'   => 'discount',
                'title'          => 'Lead Booking Discount Approval',
                'source_table'   => 'lead_bookings',
                'source_id'      => $bookingId,
                'request_amount' => $discount,
                'requested_by'  => $_SESSION['user']['id'],
                'approve_role'  => 'management',
                'meta_data'     => json_encode([
                    'lead_id' => $_POST['lead_id'],
                    'flat_id' => $flatId
                ])
            ]);
        }


        if ($bookingId) {
            $leadId = $_POST['lead_id'];
            setSuccess("Lead onboarded successfully!");
            header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            exit;
        } else {
            setError("Failed to onboard lead!");
            $leadId = $_POST['lead_id'];
            header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
            exit;
        }
    }


    public function update()
    {
        $id = (int)($_POST['id'] ?? 0);
        $flatId = ($_POST['flat_id'] ?? '') === '' ? null : (int)$_POST['flat_id'];

        $bookingData = [
            'lead_id' => (int)($_POST['lead_id'] ?? 0),
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'flat_id' => $flatId,
            'agreement_value' => $_POST['agreement_value'] ?? 0,
            'booking_date' => $_POST['booking_date'] ?? date('Y-m-d'),
            'booking_status' => $_POST['booking_status'] ?? 'booked',
            'created_by' => $_SESSION['user']['id'] ?? 1
        ];

        // Update lead_bookings table
        if ($this->bookingModel->update($bookingData, $id)) {

            // Update lead_payments table automatically
            $this->bookingModel->updateBookingPaymentAmount($id, $bookingData['agreement_value']);

            setSuccess("Booking updated successfully!");
        } else {
            setError("Failed to update booking!");
        }

        $leadId = $_POST['lead_id'] ?? 0;
        header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
        exit;
    }





    // Delete Employee
    public function delete($id) {}
}
