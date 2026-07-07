<?php

class ApiController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->db = $db;
    }

    /**
     * GET /api/pending-approvals
     */
    public function pendingApprovals()
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getPendingApprovals($this->db));
        exit;
    }

    /**
     * GET /api/project-timeline-progress/(\d+)
     */
    public function projectTimelineProgress($projectId)
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getProjectTimelineAndProgress($this->db, (int)$projectId));
        exit;
    }

    /**
     * GET /api/site-engineer-performance
     */
    public function siteEngineerPerformance()
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getSiteEngineerPerformance($this->db));
        exit;
    }

    /**
     * GET /api/project-stock-levels/(\d+)
     */
    public function projectStockLevels($projectId)
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getProjectStockMaterials($this->db, (int)$projectId));
        exit;
    }

    /**
     * GET /api/sales-executive-performance
     */
    public function salesExecutivePerformance()
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getSalesExecutivePerformance($this->db));
        exit;
    }

    /**
     * GET /api/flat-inventory-summary
     * GET /api/flat-inventory-summary/(\d+)
     */
    public function flatInventorySummary($projectId = null)
    {
        header('Content-Type: application/json');
        $pid = $projectId !== null ? (int)$projectId : null;
        echo json_encode(AiDataService::getFlatSalesInventorySummary($this->db, $pid));
        exit;
    }

    /**
     * GET /api/financial-summary
     */
    public function financialSummary()
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getCompanyFinancialSummary($this->db));
        exit;
    }
}
