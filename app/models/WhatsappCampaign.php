
<?php
class WhatsappCampaign {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Create new campaign
    public function createCampaign($data) {
        $sql = "INSERT INTO whatsapp_campaigns 
                (name, target_group, project_id, message_type, message_content, schedule_time, device, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "ssissss",
            $data['name'],
            $data['target_group'],
            $data['project_id'],
            $data['message_type'],
            $data['message_content'],
            $data['schedule_time'],
            $data['device']
        );
        $stmt->execute();

        return $this->db->insert_id;
    }

    // Map leads to campaign based on project
    public function mapLeadsToCampaign($campaign_id, $project_id, $target_group) {
        // Insert matching leads into campaign contacts
        $sql = "INSERT INTO whatsapp_campaign_contacts (campaign_id, lead_id, delivery_status, created_at)
                SELECT ?, id, 'pending', NOW() FROM leads
                WHERE project_id = ? AND lead_type = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iis", $campaign_id, $project_id, $target_group);
        $stmt->execute();

        // Optional: update leads.target_project
        $sql2 = "UPDATE leads SET target_project = ? WHERE project_id = ?";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->bind_param("ii", $project_id, $project_id);
        $stmt2->execute();
    }

    // Get all campaigns
    public function getAllCampaigns() {
        $sql = "SELECT c.*, p.name AS project_name,
                       (SELECT COUNT(*) FROM whatsapp_campaign_contacts cc WHERE cc.campaign_id = c.id) AS total_leads,
                       (SELECT COUNT(*) FROM whatsapp_campaign_contacts cc WHERE cc.campaign_id = c.id AND delivery_status='delivered') AS delivered_count
                FROM whatsapp_campaigns c
                LEFT JOIN projects p ON p.id = c.project_id
                ORDER BY c.created_at DESC";

        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get campaign stats (sent, delivered, success %)
    public function getCampaignStats($campaign_id) {
        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN delivery_status='sent' THEN 1 ELSE 0 END) AS sent,
                    SUM(CASE WHEN delivery_status='delivered' THEN 1 ELSE 0 END) AS delivered,
                    ROUND(SUM(CASE WHEN delivery_status='delivered' THEN 1 ELSE 0 END)/COUNT(*)*100,2) AS success_rate
                FROM whatsapp_campaign_contacts
                WHERE campaign_id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $campaign_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Get dead leads (no reply after X days)
    public function getDeadLeads($campaign_id, $days=30) {
        $sql = "SELECT l.id, l.full_name, l.phone_number
                FROM whatsapp_campaign_contacts cc
                JOIN leads l ON l.id = cc.lead_id
                WHERE cc.campaign_id=? 
                  AND cc.response IS NULL
                  AND cc.created_at <= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $campaign_id, $days);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Update delivery status
    public function updateDeliveryStatus($contact_id, $status) {
        $sql = "UPDATE whatsapp_campaign_contacts SET delivery_status=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $status, $contact_id);
        $stmt->execute();
    }

    // Update lead response from API webhook
    public function updateLeadResponse($contact_id, $response) {
        $sql = "UPDATE whatsapp_campaign_contacts SET response=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $response, $contact_id);
        $stmt->execute();
    }
     public function getAllWithStats()
    {
        $stmt = $this->db->prepare("
            SELECT 
                c.id,
                c.name,
                c.target_group,
                c.status,
                c.schedule_time,
                c.device,
                COUNT(cc.lead_id) AS total_sent,
                SUM(cc.delivery_status='delivered') AS delivered_count,
                ROUND((SUM(cc.delivery_status='delivered')/COUNT(cc.lead_id))*100, 2) AS success_rate
            FROM whatsapp_campaigns c
            LEFT JOIN whatsapp_campaign_contacts cc ON cc.campaign_id = c.id
            GROUP BY c.id
            ORDER BY c.schedule_time DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    // Optional: count totals for analytics cards
    public function getAnalyticsSummary()
    {
        $summary = [];

        $summary['total'] = $this->db->query("SELECT COUNT(*) FROM whatsapp_campaigns")->fetch_row()[0];
        $summary['active'] = $this->db->query("SELECT COUNT(*) FROM whatsapp_campaigns WHERE status='Active'")->fetch_row()[0];
        $summary['scheduled'] = $this->db->query("SELECT COUNT(*) FROM whatsapp_campaigns WHERE status='Scheduled'")->fetch_row()[0];
        $summary['avg_success_rate'] = $this->db->query("
            SELECT ROUND(AVG(delivered_count/total_sent)*100,2) 
            FROM (
                SELECT COUNT(cc.lead_id) AS total_sent,
                       SUM(cc.delivery_status='delivered') AS delivered_count
                FROM whatsapp_campaigns c
                LEFT JOIN whatsapp_campaign_contacts cc ON cc.campaign_id = c.id
                GROUP BY c.id
            ) t
        ")->fetch_row()[0];

        return $summary;
    }
}