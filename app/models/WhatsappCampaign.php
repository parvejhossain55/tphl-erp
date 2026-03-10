<?php
class WhatsappCampaign
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all campaigns with stats
    public function getAllWithStats()
    {
        $sql = "SELECT wc.*, p.name AS project_name,

        COUNT(wm.id) AS total_messages,

        SUM(CASE WHEN wm.status='sent' THEN 1 ELSE 0 END) AS sent_count,

        SUM(CASE WHEN wm.status='delivered' THEN 1 ELSE 0 END) AS delivered_count,

        IFNULL(
            ROUND(
                SUM(CASE WHEN wm.status='delivered' THEN 1 ELSE 0 END) 
                / NULLIF(COUNT(wm.id),0) * 100
            ,2)
        ,0) AS success_percent

    FROM whatsapp_campaigns wc

    LEFT JOIN projects p 
        ON wc.target_project_id = p.id

    LEFT JOIN whatsapp_messages wm 
        ON wm.campaign_id = wc.id

    WHERE p.deleted_at IS NULL

    GROUP BY wc.id

    ORDER BY wc.id DESC";

        $res = $this->db->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    // Create campaign
    public function create($data)
    {
        $sql = "INSERT INTO whatsapp_campaigns 
                (name,target_project_id,message_type,message_content,schedule_time,device,status,created_at) 
                VALUES (?,?,?,?,?,?,'scheduled',NOW())";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "sissss",
            $data['name'],
            $data['target_project_id'],
            $data['message_type'],
            $data['message_content'],
            $data['schedule_time'],
            $data['device']
        );

        $stmt->execute();
        return $this->db->insert_id;
    }

    // Cron: get scheduled campaigns
    public function getScheduledCampaigns()
    {
        $sql = "SELECT * FROM whatsapp_campaigns 
                WHERE schedule_time <= NOW() 
                AND status IN ('scheduled','active')";

        $res = $this->db->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    // Create message queue
    public function createQueue($campaign)
    {
        $sql = "SELECT id FROM leads WHERE project_id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $campaign['target_project_id']);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($lead = $result->fetch_assoc()) {

            // duplicate check
            $check = $this->db->prepare("
                SELECT id FROM whatsapp_messages 
                WHERE campaign_id=? AND lead_id=?
            ");

            $check->bind_param("ii", $campaign['id'], $lead['id']);
            $check->execute();

            if ($check->get_result()->num_rows == 0) {

                $insert = $this->db->prepare("
                    INSERT INTO whatsapp_messages 
                    (campaign_id,lead_id,status,created_at) 
                    VALUES (?,?, 'pending',NOW())
                ");

                $insert->bind_param("ii", $campaign['id'], $lead['id']);
                $insert->execute();
            }
        }
    }

    // Get pending messages
    public function getPendingMessages($campaign_id)
    {
        $sql = "SELECT * FROM whatsapp_messages 
                WHERE campaign_id=? AND status='pending'
                LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $campaign_id);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Mark message sent
    public function markSent($campaign_id, $lead_id)
    {
        // 1 Update the individual message
        $stmt = $this->db->prepare("
        UPDATE whatsapp_messages 
        SET status='sent', updated_at=NOW()
        WHERE campaign_id=? AND lead_id=?
    ");
        $stmt->bind_param("ii", $campaign_id, $lead_id);
        $stmt->execute();
        $stmt->close();

        // 2 Check if any pending messages remain for this campaign
        $pendingMessages = $this->getPendingMessages($campaign_id);

        // 3 If none remain, update campaign status to Active
        if (count($pendingMessages) === 0) {
            $stmt2 = $this->db->prepare("
            UPDATE whatsapp_campaigns 
            SET status='Active', updated_at=NOW()
            WHERE id=?
        ");
            $stmt2->bind_param("i", $campaign_id);
            $stmt2->execute();
            $stmt2->close();
        }
    }
    // Mark delivered (webhook)
 public function markDelivered($campaign_id, $lead_id)
{
    $stmt = $this->db->prepare("
        UPDATE whatsapp_messages 
        SET status='delivered', updated_at=NOW()
        WHERE campaign_id = ? AND lead_id = ?
    ");
    $stmt->bind_param("ii", $campaign_id, $lead_id);
    $stmt->execute();
    $stmt->close();
}

    public function markSuccess($campaign_id, $lead_id)
    {
        $stmt = $this->db->prepare("
        UPDATE whatsapp_messages 
        SET success_status = 1, updated_at = NOW()
        WHERE campaign_id = ? AND lead_id = ?
    ");
        $stmt->bind_param("ii", $campaign_id, $lead_id);
        $stmt->execute();
        $stmt->close();
    }
    // Mark failed
    public function markFailed($campaign_id, $lead_id)
    {
        $stmt = $this->db->prepare("
            UPDATE whatsapp_messages 
            SET status='failed',updated_at=NOW()
            WHERE campaign_id=? AND lead_id=?
        ");

        $stmt->bind_param("ii", $campaign_id, $lead_id);
        $stmt->execute();
    }
}
