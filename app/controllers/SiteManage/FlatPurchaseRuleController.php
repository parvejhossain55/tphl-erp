<?php
namespace SiteManage;
use Controller;
use FlatPurchaseRule;
class FlatPurchaseRuleController extends Controller
{
     protected $db;
    protected $ruleModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ruleModel = new FlatPurchaseRule($db);
    }

    // List all rules
    public function index()
    {
        $rules = $this->ruleModel->getAll();
        $this->view("site-manage/flat-rules/flat-purchase-rules", [
            "current_route" => "flat-purchase-rule",
            "page_title" => "Flat Purchasing Rules",
            "rules" => $rules
        ]);
    }

    // Store new rule
 public function store()
{
    $rules = $_POST['rules'] ?? []; // array of all input rules
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($rules)) {
        setError('Please add at least one rule.');
        header("Location: {$GLOBALS['base_url']}/flat-purchase-rules");
        exit;
    }

    try {
        foreach ($rules as $ruleDescription) {
            $ruleDescription = trim($ruleDescription);
            if ($ruleDescription !== '') {
                $this->ruleModel->create([
                    'rules_description' => $ruleDescription,
                    'status' => $status
                ]);
            }
        }

        setSuccess('All rules created successfully');
        header("Location: {$GLOBALS['base_url']}/flat-purchase-rules");
        exit;

    } catch (\Throwable $th) {
        setError($th->getMessage());
        header("Location: {$GLOBALS['base_url']}/flat-purchase-rules");
        exit;
    }
}

    // Update existing rule
    public function update()
    {
        $data = [
            'id' => $_POST['id'] ?? 0,
            'rules_description' => $_POST['rules_description'] ?? '',
            'status' => isset($_POST['status']) ? 1 : 0
        ];

        try {
            if ($this->ruleModel->update($data['id'], $data)) {
                setSuccess('Rule Updated Successfully');
                header("Location: {$GLOBALS['base_url']}/flat-purchase-rules");
                exit;
            }
        } catch (\Throwable $th) {
            setError($th->getMessage());
            header("Location: {$GLOBALS['base_url']}/flat-purchase-rules");
            exit;
        }
    }

    // Delete rule
    public function delete()
    {
        $id = $_POST['id'] ?? 0;

        try {
            if ($this->ruleModel->delete($id)) {
                setSuccess('Rule Deleted Successfully');
                header("Location: {$GLOBALS['base_url']}/flat-purchase-rules");
                exit;
            }
        } catch (\Throwable $th) {
            setError($th->getMessage());
            header("Location: {$GLOBALS['base_url']}/flat-purchase-rules");
            exit;
        }
    }

    // Optional: show single rule
    public function show()
    {
        $this->view("site-manage/flat-rules/show", [
            "current_route" => "show-rule",
            "page_title" => "Show Rule",
            "show_breadcrumb" => true,
        ]);
    }
}
