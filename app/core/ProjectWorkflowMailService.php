<?php
/**
 * ProjectWorkflowMailService
 * ─────────────────────────────────────────────────────────────────
 * Handles all sequential pre-construction workflow email notifications.
 * Used by: ProjectController (assignment), LandVisibilityStudyController,
 *          ArchitectEngineeringController, ProjectDrawingController,
 *          ProjectStructuralDesignController, SoilTestController
 * ─────────────────────────────────────────────────────────────────
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

class ProjectWorkflowMailService
{
    private $db;

    // SMTP / OAuth config (same credentials as MailService.php)
    private $senderEmail  = 'no-reply@dpremiumhomes.com';
    private $senderName   = 'TPHL ERP System';

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Build full absolute URL for use in emails.
     * $GLOBALS['base_url'] is just '/TPHL-ERP' (no host).
     * We prefix it with scheme + host from $_SERVER.
     */
    private function getBaseUrl(): string
    {
        $base = rtrim($GLOBALS['base_url'] ?? '/TPHL-ERP', '/');

        // Already a full URL (production might set it)
        if (str_starts_with($base, 'http')) {
            return $base;
        }

        // Build from request context
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';

        return $scheme . '://' . $host . $base;
    }

    // ─────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────

    /**
     * BATCH 1 (CREATE) — Sent when a project is CREATED with team assignments.
     * Sends one email to each assigned team member.
     */
    public function sendProjectAssignmentEmails(int $projectId, string $projectName): void
    {
        $team = $this->getProjectTeam($projectId);
        if (empty($team)) return;

        $base = $this->getBaseUrl();

        foreach ($team as $member) {
            if (empty($member['email'])) continue;

            $subject = "Project Assignment: {$projectName}";
            $body    = $this->buildHtml(
                title:       "You've Been Assigned to a Project",
                greeting:    "Dear {$member['full_name']},",
                message:     "You have been assigned to the project <strong>{$projectName}</strong> as <strong>{$member['role_label']}</strong>.",
                stepHtml:    $this->buildPreConstructionSteps('assignment'),
                ctaLabel:    'Login to ERP System',
                ctaLink:     $base . '/admin-login',
                projectName: $projectName,
                note:        'Please log in to the ERP system to view your tasks and responsibilities for this project.'
            );

            $this->send($member['email'], $subject, $body);
        }
    }

    /**
     * BATCH 1 (UPDATE) — Smart reassignment email.
     * Compares old team data vs new team data.
     * Only emails users who are NEWLY assigned or CHANGED.
     *
     * @param array $oldProject  Row from projects table BEFORE update
     * @param array $newData     The new POST data array being saved
     * @param string $projectName
     */
    public function sendProjectReassignmentEmails(array $oldProject, array $newData, string $projectName): void
    {
        $base = $this->getBaseUrl();

        $roleMap = [
            'pm_id'              => 'Project Manager',
            'site_engineer_id'   => 'Site Engineer',
            'landman_id'         => 'Land Manager',
            'struct_engineer_id' => 'Structural Engineer',
            'draftsman_id'       => 'Draftsman',
            'architect_id'       => 'Architect',
            'estimator_id'       => 'Estimator',
            'qc_id'              => 'Quality Controller',
            'operator_id'        => 'Site Operator',
        ];

        foreach ($roleMap as $col => $roleLabel) {
            $oldId = (int)($oldProject[$col] ?? 0);
            $newId = (int)($newData[$col] ?? 0);

            // Skip if nothing changed or new assignment is empty
            if ($newId <= 0 || $newId === $oldId) continue;

            // This is a new or changed assignment — email the new person
            $user = $this->getUserById($newId);
            if (!$user || empty($user['email'])) continue;

            // Determine context message
            if ($oldId > 0) {
                // Replacing someone
                $msgContext = "Your role as <strong>{$roleLabel}</strong> for project <strong>{$projectName}</strong> has been <strong>updated</strong>. You are now the assigned {$roleLabel} for this project.";
                $emailTitle = "Project Role Updated: {$projectName}";
            } else {
                // Newly assigned
                $msgContext = "You have been <strong>newly assigned</strong> to the project <strong>{$projectName}</strong> as <strong>{$roleLabel}</strong>.";
                $emailTitle = "New Project Assignment: {$projectName}";
            }

            $subject = "{$emailTitle}";
            $body    = $this->buildHtml(
                title:       $emailTitle,
                greeting:    "Dear {$user['full_name']},",
                message:     $msgContext,
                stepHtml:    $this->buildPreConstructionSteps('assignment'),
                ctaLabel:    'Login to ERP System',
                ctaLink:     $base . '/admin-login',
                projectName: $projectName,
                note:        'Please log in to the ERP system to check your assigned tasks for this project.'
            );

            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * A&E PARTIAL — Sent only to PM when Architect & Engineering is 'partially' completed.
     */
    public function notifyPmOnAePartial(int $projectId, string $projectName): void
    {
        $project = $this->getProjectRow($projectId);
        if (!$project) return;

        $pm = $this->getUserById((int)($project['pm_id'] ?? 0));
        if (!$pm || empty($pm['email'])) return;

        $base    = $this->getBaseUrl();
        $subject = "📋 A&E In Progress: {$projectName}";
        $body    = $this->buildHtml(
            title:       'Architect & Engineering — Partially Completed',
            greeting:    "Dear {$pm['full_name']},",
            message:     "The <strong>Architect &amp; Engineering</strong> work for project <strong>{$projectName}</strong> is <strong>partially completed</strong>. The architect is still working on the design.",
            stepHtml:    $this->buildPreConstructionSteps('architect'),
            ctaLabel:    'Login to ERP System',
            ctaLink:     $base . '/admin-login',
            projectName: $projectName,
            note:        'This is a progress update. Full notification will be sent when A&E is fully completed.'
        );
        $this->send($pm['email'], $subject, $body);
    }

    /**
     * BATCH 2 — Land Visibility Study completed.
     * → Action required: Architect
     * → Copied: ALL team members (PM & SE always included)
     */
    public function notifyArchitectAfterLandStudy(int $projectId, string $projectName): void
    {
        $this->notifyAllTeam(
            projectId:    $projectId,
            projectName:  $projectName,
            completedStep:'land',
            activeStep:   'architect',
            activeCol:    'architect_id',
            activeRole:   'Architect',
            stepTitle:    'Land Visibility Study Completed',
            actionMsg:    "The <strong>Land Visibility Study</strong> for project <strong>{$projectName}</strong> has been completed. Please proceed with <strong>Architect &amp; Engineering</strong>.",
            progressMsg:  "The <strong>Land Visibility Study</strong> for project <strong>{$projectName}</strong> has been completed. Next: <strong>Architect &amp; Engineering</strong>.",
            ctaLabel:     'Go to Architect & Engineering',
            ctaPath:      '/con-architect-engineering'
        );
    }

    /**
     * BATCH 3 — Architect & Engineering completed.
     * → Action required: Draftsman
     * → Copied: ALL team members
     */
    public function notifyDraftsmanAfterAE(int $projectId, string $projectName): void
    {
        $this->notifyAllTeam(
            projectId:    $projectId,
            projectName:  $projectName,
            completedStep:'architect',
            activeStep:   'draftsman',
            activeCol:    'draftsman_id',
            activeRole:   'Draftsman',
            stepTitle:    'Architect & Engineering Completed',
            actionMsg:    "The <strong>Architect &amp; Engineering</strong> records for project <strong>{$projectName}</strong> have been submitted. Please proceed with <strong>Design Types &amp; Project Drawings</strong>.",
            progressMsg:  "<strong>Architect &amp; Engineering</strong> for project <strong>{$projectName}</strong> is done. Next: <strong>Project Drawings</strong>.",
            ctaLabel:     'Go to Project Drawings',
            ctaPath:      '/con-project-drawings'
        );
    }

    /**
     * BATCH 4 — Project Drawings completed.
     * → Action required: Structural Engineer
     * → Copied: ALL team members
     */
    public function notifyStructuralEngineerAfterDrawings(int $projectId, string $projectName): void
    {
        $this->notifyAllTeam(
            projectId:    $projectId,
            projectName:  $projectName,
            completedStep:'draftsman',
            activeStep:   'struct_engineer',
            activeCol:    'struct_engineer_id',
            activeRole:   'Structural Engineer',
            stepTitle:    'Project Drawings Uploaded',
            actionMsg:    "Project Drawings for <strong>{$projectName}</strong> have been uploaded. Please proceed with <strong>Project Structure Design</strong>.",
            progressMsg:  "Project Drawings for <strong>{$projectName}</strong> have been uploaded. Next: <strong>Structural Design</strong>.",
            ctaLabel:     'Go to Structure Design',
            ctaPath:      '/structure-design'
        );
    }

    /**
     * BATCH 5 — Structural Design completed.
     * → Action required: Site Engineer
     * → Copied: ALL team members
     */
    public function notifySiteEngineerAfterStructuralDesign(int $projectId, string $projectName): void
    {
        $this->notifyAllTeam(
            projectId:    $projectId,
            projectName:  $projectName,
            completedStep:'struct_engineer',
            activeStep:   'site_engineer',
            activeCol:    'site_engineer_id',
            activeRole:   'Site Engineer',
            stepTitle:    'Structural Design Completed',
            actionMsg:    "The Structural Design for project <strong>{$projectName}</strong> is complete. Please proceed with the <strong>Soil Test</strong>.",
            progressMsg:  "Structural Design for <strong>{$projectName}</strong> is complete. Next: <strong>Soil Test</strong>.",
            ctaLabel:     'Go to Soil Tests',
            ctaPath:      '/con-soil-tests'
        );
    }

    /**
     * BATCH 6 — Soil Test completed. Pre-construction fully done.
     * → Notifies ALL team members with all steps checked.
     */
    public function notifyPreConstructionComplete(int $projectId, string $projectName): void
    {
        $project = $this->getProjectRow($projectId);
        if (!$project) return;

        $base    = $this->getBaseUrl();
        $subject = "🎉 Pre-Construction Complete: {$projectName} Ready for BOQ";

        // PM, SE, Estimator (next actor), Management (3), PM Head (22). Exclude Admin (1).
        $pmId = (int)($project['pm_id'] ?? 0);
        $seId = (int)($project['site_engineer_id'] ?? 0);
        $estId = (int)($project['estimator_id'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT id, full_name, email, role_id 
            FROM users 
            WHERE (id IN (?, ?, ?) OR role_id IN (3, 22)) 
              AND role_id != 1 
              AND email IS NOT NULL AND email != ''
        ");
        $stmt->bind_param('iii', $pmId, $seId, $estId);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Deduplicate
        $uniqueRecipients = [];
        $seen = [];
        foreach ($recipients as $r) {
            if (!in_array($r['id'], $seen)) {
                $seen[] = $r['id'];
                $uniqueRecipients[] = $r;
            }
        }

        foreach ($uniqueRecipients as $user) {
            $body = $this->buildHtml(
                title:       'Pre-Construction Phase Complete!',
                greeting:    "Dear {$user['full_name']},",
                message:     "All pre-construction steps for project <strong>{$projectName}</strong> have been successfully completed. The project is now ready for <strong>BOQ / Initial Estimation</strong>.",
                stepHtml:    $this->buildPreConstructionSteps('complete'),
                ctaLabel:    'Go to Estimates',
                ctaLink:     $base . '/con-estimates',
                projectName: $projectName,
                note:        'You can now start the BOQ and initial estimation process for this project.'
            );
            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * BUDGET CREATION — Initial Estimate Budget submitted.
     * → Action required: Approvers (MD=1, Director=3, PM=15)
     */
    public function notifyApproversForNewInitialBudget(int $projectId, string $projectName, int $budgetId): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $pmId = (int)($project['pm_id'] ?? 0);

        // Get approvers: Management (3), PM Head (22), assigned PM
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE (role_id IN (3, 22) OR id = ?) AND email IS NOT NULL AND email != ''");
        $stmt->bind_param('i', $pmId);
        $stmt->execute();
        $approvers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $subject = "⚡ Action Required: Approve Initial Estimate Budget — {$projectName}";

        foreach ($approvers as $user) {
            $stepHtml = "<div style='margin:20px 0;'>
                <div style='display:flex;align-items:center;gap:12px;margin-bottom:10px;'>
                    <div style='width:28px;height:28px;border-radius:50%;background:#0f5132;color:#fff;
                                text-align:center;line-height:28px;
                                font-size:13px;font-weight:700;flex-shrink:0;'>&#9654;</div>
                    <div style='font-size:13px;color:#0f5132;'><strong>Budget Approval</strong> <em style='font-size:11px;color:#0f5132'>(Action Required)</em></div>
                </div>
            </div>";

            $body = $this->buildHtml(
                title:       "Action Required: Budget Approval",
                greeting:    "Dear {$user['full_name']},",
                message:     "A new <strong>Initial Estimate Budget</strong> has been submitted for project <strong>{$projectName}</strong> and is waiting for your approval.",
                stepHtml:    $stepHtml,
                ctaLabel:    'Review & Approve Budget',
                ctaLink:     $base . "/con-estimate-show/{$budgetId}",
                projectName: $projectName,
                note:        'Please review the budget items and labor costs before approving. Any of the authorized approvers can approve this.'
            );

            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * BOQ CREATION / SUBMISSION — BOQ submitted.
     * → Action required: Approvers (MD=1, Director=3, PM=15)
     */
    public function notifyApproversForNewBOQ(int $projectId, string $projectName, int $boqId): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $pmId = (int)($project['pm_id'] ?? 0);

        // Get approvers: Management (3), PM Head (22), assigned PM
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE (role_id IN (3, 22) OR id = ?) AND email IS NOT NULL AND email != ''");
        $stmt->bind_param('i', $pmId);
        $stmt->execute();
        $approvers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $subject = "⚡ Action Required: Approve BOQ / BOM — {$projectName}";

        foreach ($approvers as $user) {
            $stepHtml = "<div style='margin:20px 0;'>
                <div style='display:flex;align-items:center;gap:12px;margin-bottom:10px;'>
                    <div style='width:28px;height:28px;border-radius:50%;background:#2563eb;color:#fff;
                                text-align:center;line-height:28px;
                                font-size:13px;font-weight:700;flex-shrink:0;'>&#9654;</div>
                    <div style='font-size:13px;color:#2563eb;'><strong>BOQ Approval</strong> <em style='font-size:11px;color:#2563eb'>(Action Required)</em></div>
                </div>
            </div>";

            $body = $this->buildHtml(
                title:       "Action Required: BOQ Approval",
                greeting:    "Dear {$user['full_name']},",
                message:     "A new <strong>Bill of Quantities (BOQ)</strong> has been submitted for project <strong>{$projectName}</strong> and is waiting for your approval.",
                stepHtml:    $stepHtml,
                ctaLabel:    'Review & Approve BOQ',
                ctaLink:     $base . "/con-boq-show/{$boqId}",
                projectName: $projectName,
                note:        'Please review the material items and estimated quantities before approving. Any of the authorized approvers can approve this.'
            );

            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * BUDGET REJECTED — Notify Estimator (who created it)
     */
    public function notifyAfterBudgetRejection(int $projectId, string $projectName, int $budgetId, string $reason): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $estimatorId = (int)($project['estimator_id'] ?? 0);

        if ($estimatorId <= 0) return;

        $user = $this->getUserById($estimatorId);
        if (!$user || empty($user['email'])) return;

        $subject = "❌ Budget Rejected: {$projectName}";

        $body = $this->buildHtml(
            title:       "Initial Estimate Budget Rejected",
            greeting:    "Dear {$user['full_name']},",
            message:     "Your Initial Estimate Budget for project <strong>{$projectName}</strong> has been rejected.<br><br><strong>Rejection Reason:</strong> {$reason}",
            stepHtml:    "",
            ctaLabel:    'View Budget Details',
            ctaLink:     $base . "/con-estimate-show/{$budgetId}",
            projectName: $projectName,
            note:        'Please review the rejection notes and make the necessary corrections.'
        );

        $this->send($user['email'], $subject, $body);
    }

    /**
     * PURCHASE ORDER GENERATION
     * → Notify the first approval stage only: Purchase Head (17) when required,
     *   otherwise QC & Internal Audit (24).
     */
    public function notifyApproversForNewPO(int $projectId, string $projectName, int $poId): void
    {
        $creatorStmt = $this->db->prepare('
            SELECT u.role_id AS creator_role_id 
            FROM purchase_orders po 
            LEFT JOIN users u ON u.id = po.created_by 
            WHERE po.id = ? 
            LIMIT 1
        ');
        if (!$creatorStmt) {
            error_log("[ProjectWorkflowMailService] Failed to prepare creatorStmt for PO #{$poId}: " . $this->db->error);
            return;
        }
        $creatorStmt->bind_param('i', $poId);
        $creatorStmt->execute();
        $creator = $creatorStmt->get_result()->fetch_assoc();
        $creatorStmt->close();

        $firstRole = in_array((int)($creator['creator_role_id'] ?? 0), [23, 31], true) ? 17 : 24;
        $firstLabel = $firstRole === 17 ? 'Purchase Head' : 'QC & Internal Audit';

        $this->notifyPOApprovalStage($projectName, $poId, [$firstRole], $firstLabel);
    }

    /** Notify only the users responsible for the current PO approval stage. */
    public function notifyPOApprovalStage(string $projectName, int $poId, array $roleIds, string $stageLabel): void
    {
        $base = $this->getBaseUrl();
        $roleIds = array_values(array_filter(array_map('intval', $roleIds)));
        if (empty($roleIds)) return;

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE role_id IN ({$placeholders}) AND email IS NOT NULL AND email != ''");
        if (!$stmt) return;
        $types = str_repeat('i', count($roleIds));
        $stmt->bind_param($types, ...$roleIds);
        $stmt->execute();
        $approvers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($approvers as $user) {
            $subject = "Action Required: PO #{$poId} {$stageLabel} Approval — {$projectName}";
            $body = $this->buildHtml(
                title:       "Action Required: {$stageLabel} Approval",
                greeting:    "Dear {$user['full_name']},",
                message:     "Purchase Order <strong>#{$poId}</strong> for project <strong>{$projectName}</strong> is awaiting your <strong>{$stageLabel}</strong> approval.",
                stepHtml:    '',
                ctaLabel:    'Review & Approve PO',
                ctaLink:     $base . "/con-purchase-order/{$poId}",
                projectName: $projectName,
                note:        'Please review the Purchase Order and complete your approval step.'
            );
            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * PR REJECTED — Notify Procurement Head (20) & Site Engineer
     */
    public function notifyAfterPRRejection(int $projectId, string $projectName, int $prId): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $seId = (int)($project['site_engineer_id'] ?? 0);

        // Procurement Head (20) & assigned SE
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE (role_id = 20 OR id = ?) AND email IS NOT NULL AND email != ''");
        $stmt->bind_param('i', $seId);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $subject = "❌ Purchase Request Rejected — {$projectName}";

        foreach ($recipients as $user) {
            $body = $this->buildHtml(
                title:       "Purchase Request Rejected",
                greeting:    "Dear {$user['full_name']},",
                message:     "Purchase Request <strong>PR-{$prId}</strong> for project <strong>{$projectName}</strong> has been rejected by Management/PM.",
                stepHtml:    "",
                ctaLabel:    'View Purchase Request',
                ctaLink:     $base . "/con-purchase-request/{$prId}",
                projectName: $projectName,
                note:        'Please review the request or create a new one with correct details.'
            );
            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * PR SUBMITTED — Notify Project Manager Head (role_id 22) for Stage 1 approval.
     */
    public function notifyOnPRSubmission(int $projectId, string $projectName, string $engineerName, string $requisitionMonth, int $prId, string $prType = 'boq_linked'): void
    {
        $base = $this->getBaseUrl();

        // Stage 1 is handled only by Project Manager Head.
        $stmt = $this->db->prepare("
            SELECT id, full_name, email 
            FROM users 
            WHERE role_id = 22
              AND email IS NOT NULL 
              AND email != ''
        ");
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Deduplicate
        $uniqueRecipients = [];
        $seen = [];
        foreach ($recipients as $r) {
            if (!in_array($r['id'], $seen)) {
                $seen[] = $r['id'];
                $uniqueRecipients[] = $r;
            }
        }

        // Differentiate by PR type
        $isIndependent = ($prType === 'independent');
        $typeLabel     = $isIndependent ? '✏️ Not link with monthly target' : '📋 Link with monthly target';
        $typeColor     = $isIndependent ? '#d97706' : '#0284c7';
        $typeBg        = $isIndependent ? '#fef3c7' : '#e0f2fe';
        $typeTextColor = $isIndependent ? '#92400e' : '#0369a1';

        $subject = $isIndependent
            ? "✏️ Purchase Request (Not link with monthly target) Submitted — {$projectName}"
            : "📋 Purchase Request (Link with monthly target) Submitted: {$requisitionMonth} — {$projectName}";

        foreach ($uniqueRecipients as $user) {
            $stepHtml = "<div style='margin:20px 0;'>
                <div style='display:flex;align-items:center;gap:12px;margin-bottom:10px;'>
                    <div style='width:28px;height:28px;border-radius:50%;background:{$typeColor};color:#fff;
                                text-align:center;line-height:28px;
                                font-size:13px;font-weight:700;flex-shrink:0;'>&#9654;</div>
                    <div style='font-size:13px;color:{$typeColor};'>
                        <strong>Type:</strong>
                        <span style='background:{$typeBg};color:{$typeTextColor};padding:2px 8px;border-radius:4px;font-weight:bold;font-size:11px;'>{$typeLabel}</span>
                        &nbsp;
                        <strong>Status:</strong>
                        <span style='background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:4px;font-weight:bold;font-size:11px;'>Submitted</span>
                    </div>
                </div>
            </div>";

            $message = $isIndependent
                ? "A new <strong>Purchase Request (PR-{$prId}, Not link with monthly target)</strong> has been submitted by <strong>{$engineerName}</strong>. Materials are required by <strong>{$requisitionMonth}</strong>."
                : "A new <strong>Purchase Request (PR-{$prId}, Link with monthly target)</strong> has been submitted by Site Engineer <strong>{$engineerName}</strong> for the month of <strong>{$requisitionMonth}</strong>.";

            $body = $this->buildHtml(
                title:       "New Purchase Requisition Submitted",
                greeting:    "Dear {$user['full_name']},",
                message:     $message,
                stepHtml:    $stepHtml,
                ctaLabel:    'Review Purchase Request',
                ctaLink:     $base . "/con-purchase-request/{$prId}",
                projectName: $projectName,
                note:        'Please review the request items and proceed with the necessary approval or quotation processing.'
            );

            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * PR STAGE 1 APPROVED — Notify Purchase Head and Senior Purchase Executive.
     */
    public function notifyPurchaseTeamForPRApproval(int $projectId, string $projectName, int $prId, string $approverName): void
    {
        $base = $this->getBaseUrl();

        $stmt = $this->db->prepare(" 
            SELECT id, full_name, email, role_id
            FROM users
            WHERE role_id IN (17, 31)
              AND email IS NOT NULL
              AND email != ''
        ");
        if (!$stmt) {
            error_log('Failed to prepare Purchase Request approval recipients query: ' . $this->db->error);
            return;
        }

        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($recipients as $user) {
            $roleLabel = ((int)$user['role_id'] === 17) ? 'Purchase Head' : 'Senior Purchase Executive';
            $subject = "Action Required: Purchase Request Approval — {$projectName}";
            $body = $this->buildHtml(
                title:       'Purchase Request Awaiting Procurement Approval',
                greeting:    "Dear {$user['full_name']},",
                message:     "Purchase Request <strong>PR-{$prId}</strong> for project <strong>{$projectName}</strong> has been approved by Project Manager Head ({$approverName}) and is now awaiting your approval as <strong>{$roleLabel}</strong>.",
                stepHtml:    '',
                ctaLabel:    'Review Purchase Request',
                ctaLink:     $base . "/con-purchase-request/{$prId}",
                projectName: $projectName,
                note:        'Please review and approve the Purchase Request to continue the procurement workflow.'
            );

            $this->send($user['email'], $subject, $body);
        }
    }


    /**
     * PO REJECTED — Notify Procurement Head (20) & Site Engineer
     */
    public function notifyAfterPORejection(int $projectId, string $projectName, int $poId, string $remarks): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $seId = (int)($project['site_engineer_id'] ?? 0);

        // Procurement Head (20) & assigned SE
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE (role_id = 20 OR id = ?) AND email IS NOT NULL AND email != ''");
        $stmt->bind_param('i', $seId);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $subject = "❌ Purchase Order Rejected — {$projectName}";

        foreach ($recipients as $user) {
            $body = $this->buildHtml(
                title:       "Purchase Order Rejected",
                greeting:    "Dear {$user['full_name']},",
                message:     "Purchase Order <strong>#{$poId}</strong> for project <strong>{$projectName}</strong> has been rejected by QC/Management.<br><br><strong>Reason:</strong> {$remarks}",
                stepHtml:    "",
                ctaLabel:    'View Purchase Order',
                ctaLink:     $base . "/con-purchase-order/{$poId}",
                projectName: $projectName,
                note:        'Please review the PO details or CST selections based on the given remarks.'
            );
            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * PO FULLY APPROVED — Notify Site Operator

     * → Action required: Site Operator (20)
     */
    public function notifySiteOperatorForApprovedPO(int $poId, string $projectName): void
    {
        $base = $this->getBaseUrl();

        // Get Site Operators (Role 20)
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE role_id = 20 AND email IS NOT NULL AND email != ''");
        $stmt->execute();
        $approvers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $subject = "✅ Purchase Order Approved: Ready for Material Receiving — {$projectName}";

        foreach ($approvers as $user) {
            $stepHtml = "<div style='margin:20px 0;'>
                <div style='display:flex;align-items:center;gap:12px;margin-bottom:10px;'>
                    <div style='width:28px;height:28px;border-radius:50%;background:#10b981;color:#fff;
                                text-align:center;line-height:28px;
                                font-size:13px;font-weight:700;flex-shrink:0;'>&#10003;</div>
                    <div style='font-size:13px;color:#10b981;'><strong>PO Fully Approved</strong> <em style='font-size:11px;color:#10b981'>(Action Required: GRN)</em></div>
                </div>
            </div>";

            $body = $this->buildHtml(
                title:       "Action Required: Material Receiving (GRN)",
                greeting:    "Dear {$user['full_name']},",
                message:     "Purchase Order <strong>#{$poId}</strong> for project <strong>{$projectName}</strong> has been fully approved by QC & Internal Audit and Management. It is now unlocked for material receiving.",
                stepHtml:    $stepHtml,
                ctaLabel:    'Log Material Receiving (GRN)',
                ctaLink:     $base . "/con-material-receiving-create/{$poId}",
                projectName: $projectName,
                note:        'You can now log the receipt of materials from the PO dashboard. Ensure you verify the quantities accurately against the invoice.'
            );

            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * MATERIAL RECEIVING (GRN) CREATED — Notify QC
     */
    public function notifyQCForNewGRN(int $projectId, string $projectName, int $poId, int $grnId): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $qcId = (int)($project['qc_id'] ?? 0);

        if ($qcId <= 0) return;

        $user = $this->getUserById($qcId);
        if (!$user || empty($user['email'])) return;

        $subject = "🔍 QC Inspection Required: PO #{$poId} — {$projectName}";

        $stepHtml = "<div style='margin:20px 0;'>
            <div style='display:flex;align-items:center;gap:12px;margin-bottom:10px;'>
                <div style='width:28px;height:28px;border-radius:50%;background:#d97706;color:#fff;
                            text-align:center;line-height:28px;
                            font-size:13px;font-weight:700;flex-shrink:0;'>&#9888;</div>
                <div style='font-size:13px;color:#d97706;'><strong>QC Inspection Pending</strong> <em style='font-size:11px;color:#d97706'>(Action Required)</em></div>
            </div>
        </div>";

        $body = $this->buildHtml(
            title:       "Material Received — QC Inspection Required",
            greeting:    "Dear {$user['full_name']},",
            message:     "Materials for <strong>PO #{$poId}</strong> have arrived at the site (GRN-{$grnId}). Before adding these to the stock, your inspection is required.",
            stepHtml:    $stepHtml,
            ctaLabel:    'Perform QC Review',
            ctaLink:     $base . "/con-material-receiving-qc/{$grnId}",
            projectName: $projectName,
            note:        'Please review the materials for quality and quantity discrepancies.'
        );

        $this->send($user['email'], $subject, $body);
    }

    /**
     * QC INSPECTION COMPLETE — Notify PM/Management (if accepted) or Purchase Head (if rejected)
     */
    public function notifyAfterQCInspection(int $projectId, string $projectName, int $grnId, bool $hasRejections): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $pmId = (int)($project['pm_id'] ?? 0);

        if ($hasRejections) {
            // Notify Purchase Head (20/22)
            $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE role_id IN (20, 22) AND email IS NOT NULL AND email != ''");
            $stmt->execute();
            $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $subject = "⚠️ QC Rejected Materials: GRN-{$grnId} — {$projectName}";
            $title = "Materials Rejected by QC";
            $message = "During the QC inspection for <strong>GRN-{$grnId}</strong>, some materials were rejected. Please review the details to arrange a penalty or return process.";
        } else {
            // Notify PM (15) and Management (3)
            $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE (role_id = 3 OR id = ?) AND email IS NOT NULL AND email != ''");
            $stmt->bind_param('i', $pmId);
            $stmt->execute();
            $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $subject = "✅ QC Passed: Pending Stock Update GRN-{$grnId} — {$projectName}";
            $title = "QC Complete: Ready for Stock Update";
            $message = "The QC inspection for <strong>GRN-{$grnId}</strong> has been successfully passed. Please review and approve the GRN to update the stock.";
        }

        foreach ($recipients as $user) {
            $body = $this->buildHtml(
                title:       $title,
                greeting:    "Dear {$user['full_name']},",
                message:     $message,
                stepHtml:    "",
                ctaLabel:    'View GRN Details',
                ctaLink:     $base . "/con-material-receiving-qc/{$grnId}",
                projectName: $projectName,
                note:        'Please take necessary actions based on the inspection result.'
            );
            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * GRN APPROVED (STOCK UPDATED) — Notify Site Operator & Site Engineer
     */
    public function notifyAfterPMGRNApproval(int $projectId, string $projectName, int $grnId): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $seId = (int)($project['site_engineer_id'] ?? 0);
        $operatorId = (int)($project['operator_id'] ?? 0);

        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE id IN (?, ?) AND email IS NOT NULL AND email != ''");
        $stmt->bind_param('ii', $seId, $operatorId);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $subject = "📦 Stock Updated: GRN-{$grnId} Approved — {$projectName}";

        foreach ($recipients as $user) {
            $body = $this->buildHtml(
                title:       "Stock Successfully Updated",
                greeting:    "Dear {$user['full_name']},",
                message:     "The Material Receiving record <strong>GRN-{$grnId}</strong> has been fully approved by the PM/Management. The stock inventory has been successfully updated.",
                stepHtml:    "",
                ctaLabel:    'View Material Inventory',
                ctaLink:     $base . "/con-stocks",
                projectName: $projectName,
                note:        'You can now issue these materials for project tasks.'
            );
            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * WEEKLY REPORT SUBMITTED — Notify PM and QC
     */
    public function notifyQCAndPMForNewWeeklyReport(int $projectId, string $projectName, int $reportId, int $weekNo): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $pmId = (int)($project['pm_id'] ?? 0);
        $qcId = (int)($project['qc_id'] ?? 0);

        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE id IN (?, ?) AND email IS NOT NULL AND email != ''");
        $stmt->bind_param('ii', $pmId, $qcId);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $subject = "📈 Weekly Progress Report Submitted: Week {$weekNo} — {$projectName}";

        foreach ($recipients as $user) {
            $body = $this->buildHtml(
                title:       "Weekly Progress Report Submitted",
                greeting:    "Dear {$user['full_name']},",
                message:     "The Site Engineer has submitted the Weekly Progress Report for <strong>Week {$weekNo}</strong> of project <strong>{$projectName}</strong>. It is now awaiting your review and approval.",
                stepHtml:    "",
                ctaLabel:    'Review Progress Report',
                ctaLink:     $base . "/con-weekly-report/{$reportId}",
                projectName: $projectName,
                note:        'Please verify the work done against the target and approve the progress percentage.'
            );
            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * WEEKLY REPORT APPROVED BY PM — Notify Management & PM Head
     */
    public function notifyManagementForWeeklyReportApproval(int $projectId, string $projectName, int $reportId, int $weekNo): void
    {
        $base = $this->getBaseUrl();

        // Notify Management (3) and PM Head (22)
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE role_id IN (3, 22) AND email IS NOT NULL AND email != ''");
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $subject = "✅ Weekly Progress Approved: Week {$weekNo} — {$projectName}";

        foreach ($recipients as $user) {
            $body = $this->buildHtml(
                title:       "Weekly Progress Report Approved",
                greeting:    "Dear {$user['full_name']},",
                message:     "The Weekly Progress Report for <strong>Week {$weekNo}</strong> of project <strong>{$projectName}</strong> has been reviewed and fully approved by the Project Manager.",
                stepHtml:    "",
                ctaLabel:    'View Approved Report',
                ctaLink:     $base . "/con-weekly-report/{$reportId}",
                projectName: $projectName,
                note:        'You can view the detailed progress breakdown and targets achieved in the report dashboard.'
            );
            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * BUDGET EXTENSION / ADJUSTMENT REQUESTED
     * → Notifies Approvers (MD=1, Director=3, PM=15, PM Head=22)
     */
    public function notifyApproversForNewBudgetAdjustment(int $projectId, string $projectName, float $amount, string $reason): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $pmId = (int)($project['pm_id'] ?? 0);

        // Get approvers: MD (1), Director (3), PM Head (22), assigned PM
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE (role_id IN (1, 3, 22) OR id = ?) AND email IS NOT NULL AND email != ''");
        $stmt->bind_param('i', $pmId);
        $stmt->execute();
        $approvers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $actionWord = $amount > 0 ? "increase" : "decrease";
        $color = $amount > 0 ? "#10b981" : "#ef4444";
        $sign = $amount > 0 ? "+" : "";

        $subject = "⚡ Action Required: Approve Budget Adjustment — {$projectName}";

        foreach ($approvers as $user) {
            $stepHtml = "<div style='margin:20px 0;'>
                <div style='display:flex;align-items:center;gap:12px;margin-bottom:10px;'>
                    <div style='width:28px;height:28px;border-radius:50%;background:#0f5132;color:#fff;
                                text-align:center;line-height:28px;
                                font-size:13px;font-weight:700;flex-shrink:0;'>&#9654;</div>
                    <div style='font-size:13px;color:#0f5132;'><strong>Budget Adjustment Approval</strong> <em style='font-size:11px;color:#0f5132'>(Action Required)</em></div>
                </div>
            </div>";

            $body = $this->buildHtml(
                title:       "Action Required: Budget Adjustment Request",
                greeting:    "Dear {$user['full_name']},",
                message:     "A new budget adjustment request for project <strong>{$projectName}</strong> has been submitted and is waiting for your approval. The request is to {$actionWord} the budget by <strong>{$sign}" . number_format($amount, 2) . "</strong>.<br><br><strong>Reason:</strong> {$reason}",
                stepHtml:    $stepHtml,
                ctaLabel:    'Review Request',
                ctaLink:     $base . "/con-budget-extends",
                projectName: $projectName,
                note:        'Please review the adjustment details before approving. Any of the authorized approvers can approve this.'
            );

            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * BUDGET EXTENSION / ADJUSTMENT APPROVED
     * → Notifies Project Manager, Procurement Head, and Site Engineer.
     */
    public function notifyAfterBudgetAdjustment(int $projectId, string $projectName, float $amount, string $reason): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $pmId = (int)($project['pm_id'] ?? 0);
        $seId = (int)($project['site_engineer_id'] ?? 0);

        // Procurement Head (20), PM Head (22), assigned PM, assigned SE
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE (role_id IN (20, 22) OR id IN (?, ?)) AND email IS NOT NULL AND email != ''");
        $stmt->bind_param('ii', $pmId, $seId);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $actionWord = $amount > 0 ? "increased" : "decreased";
        $color = $amount > 0 ? "#10b981" : "#ef4444";
        $sign = $amount > 0 ? "+" : "";

        $subject = "📋 Budget Adjustment Approved — {$projectName}";

        foreach ($recipients as $user) {
            $stepHtml = "<div style='margin:20px 0;'>
                <div style='display:flex;align-items:center;gap:12px;margin-bottom:10px;'>
                    <div style='width:28px;height:28px;border-radius:50%;background:{$color};color:#fff;
                                text-align:center;line-height:28px;
                                font-size:13px;font-weight:700;flex-shrink:0;'>৳</div>
                    <div style='font-size:13px;color:{$color};'><strong>Budget {$actionWord} by {$sign}" . number_format($amount, 2) . "</strong></div>
                </div>
            </div>";

            $body = $this->buildHtml(
                title:       "Budget Adjustment Notification",
                greeting:    "Dear {$user['full_name']},",
                message:     "A budget adjustment request for project <strong>{$projectName}</strong> has been approved. The budget was {$actionWord} by <strong>{$sign}" . number_format($amount, 2) . "</strong>.<br><br><strong>Reason:</strong> {$reason}",
                stepHtml:    $stepHtml,
                ctaLabel:    'View Budget Extends',
                ctaLink:     $base . "/con-budget-extends",
                projectName: $projectName,
                note:        'Please review your target limits and purchase plans according to the new revised budget.'
            );

            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * Core "notify all team" engine used by Batch 2–5.
     *
     * - Responsible person  → "Action Required" email with their CTA link
     * - All other members   → "Progress Update" email (informational)
     * - PM & Site Engineer  → ALWAYS included regardless of step
     */
    private function notifyAllTeam(
        int    $projectId,
        string $projectName,
        string $completedStep,   // e.g. 'land'
        string $activeStep,      // e.g. 'architect'
        string $activeCol,       // e.g. 'architect_id'
        string $activeRole,      // e.g. 'Architect'
        string $stepTitle,
        string $actionMsg,       // message shown TO the responsible person
        string $progressMsg,     // message shown TO everyone else
        string $ctaLabel,
        string $ctaPath
    ): void {
        $project = $this->getProjectRow($projectId);
        if (!$project) return;

        $base = $this->getBaseUrl();
        $activeUserId = (int)($project[$activeCol] ?? 0);
        $pmId = (int)($project['pm_id'] ?? 0);
        $seId = (int)($project['site_engineer_id'] ?? 0);

        // Fetch targeted recipients: Active User, Assigned PM, Assigned SE, Management (3), PM Head (22)
        // Exclude Admin (1)
        $stmt = $this->db->prepare("
            SELECT id, full_name, email, role_id 
            FROM users 
            WHERE (id IN (?, ?, ?) OR role_id IN (3, 22)) 
              AND role_id != 1 
              AND email IS NOT NULL AND email != ''
        ");
        $stmt->bind_param('iii', $activeUserId, $pmId, $seId);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Deduplicate
        $uniqueRecipients = [];
        $seen = [];
        foreach ($recipients as $r) {
            if (!in_array($r['id'], $seen)) {
                $seen[] = $r['id'];
                $uniqueRecipients[] = $r;
            }
        }

        // Step HTML variants
        $stepHtmlActive  = $this->buildPreConstructionSteps($activeStep);
        $stepHtmlWatcher = $this->buildPreConstructionSteps($completedStep); // shows completed 

        foreach ($uniqueRecipients as $user) {
            $isResponsible = ($user['id'] === $activeUserId);

            if ($isResponsible) {
                // ── ACTION REQUIRED email ──
                $subject = "⚡ Action Required: {$stepTitle} — {$projectName}";
                $body    = $this->buildHtml(
                    title:       "Action Required: {$activeRole}",
                    greeting:    "Dear {$user['full_name']},",
                    message:     $actionMsg,
                    stepHtml:    $stepHtmlActive,
                    ctaLabel:    $ctaLabel,
                    ctaLink:     $base . $ctaPath,
                    projectName: $projectName,
                    note:        'Please complete your step to keep the project on track.'
                );
            } else {
                // ── PROGRESS UPDATE email ──
                $subject = "📋 Workflow Update: {$stepTitle} — {$projectName}";
                $body    = $this->buildHtml(
                    title:       "Progress Update: {$stepTitle}",
                    greeting:    "Dear {$user['full_name']},",
                    message:     $progressMsg . " <em style='color:#888;font-size:13px'>(Action required from: <strong>{$activeRole}</strong>)</em>",
                    stepHtml:    $stepHtmlWatcher,
                    ctaLabel:    'Login to ERP System',
                    ctaLink:     $base . '/admin-login',
                    projectName: $projectName,
                    note:        'This is a progress update. No action is required from you at this time.'
                );
            }

            $this->send($user['email'], $subject, $body);
        }
    }

    /**
     * Returns all unique team members who have an email address.
     * PM and Site Engineer are always included (they monitor everything).
     */
    private function getAllTeamMembers(int $projectId): array
    {
        $project = $this->getProjectRow($projectId);
        if (!$project) return [];

        // All role columns — PM & site_engineer always first (priority)
        $cols = [
            'pm_id', 'site_engineer_id',
            'landman_id', 'architect_id', 'draftsman_id',
            'struct_engineer_id', 'estimator_id', 'qc_id', 'operator_id',
        ];

        $seen    = [];
        $members = [];

        foreach ($cols as $col) {
            $uid = (int)($project[$col] ?? 0);
            if ($uid <= 0 || in_array($uid, $seen)) continue;
            $user = $this->getUserById($uid);
            if ($user && !empty($user['email'])) {
                $seen[]    = $uid;
                $members[] = $user;
            }
        }

        return $members;
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────

    private function getProjectRow(int $projectId): ?array
    {
        if ($projectId <= 0) return null;
        $stmt = $this->db->prepare(
            "SELECT id, name, pm_id, site_engineer_id, landman_id,
                    struct_engineer_id, draftsman_id, architect_id,
                    estimator_id, qc_id, operator_id
             FROM projects WHERE id = ? AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    private function getUserById(int $userId): ?array
    {
        if ($userId <= 0) return null;
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Returns all assigned team members with their role labels.
     */
    /**
     * MATERIAL ISSUED — Notify Site Engineer & Site Operator
     */
    public function notifyAfterMaterialIssue(int $projectId, string $projectName, int $issueId, string $contractorName): void
    {
        $base = $this->getBaseUrl();
        $project = $this->getProjectRow($projectId);
        $seId = (int)($project['site_engineer_id'] ?? 0);
        $operatorId = (int)($project['operator_id'] ?? 0);

        $stmt = $this->db->prepare("SELECT id, full_name, email FROM users WHERE id IN (?, ?) AND email IS NOT NULL AND email != ''");
        $stmt->bind_param('ii', $seId, $operatorId);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $subject = "📤 Material Issued to {$contractorName} — {$projectName}";

        foreach ($recipients as $user) {
            $body = $this->buildHtml(
                title:       "Material Issue Logged",
                greeting:    "Dear {$user['full_name']},",
                message:     "Materials have been successfully issued to <strong>{$contractorName}</strong> (Issue Record: ISS-{$issueId}). Stock has been automatically deducted.",
                stepHtml:    "",
                ctaLabel:    'View Issue Details',
                ctaLink:     $base . "/con-material-issue/{$issueId}",
                projectName: $projectName,
                note:        'Please ensure the materials are properly utilized at the site.'
            );
            $this->send($user['email'], $subject, $body);
        }
    }

    private function getProjectTeam(int $projectId): array
    {
        $project = $this->getProjectRow($projectId);
        if (!$project) return [];

        $roleMap = [
            'pm_id'              => 'Project Manager',
            'site_engineer_id'   => 'Site Engineer',
            'landman_id'         => 'Land Manager',
            'struct_engineer_id' => 'Structural Engineer',
            'draftsman_id'       => 'Draftsman',
            'architect_id'       => 'Architect',
            'estimator_id'       => 'Estimator',
            'qc_id'              => 'Quality Controller',
            'operator_id'        => 'Site Operator',
        ];

        $team = [];
        foreach ($roleMap as $col => $label) {
            $uid = (int)($project[$col] ?? 0);
            if ($uid <= 0) continue;
            $user = $this->getUserById($uid);
            if ($user) {
                $user['role_label'] = $label;
                $team[] = $user;
            }
        }
        return $team;
    }

    /**
     * Build the step-by-step progress HTML for email body.
     * $currentStep: 'assignment'|'architect'|'draftsman'|'struct_engineer'|'site_engineer'|'complete'
     */
    private function buildPreConstructionSteps(string $currentStep): string
    {
        $steps = [
            ['key' => 'land',          'label' => 'Land Visibility Study'],
            ['key' => 'architect',     'label' => 'Architect & Engineering'],
            ['key' => 'draftsman',     'label' => 'Design Types & Project Drawings'],
            ['key' => 'struct_engineer','label' => 'Project Structure Design'],
            ['key' => 'site_engineer', 'label' => 'Soil Tests'],
        ];

        $order = ['assignment' => -1, 'land' => 0, 'architect' => 1,
                  'draftsman' => 2, 'struct_engineer' => 3,
                  'site_engineer' => 4, 'complete' => 5];
        $currentIdx = $order[$currentStep] ?? -1;

        $html = "<div style='margin:20px 0;'>";
        foreach ($steps as $i => $step) {
            $stepIdx = $order[$step['key']] ?? $i;
            if ($stepIdx < $currentIdx) {
                // Done
                $icon  = '&#10003;';
                $bg    = '#198754';
                $color = '#198754';
                $label = $step['label'] . ' <em style="font-size:11px;color:#198754">(Completed)</em>';
            } elseif ($stepIdx === $currentIdx) {
                // Current / Active
                $icon  = '&#9654;';
                $bg    = '#0f5132';
                $color = '#0f5132';
                $label = "<strong>{$step['label']}</strong> <em style='font-size:11px;color:#0f5132'>(Your Step)</em>";
            } else {
                // Upcoming
                $icon  = (string)($i + 1);
                $bg    = '#ccc';
                $color = '#888';
                $label = $step['label'];
            }

            $html .= "
            <div style='display:flex;align-items:center;gap:12px;margin-bottom:10px;'>
                <div style='width:28px;height:28px;border-radius:50%;background:{$bg};color:#fff;
                            text-align:center;line-height:28px;
                            font-size:13px;font-weight:700;flex-shrink:0;'>{$icon}</div>
                <div style='font-size:13px;color:{$color};'>{$label}</div>
            </div>";
        }
        $html .= "</div>";
        return $html;
    }

    /**
     * Build full HTML email body.
     */
    private function buildHtml(
        string $title,
        string $greeting,
        string $message,
        string $stepHtml,
        string $ctaLabel,
        string $ctaLink,
        string $projectName,
        string $note = ''
    ): string {
        return "
        <div style='font-family:\"Segoe UI\",Tahoma,Geneva,Verdana,sans-serif;background:#f4f4f4;padding:40px 10px;'>
          <div style='max-width:620px;margin:0 auto;background:#fff;border-radius:10px;
                      overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.08);border:1px solid #e5e5e5;'>

            <!-- Header bar -->
            <div style='background:linear-gradient(135deg,#0f5132,#198754);padding:28px 30px;'>
              <div style='font-size:11px;letter-spacing:2px;text-transform:uppercase;
                          color:rgba(255,255,255,.7);margin-bottom:6px;'>TPHL ERP &middot; Construction Workflow</div>
              <h2 style='margin:0;color:#fff;font-size:20px;font-weight:800;'>{$title}</h2>
              <div style='margin-top:8px;font-size:12px;color:rgba(255,255,255,.8);'>
                Project: <strong style='color:#fff;'>{$projectName}</strong>
              </div>
            </div>

            <!-- Body -->
            <div style='padding:30px;'>
              <p style='color:#333;font-size:15px;margin:0 0 12px;'>{$greeting}</p>
              <p style='color:#555;font-size:14px;line-height:1.7;margin:0 0 20px;'>{$message}</p>

              <!-- Pre-Construction Steps -->
              <div style='background:#f8fdf9;border:1px solid #d1e7dd;border-radius:8px;
                          padding:18px 20px;margin-bottom:22px;'>
                <div style='font-size:11px;letter-spacing:1.5px;text-transform:uppercase;
                            color:#198754;font-weight:700;margin-bottom:14px;'>
                  Pre-Construction Workflow Progress
                </div>
                {$stepHtml}
              </div>

              " . ($note ? "<p style='color:#888;font-size:12px;margin:0 0 22px;'><em>{$note}</em></p>" : '') . "

              <!-- CTA Button -->
              <div style='text-align:center;margin-top:24px;'>
                <a href='{$ctaLink}'
                   style='background:#198754;color:#fff;padding:13px 32px;text-decoration:none;
                          border-radius:6px;font-weight:700;font-size:14px;display:inline-block;'>
                  {$ctaLabel} &rarr;
                </a>
              </div>
            </div>

            <!-- Footer -->
            <div style='background:#f8f9fa;padding:16px 30px;text-align:center;border-top:1px solid #eee;'>
              <p style='margin:0;font-size:11px;color:#aaa;'>
                This is an automated message from TPHL ERP System. Please do not reply.
              </p>
            </div>

          </div>
        </div>";
    }

    /**
     * Core send method using PHPMailer + Google XOAUTH2.
     * Modified to use async database queue to prevent UI blocking.
     */
    /**
     * Debug and test send BOQ submission email synchronously (no queue)
     * prints results directly using echo / print_r
     */
    public function debugBOQMailSubmission(int $projectId, string $projectName, string $engineerName, string $requisitionMonth): void
    {
        // Get potential email recipients: role_id IN (3, 15, 17, 22)
        // Exclude Admin (1)
        $stmt = $this->db->prepare("
            SELECT id, full_name, email, role_id 
            FROM users 
            WHERE role_id IN (3, 15, 17, 22)
              AND role_id != 1
              AND email IS NOT NULL 
              AND email != ''
        ");
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Deduplicate
        $uniqueRecipients = [];
        $seen = [];
        foreach ($recipients as $r) {
            if (!in_array($r['id'], $seen)) {
                $seen[] = $r['id'];
                $uniqueRecipients[] = $r;
            }
        }

        echo "<h2>=== BOQ Email Submission Debug Mode ===</h2>";
        echo "<p><strong>Project:</strong> {$projectName} (ID: {$projectId})</p>";
        echo "<p><strong>Engineer:</strong> {$engineerName}</p>";
        echo "<p><strong>Requisition Month:</strong> {$requisitionMonth}</p>";
        echo "<p><strong>Total Recipients Found:</strong> " . count($uniqueRecipients) . "</p>";
        echo "<hr>";

        if (empty($uniqueRecipients)) {
            echo "<p style='color:red;'>No valid recipients found with email addresses!</p>";
            return;
        }

        $config = require __DIR__ . '/../config/config.php';
        $smtpHost = $config['smtp_host'] ?? 'smtp.gmail.com';
        $smtpPort = $config['smtp_port'] ?? 465;
        $senderEmail = $config['smtp_user'] ?? 'no-reply@dpremiumhomes.com';
        $smtpPass = $config['smtp_pass'] ?? '';
        $smtpSecure = $config['smtp_secure'] ?? 'ssl';

        foreach ($uniqueRecipients as $user) {
            $subject = "⚡ [DEBUG] Approve BOQ / BOM — {$projectName}";
            $body = $this->buildHtml(
                title:       "Action Required: BOQ Approval (DEBUG TEST)",
                greeting:    "Dear {$user['full_name']},",
                message:     "A new <strong>Bill of Quantities (BOQ)</strong> is ready for project <strong>{$projectName}</strong> (Requisition Month: {$requisitionMonth}) and is awaiting approval.",
                stepHtml:    "<p>This is a debug test mail.</p>",
                ctaLabel:    'Review & Approve BOQ',
                ctaLink:     $this->getBaseUrl() . "/con-boq-show/9999",
                projectName: $projectName,
                note:        'This email was sent in debug mode. No actual BOQ record was saved.'
            );

            echo "Sending to <strong>{$user['full_name']}</strong> (Role: {$user['role_id']}, Email: {$user['email']})... ";

            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $smtpHost;
                $mail->Port       = $smtpPort;
                $mail->SMTPAuth   = true;
                $mail->Username   = $senderEmail;
                $mail->Password   = $smtpPass;
                $mail->SMTPSecure = (strtolower($smtpSecure) === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

                $mail->setFrom($this->senderEmail, $this->senderName);
                $mail->addAddress($user['email']);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $body));

                $mail->send();
                echo "<p style='color:green'>Email sent successfully to " . htmlspecialchars($user['email']) . "</p>";
            } catch (\Throwable $th) {
                echo "<p style='color:red'>Mail Sending Failed: " . htmlspecialchars($th->getMessage()) . "</p>";
                error_log("Mail Sending Failed: " . $th->getMessage());
            }
            echo "<br>";
        }

        echo "<h3>Debug Finished. No database records were saved.</h3>";
    }

    private function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, 'pending')");
            if (!$stmt) {
                // email_queue table may not exist on this server — log and skip silently
                error_log("[ProjectWorkflowMailService] email_queue table missing or prepare failed: " . $this->db->error);
                return false;
            }
            $stmt->bind_param("sss", $toEmail, $subject, $htmlBody);
            $stmt->execute();
            $stmt->close();
            
            // Trigger the async processor immediately via cURL so we don't have to wait for the next page load
            $url = $this->getBaseUrl() . '/process_email_queue.php';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200); // 200ms timeout
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
            
            return true;
        } catch (Exception $e) {
            error_log("[ProjectWorkflowMailService] Failed to queue email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}
